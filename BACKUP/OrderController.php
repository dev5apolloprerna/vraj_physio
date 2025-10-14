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
                        foreach ($MyPackage as $item) 
                        {
                            $totalAmount += $item->amount;
                        }
    
    					$iNetAmount = $totalAmount - ($totalAmount * $request->discount / 100);
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
                            $order->iAmount=$totalAmount;   
                            $order->iDiscount=$request->discount;   
                            $order->DueAmount=$totalAmount;   
                            $order->save();
    
        
                            foreach ($MyPackage as $item) 
                            {
                                $OrderDetail=new OrderDetail();
                                $OrderDetail->iOrderId=$order->iOrderId;
                                $OrderDetail->iTreatmentId=$item->treatment_id;
                                $OrderDetail->iPlanId=$item->plan_id;
                                $OrderDetail->iAmount=$item->amount;
                                $OrderDetail->iDueAmount=$item->amount;
                                $OrderDetail->iSession=$item->no_of_session;
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

                            $paymentList[] = array(
                                    "order_id" => $val->iOrderId,
                                    "total_amount" => $val->iAmount,
                                    "paid_amount" => $totalPaid,
                                    "discount_amount" => $val->iDiscount,
                                    "due_amount" => $val->DueAmount,
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

                    $totalpayment=0;
                    $nettotal=0;
                                 
                    foreach ($request->data as $key => $value) 
                    {
                            if($value['pay_amount'] != 0)
                            {

                        $orderPay=OrderPayment::where('orderpayment.orderDetailId',$value['order_detail_id'])->join('patientorderdetail', 'patientorderdetail.iOrderDetailId', '=', 'orderpayment.orderDetailId')
                                ->get();

                            // if ($orderPay->isNotEmpty()) 
                            // {

                                 $totalPaidAmount = $orderPay->sum('Amount') ?? 0; 
                                    $order=OrderDetail::select('iAmount','iOrderId')->where('iOrderDetailId',$value['order_detail_id'])->first();
                                    $ordermaster=Order::where(['iOrderId'=>$order->iOrderId])->first();

                                    if ($order) 
                                    {
                                        // Calculate the remaining due amount for this order detail
                                        $remainingDue = $order->iAmount - $totalPaidAmount;

                                        // If the remaining due amount is zero or less, return an error
                                            if ($remainingDue <= 0) 
                                            {
                                                return response()->json([
                                                    'status' => 'error',
                                                    'message' => 'Order Payment Already Done for order detail ID ' . $value['order_detail_id']
                                                ]);
                                            }

                                                

                                                DB::table('patientorderdetail')
                                                    ->where('iOrderDetailId', $value['order_detail_id'])
                                                    ->update([
                                                        'iDueAmount' => $remainingDue - $value['pay_amount']// Deduct current payment
                                                    ]);

                                                $lastPayment = OrderPayment::orderBy('OrderPaymentId', 'desc')->first(); // Get the last payment entry (by ID, or by a custom field if needed)
                                                
                                                // Get the last number and increment it
                                                $lastNumber = $lastPayment ? substr($lastPayment->invoice_no, 4) : 0; // Assuming your format starts with 'INV ' (length of 4)
                                                $nextNumber = str_pad((int)$lastNumber + 1, 4, '0', STR_PAD_LEFT); // Increment by 1 and pad with zeros
                                                
                                                $invoice_no = 'INV ' . $nextNumber;
                                                $receipt_no = 'RCPT ' . $nextNumber;
                                                

                                                // Insert the new payment record
                                                $OrderPayment = new OrderPayment();
                                                $OrderPayment->iOrderId = $order->iOrderId;
                                                $OrderPayment->orderDetailId = $value['order_detail_id'];
                                                $OrderPayment->Amount = $value['pay_amount'];
                                                $OrderPayment->payment_mode = $request->payment_mode;
                                                $OrderPayment->bad_dept = $value['bad_dept'];
                                                $OrderPayment->PaymentDateTime = date('Y-m-d h:i:s');
                                                $OrderPayment->invoice_no = $invoice_no;
                                                $OrderPayment->receipt_no =$receipt_no;
                                                $OrderPayment->save();


                                                $total=OrderPayment::where(['iOrderId'=>$order->iOrderId])->get();

                                                $ordermaster->DueAmount =$ordermaster->iAmount - $total->sum('Amount');
                                                $ordermaster->InvoiceDateTime =date('Y-m-d h:i:s');
                                                $ordermaster->save();
                                                
                                                
                                                $key = $_ENV['WHATSAPPKEY'];
                                                
                                                $patient=Patient::select('phone')->where(['patient_id'=>$ordermaster->patient_id])->first();
                                                if(!empty($patient)){
                                                    
                                                    $users = new User();
                                            	
                                                $msg = "Dear Parent,\n\n"
                                                . "We have received your payment successfully. Thank you for your prompt payment!\n\n"
                                                . "Payment Details:\n\n"
                                                . "* Amount: {$value['pay_amount']}\n"
                                                . "* Payment Mode: {$request->payment_mode} \n\n"
                                                . "If you have any questions or need further assistance, feel free to reach out. We look forward to continuing to support your child’s growth and development! \n\n"
                                                . "Best regards,\n\n"
                                                . "Vraj PHYSIOTHERAPY AND CHILD DEVELOPMENT CENTER";

                                               
                        						$status = $users->sendWhatsappMessage($patient->phone,$key,$msg, $someOtherParam = null);    
                                                }
                                        }           
                                    }

                    }
                                 return response()->json([
                                        'status' => 'success',
                                        'message' => 'Order Payment Successfully'
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

                    $OrderPayment=OrderPayment::select('orderpayment.*','patientordermaster.patient_id')->where(['patient_id'=>$request->patient_id])
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
                    $order=Order::select('patientordermaster.iOrderId','patientorderdetail.*','patientordermaster.iAmount as totalamount','patientordermaster.DueAmount as totaldue','patientorderdetail.iDueAmount as dueAmount',
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


                            $odetail[] = array(
                                "order_id" => $val->iOrderId,
                                "order_detail_id" => $val->iOrderDetailId,
                                "treatment_id" => $val->iTreatmentId,
                                "treatment_name" => $val->treatment_name,
                                "plan_id" => $val->iPlanId,
                                "plan_name" => $val->plan_name,
                                "total_amount" => $val->iAmount,
                                "paid_amount" => $totalPaid,
                                "due_amount" => $val->dueAmount,
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Order Detail',
                                'total_amount' => $val->totalamount,
                                'discount_amount' => $val->iDiscount ?? 0,
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

            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            } catch (\Throwable $th) {
                return response()->json(['error' => $th->getMessage()], 500);
            }
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
}
