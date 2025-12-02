<?php
namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Session;

use App\Models\SessionMaster;
use App\Models\Treatment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PatientSchedule;
use App\Models\PatientSuggestedTreatment;
use App\Models\Plan;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SessionExport;
use App\Exports\TreatmentExport;
use App\Exports\PatientPayment;
use App\Exports\TotalCollection;
use App\Exports\PatientCollection;
use App\Exports\TotalAttended;
use App\Exports\DailyCollection;
use App\Exports\GroupSessionExport;
use App\Exports\PatientVisit;

class ReportController extends Controller
{

 public function patient_attended_session(Request $request)
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
                $attendedSession =SessionMaster::select([
                                        'sessionmaster.created_at','pst.iUsedSession','sessionmaster.iSessionTakenId',
                                        DB::raw('(SELECT treatment_name FROM treatment_master WHERE treatment_master.treatment_id = sessionmaster.treatment_id LIMIT 1) AS treatment_name'),
                                        DB::raw('(SELECT name FROM users WHERE users.id = sessionmaster.therapist_id LIMIT 1) AS therapist_name'),
                                        DB::raw('(SELECT invoice_no FROM orderpayment WHERE orderpayment.orderDetailId = pst.iOrderDetailId  LIMIT 1) AS invoice_no'),
                                        DB::raw('(SELECT CONCAT(patient_master.patient_first_name, " ", patient_master.patient_last_name) FROM patient_master WHERE patient_master.patient_id = sessionmaster.patient_id LIMIT 1) AS patient_name'),
                                        DB::raw('(SELECT per_session_amount FROM plan_master WHERE plan_master.plan_id = pod.iPlanId LIMIT 1) AS per_session_amount'),
                                        DB::raw('(SELECT clinic_id FROM plan_master WHERE plan_master.treatment_id = sessionmaster.treatment_id LIMIT 1) AS clinic_id'),
                                        DB::raw('(SELECT isGroupSession FROM patientin WHERE patientin.iPatientInId  = sessionmaster.iPatientInId LIMIT 1) AS isGroupSession')
                                    ])
                                    ->where('sessionmaster.session_status', 2)
                                    ->when($request->fromdate, function ($query) use ($request) {
                                        return $query->where('sessionmaster.created_at', '>=', date('Y-m-d 00:00:00', strtotime($request->fromdate)));
                                    })
                                    ->when($request->todate, function ($query) use ($request) {
                                        return $query->where('sessionmaster.created_at', '<=', date('Y-m-d 23:59:59', strtotime($request->todate)));
                                    })
                                    ->when($request->month, function ($query) use ($request) {
                                        return $query->whereMonth('sessionmaster.created_at', $request->month);
                                    })
                                    ->when($request->year, function ($query) use ($request) {
                                        return $query->whereYear('sessionmaster.created_at', $request->year);
                                    })
                                    ->join('patient_schedule AS ps', 'ps.patient_schedule_id', '=', 'sessionmaster.scheduleid')
                                    ->join('patient_suggested_treatment AS pst', 'pst.iOrderId', '=', 'ps.orderId')
                                    ->join('patientorderdetail AS pod', 'pod.iOrderId', '=', 'ps.orderId')
                                    ->orderBy('sessionmaster.iSessionTakenId','asc')
                                    ->get();

                
                         
                         $sessionList=[];
                         $sessionList2=[];
                        $session=0;
                        $amount=0;
                        $total=0;

