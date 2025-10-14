<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\Patient;
use App\Models\PatientIn;
use App\Models\PatientDocument;
use App\Models\TreatmentTherapist;
use App\Models\PatientSchedule;
use App\Models\PatientSuggestedTreatment;
use App\Models\PatientTreatmentLedger;
use App\Models\SessionMaster;
use App\Models\User;

use Carbon\Carbon;

class PatientSessionController extends Controller
{
    public function patient_session_start(Request $request)
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
                    
                    $schedule=PatientSchedule::where(['patient_schedule.patient_id'=>$request->patient_id,'patient_schedule.treatment_id'=>$request->treatment_id,'cancel_package' => 0,'isActive'=>1,'patient_schedule_id'=>$request->patient_schedule_id])
                    ->join('patientordermaster', 'patientordermaster.iOrderId', '=', 'patient_schedule.orderId')
                        
                    ->join('patientorderdetail', function ($join) 
                        {
                                $join->on('patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')
                             ->on('patientorderdetail.iTreatmentId', '=', 'patient_schedule.treatment_id');
                        })
                    ->join('patient_suggested_treatment', function ($join) 
                        {
                                $join->on('patientorderdetail.iOrderId', '=', 'patient_suggested_treatment.iOrderId')
                                ->on('patientorderdetail.iOrderDetailId', '=', 'patient_suggested_treatment.iOrderDetailId')
                                ->on('patientorderdetail.iTreatmentId', '=', 'patient_schedule.treatment_id');
                        })
                        ->first();

