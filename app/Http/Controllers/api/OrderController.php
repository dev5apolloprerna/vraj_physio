<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
    
use App\Models\Billing;
use App\Models\Plan;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\OrderPayment;
use App\Models\CashLedger;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\MyPackage;
use App\Models\PatientSuggestedTreatment;
use App\Models\PatientTreatmentLedger;
use App\Models\SettingBillId;
use App\Models\PatientSchedule;

use Carbon\Carbon;

class OrderController extends Controller
{
    public function buy_treatment_package(Request $request)
    {
        try
        {
            $request->validate([
                'tempid' => 'required|array',
                'tempid.*' => 'string' // or 'integer' depending on your tempid type
            ]);

            if(auth()->guard('api')->user())
            {
                $User = auth()->guard('api')->user();
                 
                    if($request->device_token != $User->device_token)
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Device Token Not Match',
                        ], 401);
                    }
                     $MyPackage =MyPackage::whereIn('tempid', $request->tempid)->get();
                     
                    if(sizeof($MyPackage) != 0)
                    {
                        $totalAmount = 0;
                        foreach ($MyPackage as $item) {
                            $totalAmount += (float) $item->amount;
                        }
                        
                        $discountAmount = (float) ($request->discount ?? 0);
                        
                        // ✅ discount cannot be negative and cannot be more than total
                        $discountAmount = max(0, min($totalAmount, $discountAmount));
                        
                        $iNetAmount = $totalAmount - $discountAmount;  // ✅ 1000 - 100 = 900
                        

                        /*if($request->dueAmount == 0)
                        {
                            $dueamount =$totalAmount;
                        }else{
                            $dueamount = $request->dueAmount;
                        }*/
                        /*$session = PatientSchedule::whereIn('patient_schedule_id', $request->patient_schedule_id)->first();
                        if($session == null)
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Patient Schedule Not Added',

                            ], 401);  
                        }else
                        {*/

                            $order=new Order();
                            $order->patient_id=$MyPackage->first()->patient_id;
                            $order->Date=date('Y-m-d');
                            $order->iNetAmount=$iNetAmount;
                            $order->GUID=Str::uuid();  
                            $order->iAmount     = $totalAmount;        // 1000
                            $order->iDiscount   = $discountAmount;     // 100
                            $order->DueAmount   = $iNetAmount;         // 900
                            $order->save();

                            $items = $MyPackage->values();
                            $discountLeft = $discountAmount;
                            $count = $items->count();
                            
                            foreach ($items as $idx => $item) {
                                $amount = (float) $item->amount;
                            
                                // last item takes remaining to avoid rounding issues
                                if ($idx === $count - 1) {
                                    $itemDiscount = $discountLeft;
                                } else {
                                    $itemDiscount = ($totalAmount > 0) ? round($discountAmount * ($amount / $totalAmount), 2) : 0;
                                    $discountLeft -= $itemDiscount;
                                }
                            
                                $due = max(0, $amount - $itemDiscount);
                            
                                $OrderDetail = new OrderDetail();
                                $OrderDetail->iOrderId     = $order->iOrderId;
                                $OrderDetail->iTreatmentId = $item->treatment_id;
                                $OrderDetail->iPlanId      = $item->plan_id;
                                $OrderDetail->iAmount      = $amount;
                                $OrderDetail->iDueAmount   = $due; // ✅ discounted due
                                $OrderDetail->iSession     = $item->no_of_session;
                                $OrderDetail->save();
                            
                                $suggested=new PatientSuggestedTreatment();
                                $suggested->iOrderId=$order->iOrderId;
                                $suggested->iOrderDetailId=$OrderDetail->iOrderDetailId;
                                $suggested->patient_id=$item->patient_id;
                                $suggested->treatment_id=$item->treatment_id;
                                $suggested->iSessionBuy=$item->no_of_session;
                                $suggested->iUsedSession=0;
                                $suggested->iAvailableSession=$item->no_of_session;
                                $suggested->isActive=1;
                                $suggested->save();
    
                                // $ledger=new PatientTreatmentLedger();
                                // $ledger->patient_id=$item->patient_id;
                                // $ledger->treatment_id=$item->treatment_id;
                                // $ledger->therapist_id=0;
                                // $ledger->iOrderDetailId=$OrderDetail->iOrderDetailId;
                                // $ledger->opening_balance=0;
                                // $ledger->credit_balance=$item->no_of_session;
                                // $ledger->debit_balance=0;
                                // $ledger->closing_balance=$item->no_of_session;
                                // $ledger->save();
                                
                                   $ledger = PatientTreatmentLedger::where(['patient_id'=>$item->patient_id,'treatment_id'=>$item->treatment_id,'iOrderDetailId'=>0])->first();
                                    if ($ledger) 
                                    {
                                        $ledger->iOrderDetailId=$OrderDetail->iOrderDetailId;
                                        $ledger->opening_balance=0;
                                        $ledger->credit_balance=$item->no_of_session;
                                        $ledger->debit_balance=0;
                                        $ledger->closing_balance=$item->no_of_session;
                                        $ledger->save();
                                    }
                                }
                            $deletePackage = MyPackage::whereIn('tempid', $request->tempid)->delete();
                            //}
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Package Buy Successfully',
        
                            ], 401);
                }else{
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Package Already Purchased',

                        ], 401);
                    }
            }else
            {
                return response()->json([
                        'status' => 'error',
                        'message' => 'User is not Authorised.',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    public function order_total_payment(Request $request)
    {
        try
        {
            if(auth()->guard('api')->user())
            {
                 $User = auth()->guard('api')->user();
                 
                    if($request->device_token != $User->device_token)
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Device Token Not Match',
                        ], 401);
                    }

                    $totalpayment=Order::select('patientordermaster.iOrderId','patientordermaster.iAmount','DueAmount','iDiscount','patientordermaster.created_at')
                    ->join('patient_master', 'patient_master.patient_id', '=', 'patientordermaster.patient_id')
                    ->where(['patientordermaster.patient_id'=>$request->patient_id,'patient_master.isDelete'=>0])->get();
                    if(sizeof($totalpayment) != 0)
                    {
                        foreach ($totalpayment as $key => $val) 
                        {
                            $totalPaid = OrderPayment::where('iOrderId', $val->iOrderId)->sum('Amount');
                            $bad_dept = OrderPayment::where('iOrderId', $val->iOrderId)->sum('bad_dept');

                            $paymentList[] = array(
                                    "order_id" => $val->iOrderId,
                                    "total_amount" => $val->iAmount,
                                    "paid_amount" => $totalPaid,
                                    "discount_amount" => $val->iDiscount,
                                    "due_amount" => $val->DueAmount,
                                    "bad_dept" => $bad_dept,
                                    "buy_date" => date('d-m-Y',strtotime($val->created_at)),
                                );
                        }
                        return response()->json([
                                'status' => 'success',
                                'message' => 'Order Payment List',
                                'Order Payment' => $paymentList
                            ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Order Payment List' => []
                        ]);
                    }

            }else{
                return response()->json([
                        'status' => 'error',
                        'message' => 'User is not Authorised.',
                ], 401);
            }

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
  public function order_payment(Request $request)
{
    if (auth()->guard('api')->user())
    {
        $User = auth()->guard('api')->user();

        if ($request->device_token != $User->device_token)
        {
            return response()->json([
                "ErrorCode" => "1",
                'Status' => 'Failed',
                'Message' => 'Device Token Not Match',
            ], 401);
        }

        foreach ($request->data as $key => $value)
        {
            if (($value['pay_amount'] ?? 0) != 0)
            {
                $cashPaid = (float)($value['pay_amount'] ?? 0);          // CASH
                $badDept  = (float)($value['bad_dept'] ?? 0);            // EXTRA DISCOUNT

                // find order detail + order master
                $order = OrderDetail::select('iAmount','iOrderId')
                    ->where('iOrderDetailId', $value['order_detail_id'])
                    ->first();

                if (!$order) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid order detail ID ' . $value['order_detail_id']
                    ], 422);
                }

                $ordermaster = Order::where(['iOrderId' => $order->iOrderId])->first();
                if (!$ordermaster) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Order not found.'
                    ], 422);
                }

                // ✅ Order level totals (so due matches master)
                $totalCashPaidOrder = (float)OrderPayment::where('iOrderId', $order->iOrderId)->sum('Amount');
                $totalBadDeptOrder  = (float)OrderPayment::where('iOrderId', $order->iOrderId)->sum('bad_dept');

                // ✅ effective discount = original discount + all bad_dept till now (but DON'T update iDiscount yet)
                $effectiveDiscount = (float)$ordermaster->iDiscount + $totalBadDeptOrder;

                // ✅ remaining due before current payment
                $remainingDue = ((float)$ordermaster->iAmount - $effectiveDiscount) - $totalCashPaidOrder;

                if ($remainingDue <= 0)
                {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Order Payment Already Done for order detail ID ' . $value['order_detail_id']
                    ]);
                }

                // ✅ current settlement = cash + bad_dept (both reduce due)
                if (($cashPaid + $badDept) > $remainingDue)
                {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Pay amount + bad debt is greater than remaining due'
                    ], 422);
                }

                // Invoice / Receipt
                $lastPayment = OrderPayment::orderBy('OrderPaymentId', 'desc')->first();
                $lastNumber  = ($lastPayment && !empty($lastPayment->invoice_no))
                    ? (int)substr($lastPayment->invoice_no, 4)
                    : 0;

                $nextNumber  = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                $invoice_no  = 'INV ' . $nextNumber;
                $receipt_no  = 'RCPT ' . $nextNumber;

                // ✅ Save payment: Amount = CASH ONLY, bad_dept separate
                $OrderPayment = new OrderPayment();
                $OrderPayment->iOrderId = $order->iOrderId;
                $OrderPayment->orderDetailId = $value['order_detail_id'];
                $OrderPayment->Amount = $cashPaid;
                $OrderPayment->payment_mode = $request->payment_mode;
                $OrderPayment->bad_dept = $badDept;
                $OrderPayment->PaymentDateTime = date('Y-m-d H:i:s');
                $OrderPayment->invoice_no = $invoice_no;
                $OrderPayment->receipt_no = $receipt_no;
                $OrderPayment->save();

                // ✅ Recalculate after saving current payment
                $totalCashPaidOrder = (float)OrderPayment::where('iOrderId', $order->iOrderId)->sum('Amount');
                $totalBadDeptOrder  = (float)OrderPayment::where('iOrderId', $order->iOrderId)->sum('bad_dept');
                $effectiveDiscount  = (float)$ordermaster->iDiscount + $totalBadDeptOrder;

                $newDue = ((float)$ordermaster->iAmount - $effectiveDiscount) - $totalCashPaidOrder;
                if ($newDue < 0) $newDue = 0;

                // ✅ Update master due always
                $ordermaster->DueAmount = $newDue;
                $ordermaster->InvoiceDateTime = date('Y-m-d H:i:s');

                // ✅ Update iDiscount ONLY when fully paid
                if ($newDue == 0) {
                    $ordermaster->iDiscount = $effectiveDiscount;
                }

                $ordermaster->save();

                // ✅ Make order detail due SAME as order master due
                DB::table('patientorderdetail')
                    ->where('iOrderDetailId', $value['order_detail_id'])
                    ->update([
                        'iDueAmount' => $newDue
                    ]);

                // ✅ Cash Ledger (cash only)
                if ($request->payment_mode == 'Cash')
                {
                    $cashLedger = CashLedger::where(["clinic_id" => $User->clinic_id])->orderBy("id","desc")->first();
                    $op_amt = $cashLedger->cl_amt ?? 0;
                    $cr_amt = $cashPaid;
                    $dr_amt = 0;
                    $cl_amt = $op_amt + $cashPaid;

                    $ledger = array(
                        "clinic_id" => $User->clinic_id,
                        "op_amt" => $op_amt,
                        "cr_amt" => $cr_amt,
                        "dr_amt" => $dr_amt,
                        "cl_amt" => $cl_amt,
                        "order_id" => $order->iOrderId,
                        "order_payment_id" => $OrderPayment->OrderPaymentId,
                        "strIP" => $request->ip(),
                        "created_at" => date('Y-m-d H:i:s')
                    );
                    CashLedger::create($ledger);
                }

                // WhatsApp
                $key = $_ENV['WHATSAPPKEY'];
                $patient = Patient::select('phone')->where(['patient_id' => $ordermaster->patient_id])->first();

                if (!empty($patient))
                {
                    $users = new User();

                    $msg = "Dear Parent,\n\n"
                        . "We have received your payment successfully. Thank you for your prompt payment!\n\n"
                        . "Payment Details:\n\n"
                        . "* Amount Paid (Cash): {$cashPaid}\n"
                        . "* Extra Discount (Bad Debt): {$badDept}\n"
                        . "* Payment Mode: {$request->payment_mode}\n\n"
                        . "Best regards,\n\n"
                        . "Vraj PHYSIOTHERAPY AND CHILD DEVELOPMENT CENTER";

                    $users->sendWhatsappMessage($patient->phone, $key, $msg, $someOtherParam = null);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order Payment Successfully'
        ]);
    }
    else
    {
        return response()->json([
            'status' => 'error',
            'message' => 'User is not Authorised.',
        ], 401);
    }
}


    public function payment_list(Request $request)
    {
        try
        {
            if(auth()->guard('api')->user())
            {
                 $User = auth()->guard('api')->user();
                 
                    if($request->device_token != $User->device_token)
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Device Token Not Match',
                        ], 401);
                    }

                    $OrderPayment=OrderPayment::select('orderpayment.*','patientordermaster.patient_id','patientordermaster.iDiscount')->where(['patient_id'=>$request->patient_id])
                    ->join('patientorderdetail', 'patientorderdetail.iOrderDetailId', '=', 'orderpayment.orderDetailId')
                    ->join('patientordermaster', 'patientordermaster.iOrderId', '=', 'patientorderdetail.iOrderId')
                    ->get();
                    if(sizeof($OrderPayment) != 0)
                    {

                        foreach ($OrderPayment as $key => $val) 
                        {
                            
                                $pList[] = array(
                                    "iOrderId" => $val->iOrderId,
                                    "order_detail_id" => $val->orderDetailId,
                                    "discount" => $val->iDiscount,
                                    "Amount" => $val->Amount,
                                    "payment_mode" => $val->payment_mode,
                                    "bad_dept" => $val->bad_dept,
                                    "payment_date" => date('d-M-Y',strtotime($val->PaymentDateTime)),
                                );
                        }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Payment List',
                                    'Payment' => $pList
                                ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Payment' => []
                        ]);
                    }



            }else{
                    return response()->json([
                            'status' => 'error',
                            'message' => 'User is not Authorised.',
                    ], 401);
                }

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
   public function order_payment_detail(Request $request)
    {
        /*try
        {*/
            if(auth()->guard('api')->user())
            {
                 $User = auth()->guard('api')->user();
                 
                    if($request->device_token != $User->device_token)
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Device Token Not Match',
                        ], 401);
                    }
                    $order=Order::select('patientordermaster.iOrderId','patientorderdetail.*','patientordermaster.iAmount as totalamount','patientordermaster.DueAmount as totaldue','patientorderdetail.iDueAmount as dueAmount','patientordermaster.iDiscount',
                        DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=patientorderdetail.iTreatmentId limit 1) as treatment_name")
                        ,DB::raw("(select plan_name from plan_master where plan_master.plan_id=patientorderdetail.iPlanId limit 1) as plan_name")
                        )
                    ->where(['patientorderdetail.iOrderId'=>$request->order_id])
                    ->join('patientorderdetail', 'patientordermaster.iOrderId', '=', 'patientorderdetail.iOrderId')->get();

                    if(sizeof($order) != 0)
                    {
                         foreach($order as $val)
                        {
                            $totalPaid = OrderPayment::where('orderDetailId', $val->iOrderDetailId)->sum('Amount');
                            $bad_dept = OrderPayment::where('orderDetailId', $val->iOrderDetailId)->sum('bad_dept');


                            $odetail[] = array(
                                "order_id" => $val->iOrderId,
                                "order_detail_id" => $val->iOrderDetailId,
                                "treatment_id" => $val->iTreatmentId,
                                "treatment_name" => $val->treatment_name,
                                "plan_id" => $val->iPlanId,
                                "plan_name" => $val->plan_name,
                                "total_amount" => $val->iAmount,
                                "paid_amount" => $totalPaid,
                                "due_amount" => $val->dueAmount
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Order Detail',
                                'total_amount' => $val->totalamount,
                                'discount_amount' => $val->iDiscount ?? 0,
                                'bad_dept' => $bad_dept ?? 0,
                                'total_dueamount' => $val->totaldue,
                                'Order Detail' => $odetail
                            ]);

                    }else
                    {
                         return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Order Detail' => []
                        ]);
                    }

            }else{
                        return response()->json([
                                'status' => 'error',
                                'message' => 'User is not Authorised.',
                        ], 401);
                    }

            /*} catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            } catch (\Throwable $th) {
                return response()->json(['error' => $th->getMessage()], 500);
            }*/
    }
       public function generate_invoice(Request $request)
    {
         try
        {
            if(auth()->guard('api')->user())
            {
                $User = auth()->guard('api')->user();
                 
                    if($request->device_token != $User->device_token)
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Device Token Not Match',
                        ], 401);
                    }
            $checkpayment=OrderPayment::where('iOrderId',$request->order_id)->get();
            if ($checkpayment->isNotEmpty() == 1) 
            {

                    $bill=Order::where('iOrderId',$request->order_id)->first();
                    
                    if (!empty($bill)) 
                    {
                        // Retrieve the maximum IBillId from the Order table and the SettingBillId table
                        $maxOrderBillId = Order::max('IBillId');
                        $maxSettingBillId = SettingBillId::orderBy('billId', 'desc')->first()->bill_prefix;

                        // Determine the highest IBillId across both tables
                        $maxBillId = max($maxOrderBillId, $maxSettingBillId);

                        // Retrieve orders to be updated in the patientordermaster table where IBillId is 0
                        $getOrders = Order::where('iOrderId', $request->order_id)
                                          ->where('IBillId', 0)
                                          ->first();

                        if (!empty($getOrders)) 
                        {
                                    $allBillIdsAreZero = DB::table('patientordermaster')->where('IBillId', '!=', 0)->doesntExist();
                                    if ($allBillIdsAreZero) 
                                    {
                                        $maxBillId;
                                    }
                                    else 
                                    {

                                        $maxBillId++;
                                    }
                                    
                                    DB::table('patientordermaster')
                                        ->where('iOrderId', $getOrders->iOrderId)
                                        ->update([
                                            'bill_prefix' => $maxSettingBillId->bill_prefix,
                                            'IBillId' => $maxBillId
                                        ]);
                                
                        

                                 return response()->json([
                                        'status' => 'success',
                                        'message' => 'Invoice generated successfully for this orders.',
                                    ], 401);

                            } else {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No orders found to process.'
                                ], 404);
                            }
                        } else {
                                    return response()->json([
                                        'status' => 'error',
                                        'message' => 'Invoice already generated for the provided orders'
                                    ], 404);
                                }
                        
                } else {
                                    return response()->json([
                                        'status' => 'error',
                                        'message' => 'Payment must be completed before generating an invoice.'
                                    ], 404);
                                }
                        }
            else
            {
                    return response()->json([
                            'status' => 'error',
                            'message' => 'User is not Authorised.',
                    ], 401);
            }

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }

    }
    public function invoice_list(Request $request)
    {
        try
        {
            if(auth()->guard('api')->user())
            {
                 $User = auth()->guard('api')->user();
                 
                    if($request->device_token != $User->device_token)
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Device Token Not Match',
                        ], 401);
                    }

                    $Invoice=Billing::select('billingmaster.*','orderpayment.iOrderId as payment')->join('patientordermaster', 'patientordermaster.IBillId', '=', 'billingmaster.IBillId')->leftjoin('orderpayment', 'patientordermaster.IBillId', '=', 'orderpayment.iOrderId')->get();
                    if(sizeof($Invoice) != 0)
                    {

                        foreach ($Invoice as $key => $val) 
                        {
                            
                             if($val->payment != null)
                                {
                                    $status ="paid";

                                }else{
                                    $status="pending";
                                }
                                
                                $InvoiceList[] = array(
                                    "bill_id" => $val->IBillId,
                                    "invoice_id" => $val->strInvoiceId,
                                    "patient_id" => $val->patient_id,
                                    "total_amount" => $val->Netamount,
                                    "discount" => $val->Discount,
                                    "amount" => $val->Amount,
                                    "status" => $status,
                                    "invoice_date" => date('d-F-Y',strtotime($val->InvoiceDateTime)),
                                );
                        }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Invoice List',
                                    'Invoice' => $InvoiceList
                                ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Invoice' => []
                        ]);
                    }



            }else{
                    return response()->json([
                            'status' => 'error',
                            'message' => 'User is not Authorised.',
                    ], 401);
                }

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
     public function cancel_patient(Request $request)
{
    
    try 
    {
        if(auth()->guard('api')->user())
        {
             $User = auth()->guard('api')->user();
             
                if($request->device_token != $User->device_token)
                {
                    return response()->json([
                        "ErrorCode" => "1",
                        'Status' => 'Failed',
                        'Message' => 'Device Token Not Match',
                    ], 401);
                }
                

                    $date=date('Y-m-d');

                        $order = OrderDetail::where([
                                'patientorderdetail.iOrderId' => $request->iOrderId,
                                'patientorderdetail.iOrderDetailId' => $request->iOrderDetailId,
                                'patient_id' => $request->patient_id
                            ])
                            ->join('patientordermaster', 'patientordermaster.iOrderId', '=', 'patientorderdetail.iOrderId')
                            ->where(function ($query) {
                                $query->where('cancel_package', 1);
                            })
                            ->first();

                            if ($order) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Patient Package Already Canceled',
                                ]);
                            }

                            // If no order is found, create or find a valid one to update
                            $order = OrderDetail::where([
                                'iOrderId' => $request->iOrderId,
                                'iOrderDetailId' => $request->iOrderDetailId,
                            ])->first();

                            if ($order && $request->cancel_package == 1) 
                            {
                                $order->cancel_package = 1;
                                $order->save();

                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Patient Package Canceled Successfully',
                                ]);
                            }

                            return response()->json([
                                'status' => 'error',
                                'message' => 'Order not found',
                            ]);


        }else{
            return response()->json([
                    'status' => 'error',
                    'message' => 'User is not Authorised.',
            ], 401);
        }
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()], 500);
    }
  }   
    private function recalcCashLedgerFrom(int $clinicId, int $fromId): void
    {
        // previous closing balance (row just before fromId)
        $prev = CashLedger::where('clinic_id', $clinicId)
            ->where('id', '<', $fromId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $running = (float) ($prev->cl_amt ?? 0);

        // rewrite all subsequent rows
        $rows = CashLedger::where('clinic_id', $clinicId)
            ->where('id', '>=', $fromId)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($rows as $row) {
            $row->op_amt = $running;

            $cr = (float) ($row->cr_amt ?? 0);
            $dr = (float) ($row->dr_amt ?? 0);

            $running = $running + $cr - $dr;
            // optional safety
            if ($running < 0) $running = 0;

            $row->cl_amt = $running;
            $row->save();
        }
    }

    public function cancel_payment(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();
            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User is not Authorised.',
                ], 401);
            }

            // ✅ Validate input (add/remove fields as per your request payload)
            $request->validate([
                'device_token'     => 'required|string',
                'patient_id'       => 'required|integer',
                'iOrderId'         => 'required|integer',
                'order_detail_id'  => 'required|integer',   // this is iOrderDetailId
                'order_payment_id' => 'nullable|integer',   // recommended for multiple payments
            ]);

            // ✅ Device token check
            if ($request->device_token !== $user->device_token) {
                return response()->json([
                    "ErrorCode" => "1",
                    'Status'    => 'Failed',
                    'Message'   => 'Device Token Not Match',
                ], 401);
            }

            return DB::transaction(function () use ($request) {

                // ✅ Lock order row
                $order = Order::where('iOrderId', $request->iOrderId)
                    ->where('patient_id', $request->patient_id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Order not found for this patient.',
                    ], 404);
                }

                // ✅ Lock order detail row
                $detail = OrderDetail::where('iOrderId', $request->iOrderId)
                    ->where('iOrderDetailId', $request->order_detail_id)
                    ->lockForUpdate()
                    ->first();

                if (!$detail) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Order detail not found.',
                    ], 404);
                }

                // ✅ Find which payment to cancel (supports multiple payments)
                $paymentQuery = OrderPayment::where('iOrderId', $request->iOrderId)
                    ->where('orderDetailId', $request->order_detail_id)
                    ->lockForUpdate();

                if ($request->filled('order_payment_id')) {
                    $paymentQuery->where('OrderPaymentId', $request->order_payment_id);
                } else {
                    // fallback: cancel latest payment for this order detail
                    $paymentQuery->orderByDesc('OrderPaymentId');
                }

                $payment = $paymentQuery->first();

                if (!$payment) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Payment not found for this order detail.',
                    ], 404);
                }

                $cancelledPaymentId = (int) $payment->OrderPaymentId;
                $cancelledAmount    = (float) $payment->Amount;

                // -----------------------------
                // ✅ CASH LEDGER REVERT + REWRITE
                // -----------------------------
                $ledgerRow = CashLedger::where('order_id', $order->iOrderId)
                    ->where('order_payment_id', $cancelledPaymentId)
                    ->lockForUpdate()
                    ->first();

                $ledgerDeleted = false;

                if ($ledgerRow) {
                    $clinicId = (int) $ledgerRow->clinic_id;
                    $fromId   = (int) $ledgerRow->id;

                    $ledgerRow->delete();
                    $ledgerDeleted = true;

                    // rewrite balances for remaining rows after deleted row
                    $this->recalcCashLedgerFrom($clinicId, $fromId);
                }

                // -----------------------------
                // ✅ DELETE PAYMENT
                // -----------------------------
                $payment->delete();

                // -----------------------------
                // ✅ REWRITE DUE AMOUNTS (ORDER + DETAIL)
                // -----------------------------

                // total paid for whole order (remaining payments after delete)
                $paidOrderTotal = (float) OrderPayment::where('iOrderId', $order->iOrderId)->sum('Amount');

                // choose net amount: iNetAmount preferred, else iAmount
                $orderNet = (float) (($order->iNetAmount !== null && $order->iNetAmount !== '') ? $order->iNetAmount : ($order->iAmount ?? 0));

                $newOrderDue = $orderNet - $paidOrderTotal;
                if ($newOrderDue < 0) $newOrderDue = 0;

                $order->DueAmount = $newOrderDue;
                $order->save();

                // total paid for this order detail (remaining)
                $paidDetailTotal = (float) OrderPayment::where('iOrderId', $detail->iOrderId)
                    ->where('orderDetailId', $detail->iOrderDetailId)
                    ->sum('Amount');

                $detailAmount = (float) ($detail->iAmount ?? 0);

                $newDetailDue = $detailAmount - $paidDetailTotal;
                if ($newDetailDue < 0) $newDetailDue = 0;

                $detail->iDueAmount = $newDetailDue;
                $detail->save();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Payment cancelled successfully.',
                    'data'    => [
                        'iOrderId'                   => (int) $order->iOrderId,
                        'iOrderDetailId'             => (int) $detail->iOrderDetailId,
                        'cancelled_order_payment_id' => $cancelledPaymentId,
                        'cancelled_amount'           => $cancelledAmount,
                        'order_due_amount'           => (float) $order->DueAmount,
                        'order_detail_due_amount'    => (float) $detail->iDueAmount,
                        'cash_ledger_deleted'        => $ledgerDeleted,
                    ],
                ], 200);
            });

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error'  => $th->getMessage(),
            ], 500);
        }
    }


}