                    foreach ($attendedSession as $key => $val) 
                    {
                            $session = $val->iUsedSession;
                            $amount = $val->per_session_amount;
                            $total = $amount * $session;
                            
                             if($val->isGroupSession == 1)
                            {
                                $groupsession="yes";
                            }
                            else{
                                $groupsession="no";
                            }
                            
                            $sessionList[] = array
                            (               
                                     "invoice_no"=>$val->invoice_no ?? '-',
                                    "date"=>$val->created_at,
                                    "patient_name" => $val->patient_name,
                                    "therapist_name" => $val->therapist_name ?? '-',
                                    "treatment_name" => $val->treatment_name,
                                    "total_session"=>$val->iUsedSession,
                                    "total_session_amount"=>$val->per_session_amount,
                                    "group_session"=>$groupsession,
                                    "total_amount"=>$total

                                );
                                $sessionList2[] = array
                                (               
                                     "invoice_no"=>$val->invoice_no ?? '-',
                                    "date"=>$val->created_at,
                                    "patient_name" => $val->patient_name,
                                    "therapist_name" => $val->therapist_name ?? '-',
                                    "treatment_name" => $val->treatment_name,
                                    "group_session"=>$groupsession,
                                    "total_session_amount"=>$val->per_session_amount,

                                );
                    }
                    if($request->status == 1)
                    {
                        
                    
                      $export = new SessionExport($sessionList2, $request->fromdate, $request->todate, $request->month, $request->year);

                    // Define the target directory and ensure it exists
                    $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                    if (!file_exists($basePath)) {
                        mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                    }
                    
                    // Define the file path
                    $fileName = 'Patient_attended_session_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                    $filePath = $basePath . '/' . $fileName;
                    
                    // Store the Excel file
                    Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                    
                    // Generate the public file URL
                    $fileUrl = asset('reports/export/' . $fileName);
                    
                    return response()->json([
                        'status' => 'success',
                        'file_url' => $fileUrl
                        ]);
                        
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Attended Session List',
                        'Attended Session List' => $sessionList,
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
    public function total_session_report(Request $request)
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

                    
                    $treatment = SessionMaster::selectRaw('sessionmaster.treatment_id,COUNT(*) as session_count,pst.iUsedSession,
                        (SELECT treatment_name FROM treatment_master WHERE treatment_master.treatment_id = sessionmaster.treatment_id LIMIT 1) AS treatment_name,
                        (SELECT per_session_amount FROM plan_master WHERE plan_master.plan_id = pod.iPlanId LIMIT 1) AS per_session_amount,
                        (SELECT clinic_id FROM plan_master WHERE plan_master.treatment_id = sessionmaster.treatment_id LIMIT 1) AS clinic_id'
                        )
                        ->where(['session_status' => 2])
                        ->when($request->fromdate, fn($query, $FromDate) =>$query->where('sessionmaster.created_at', '>=', date('Y-m-d 00:00:00', strtotime($FromDate))))
                        ->when($request->todate, fn($query, $ToDate) =>$query->where('sessionmaster.created_at', '<=', date('Y-m-d 23:59:59', strtotime($ToDate))))
                        ->when($request->month, fn($query, $month) => $query->whereMonth('sessionmaster.created_at', $month))
                        ->when($request->year, fn($query, $year) =>$query->whereYear('sessionmaster.created_at', $year))
                        ->join('patient_schedule AS ps', 'ps.patient_schedule_id', '=', 'sessionmaster.scheduleid')
                        ->join('patient_suggested_treatment AS pst', 'pst.iOrderId', '=', 'ps.orderId')
                        ->join('patientorderdetail AS pod', 'pod.iOrderId', '=', 'ps.orderId')
                        ->groupBy('sessionmaster.treatment_id') 
                        ->get();
                        

                    if (!$treatment->isEmpty()) 
                    {
                        $grandtotal = 0;  // Move grand total outside the loop
                        $treatmentList = [];
                        $session=0;
                        $amount=0;
                        $grandtotal=0;
                        $totalsession=0;
                        $totalamount=0;
                        $total=0;
                        foreach ($treatment as $val) 
                        {
                            /*$session = $val->iUsedSession;*/
                            $session = $val->session_count;
                            $amount = $val->per_session_amount;
                            $total = $amount * $session;
                            
                            $grandtotal += $total; // Accumulate grand total correctly
                            $totalsession += $session; // Accumulate grand total correctly
                            $totalamount += $amount; // Accumulate grand total correctly
                    
                            $treatmentList[] = [
                                "clinic_id" => $val->clinic_id,
                                "treatment_id" => $val->treatment_id,
                                "treatment_name" => $val->treatment_name,
                                /*"attended_session" => $val->iUsedSession,*/
                                "attended_session" => $val->session_count,
                                "amount" => $amount,
                                "total" => $total
                            ];
                                $treatmentList2[] = array
                                (
                                "treatment_name" => $val->treatment_name,
                                "attended_session" => $val->session_count ?? '-',
                                "amount" => $amount,
                                "total" => $total
                                );
                        }

                    if($request->status == 1)
                    {
                    
                      $export = new TreatmentExport($treatmentList2, $request->fromdate, $request->todate, $request->month, $request->year);

                    // Define the target directory and ensure it exists
                    $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                    if (!file_exists($basePath)) {
                        mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                    }
                    
                    // Define the file path
                    $fileName = 'Collection_of_Treatment_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                    $filePath = $basePath . '/' . $fileName;
                    
                    // Store the Excel file
                    Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                    
                    // Generate the public file URL
                    $fileUrl = asset('reports/export/' . $fileName);
                    
                    return response()->json([
                        'status' => 'success',
                        'file_url' => $fileUrl]);
                        
                    }
                    
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Total Session Report',
                                "total_session"=>$totalsession,
                                "total_amount"=>$totalamount,
                                "total"=>$grandtotal,
                                'total_session_report' => $treatmentList
                            ]);
                        }else{
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Total Session Report',
                                'total_session_report' => []
                            ],401);
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
    public function patient_payment_collection(Request $request)
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

                    $doctortreatment = OrderPayment::select(
                    'orderpayment.*',
                    'pom.patient_id',
                    'pm.clinic_id',
                    'ps.therapist_id',
                    'pod.iTreatmentId',
                    
                    DB::raw("(SELECT CONCAT(pm.patient_first_name, ' ', pm.patient_last_name) 
                              FROM patient_master pm WHERE pm.patient_id = pom.patient_id LIMIT 1) AS patient_name"),
                    DB::raw("(SELECT u.name FROM users u WHERE ps.therapist_id = u.id LIMIT 1) AS therapist_name"),
                    DB::raw("(SELECT tm.treatment_name FROM treatment_master tm WHERE tm.treatment_id = pod.iTreatmentId LIMIT 1) AS treatment_name")
                )
                ->join('patientorderdetail AS pod', 'pod.iOrderDetailId', '=', 'orderpayment.orderDetailId')
                ->join('patientordermaster AS pom', 'pom.iOrderId', '=', 'pod.iOrderId')
                ->join('patient_master AS pm', 'pm.patient_id', '=', 'pom.patient_id')
                ->leftJoin('patient_schedule AS ps', 'ps.treatment_id', '=', 'pod.iTreatmentId')
                ->where('pm.clinic_id', $request->clinic_id)
                ->when($request->fromdate, fn ($query, $FromDate) => $query->where('PaymentDateTime', '>=', date('Y-m-d 00:00:00', strtotime($FromDate))))
                ->when($request->todate, fn ($query, $ToDate) => $query->where('PaymentDateTime', '<=', date('Y-m-d 23:59:59', strtotime($ToDate))))
                ->when($request->month, fn ($query, $month) => $query->whereMonth('PaymentDateTime', $month))
                ->when($request->year, fn ($query, $year) => $query->whereYear('PaymentDateTime', $year))
                ->when($request->therapist_id, fn ($query, $TherapistId) => $query->where('ps.therapist_id', '=', $TherapistId))
                ->groupBy('orderpayment.orderDetailId') // Ensures unique records
                ->get();


                    if(sizeof($doctortreatment) != 0)
                      {
                            foreach ($doctortreatment as $key => $val) 
                            {
                                $drtreatmentList[] = array
                                (
                                    "clinic_id" => $val->clinic_id,
                                    "therapist_id" => $val->therapist_id,
                                    "therapist_name" => $val->therapist_name,
                                    "treatment_id" => $val->iTreatmentId,
                                    "treatment_name" => $val->treatment_name,
                                    "patient_id"=>$val->patient_id,
                                    "patient_name"=>$val->patient_name,
                                    "amount"=>$val->Amount,
                                    "payment_mode"=>$val->payment_mode,
                                );
                                
                                $drtreatmentList2[] = array
                                (
                                    "clinic_id" => $val->clinic_id,
                                    "patient_name"=>$val->patient_name,
                                    "therapist_name" => $val->therapist_name,
                                    "treatment_name" => $val->treatment_name,
                                    "amount"=>$val->Amount,
                                    "payment_mode"=>$val->payment_mode,
                                );
                            }

    
                            if($request->status == 1)
                        {
                        
                          $export = new PatientPayment($drtreatmentList2, $request->fromdate, $request->todate, $request->month, $request->year);
    
                        // Define the target directory and ensure it exists
                        $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                        if (!file_exists($basePath)) {
                            mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                        }
                        
                        // Define the file path
                        $fileName = 'Patient_Payment_Collection_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                        $filePath = $basePath . '/' . $fileName;
                        
                        // Store the Excel file
                        Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                        
                        // Generate the public file URL
                        $fileUrl = asset('reports/export/' . $fileName);
                        
                        return response()->json([
                            'status' => 'success',
                            'file_url' => $fileUrl]);
                            
                        }
                        
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Patient Payment Collection',
                                'payment_collection' => $drtreatmentList
                            ]);
                        }else{
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Patient Payment Collection',
                                'payment_collection' => []
                            ],401);
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
    public function patient_due_amount_msg(Request $request)
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

                    $data=Order::select('patient_master.phone','DueAmount','patientordermaster.patient_id')->where(['patientordermaster.patient_id'=>$request->patient_id,'clinic_id'=>$request->clinic_id])->join('patient_master', 'patient_master.patient_id', '=', 'patientordermaster.patient_id')->latest('patientordermaster.created_at')->first(); 

                        $key = $_ENV['WHATSAPPKEY'];
                        $users = new User();
                        $msg = "Dear Parent,\n\n"
                            . "This is a reminder that your payment is due. Kindly make the payment at your earliest convenience to avoid any interruptions in services.\n\n"
                            . "*Payment Details:*\n"
                            . "• *Due Amount:* {$data->DueAmount}\n\n"
                            . "For any queries or assistance, please feel free to contact us.\n\n"
                            . "Thank you!";

                        $status = $users->sendWhatsappMessage($data->phone,$key,$msg, $someOtherParam = null);

                        return response()->json([
                        'status' => 'success',
                        'message' => 'Whats app send Successfully',

                    ], 401);

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
    public function total_collection_report(Request $request)
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
                $OrderPayment=OrderPayment::select('orderpayment.*')
                    ->when($request->fromdate || $request->todate || $request->month || $request->year, function ($query) use ($request) 
                        {
                            if ($request->fromdate) {
                                $query->where('PaymentDateTime', '>=', date('Y-m-d 00:00:00', strtotime($request->fromdate)));
                            }
                            if ($request->todate) {
                                $query->where('PaymentDateTime', '<=', date('Y-m-d 23:59:59', strtotime($request->todate)));
                            }
                            if ($request->month) {
                                $query->whereMonth('PaymentDateTime', $request->month);
                            }
                            if ($request->year) {
                                $query->whereYear('PaymentDateTime', $request->year);
                            }

                        })  // Ensuring unique payments
                    ->get();

                    if(sizeof($OrderPayment) != 0)
                    {

                        $opayment = 0;
                        $cashpayment = 0;
                        $npayment = 0;
                        $cardpayment = 0;
                        $totalamount = 0;
                        
                        $online = 0;
                        $cash = 0;
                        $NEFT = 0;
                        $card = 0;
                        
                        $pList = [];
                        
                        foreach ($OrderPayment as $key => $val) 
                        {
                            // Reset values for each order
                            $opayment = 0;
                            $cashpayment = 0;
                            $npayment = 0;
                            $cardpayment = 0;
                        
                            if ($val->payment_mode == 'Online') {
                                $opayment = $val->Amount;
                            }
                            if ($val->payment_mode == 'Cash') {
                                $cashpayment = $val->Amount;
                            }
                            if ($val->payment_mode == 'NEFT') {
                                $npayment = $val->Amount;
                            }     
                            if ($val->payment_mode == 'Card') {
                                $cardpayment = $val->Amount;
                            }
                        
                            $totalamount = $opayment + $cardpayment + $npayment + $cashpayment;
                        
                            $pList[] = [
                                //"order_id" => $val->iOrderId,
                                //"order_detail_id" => $val->orderDetailId,
                                "payment_date" => date('d-M-Y', strtotime($val->PaymentDateTime)),
                                "Online" => $opayment ?: '-',
                                "Cash" => $cashpayment ?: '-',
                                "NEFT" => $npayment ?: '-',
                                "Card" => $cardpayment ?: '-',
                                "Total_Amount" => $totalamount ?: '-',
                            ];
                        
                            // Accumulate totals
                            $online += $opayment;
                            $cash += $cashpayment;
                            $NEFT += $npayment;
                            $card += $cardpayment;  // Corrected from $npayment
                        }

                          if($request->status == 1)
                        {
                        
                          $export = new TotalCollection($pList, $request->fromdate, $request->todate, $request->month, $request->year);
    
                        // Define the target directory and ensure it exists
                        $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                        if (!file_exists($basePath)) {
                            mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                        }
                        
                        // Define the file path
                        $fileName = 'Total_Collection_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                        $filePath = $basePath . '/' . $fileName;
                        
                        // Store the Excel file
                        Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                        
                        // Generate the public file URL
                        $fileUrl = asset('reports/export/' . $fileName);
                        
                        return response()->json([
                            'status' => 'success',
                            'file_url' => $fileUrl]);
                            
                        }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Total Collection Report',
                                    'total_online' => $online,
                                    'total_cash' => $cash,
                                    'total_NEFT' => $NEFT,
                                    'total_card' => $card,

                                    'total_collection' => $pList
                                ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'total_collection' => []
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
    public function total_attended_session_report(Request $request)
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


                $attendedSession=SessionMaster::select('sessionmaster.patient_id','patient_master.clinic_id','sessionmaster.treatment_id','sessionmaster.created_at',DB::raw("(SELECT CONCAT(patient_master.patient_first_name, ' ', patient_master.patient_last_name)FROM patient_master WHERE patient_master.patient_id = sessionmaster.patient_id LIMIT 1) AS patient_name")
                    ,DB::raw("(select treatment_name from treatment_master where sessionmaster.treatment_id=treatment_master.treatment_id limit 1) as treatment_name")
            )
                        ->where('sessionmaster.session_status', 2)
                        ->where(['patient_master.clinic_id'=>$request->clinic_id])
                        ->join('patient_master', 'patient_master.patient_id', '=', 'sessionmaster.patient_id') // Join to get clinic_id
                        ->when($request->fromdate, fn ($query, $FromDate) => $query->where('sessionmaster.created_at', '>=', date('Y-m-d 00:00:00', strtotime($FromDate))))
                        ->when($request->todate, fn ($query, $ToDate) => $query->where('sessionmaster.created_at', '<=', date('Y-m-d 23:59:59', strtotime($ToDate))))
                        ->when($request->month, fn ($query, $month) => $query->whereMonth('sessionmaster.created_at', $month))
                        ->when($request->year, fn ($query, $year) => $query->whereYear('sessionmaster.created_at', $year))

                         ->get();
                         
                         $sessionList=[];
                         
                    foreach ($attendedSession as $key => $val) 
                    {

                                $sessionList[] = array
                                (
                                    "date" => $val->created_at,
                                    "patient_name" => $val->patient_name,
                                    "treatment_name" => $val->treatment_name ?? '-',
                                    "amount"=>"",
                                    "payment_mode"=>""
                                );
                    }
                    
                     if($request->status == 1)
                    {
                    
                      $export = new TotalAttended($sessionList, $request->fromdate, $request->todate, $request->month, $request->year);

                    // Define the target directory and ensure it exists
                    $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                    if (!file_exists($basePath)) {
                        mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                    }
                    
                    // Define the file path
                    $fileName = 'Total_Attended_Session_report' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                    $filePath = $basePath . '/' . $fileName;
                    
                    // Store the Excel file
                    Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                    
                    // Generate the public file URL
                    $fileUrl = asset('reports/export/' . $fileName);
                    
                    return response()->json([
                        'status' => 'success',
                        'file_url' => $fileUrl]);
                        
                    }
                    
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Total Attended Session Report',
                        'total_attended_session' => $sessionList,
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
    public function daily_collection_report(Request $request)
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

                $OrderPayment=OrderPayment::select('orderpayment.*',DB::raw("(SELECT CONCAT(patient_master.patient_first_name, ' ', patient_master.patient_last_name) 
                  FROM patient_master WHERE patient_master.patient_id = patientordermaster.patient_id LIMIT 1) AS patient_name")
                    ,'patientordermaster.patient_id','patientordermaster.DueAmount','patientordermaster.iAmount')
                    ->join('patientorderdetail', 'patientorderdetail.iOrderDetailId', '=', 'orderpayment.orderDetailId')
                    ->join('patientordermaster', 'patientordermaster.iOrderId', '=', 'patientorderdetail.iOrderId')
                    ->when($request->fromdate || $request->todate || $request->month || $request->year, function ($query) use ($request) 
                        {
                            if ($request->fromdate) {
                                $query->where('PaymentDateTime', '>=', date('Y-m-d 00:00:00', strtotime($request->fromdate)));
                            }
                            if ($request->todate) {
                                $query->where('PaymentDateTime', '<=', date('Y-m-d 23:59:59', strtotime($request->todate)));
                            }
                            if ($request->month) {
                                $query->whereMonth('PaymentDateTime', $request->month);
                            }
                            if ($request->year) {
                                $query->whereYear('PaymentDateTime', $request->year);
                            }
                        }, function ($query) {
                            $query->whereDate('PaymentDateTime', today());
                        })
                    ->get();
                    if(sizeof($OrderPayment) != 0)
                    {

                        foreach ($OrderPayment as $key => $val) 
                        {
                             $totalPaid = OrderPayment::where(['iOrderId' => $val->iOrderId])->sum('Amount');
                             
                                $pList[] = array(
                                    "payment_date" => date('d-M-Y',strtotime($val->PaymentDateTime)),
                                    "receipt_no"=>$val->OrderPaymentId ?? 0,
                                    "patient_name" => $val->patient_name,
                                    "amount"=>$totalPaid ?? 0,
                                    "payment_mode" => $val->payment_mode,
                                );
                        }
                        
                         if($request->status == 1)
                    {
                    
                      $export = new DailyCollection($pList, $request->fromdate, $request->todate, $request->month, $request->year);

                    // Define the target directory and ensure it exists
                    $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                    if (!file_exists($basePath)) {
                        mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                    }
                    
                    // Define the file path
                    $fileName = 'daily_collection_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                    $filePath = $basePath . '/' . $fileName;
                    
                    // Store the Excel file
                    Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                    
                    // Generate the public file URL
                    $fileUrl = asset('reports/export/' . $fileName);
                    
                    return response()->json([
                        'status' => 'success',
                        'file_url' => $fileUrl]);
                        
                    }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Daily Collection Report',
                                    'daily_collection' => $pList
                                ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'daily_collection' => []
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
        public function total_patient_collection_report(Request $request)
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

                $OrderPayment=OrderPayment::select('orderpayment.*',DB::raw("(SELECT CONCAT(patient_master.patient_first_name, ' ', patient_master.patient_last_name) 
                  FROM patient_master WHERE patient_master.patient_id = patientordermaster.patient_id LIMIT 1) AS patient_name")
                    ,'patientordermaster.patient_id','patientordermaster.DueAmount','patientordermaster.iAmount')
                    ->join('patientorderdetail', 'patientorderdetail.iOrderDetailId', '=', 'orderpayment.orderDetailId')
                    ->join('patientordermaster', 'patientordermaster.iOrderId', '=', 'patientorderdetail.iOrderId')
                    ->when($request->fromdate || $request->todate || $request->month || $request->year, function ($query) use ($request) 
                        {
                            if ($request->fromdate) {
                                $query->where('PaymentDateTime', '>=', date('Y-m-d 00:00:00', strtotime($request->fromdate)));
                            }
                            if ($request->todate) {
                                $query->where('PaymentDateTime', '<=', date('Y-m-d 23:59:59', strtotime($request->todate)));
                            }
                            if ($request->month) {
                                $query->whereMonth('PaymentDateTime', $request->month);
                            }
                            if ($request->year) {
                                $query->whereYear('PaymentDateTime', $request->year);
                            }

                        })
                    ->get();
                    if(sizeof($OrderPayment) != 0)
                    {

                        foreach ($OrderPayment as $key => $val) 
                        {
                             $totalPaid = OrderPayment::where(['iOrderId' => $val->iOrderId])->sum('Amount');
                               
                                $pList[] = array(
                                    "receipt_no" => $val->receipt_no,
                                    "payment_date" => date('d-M-Y',strtotime($val->PaymentDateTime)),
                                    "patient_name" => $val->patient_name,
                                    "payment_mode"=>$val->payment_mode,
                                    "amount"=>$val->Amount
                                );
                        }
                        if($request->status == 1)
                    {
                        
                    
                      $export = new PatientCollection($pList, $request->fromdate, $request->todate, $request->month, $request->year);

                    // Define the target directory and ensure it exists
                    $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                    if (!file_exists($basePath)) {
                        mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                    }
                    
                    // Define the file path
                    $fileName = 'Patient_Collection_Report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                    $filePath = $basePath . '/' . $fileName;
                    
                    // Store the Excel file
                    Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                    
                    // Generate the public file URL
                    $fileUrl = asset('reports/export/' . $fileName);
                    
                    return response()->json([
                        'status' => 'success',
                        'file_url' => $fileUrl
                        ]);
                        
                    }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Total Patient Collection Report',
                                    'total_patient_collection' => $pList
                                ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'total_patient_collection' => []
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
     public function groupsession_report(Request $request)
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
                    //\DB::enableQueryLog(); // Enable query log

                    $attendedSession =SessionMaster::select(['sessionmaster.created_at','sessionmaster.SessionStartTime','sessionmaster.SessionEndTime','sessionmaster.patient_id','patientin.isGroupSession',DB::raw('(SELECT treatment_name FROM treatment_master WHERE treatment_master.treatment_id = sessionmaster.treatment_id LIMIT 1) AS treatment_name'),
                        DB::raw('(SELECT name FROM users WHERE users.id = sessionmaster.therapist_id LIMIT 1) AS therapist_name'),
                        DB::raw('(SELECT CONCAT(patient_master.patient_first_name, " ", patient_master.patient_last_name) FROM patient_master WHERE patient_master.patient_id = sessionmaster.patient_id LIMIT 1) AS patient_name'),
                        DB::raw('(SELECT clinic_id FROM plan_master WHERE plan_master.treatment_id = sessionmaster.treatment_id LIMIT 1) AS clinic_id')
                    ])
                    // ->where('sessionmaster.session_status', 2)
                    ->when($request->patient_id, fn($query, $patient_id) => $query->where('sessionmaster.patient_id', '=', $patient_id))
                    ->when($request->fromdate, function ($query) use ($request) {
                        return $query->where('sessionmaster.created_at', '>=', date('Y-m-d 00:00:00', strtotime($request->fromdate)));
                    })
                    ->when($request->todate, function ($query) use ($request) {
                        return $query->where('sessionmaster.created_at', '<=', date('Y-m-d 23:59:59', strtotime($request->todate)));
                    })
                    ->when($request->month, function ($query) use ($request) {
                        return $query->whereMonth('sessionmaster.created_at', $request->month);
                    })
                    ->when($request->year, function ($query) use ($request) {
                        return $query->whereYear('sessionmaster.created_at', $request->year);
                    })
                    ->join('patientin', function ($join) {
                        $join->on('patientin.iPatientInId', '=', 'sessionmaster.iPatientInId');
                    }) 
                    ->orderBy('sessionmaster.created_at','asc')
                    ->groupBy('iSessionTakenId')
                    ->get();
///dd(\DB::getQueryLog()); // Show results of log

                         
                        $sessionList=[];
                        $sessionList2=[];
                        $session=0;
                        $amount=0;
                        $total=0;
                        $groupsession=0;
                    foreach ($attendedSession as $key => $val) 
                    {
                            
                            if($val->isGroupSession == 1)
                            {
                                $groupsession="yes";
                            }
                            else{
                                $groupsession="no";
                            }
                            $sessionList[] = array
                            (               
                                    "patient_id" => $val->patient_id,
                                    "patient_name" => $val->patient_name,
                                    "date"=>$val->created_at,
                                    "start_time" => $val->SessionStartTime,
                                    "end_time" => $val->SessionEndTime,
                                    "therapist_name" => $val->therapist_name ?? '-',
                                    "treatment_name" => $val->treatment_name,
                                    "group_session" => $groupsession

                                );
                                $sessionList2[] = array
                                (               
                                    "patient_name" => $val->patient_name,
                                    "date"=>$val->created_at,
                                    "start_time" => $val->SessionStartTime,
                                    "end_time" => $val->SessionEndTime,
                                    "therapist_name" => $val->therapist_name ?? '-',
                                    "treatment_name" => $val->treatment_name,
                                    "group_session" => $groupsession

                                );
                    }
                    if($request->status == 1)
                    {
                        
                    
                      $export = new GroupSessionExport($sessionList2, $request->patient_id, $request->fromdate, $request->todate, $request->month, $request->year);

                    // Define the target directory and ensure it exists
                    $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                    if (!file_exists($basePath)) {
                        mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                    }
                    
                    // Define the file path
                    $fileName = 'Group_session_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                    $filePath = $basePath . '/' . $fileName;
                    
                    // Store the Excel file
                    Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                    
                    // Generate the public file URL
                    $fileUrl = asset('reports/export/' . $fileName);
                    
                    return response()->json([
                        'status' => 'success',
                        'file_url' => $fileUrl
                        ]);
                        
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Attended Session List',
                        'Attended Session List' => $sessionList,
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
    public function patient_visit_report(Request $request)
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
                        $month     = $request->input('month');
                        $year      = $request->input('year');
                        $fromDate  = $request->input('from_date');
                        $toDate    = $request->input('to_date');
                        
                        // STEP 1: Get all treatment names (in uppercase) with default 0
                        $defaultTreatments = DB::table('treatment_master')
                            ->where('isDelete', 0)
                            ->pluck('treatment_name')
                            ->mapWithKeys(function ($name) {
                                return [strtoupper($name) => 0];
                            })
                            ->toArray();
                        
                        // STEP 2: Get patient-wise + treatment-wise session counts
                        $query = DB::table('sessionmaster')
                            ->join('patient_master', 'sessionmaster.patient_id', '=', 'patient_master.patient_id')
                            ->join('treatment_master', 'sessionmaster.treatment_id', '=', 'treatment_master.treatment_id')
                            ->select(
                                DB::raw("CONCAT(patient_master.patient_first_name, ' ', patient_master.patient_last_name) as patient_name"),
                                DB::raw("UPPER(treatment_master.treatment_name) as treatment_name"),
                                DB::raw("COUNT(*) as session_count")
                            )
                            ->where('sessionmaster.session_status', 2); // ended sessions only
                        
                        // Optional date filters
                        if ($month && $year) {
                            $query->whereYear('sessionmaster.created_at', $year)
                                  ->whereMonth('sessionmaster.created_at', $month);
                        }
                        if ($fromDate && $toDate) {
                            $query->whereBetween('sessionmaster.created_at', [$fromDate, $toDate]);
                        }
                        
                        $query->groupBy('sessionmaster.patient_id', 'patient_name', 'treatment_name');
                        $results = $query->get();
                        
                        // STEP 3: Group results per patient and include all treatments
                        $finalData = [];
                        
                        foreach ($results as $row) {
                            $patientName = $row->patient_name;
                            $treatmentName = $row->treatment_name;
                            $sessionCount = $row->session_count;
                        
                            // Initialize patient if not present
                            if (!isset($finalData[$patientName])) {
                                $finalData[$patientName] = [
                                    'patient_name' => $patientName,
                                    'treatments' => $defaultTreatments // clone full treatment list
                                ];
                            }
                        
                            // Set the session count for the existing treatment
                            $finalData[$patientName]['treatments'][$treatmentName] = $sessionCount;
                        }
                        
                        foreach ($finalData as $patient => &$data) {
                                $treatments = $data['treatments'];
                                $total = array_sum($treatments);
                            
                                // Rebuild array to control key order: patient_name → total → treatments
                                $data = [
                                    'patient_name' => $data['patient_name'],
                                    'total' => $total,
                                    'treatments' => $treatments,
                                ];
                            
                                $finalData[$patient] = $data;
                            }

                        // Re-index array
                        $patientVisitArray = array_values($finalData);



                        
                    if($request->status == 1)
                    {
                        
                    
                      $export = new PatientVisit($patientVisitArray, $request->fromdate, $request->todate, $request->month, $request->year);

                    // Define the target directory and ensure it exists
                    $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                    if (!file_exists($basePath)) {
                        mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                    }
                    
                    // Define the file path
                    $fileName = 'Patient_visit_Report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                    $filePath = $basePath . '/' . $fileName;
                    
                    // Store the Excel file
                    Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                    
                    // Generate the public file URL
                    $fileUrl = asset('reports/export/' . $fileName);
                    
                    return response()->json([
                        'status' => 'success',
                        'file_url' => $fileUrl
                        ]);
                        
                    }
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Patient Visit Count',
                        'Patient Visit Count' => $patientVisitArray,
                        ]);


                      //  return response()->json($results);
                 
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
    public function upcoming_renewal_report(Request $request)
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
    

                     $schedule = PatientSchedule::select(
                'patient_schedule.*',
                'patient_master.clinic_id',
                'patientorderdetail.iAmount as total_amount',
                'patientorderdetail.iDueAmount as due_amount',
                'patientorderdetail.iPlanId',
                'patientorderdetail.iOrderDetailId',
                'patientorderdetail.iOrderId',
                'patientorderdetail.iSession as total_session_buy',

                DB::raw("(SELECT CONCAT(pm.patient_first_name,' ',pm.patient_last_name)
                        FROM patient_master pm 
                        WHERE pm.patient_id = patient_schedule.patient_id LIMIT 1) AS patient_name")
            )
            ->join('patient_master', 'patient_master.patient_id', '=', 'patient_schedule.patient_id')
            ->join('patientordermaster', 'patientordermaster.iOrderId', '=', 'patient_schedule.orderId')
            ->join('patientorderdetail', function ($join) {
                $join->on('patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')
                     ->on('patientorderdetail.iTreatmentId', '=', 'patient_schedule.treatment_id');
            })
            ->where([
                'patient_master.clinic_id' => $request->clinic_id,
                'cancel_package' => 0
            ])
            ->groupBy('patientorderdetail.iOrderDetailId')
            ->orderBy('patient_schedule_id', 'desc')
            ->get();

        if ($schedule->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No Data Found',
                'upcoming_renewal' => []
            ]);
        }

        // ---------------- PROCESS EACH PATIENT --------------------

        $renewalList = [];

        foreach ($schedule as $row) {

            $session = PatientSuggestedTreatment::where([
                    'patient_id'    => $row->patient_id,
                    'iOrderId'      => $row->iOrderId,
                    'iOrderDetailId'=> $row->iOrderDetailId,
                    'treatment_id'  => $row->treatment_id
                ])->first();

            if (!$session) continue;

            // Per session amount
            $plan = Plan::select('plan_master.per_session_amount')
                        ->where('plan_id', $row->iPlanId)
                        ->first();

            $perSession = $plan->per_session_amount ?? 0;

            // ---------------- CALCULATIONS --------------------

            $totalPaid = ($row->total_amount ?? 0) - ($row->due_amount ?? 0);

            // Paid session count
            $paidSession = ($perSession != 0) 
                ? ($totalPaid / $perSession) 
                : 0;

            // Consumed amount
            $consumedAmount = $session->iUsedSession * $perSession;

            // Available amount
            $availableAmount = $totalPaid - $consumedAmount;

            // Remaining sessions (due)
            $dueSession = $row->total_session_buy - $paidSession;

            // Available sessions
            $availableSession = $paidSession - ($session->iUsedSession ?? 0);

            // ---------------- UPCOMING RENEWAL CONDITION --------------------
            // ⭐ include only if available sessions <= 2
            if ($availableSession <= 2) {

                    $renewalList[] = [
                        "patient_id"         => $row->patient_id,
                        "patient_name"       => $row->patient_name,
                        "clinic_id"          => $row->clinic_id,

                        "total_session"      => $row->total_session_buy ?? 0,
                        "paid_session"       => number_format($paidSession, 1),
                        "due_session"        => number_format($dueSession, 1),
                        "consumed_session"   => $session->iUsedSession ?? 0,
                        "available_session"  => number_format($availableSession, 1),
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Upcoming Renewal List',
                'upcoming_renewal' => $renewalList
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
    
}