                    if($schedule)
                    {
                         $date=date('Y-m-d');
                         $SessionMasterdata=SessionMaster::where(['patient_id'=>$request->patient_id,'therapist_id'=>$request->therapist_id,'treatment_id'=>$request->treatment_id,'iPatientInId'=>$request->patient_in_id,'created_at'=>$date])->first();
                         
                        if($SessionMasterdata)
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Patient Session Already Started',
                            ],401);
                        }else{


                            $SessionMaster=new SessionMaster();
                            $SessionMaster->patient_id=$request->patient_id;
                            $SessionMaster->scheduleid=$schedule->patient_schedule_id;
                            $SessionMaster->iPatientInId=$request->patient_in_id;
                            $SessionMaster->SessionStartTime=date('H:i:s');
                            $SessionMaster->treatment_id=$request->treatment_id;
                            $SessionMaster->therapist_id=$request->therapist_id;
                            $SessionMaster->created_at=$date;
                            $SessionMaster->save();

                            return response()->json([
                                'status' => 'success',
                                'session_id' => $SessionMaster->iSessionTakenId,
                                'message' => 'Patient Session Start Successfully',
                            ]);
                            }
                    }else{
                         return response()->json([
                            'status' => 'error',
                            'message' => 'Patient Session Not Found',
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
    public function patient_session_end(Request $request)
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
                    $id=$request->session_id;
                    $date=date('Y-m-d');
                    $session = SessionMaster::where(['iSessionTakenId'=>$id,'iPatientInId'=>$request->patient_in_id,'created_at'=>$date])->where('SessionEndTime','=',null)->first();
                    
                    // Update the fields if session exists
                    if ($session) 
                    {
                        

                        $ledger = PatientTreatmentLedger::where('patient_id', $session->patient_id)
                            ->where('treatment_id', $session->treatment_id)
                            ->where('therapist_id', $session->therapist_id)
                            ->where('patient_id', $session->patient_id)
                            ->first();

                            
                            if ($ledger) 
                            {
                                $used_session = 1; // The number of sessions being used

                                $new_opening_balance = $ledger->closing_balance; 
                                $new_credit_balance = $ledger->credit_balance - $used_session;
                                $new_debit_balance = $ledger->debit_balance + $used_session; 
                                $new_closing_balance = $new_opening_balance - $used_session; 


                                $ledger = new PatientTreatmentLedger();
                                $ledger->patient_id = $session->patient_id;
                                $ledger->treatment_id = $session->treatment_id;
                                $ledger->therapist_id = $session->therapist_id;
                                $ledger->iOrderDetailId = $ledger->iOrderDetailId;
                                $ledger->iSessionTakenId = $session->iSessionTakenId;
                                $ledger->opening_balance = $new_opening_balance;
                                $ledger->credit_balance = $new_credit_balance;
                                $ledger->debit_balance = $new_debit_balance;
                                $ledger->closing_balance = $new_closing_balance;
                                $ledger->iSessionTakenId = $session->iSessionTakenId;

                                // Save the ledger entry
                                $ledger->save();
                            }
                            $schedule=PatientSchedule::where(['patient_schedule_id'=>$session->scheduleid])->first();

                            $suggested=PatientSuggestedTreatment::where('patient_id', $session->patient_id)
                                ->where(['treatment_id'=> $session->treatment_id,'iOrderId'=>$schedule->orderId,'isActive'=>1])->first();

                               if($suggested)
                               {
                                 if ($suggested->iUsedSession == 0) 
                                    {
                                       $used = $suggested->iUsedSession = 1;
                                    } else {
                                        $used=$suggested->iUsedSession += 1; // Increment by 1 if already set
                                    }
                                    
                                    $suggested->iUsedSession=$used; 
                                    $suggested->iAvailableSession = $suggested->iSessionBuy - $used;
                                    $suggested->save();
                               }
                               $suggest=PatientSuggestedTreatment::where('patient_id', $session->patient_id)->where('treatment_id', $session->treatment_id)
                                ->where('PatientSTreatmentId', $suggested->PatientSTreatmentId)->first();
                       
                       if($suggest->iAvailableSession == 0)
                       {
                                $suggest->isActive=0; 
                                $suggest->save();
                       }

                        $session->SessionEndTime=date('H:i:s');
                        $session->session_status=2;
                        $session->save();

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Patient Session End Successfully',
                        ]);
                    }else{
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Patient Session Already Ended',
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
    public function patient_session_cancel(Request $request)
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
                    $id=$request->session_id;
                    $session = SessionMaster::where('iSessionTakenId', $id)->where(['iPatientInId'=>$request->patient_in_id])->first();

                    // Update the fields if session exists
                    if ($session) 
                    {
                       // $session->SessionEndTime=date('H:i:s');
                        $session->SessionEndTime=null;
                        $session->session_status=3;

                        $session->save();
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Patient Session Cancel Successfully',
                        ]);
                    }else{
                         return response()->json([
                        'status' => 'success',
                        'message' => 'Patient Session Data Not Found',
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
                   
                   $attendedSession=SessionMaster::selectRaw('patient_id,treatment_id,COUNT(*) as session_count,GROUP_CONCAT(DISTINCT therapist_id) as therapist_id,
                    MAX((SELECT name FROM users WHERE users.id = sessionmaster.therapist_id LIMIT 1)) as therapist_name, 
                    MAX((SELECT treatment_name FROM treatment_master WHERE treatment_master.treatment_id = sessionmaster.treatment_id LIMIT 1)) as treatment_name,
                            MAX((SELECT CONCAT(patient_first_name, " ", patient_last_name) FROM patient_master WHERE patient_master.patient_id = sessionmaster.patient_id LIMIT 1)) as patient_name')
                            ->where('session_status', 2)
                            ->groupBy('patient_id','treatment_id')
                        ->when($request->fromdate, fn ($query, $FromDate) => $query->where('sessionmaster.created_at', '>=', date('Y-m-d 00:00:00', strtotime($FromDate))))
                        ->when($request->todate, fn ($query, $ToDate) => $query->where('sessionmaster.created_at', '<=', date('Y-m-d 23:59:59', strtotime($ToDate))))
                         ->get();
                    if(sizeof($attendedSession) != 0)
                    {
                        foreach ($attendedSession as $key => $val) 
                        {
                                $sessionList[] = array
                                (
                                    "patient_id"=>$val->patient_id,
                                    "patient_name" => $val->patient_name,
                                    "therapist_id" => $val->therapist_id,
                                    "therapist_name" => $val->therapist_name,
                                    "treatment_id" => $val->treatment_id,
                                    "treatment_name" => $val->treatment_name,
                                    "attended_session"=>$val->session_count
                                );
                        }
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Attended Session List',
                            'Attended Session List' => $sessionList
                        ]);
                    }else{
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Attended Session List',
                            'Attended Session List' => []
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
     public function add_patient_leave(Request $request)
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
                        $session = PatientSchedule::where(['patient_schedule_id'=>$request->patient_schedule_id])->first();
                        if ($session) 
                        {
                            $today = now()->toDateString(); // Get today's date in 'Y-m-d' format

                            $PatientIn = PatientIn::where([
                                    'treatment_id' => $session->treatment_id,
                                    'therapist_id' => $session->therapist_id,
                                    'patient_id' => $session->patient_id
                                ])
                                ->where(function ($query) {
                                    $query->where('status', 1)
                                          ->orWhere('leave', 1);
                                })
                            ->whereDate('inDateTime', $today) // Compare only the date
                            ->first();

                                if(empty($PatientIn))
                                {
                                    if($request->status == 1)
                                    {
                                         $ledger = PatientTreatmentLedger::where('patient_id', $session->patient_id)
                                            ->where('treatment_id', $session->treatment_id)
                                            ->where('therapist_id', $session->therapist_id)
                                            ->where('patient_id', $session->patient_id)
                                            ->first();

                                        
                                        if ($ledger) 
                                         {
                                            $used_session = 1; // The number of sessions being used

                                            $new_opening_balance = $ledger->closing_balance; 
                                            $new_credit_balance = $ledger->credit_balance - $used_session;
                                            $new_debit_balance = $ledger->debit_balance + $used_session; 
                                            $new_closing_balance = $new_opening_balance - $used_session; 


                                            $ledger = new PatientTreatmentLedger();
                                            $ledger->patient_id = $session->patient_id;
                                            $ledger->treatment_id = $session->treatment_id;
                                            $ledger->therapist_id = $session->therapist_id;
                                            $ledger->iOrderDetailId = $ledger->iOrderDetailId;
                                            $ledger->iSessionTakenId = $session->iSessionTakenId;
                                            $ledger->opening_balance = $new_opening_balance;
                                            $ledger->credit_balance = $new_credit_balance;
                                            $ledger->debit_balance = $new_debit_balance;
                                            $ledger->closing_balance = $new_closing_balance;
                                            $ledger->iSessionTakenId = $session->iSessionTakenId;

                                            // Save the ledger entry
                                            $ledger->save();
                                        }

                                        $suggested=PatientSuggestedTreatment::where(['patient_id'=> $session->patient_id,'treatment_id'=>$session->treatment_id,'iOrderId'=>$session->orderId])->first();

                                       if($suggested)
                                       {
                                         if ($suggested->iUsedSession == 0) 
                                            {
                                               $used = $suggested->iUsedSession = 1;
                                            } else {
                                                $used=$suggested->iUsedSession += 1; // Increment by 1 if already set
                                            }
                                            
                                            $suggested->iUsedSession=$used; 
                                            $suggested->iAvailableSession =$suggested->iAvailableSession - 1 ;
                                            $suggested->save();
                                            
                                             if($suggested->iAvailableSession == 0)
                                               {
                                                        $suggested->isActive=0; 
                                                        $suggested->save();
                                               }
                                       }
                                            $inpatient=new PatientIn();
                                            $inpatient->treatment_id = $session->treatment_id;
                                            $inpatient->therapist_id = $session->therapist_id;
                                            $inpatient->patient_id = $session->patient_id;
                                            $inpatient->inDateTime=date('Y-m-d H:i:s');
                                            $inpatient->leave=1;
                                            $inpatient->status=1;
                                            $inpatient->patient_schedule_id=$session->patient_schedule_id;
                                            $inpatient->save();

                                    }
                                    if($request->status == 0)
                                    {                        
                                        $inpatient=new PatientIn();
                                        $inpatient->treatment_id = $session->treatment_id;
                                        $inpatient->therapist_id = $session->therapist_id;
                                        $inpatient->patient_id = $session->patient_id;
                                        $inpatient->inDateTime=date('Y-m-d H:i:s');
                                        $inpatient->leave=0;
                                        $inpatient->status=1;
                                        $inpatient->patient_schedule_id=$session->patient_schedule_id;
                                        $inpatient->save();
                                    }


                                        return response()->json([
                                            'status' => 'success',
                                            'message' => 'Patient Session Cancled Successfully',
                                        ]);
                                }else{
                                        return response()->json([
                                            'status' => 'success',
                                            'message' => 'Patient Session Already Cancled',
                                        ]);

                                }
                        }
                        else
                        {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Patient Schedule Not Found',
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

    public function add_consumed_session(Request $request)
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
                $suggested=PatientSuggestedTreatment::where('patient_id', $request->patient_id)->where('treatment_id', $request->treatment_id)->where('iOrderDetailId', $request->iOrderDetailId)->first();

                       if ($suggested) 
                       {
                        $newUsedSession = max(0, $suggested->iUsedSession + $request->used_session);
                        $newAvailableSession = $suggested->iSessionBuy - $newUsedSession;
                        
                        if ($newAvailableSession >= 0 && $request->used_session != 0) 
                        {
                            
                            $suggested->iUsedSession = $newUsedSession;
                            $suggested->iAvailableSession = $newAvailableSession;
                            $suggested->manually_consumed = $request->used_session;
                            $suggested->save();
                        } 
                        else
                        {
                            $suggested->iUsedSession = $request->used_session;
                            $suggested->iAvailableSession = $suggested->iSessionBuy - $request->used_session;
                            $suggested->manually_consumed = $request->used_session;
                            $suggested->save();                           
                        }
                    } else 
                    {
                        $suggested = new PatientSuggestedTreatment();
                        $suggested->patient_id = $request->patient_id;
                        $suggested->treatment_id = $request->treatment_id;
                    
                        $suggested->iUsedSession = max(0, $request->used_session);
                        $suggested->iAvailableSession = max(0, $request->iSessionBuy - $suggested->iUsedSession);
                        $suggested->iSessionBuy = $request->iSessionBuy; // Add this if session buy is in the request
                        $suggested->manually_consumed = $request->used_session;
                        $suggested->save();
                    }
                    
                    if($suggested->iAvailableSession == 0)
                       {
                                $suggested->isActive=0; 
                                $suggested->save();
                       }

    
                    $session = PatientSchedule::where(['patient_schedule_id'=>$request->patient_schedule_id,'status'=>0])->first();

                    $ledger = PatientTreatmentLedger::where(['patient_id'=>$request->patient_id,'treatment_id'=>$request->treatment_id,'iOrderDetailId'=>$request->iOrderDetailId])->first();
                    
                    if ($ledger == null) 
                    {
                        $ledger = new PatientTreatmentLedger();
                        $ledger->patient_id = $request->patient_id;
                        $ledger->treatment_id = $request->treatment_id;
                        $ledger->therapist_id = $request->therapist_id;
                        $ledger->iOrderDetailId = $request->iOrderDetailId;
                        $ledger->iSessionTakenId = $request->patient_schedule_id;
                        $ledger->opening_balance = $suggested->iSessionBuy; // Total sessions bought
                    
                        $usedSession = max(-$ledger->opening_balance, $request->used_session);
                    
                        if ($usedSession == 0) {
                            $ledger->credit_balance = 0.0; // No sessions used
                            $ledger->debit_balance = 0.0; // No sessions added back
                            $ledger->closing_balance = $ledger->opening_balance; // No change
                        } elseif ($usedSession > 0) {
                            $ledger->credit_balance = $usedSession; // Sessions used
                            $ledger->debit_balance = 0.0; // No sessions added back
                        } else {
                            $ledger->credit_balance = 0.0; // No sessions used
                            $ledger->debit_balance = abs($usedSession); // Add back sessions
                        }
                    
                        // Calculate closing balance
                        $ledger->closing_balance = $ledger->opening_balance - $ledger->credit_balance + $ledger->debit_balance;
                    
                        // Ensure closing balance is not negative
                        if ($ledger->closing_balance < 0) {
                            return response()->json(['error' => 'Invalid session adjustment.'], 400);
                        }
                    
                        $ledger->save();
                    } else 
                    {
                        // Update ledger when a previous entry exists
                        $newLedger = new PatientTreatmentLedger();
                        $newLedger->patient_id = $session->patient_id;
                        $newLedger->treatment_id = $session->treatment_id;
                        $newLedger->therapist_id = $session->therapist_id;
                        $newLedger->iOrderDetailId = $ledger->iOrderDetailId;
                        $newLedger->iSessionTakenId = $session->iSessionTakenId;
                        $newLedger->opening_balance = $ledger->closing_balance ?? $suggested->iSessionBuy;
                        $usedSession = max(-$newLedger->opening_balance, $request->used_session);
                    
                        if ($usedSession == 0) {
                            $newLedger->credit_balance = 0.0; // No sessions used
                            $newLedger->debit_balance = 0.0; // No sessions added back
                        } elseif ($usedSession > 0) {
                            $newLedger->credit_balance = $usedSession; // Sessions used
                            $newLedger->debit_balance = 0.0; // No sessions added back
                        } else {
                            $newLedger->credit_balance = 0.0; // No sessions used
                            $newLedger->debit_balance = abs($usedSession); // Add back sessions
                        }
                    
                        // Calculate closing balance
                        $newLedger->closing_balance = $newLedger->opening_balance - $newLedger->credit_balance + $newLedger->debit_balance;
                    
                        // Ensure closing balance is not negative
                        if ($newLedger->closing_balance < 0) {
                            return response()->json(['error' => 'Invalid session adjustment.'], 400);
                        }
                    
                        $newLedger->save();
                    }
       
                   return response()->json([
                        'status' => 'success',
                        'message' => 'Patient Session Consumed Successfully',
                    ]);
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

}
?>