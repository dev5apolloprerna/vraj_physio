<?php
namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Session;

use App\Models\PatientSchedule;
use App\Models\SessionMaster;
use App\Models\Patient;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use Barryvdh\DomPDF\Facade\Pdf;


class DashboardController extends Controller
{

	public function index(Request $request)
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
                $dayNumber = date('N'); // 1 (Monday) to 7 (Sunday)
                $date=today();

				$today_total_appointment=PatientSchedule::where(['patient_schedule.day'=>$dayNumber])
                        ->join('patient_master', 'patient_master.patient_id', '=', 'patient_schedule.patient_id')
                        ->orderBy('patient_schedule_id', 'desc')->count();
							
				$todayCancelAppointment = SessionMaster::
				    when($request->fromdate || $request->todate || $request->month || $request->year, function ($query) use ($request) {
                            if ($request->fromdate) {
                                $query->where('sessionmaster.created_at', '>=', date('Y-m-d 00:00:00', strtotime($request->fromdate)));
                            }
                            if ($request->todate) {
                                $query->where('sessionmaster.created_at', '<=', date('Y-m-d 23:59:59', strtotime($request->todate)));
                            }
                            if ($request->month) {
                                $query->whereMonth('sessionmaster.created_at', $request->month);
                            }
                            if ($request->year) {
                                $query->whereYear('sessionmaster.created_at', $request->year);
                            }
                        }, function ($query) {
                            $query->whereDate('sessionmaster.created_at', today());
                        })
				    ->where('session_status', 3)
				    ->orderBy('iSessionTakenId', 'desc')
				    ->count();
			
				$todayNewPatient = Patient::where(['iStatus' => 1, 'isDelete' => 0])
				    ->when($request->fromdate || $request->todate || $request->month || $request->year, function ($query) use ($request) {
                            if ($request->fromdate) {
                                $query->where('patient_master.created_at', '>=', date('Y-m-d 00:00:00', strtotime($request->fromdate)));
                            }
                            if ($request->todate) {
                                $query->where('patient_master.created_at', '<=', date('Y-m-d 23:59:59', strtotime($request->todate)));
                            }
                            if ($request->month) {
                                $query->whereMonth('patient_master.created_at', $request->month);
                            }
                            if ($request->year) {
                                $query->whereYear('patient_master.created_at', $request->year);
                            }
                        }, function ($query) {
                            $query->whereDate('patient_master.created_at', today());
                        })
				    
				    ->orderBy('patient_id', 'desc')
				    ->count();

//				 $todayCollection = OrderPayment::whereDate('PaymentDateTime', today())->orderBy('OrderPaymentId', 'desc')->count();


                $todayCollection = OrderPayment::
                    when($request->fromdate || $request->todate || $request->month || $request->year, function ($query) use ($request) 
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
                        })->sum('amount');

						$data = [
                            "today_total_appointment" => $today_total_appointment,
                            "today_cancel_appointment" => $todayCancelAppointment,
                            "today_new_patient" => $todayNewPatient,
                            "today_collection" => $todayCollection
                        ];

				return response()->json([
                    'status' => 'success',
                    'message' => 'Dashboard Count',
                    'Dashboard' => $data
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