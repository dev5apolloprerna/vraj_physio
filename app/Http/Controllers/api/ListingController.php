<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

use App\Models\Billing;
use App\Models\CaseNo;
use App\Models\Role;
use App\Models\ConsentMaster;
use App\Models\Designation;
use App\Models\MyPackage;
use App\Models\Notes;
use App\Models\Plan;
use App\Models\Patient;
use App\Models\PatientTreatmentLedger;
use App\Models\PatientDocument;
use App\Models\Treatment;
use App\Models\TreatmentTherapist;
use App\Models\RefrenceBy;
use App\Models\Schedule;
use App\Models\ScheduleTreatment;
use App\Models\PatientIn;
use App\Models\PatientSchedule;
use App\Models\SessionMaster;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderPayment;
use App\Models\SettingBillId;
use App\Models\PatientSuggestedTreatment;

use Maatwebsite\Excel\Facades\Excel;

use App\Exports\CancelAppointment;
use App\Exports\PatientExport;

use Carbon\Carbon;


class ListingController extends Controller
{
    
     public function plan_list(Request $request)
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

                            $Plan=Plan::select('plan_master.*',DB::raw("(select treatment_name from treatment_master where plan_master.treatment_id=treatment_master.treatment_id limit 1) as treatment_name"))->where(['iStatus'=>1,'isDelete'=>0,'clinic_id'=>$request->clinic_id])->orderBy('plan_name', 'asc')->get();

                            if(sizeof($Plan) != 0)
                            {
                                foreach($Plan as $val)
                                {
                                    $PlanList[] = array(
                                        "clinic_id" => $val->clinic_id,
                                        "plan_id" => $val->plan_id,
                                        "plan_name" => $val->plan_name,
                                        "no_of_session" => $val->no_of_session,
                                        "treatment_id" => $val->treatment_id,
                                        "treatment_name" => $val->treatment_name,
                                        "base_amount" => $val->base_amount,
                                        "discount_amount" => $val->discount_amount,
                                        "amount" => $val->amount,
                                        "remaining_session_notification" => $val->NotificatoToPatientOnRemainignSession,
                                    );
                                }
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'Plan List',
                                        'Plan' => $PlanList
                                    ]);

                            } else 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'Plan' => []
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
    public function patient_list(Request $request)
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

                    $Patient = Patient::where(['iStatus' => 1, 'isDelete' => 0, 'clinic_id' => $request->clinic_id])
                                
                                ->when($request->search, function ($query, $Search) {
                                    return $query->where(function ($query) use ($Search) {
                                        $query->where('patient_first_name', 'LIKE', "%{$Search}%")
                                              ->orWhere('patient_last_name', 'LIKE', "%{$Search}%")
                                              ->orWhere('phone', 'LIKE', "%{$Search}%");
                                    });
                                })
                                ->orderBy('patient_id', 'desc')
                                ->get();


                        if(sizeof($Patient) != 0)
                        {
                            foreach($Patient as $val)
                            {
                                $PatientList[] = array(
                                    "patient_id" => $val->patient_id,
                                    // "patient_user_id" => $val->patient_user_id,
                                    "patient_case_no" => $val->patient_case_no,
                                    "patient_name" => $val->patient_first_name.' '.$val->patient_last_name,
                                    "patient_age" => $val->patient_age,
                                    "dob" => $val->dob ? date('d-m-Y', strtotime($val->dob)) : '-',
                                    "phone" => $val->phone,
                                    "other_mobile" => $val->other_mobile_no,
                                );

                                $PatientList2[] = array(
                                    "patient_name" => $val->patient_first_name.' '.$val->patient_last_name,
                                    "patient_case_no" => $val->patient_case_no,
                                    "phone" => $val->phone,
                                    "other_mobile" => $val->other_mobile_no,
                                    "Email" => $val->email,
                                    "Patient Age" => $val->patient_age,
                                    "dob" => $val->dob ? date('d-m-Y', strtotime($val->dob)) : '-',
                                );
                            }
                    
                    if($request->status == 1)
                    {
                        
                    
                      $export = new PatientExport($PatientList2, $request->search);

                        // Define the target directory and ensure it exists
                        $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                        if (!file_exists($basePath)) {
                            mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                        }
                        // Define the file path
                        $fileName = 'patient_list_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
                        $filePath = $basePath . '/' . $fileName;
                        
                        // Store the Excel file
                        Excel::store($export, 'export/' . $fileName, 'public'); // Adjust path relative to 'public' disk
                        // dd("Hi");
                        
                        // Generate the public file URL
                        $fileUrl = asset('reports/export/' . $fileName);
                        
                        return response()->json([
                            'status' => 'success',
                            'file_url' => $fileUrl
                            ]);
                            
                    }
                        return response()->json([
                                    'status' => 'success',
                                    'message' => 'Patient List',
                                    'Patient' => $PatientList
                                ]);
                                
                    
                        

                        } 
                        else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Patient' => []
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
    public function patient_detail(Request $request)
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

                    $PatientDetail = [];
                    $Patient = Patient::select('patient_master.*',DB::raw("(select refrence_name from refrence_by where refrence_by.refrence_id=patient_master.ref_by limit 1) as refrenceby_name"))
                        ->where([
                            'patient_master.iStatus' => 1,
                            'patient_master.isDelete' => 0,
                            'clinic_id' => $request->clinic_id,
                            'patient_master.patient_id' => $request->patient_id
                        ])
                        ->orderBy('patient_master.patient_id', 'desc')
                        ->first(); // Change to first() to get a single record

                    if ($Patient) 
                    {
                        $totalAmount = Order::where(['patient_id' => $Patient->patient_id])->sum('iAmount');
                        $totalDiscountAmount = Order::where(['patient_id' => $Patient->patient_id])->sum('iDiscount');
                        $totalDueAmount = Order::where(['patient_id' => $Patient->patient_id])->sum('DueAmount');
                        // Fetch counts for therapy, documents, and billing
                        $therapyCount = PatientTreatmentLedger::where(['patient_id' => $Patient->patient_id])->count();
                        $documentCount = PatientDocument::where(['patient_id' => $Patient->patient_id])->count();
                        $billingCount = Billing::where(['billingmaster.patient_id' => $Patient->patient_id])
                            ->join('patientordermaster', 'billingmaster.IBillId', '=', 'patientordermaster.IBillId')
                            ->count();
                        $appointmentCount=PatientSchedule::where(['patient_id'=>$Patient->patient_id])->count();

                        // Prepare patient detail data
                       $PatientDetail = [
                            "patient_id" => $Patient->patient_id,
                            "patient_case_no" => $Patient->patient_case_no,
                            "patient_name" => $Patient->patient_first_name . ' ' . $Patient->patient_last_name,
                            "first_name" => $Patient->patient_first_name,
                            "last_name" => $Patient->patient_last_name,
                            "patient_age" => $Patient->patient_age,
                            "address" => $Patient->address ?? '-',
                            "gender" => $Patient->gender,
                            "email" => $Patient->email ?? '-',
                            "other_mobile" => $Patient->other_mobile_no,
                            "dob" => date('d-m-Y',strtotime($Patient->dob)),
                            "total_amount" => $totalAmount ?? 0,
                            "discount_amount" => $totalDiscountAmount ?? 0,
                            "due_amount" => $totalDueAmount ?? 0,
                            "phone" => $Patient->phone,
                            "refrenceby_id" => $Patient->ref_by,
                            "refrenceby_name" => $Patient->refrenceby_name
                        ];


                        // Construct the data array with unique keys
                        $data = [
                            'therapy_count' => $therapyCount,
                            'document_count' => $documentCount,
                            'billing_count' => $billingCount,
                            'appointment_count' => $appointmentCount
                        ];

                        // Return the final JSON response
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Patient detail',
                            'Patient_details' => $PatientDetail,
                            'data' => $data
                        ]);
                    
                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Patient' => []
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
    public function patient_documents(Request $request)
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

                    $root = $_SERVER['DOCUMENT_ROOT'];
                                
                    $target_path = 'http://vrajphysioapp.vrajdentalclinic.com/'.$request->patient_id.'/'.'PatientDocument/';

                    $PatientDoc=PatientDocument::where(['patient_documents.patient_id'=>$request->patient_id])
                                ->join('patient_master', 'patient_master.patient_id', '=', 'patient_documents.patient_id')
                                ->orderBy('patient_documents.patient_id', 'asc')->get();

                            if(sizeof($PatientDoc) != 0)
                            {
                                foreach($PatientDoc as $val)
                                {
                                    $document[] = array(
                                        "document_id" => $val->document_id,
                                        "patient_id" => $val->patient_id,
                                        "patient_document" => $target_path . $val->document,
                                    );
                                }
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'Patient Document List',
                                        'Document' => $document
                                    ]);

                            } else 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'Document' => []
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
    public function employee_list(Request $request)
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

                        $Employee=User::select('users.*',DB::raw("(select name from roles where roles.id=users.role_id limit 1) as designation"))
                            ->where(['clinic_id'=>$request->clinic_id,'status'=>1])
                            ->when($request->role_id, fn ($query, $roleid) => $query->where('role_id',$roleid))
                            ->orderBy('id', 'desc')->get();

                    if(sizeof($Employee) != 0)
                    {
                        foreach($Employee as $val)
                        {
                            $EmployeeList[] = array(
                                "employee_id" => $val->id,
                                "employee_name" => $val->name,
                                "email" => $val->email ?? '-',
                                "mobile" => $val->mobile_number,
                                "address" => $val->address,
                                "desination_id" => $val->role_id,
                                "desination_name" => $val->designation,
                                "dob" => $val->dob ? date('d-m-Y', strtotime($val->dob)) : '-',
                                "role_id" => $val->role_id,
                                "status" => $val->status,
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Employee List',
                                'Employee' => $EmployeeList
                            ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Employee' => []
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
     public function inactive_employee_list(Request $request)
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

                    $Employee=User::select('users.*',DB::raw("(select designation_name from designation where designation.designation_id=users.designation_id limit 1) as designation "))
                            ->where(['clinic_id'=>$request->clinic_id,'status'=>0])
                            ->when($request->role_id, fn ($query, $roleid) => $query->where('role_id',$roleid))
                            ->orderBy('id', 'desc')->get();

                    if(sizeof($Employee) != 0)
                    {
                        foreach($Employee as $val)
                        {
                            $EmployeeList[] = array(
                                "employee_id" => $val->id,
                                "employee_name" => $val->name,
                                "email" => $val->email ?? '-',
                                "mobile" => $val->mobile_number,
                                "address" => $val->address,
                                "desination_id" => $val->designation_id,
                                "desination_name" => $val->designation,
                                "dob" => $val->dob ? date('d-m-Y', strtotime($val->dob)) : '-',
                                "role_id" => $val->role_id,
                                "status" => $val->status,
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Employee List',
                                'Employee' => $EmployeeList
                            ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Doctor' => []
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
    public function designation_list(Request $request)
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

                    $designation=Role::select('roles.*')->orderBy('roles.id', 'desc')->get();

                    if(sizeof($designation) != 0)
                    {
                        foreach($designation as $val)
                        {
                            $designationList[] = array(
                                "desination_id" => $val->id,
                                "desination_name" => $val->name
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Designation List',
                                'Designation' => $designationList
                            ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Designation' => []
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
public function refrenceBy_list(Request $request)
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

                    $refrenceBy=RefrenceBy::orderBy('refrence_name', 'desc')->get();

                    if(sizeof($refrenceBy) != 0)
                    {
                        foreach($refrenceBy as $val)
                        {
                            $RefrenceByList[] = array(
                                "refrence_id" => $val->refrence_id,
                                "refrence_name" => $val->refrence_name
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'RefrenceBy List',
                                'RefrenceBy' => $RefrenceByList
                            ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'RefrenceBy' => []
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
    public function tretment_list(Request $request)
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

                            $treatmentList = [];

                            $Treatment = Treatment::select('treatment_master.*', 'treatment_therapist.therapist_id')
                                ->where(['iStatus' => 1, 'isDelete' => 0, 'clinic_id' => $request->clinic_id])
                                ->orderBy('treatment_name', 'asc')
                                ->leftjoin('treatment_therapist', 'treatment_master.treatment_id', '=', 'treatment_therapist.treatment_id')
                                ->get();
                            
                            if ($Treatment->isNotEmpty()) 
                            {
                                $treatmentMap = [];
                            
                                foreach ($Treatment as $val) 
                                {
                                    // If this treatment is not already in the map, initialize it
                                    if (!isset($treatmentMap[$val->treatment_id])) 
                                    {
                                        $treatmentMap[$val->treatment_id] = [
                                            "treatment_id" => $val->treatment_id,
                                            "clinic_id" => $val->clinic_id,
                                            "treatment_name" => $val->treatment_name,
                                            "amount" => $val->amount,
                                            "therapist" => []  // Set therapist to null by default
                                        ];
                                    }
                            
                                    // If a therapist ID exists, fetch therapist details
                                    if ($val->therapist_id) {
                                        $therapist = User::select('id', 'name')->where('id', $val->therapist_id)->first();
                            
                                        // Initialize the therapist array if not already done
                                        if (is_null($treatmentMap[$val->treatment_id]['therapist'])) {
                                            $treatmentMap[$val->treatment_id]['therapist'] = [];
                                        }
                            
                                        // Add therapist to the treatment's therapist list
                                        $treatmentMap[$val->treatment_id]['therapist'][] = [
                                            "therapist_id" => $therapist->id ?? 0,
                                            "therapist_name" => $therapist->name ?? "",
                                        ];
                                    }
                                }
                            
                                // Convert the treatment map to an indexed array for response
                                $treatmentList = array_values($treatmentMap);
                            
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Treatment List',
                                    'Treatment' => $treatmentList
                                ]);
                            }

                             else 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'Treatment' => []
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
     public function treatment_package_list(Request $request)
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

                    $treatmentList = [];

                    $Treatment = Treatment::where(['treatment_master.iStatus' => 1,'treatment_master.isDelete' => 0,'treatment_master.clinic_id' => $request->clinic_id])->orderBy('treatment_name', 'asc')->get();

                    if ($Treatment->isNotEmpty()) 
                    {
                        foreach ($Treatment as $val) 
                        {
                            $package = Plan::where(['iStatus' => 1,'isDelete' => 0,'treatment_id' => $val->treatment_id])->get();

                            $packagearray = [];
                            
                            foreach ($package as $value) 
                            {
                                $packagearray[] = [
                                    "plan_id" => $value->plan_id,
                                    "plan_name" => $value->plan_name,
                                    "no_of_session" => $value->no_of_session,
                                    "amount" => $value->amount,
                                ];
                            }

                            $treatmentList[] = [
                                "treatment_id" => $val->treatment_id,
                                "clinic_id" => $val->clinic_id,
                                "treatment_name" => $val->treatment_name,
                                "package_list" => $packagearray
                            ];
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Treatment Package List',
                            'Treatment Package' => $treatmentList
                        ]);
                    }
                        else 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'Treatment Package' => []
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
     public function my_package(Request $request)
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

                    $treatmentList = [];
                    $total = 0; // Initialize total outside of the loop

                    $tempCart = MyPackage::select('temp_patient_package.*',DB::raw("(select treatment_name from treatment_master where temp_patient_package.treatment_id=treatment_master.treatment_id limit 1) as treatment_name"))->where(['clinic_id' => $request->clinic_id,'patient_id'=>$request->patient_id])->orderBy('tempid', 'asc')->get();

                    if ($tempCart->isNotEmpty()) 
                    {

                        foreach ($tempCart as $val) 
                        {
                            $package = Plan::where(['iStatus' => 1,'isDelete' => 0,'treatment_id' => $val->treatment_id,'plan_id'=>$val->plan_id])->get();

                            $packagearray = [];
                            foreach ($package as $value) 
                            {
                                $total += $value->amount; 
                                $packagearray[] = [
                                    "plan_id" => $value->plan_id,
                                    "plan_name" => $value->plan_name,
                                    "no_of_session" => $value->no_of_session,
                                    "amount" => $value->amount,
                                ];
                            }

                            $treatmentList[] = [
                                "tempid" => $val->tempid,
                                "treatment_id" => $val->treatment_id,
                                "clinic_id" => $val->clinic_id,
                                "treatment_name" => $val->treatment_name,
                               
                                "package_list" => $packagearray
                            ];
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => 'My Treatment Package',
                            "total_amount"=>$total,
                            'My Package' => $treatmentList
                        ]);
                    }
                        else 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'My Package' => []
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
    public function schedule(Request $request)
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
                        // $dayNumber = date('N'); // 1 (Monday) to 7 (Sunday)

                    $schedule=Schedule::select('schedule.*',DB::raw("(select name from users where users.id=schedule.therapist_id limit 1) as therpist_name"),DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=schedule.treatment_id limit 1) as treatment_name"))->where(['iStatus'=>1,'isDelete'=>0,'clinic_id'=>$request->clinic_id])
                        ->when($request->therapist_id, fn($query, $therapist_id) => $query->where('schedule.therapist_id', '=', $therapist_id))
                        // ->where(['days'=>$dayNumber])
                        ->orderBy('days', 'asc')->get();
                        
                        if(sizeof($schedule) != 0)
                        {
                            foreach($schedule as $val)
                            {
                                   $treatmentIds=ScheduleTreatment::where(['schedule_id'=>$val->scheduleid])->pluck('treatment_id')->toArray();

                                $treatmentNames = ScheduleTreatment::where('schedule_id', $val->scheduleid)
                                ->join('treatment_master', 'treatment_master.treatment_id', '=', 'schedule_treatment.treatment_id')
                                ->pluck('treatment_master.treatment_name') // Get the treatment_name values
                                ->toArray(); // Convert the result to an array

                                $daysOfWeek = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday',
                                ];
                                
                                $scheduleList[] = array(
                                    "schedule_id" => $val->scheduleid,
                                    "days" => $val->days,
                                    "days_name" => $daysOfWeek[$val->days] ?? 'Invalid Day',
                                    "therapist_id" => $val->therapist_id,
                                    "therpist_name" => $val->therpist_name,
                                    "clinic_id" => $val->clinic_id,
                                    "start_time" => $val->start_time,
                                    "end_time" => $val->end_time,
                                    "treatment_id" => implode(',', $treatmentIds),
                                    "treatment_name" => implode(',', $treatmentNames),
                                    "maximum_patient" => $val->maximum_patient,
                                );
                            }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'schedule List',
                                    'Schedule' => $scheduleList
                                ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Schedule' => []
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
   public function caseno_list(Request $request)
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
                    $caseNo=CaseNo::where(['clinic_id'=>$request->clinic_id])->orderBy('case_id', 'desc')->get();

                        if(sizeof($caseNo) != 0)
                        {
                            foreach($caseNo as $val)
                            {
                                $nextCaseNo = DB::table('patient_master')->where('clinic_id', 1)->max('autocase_no') + 1;

                                $caseList[] = array(
                                    "case_id" => $val->case_id,
                                    "clinic_id" => $val->clinic_id,
                                    "case_prefix" => $val->case_prefix,
                                    "case_number" => $val->case_number,
                                    "case_suffix" => $val->case_suffix,
                                );
                            }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Case List',
                                    'Caseno' => $caseList,
                                    "NextPatientCaseno"=>$nextCaseNo
                                ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Caseno' => []
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
    public function my_tratement_list(Request $request)
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

                    $order=OrderDetail::select('patientorderdetail.*','patientordermaster.patient_id','patientordermaster.Date',DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=patientorderdetail.iTreatmentId limit 1) as treatment_name"))->where(['patientordermaster.patient_id'=>$request->patient_id])->join('patientordermaster', 'patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')->get();
                    $orderList = []; // Initialize order list
                    if(sizeof($order) != 0)
                    {
                        foreach($order as $val)
                        {
                            $package=Plan::select('plan_name')->where(['plan_id'=>$val->iPlanId])->first();

                            $schedule=PatientSchedule::select('patient_schedule.*',DB::raw("(select name from users where users.id=patient_schedule.therapist_id limit 1) as therpist_name")
                            ,DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=patient_schedule.treatment_id limit 1) as treatment_name"))
                            ->where(['patient_schedule.patient_id'=>$request->patient_id,'treatment_id'=>$val->iTreatmentId,"orderId" => $val->iOrderId])
                                // ->join('patient_master', 'patient_master.patient_id', '=', 'patient_schedule.patient_id')
                            ->get();
                           $scheduleList = []; // Reset schedule list for each order
                            $status=0;
                       
                            foreach ($schedule as $key => $value) 
                            {
                                $daysOfWeek = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday',
                                ];
                                if($val->cancel_package == 1)
                                {
                                    $status=1;
                                }else{
                                    $status=0;
                                }
                                
                                    $scheduleList[] = [
                                        "patient_schedule_id" => $value->patient_schedule_id,
                                        "days" => $value->day,
                                        "days_name" => $daysOfWeek[$value->day] ?? 'Invalid Day',
                                        "therapist_id" => $value->therapist_id,
                                        "therpist_name" => $value->therpist_name,
                                        "start_time" => $value->schedule_start_time,
                                        "end_time" => $value->schedule_end_time,
                                        "treatment_id" => $value->treatment_id,
                                        "treatment_name" => $value->treatment_name,
                                    ];

                            }
                            $orderList[] = array(
                                "order_id" => $val->iOrderId,
                                "order_detail_id" => $val->iOrderDetailId,
                                "treatment_id" => $val->iTreatmentId,
                                "treatment_name" => $val->treatment_name,
                                "package_name" => $package->plan_name ?? '-',
                                "amount" => $val->iAmount,
                                "no_of_session" => $val->iSession,
                                "time" => date('h:i A'),
                                 "cancle_package" => $status,
                                "purchase_date" => date('d-M-Y',strtotime($val->Date)),

                                "patient_schedule"=>$scheduleList
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'My Treatment List',
                                'My Treatment' => $orderList
                            ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'My Treatment' => []
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
    public function get_therepist_from_treatement(Request $request)
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

                    /*$therapistList=User::select('users.*','treatment_master.treatment_id','treatment_master.therpist_Id','treatment_master.treatment_name')->where(['treatment_master.treatment_id'=>$request->treatment_id])
                    ->join('treatment_master', 'treatment_master.therpist_Id', '=', 'users.id')->get();*/
                    
                    $therapistList=User::select('users.*','treatment_master.treatment_id','treatment_therapist.therapist_id','treatment_master.treatment_name')->where(['treatment_master.treatment_id'=>$request->treatment_id])
                    ->join('treatment_therapist', 'users.id', '=', 'treatment_therapist.therapist_id')
                    ->join('treatment_master', 'treatment_master.treatment_id', '=', 'treatment_therapist.treatment_id')->get();
                    
                    
                    if(sizeof($therapistList) != 0)
                    {
                        foreach($therapistList as $val)
                        {
                            $tList[] = array(
                                "treatment_id" => $val->treatment_id,
                                "treatment_name" => $val->treatment_name,
                                "therapist_id" => $val->id,
                                "therpist_name" => $val->name,
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Therapist List',
                                'Therapist' => $tList
                            ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Therapist' => []
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
     public function notes_list(Request $request)
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

                            $Notes=Notes::where(['patient_id'=>$request->patient_id,'clinic_id'=>$request->clinic_id])->orderBy('note_id', 'asc')->get();

                            if(sizeof($Notes) != 0)
                            {
                                foreach($Notes as $val)
                                {
                                    $NotesList[] = array(
                                        "note_id" => $val->note_id,
                                        "title" => $val->title,
                                        "description" => $val->description,
                                    );
                                }
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'Notes List',
                                        'Notes' => $NotesList
                                    ]);

                            } else 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'Notes' => []
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
      public function consent_list(Request $request)
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

                            $consent=ConsentMaster::where(['clinic_id'=>$request->clinic_id])->orderBy('id', 'asc')->get();

                            if(sizeof($consent) != 0)
                            {
                                foreach($consent as $val)
                                {
                                    $consentList[] = array(
                                        "consent_id" => $val->id,
                                        "clinic_id" => $val->clinic_id,
                                        "title" => $val->title,
                                        "description" => $val->description,
                                    );
                                }
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'Consent List',
                                        'Consent' => $consentList
                                    ]);

                            } else 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'Consent' => []
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
     public function therapist_schedule(Request $request)
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
                    $schedule=Schedule::select('schedule.*','schedule_treatment.treatment_id',DB::raw("(select name from users where users.id=schedule.therapist_id limit 1) as therpist_name"),DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=schedule_treatment.treatment_id limit 1) as treatment_name"))
                    ->where(['iStatus'=>1,'isDelete'=>0,'clinic_id'=>$request->clinic_id,'schedule.therapist_id'=>$request->therapist_id,'schedule_treatment.treatment_id'=>$request->treatment_id])
                    ->join('schedule_treatment', 'schedule_treatment.schedule_id', '=', 'schedule.scheduleid')
                    ->orderBy('scheduleid', 'desc')->get();

                        if(sizeof($schedule) != 0)
                        {
                            foreach($schedule as $val)
                            {
                                $allocatedPatient=PatientSchedule::where(['treatment_id'=>$request->treatment_id,'therapist_id'=>$request->therapist_id,'scheduleid'=>$val->scheduleid])->count();
                                
                                $available_patient=$val->maximum_patient - $allocatedPatient;
                                
                                $daysOfWeek = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday',
                                ];
                                
                                $scheduleList[] = array(
                                    "schedule_id" => $val->scheduleid,
                                    "days" => $val->days,
                                    "days_name" => $daysOfWeek[$val->days] ?? 'Invalid Day',
                                    "therapist_id" => $val->therapist_id,
                                    "therpist_name" => $val->therpist_name,
                                    "clinic_id" => $val->clinic_id,
                                    "start_time" => $val->start_time,
                                    "end_time" => $val->end_time,
                                    "treatment_id" => $val->treatment_id,
                                    "treatment_name" => $val->treatment_name,
                                    "maximum_patient" => $val->maximum_patient,
                                    "allocated_patient" => $allocatedPatient,
                                    "available_patient" => $available_patient,
 
                                );
                            }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Therapist schedule List',
                                    'Therapist Schedule' => $scheduleList
                                ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Therapist Schedule' => []
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
    public function bill_list(Request $request)
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

                    $bill=SettingBillId::orderBy('id', 'desc')->get();

                    if(sizeof($bill) != 0)
                    {
                        foreach($bill as $val)
                        {
                            $billList[] = array(
                                "id" => $val->id,
                                "bill_prefix" => $val->bill_prefix,
                                "billId" => $val->billId
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Billid List',
                                'Billid' => $billList
                            ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Billid' => []
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
  public function patient_daily_activity(Request $request)
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

                $schedule = PatientSchedule::select(
                    'patient_schedule.*',
                    'patient_master.clinic_id',
                    'patientorderdetail.iAmount as total',
                    'patientorderdetail.iDueAmount as due',
                    'patientorderdetail.iPlanId',
                    'patientorderdetail.iSession',
                    'patientorderdetail.iOrderDetailId',
                    DB::raw("(SELECT name FROM users WHERE users.id = patient_schedule.therapist_id LIMIT 1) AS therapist_name"),
                    DB::raw("(SELECT treatment_name FROM treatment_master WHERE treatment_master.treatment_id = patient_schedule.treatment_id LIMIT 1) AS treatment_name")
                )
                ->join('patient_master', 'patient_master.patient_id', '=', 'patient_schedule.patient_id')
                ->join('patientordermaster', 'patientordermaster.iOrderId', '=', 'patient_schedule.orderId')
                ->join('patientorderdetail', function ($join) {
                    $join->on('patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')
                         ->on('patientorderdetail.iTreatmentId', '=', 'patient_schedule.treatment_id');
                })
                ->where([
                    'patient_schedule.patient_id' => $request->patient_id,
                    'patient_master.clinic_id' => $request->clinic_id,
                    // 'patient_schedule.day' => $dayNumber,
                    'cancel_package' => 0
                ])
                ->when($request->therapist_id, function ($query, $therapist_id) {
                    return $query->where('patient_schedule.therapist_id', '=', $therapist_id);
                })
                // ->groupBy('patientorderdetail.iOrderDetailId')
                ->orderBy('patient_schedule.patient_schedule_id', 'desc')
                ->get();
                
                if ($schedule->isEmpty()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No Data Found!',
                        'Patient Schedule' => []
                    ]);
                }
                
                $scheduleList = [];
                
                foreach ($schedule as $val) {
                    $therapistList = User::select('users.*', 'treatment_master.treatment_id', 'treatment_therapist.therapist_id', 'treatment_master.treatment_name')
                        ->join('treatment_therapist', 'users.id', '=', 'treatment_therapist.therapist_id')
                        ->join('treatment_master', 'treatment_master.treatment_id', '=', 'treatment_therapist.treatment_id')
                        ->where('treatment_master.treatment_id', $val->treatment_id)
                        ->get();
                
                    $session = PatientSuggestedTreatment::where([
                        'patient_id' => $val->patient_id,
                        'treatment_id' => $val->treatment_id,
                        'iOrderDetailId' => $val->iOrderDetailId
                    ])->first();
                    
                    //$session=PatientSuggestedTreatment::where(['patient_id'=>$val->patient_id,'iOrderId'=>$val->iOrderId,'iOrderDetailId'=>$val->iOrderDetailId,'treatment_id' => $val->treatment_id])->first();  

                
                    $paid_session = Plan::select('plan_master.per_session_amount')
                        ->where('plan_id', $val->iPlanId)
                        ->first();
                
                    $consumeAmount = optional($session)->iUsedSession ? ($paid_session->per_session_amount * $session->iUsedSession) : 0;
                
                    $totalPaid = max(($val->total ?? 0) - ($val->due ?? 0), 0);
                    //$totalPaid = max(($val->total ?? 0) - ($val->due ?? 0), $val->total);

                    $totalpaidSession = $paid_session->per_session_amount ? ($totalPaid / $paid_session->per_session_amount) : 0;
                    $availableAmount = $totalPaid - $consumeAmount;
                    $due_session = optional($session)->iSessionBuy - $totalpaidSession;
                    $availableSession = $totalpaidSession - optional($session)->iUsedSession;
                    $balanceAmount = max($totalPaid - $consumeAmount, 0);
                
                    $tlist = [];
                    foreach ($therapistList as $val1) {
                        $tlist[] = [
                            "therapist_id" => $val1->id,
                            "therapist_name" => $val1->name,
                        ];
                    }
                
                    $daysOfWeek = [
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        7 => 'Sunday',
                    ];
                
                if((optional($session)->iSessionBuy > optional($session)->iUsedSession))
                {
  
                    $scheduleList[] = [
                        "iOrderDetailId" => $val->iOrderDetailId,
                        "patient_schedule_id" => $val->patient_schedule_id,
                        "days" => $val->day,
                        "days_name" => $daysOfWeek[$val->day] ?? 'Invalid Day',
                        "therapist_id" => $val->therapist_id,
                        "therapist_name" => $val->therapist_name,
                        "clinic_id" => $val->clinic_id,
                        "start_time" => $val->schedule_start_time,
                        "end_time" => $val->schedule_end_time,
                        "treatment_id" => $val->treatment_id,
                        "treatment_name" => $val->treatment_name,
                        "total_amount" => optional($val)->total ?? 0,
                        "paid_amount" => $totalPaid,
                        "consume_amount" => $consumeAmount,
                        "available_amount" => $availableAmount,
                        "remain_balance" => $balanceAmount,
                        "due_amount" => optional($val)->due ?? 0,
                        "total_session" => optional($session)->iSessionBuy ?? 0,
                        "paid_session" => number_format($totalpaidSession, 1),
                        "due_session" => number_format($due_session, 1),
                        "consumed_session" => optional($session)->iUsedSession ?? 0,
                        "available_session" => number_format($availableSession, 1),
                        "therapist_list" => $tlist
                    ];
                    
                    }
                }
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Patient schedule List',
                    'Patient Schedule' => $scheduleList
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
     public function inPatient_list(Request $request)
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
                    // \DB::enableQueryLog(); // Enable query log

                    $InPatient=PatientIn::select('patientin.*',DB::raw("(select treatment_name from treatment_master where patientin.treatment_id=treatment_master.treatment_id limit 1) as treatment_name"),DB::raw("(SELECT CONCAT(patient_master.patient_first_name, ' ', patient_master.patient_last_name) 
                  FROM patient_master WHERE patient_master.patient_id = patientin.patient_id LIMIT 1) AS patient_name"))
                        ->when($request->therapist_id, fn($query, $therapist_id) => $query->where('patientin.therapist_id', '=', $therapist_id))
                         ->whereRaw("DATE(patientin.inDateTime) = ?", [$date])
                         ->where(function ($query) {
                                    $query->where('status', 0)
                                          ->orWhere('leave', 0);
                                })
                        ->orderBy('iPatientInId', 'asc')->get();
// dd(\DB::getQueryLog()); // Show results of log


                            $inPatientList=[];
                            if(sizeof($InPatient) != 0)
                            {
                                foreach($InPatient as $val)
                                {
                                    
                                        $intime=date('Y-m-d',strtotime($val->inDateTime));
                                        $SessionMasterdata=SessionMaster::where(['patient_id'=>$val->patient_id,'treatment_id'=>$val->treatment_id,'created_at'=>$intime,'iPatientInId'=>$val->iPatientInId])->first();
                                    //$SessionMasterdata=SessionMaster::where(['patient_id'=>$val->patient_id,'therapist_id'=>$request->therapist_id,'treatment_id'=>$val->treatment_id,'created_at'=>$intime])->first();

                                        if(!empty($SessionMasterdata) )
                                        {   
                                            if($SessionMasterdata->session_status != 2 && $SessionMasterdata->session_status != 3)
                                            {
                                                
                                                $inPatientList[] = array(
                                                    "patient_in_id" => $val->iPatientInId,
                                                    "patient_schedule_id" => $val->patient_schedule_id,
                                                    "patient_id" => $val->patient_id,
                                                    "patient_name" => $val->patient_name ?? '-',
                                                    "in_date_time" => date('d-m-Y h:i',strtotime($val->inDateTime)),
                                                    "therapist_id" => $val->therapist_id,
                                                    "treatment_id" => $val->treatment_id,
                                                    "treatment_name" => $val->treatment_name,
                                                    "session_id" => optional($SessionMasterdata)->iSessionTakenId ?? 0,
                                                );
                                            }
                                        }else
                                        {
                                            if($val->status == 0 && $val->leave == 0)
                                            {
                                               $inPatientList[] = array(
                                                    "patient_in_id" => $val->iPatientInId,
                                                    "patient_schedule_id" => $val->patient_schedule_id,
                                                    "patient_id" => $val->patient_id,
                                                    "patient_name" => $val->patient_name ?? '-',
                                                    "in_date_time" => date('d-m-Y h:i',strtotime($val->inDateTime)),
                                                    "therapist_id" => $val->therapist_id,
                                                    "treatment_id" => $val->treatment_id,
                                                    "treatment_name" => $val->treatment_name,
                                                    "session_id" => optional($SessionMasterdata)->iSessionTakenId ?? 0
                                                );     
                                            }
                                           
                                           
                                        }
                                }
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'In Patient List',
                                        'In Patient' => $inPatientList
                                    ]);

                            } else 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'In Patient' => []
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
    public function team_schedule(Request $request)
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

                    $treatment=Treatment::where(['treatment_therapist.therapist_id'=>$User->id])->join('treatment_therapist', 'treatment_therapist.treatment_id', '=', 'treatment_master.treatment_id')->first();


                    $schedule=Schedule::select('schedule.*','schedule_treatment.treatment_id',DB::raw("(select name from users where users.id=schedule.therapist_id limit 1) as therpist_name"),
                        DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=schedule_treatment.treatment_id limit 1) as treatment_name"))
                        ->join('schedule_treatment', 'schedule_treatment.schedule_id', '=', 'schedule.scheduleid')
                        ->where('schedule.therapist_id','!=',$User->id)
                        ->where(['iStatus'=>1,'isDelete'=>0,'clinic_id'=>$request->clinic_id,'schedule.therapist_id'=>$request->therapist_id])
                        ->when($request->day, fn($query, $day) => $query->where('schedule.days', '=', $day))
                        ->orderBy('schedule.days', 'asc')->get();
                        //->where(['iStatus'=>1,'isDelete'=>0,'clinic_id'=>$request->clinic_id,'schedule_treatment.treatment_id'=>$treatment->treatment_id,'schedule.therapist_id'=>$request->therapist_id])

                        if(sizeof($schedule) != 0)
                        {
                            foreach($schedule as $val)
                            {
                                $allocatedPatient=PatientSchedule::where(['treatment_id'=>$request->treatment_id,'therapist_id'=>$request->therapist_id])->count();
                                
                                $available_patient=$val->maximum_patient - $allocatedPatient;
                                $daysOfWeek = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday',
                                ];
                                
                                $scheduleList[] = array(
                                    "schedule_id" => $val->scheduleid,
                                    "days" => $val->days,
                                    "days_name" => $daysOfWeek[$val->days] ?? 'Invalid Day',
                                    "therapist_id" => $val->therapist_id,
                                    "therpist_name" => $val->therpist_name,
                                    "clinic_id" => $val->clinic_id,
                                    "start_time" => $val->start_time,
                                    "end_time" => $val->end_time,
                                    "treatment_id" => $val->treatment_id,
                                    "treatment_name" => $val->treatment_name,
                                );
                            }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Team schedule List',
                                    'Team Schedule' => $scheduleList
                                ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Team Schedule' => []
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
    public function login_therapist_schedule(Request $request)
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
                    $schedule=Schedule::select('schedule.*','schedule_treatment.treatment_id',DB::raw("(select name from users where users.id=schedule.therapist_id limit 1) as therpist_name")
                    ,DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=schedule_treatment.treatment_id limit 1) as treatment_name"))
                    ->join('schedule_treatment', 'schedule_treatment.schedule_id', '=', 'schedule.scheduleid')
                    ->where(['iStatus'=>1,'isDelete'=>0,'clinic_id'=>$request->clinic_id,'schedule.therapist_id'=>$request->therapist_id])->orderBy('scheduleid', 'desc')->get();

                        if(sizeof($schedule) != 0)
                        {
                            foreach($schedule as $val)
                            {
                                $allocatedPatient=PatientSchedule::where(['treatment_id'=>$val->treatment_id,'therapist_id'=>$request->therapist_id])->count();
                                
                                $available_patient=$val->maximum_patient - $allocatedPatient;
                                $daysOfWeek = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday',
                                ];
                                
                                $scheduleList[] = array(
                                    "schedule_id" => $val->scheduleid,
                                    "days" => $val->days,
                                    "days_name" => $daysOfWeek[$val->days] ?? 'Invalid Day',
                                    "therapist_id" => $val->therapist_id,
                                    "therpist_name" => $val->therpist_name,
                                    "clinic_id" => $val->clinic_id,
                                    "start_time" => $val->start_time,
                                    "end_time" => $val->end_time,
                                    "treatment_id" => $val->treatment_id,
                                    "treatment_name" => $val->treatment_name,
                                    "maximum_patient" => $val->maximum_patient,
                                    "allocated_patient" => $allocatedPatient,
                                    "available_patient" => $available_patient,
                                );
                            }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Therapist schedule List',
                                    'Therapist Schedule' => $scheduleList
                                ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Therapist Schedule' => []
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
    public function therapist_patient_list(Request $request)
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
                $dayNumber =  $request->day;

                    $patient=PatientSchedule::select('patient_schedule.*',DB::raw("(SELECT CONCAT(patient_master.patient_first_name, ' ', patient_master.patient_last_name) 
                  FROM patient_master 
                  WHERE patient_master.patient_id = patient_schedule.patient_id LIMIT 1) AS patient_name"),DB::raw("(select name from users where users.id=patient_schedule.therapist_id limit 1) as therpist_name")
                    ,DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=patient_schedule.treatment_id limit 1) as treatment_name")
                    )->where(['patient_schedule.therapist_id'=>$request->therapist_id])
                    ->when($dayNumber, fn($query, $day) => $query->where('patient_schedule.day', '=', $day))
                    ->orderBy('patient_schedule.day', 'asc')
                    ->get();
                if(sizeof($patient) != 0)
                {
                    
                    foreach($patient as $val)
                        {
                            $session=SessionMaster::where(['scheduleid'=>$val->scheduleid])->first();

                            if(!empty($session)){
                                $starttime=date('h:i A',strtotime($session->SessionStartTime));
                                $endtime=date('h:i A',strtotime($session->SessionEndTime));
                            }else{
                                $starttime="-";
                                $endtime="-";
                            }

                            $daysOfWeek = [
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                                7 => 'Sunday',
                            ];
                            
                            $scheduleList[] = array(
                                "patient_id" => $val->patient_id,
                                "patient_name" => $val->patient_name,
                                "days" => $val->day,
                                "days_name" => $daysOfWeek[$val->day] ?? 'Invalid Day',
                                "therapist_id" => $val->therapist_id,
                                "therpist_name" => $val->therpist_name,
                                "treatment_id" => $val->treatment_id,
                                "treatment_name" => $val->treatment_name,
                                "start_time" => date('h:i A',strtotime($val->schedule_start_time)),
                                "end_time" => date('h:i A',strtotime($val->schedule_end_time)),
                            );
                        }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Therapist Patient Schedule',
                                'Therapist Patient' => $scheduleList
                            ]);
                    }else{

                         return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Therapist Patient' => []
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
    public function therapist_treatment_list(Request $request)
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
                    $treatment=TreatmentTherapist::select('treatment_master.*','treatment_therapist.therapist_id',DB::raw("(select name from users where users.id=treatment_therapist.therapist_id limit 1) as therpist_name "))->where(['therapist_id'=>$request->therapist_id])->join('treatment_master','treatment_master.treatment_id','=','treatment_therapist.treatment_id')->get();
                    if(sizeof($treatment))
                    {

                        foreach ($treatment as $key => $val) 
                        {
                           $treatmentList[] = array(
                                    "treatment_id" => $val->treatment_id,
                                    "treatment_name" => $val->treatment_name,
                                    "therapist_id" => $val->therapist_id,
                                    "therpist_name" => $val->therpist_name
                                );
                        }
                         return response()->json([
                                    'status' => 'success',
                                    'message' => 'Therapist Wise Treatment',
                                    'Therapist Wise Treatment' => $treatmentList
                                ]);
                     }else{

                         return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Therapist Wise Treatment' => []
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
     public function patient_consumed_history(Request $request)
    {
        /* try
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

                    $sessionMaster=SessionMaster::select('sessionmaster.*','treatment_master.treatment_name','patient_master.clinic_id',DB::raw("(select name from users where users.id=sessionmaster.therapist_id limit 1) as therpist_name")
                    ,DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=sessionmaster.treatment_id limit 1) as treatment_name"))->where(['sessionmaster.patient_id'=>$request->patient_id,'patient_master.clinic_id'=>$request->clinic_id])
                        ->when($request->therapist_id, fn($query, $therapist_id) => $query->where('sessionmaster.therapist_id', '=', $therapist_id))
                        ->join('patient_master', 'patient_master.patient_id', '=', 'sessionmaster.patient_id')
                        ->join('patient_schedule', 'patient_schedule.patient_schedule_id', '=', 'sessionmaster.scheduleId')
                        ->join('treatment_master', 'treatment_master.treatment_id', '=', 'sessionmaster.treatment_id')
                        ->join('patientorderdetail', function ($join) {
                            $join->on('patientorderdetail.iOrderId', '=', 'patient_schedule.orderId')
                                 ->on('patientorderdetail.iTreatmentId', '=', 'sessionmaster.treatment_id'); // Changed to 'on' for proper JOIN condition
                        })
                         ->when($request->search, function ($query, $search) {
                                return $query->where(function ($q) use ($search) {
                                    $q->where('treatment_name', 'LIKE', "%{$search}%")
                                      ->orWhere('treatment_name', 'LIKE', "%{$search}%");
                                });
                            })

                        ->orderBy('iSessionTakenId', 'desc')->get();

                        
                    $treatmentCounter = []; // Initialize treatment counter

                        if(sizeof($sessionMaster) != 0)
                        {
                            foreach($sessionMaster as $val)
                            {

                            /*$detail = Order::select('patientorderdetail.iAmount as total', 'patientorderdetail.iDueAmount as due','patientorderdetail.iPlanId','patientorderdetail.iSession')
                                        ->where([
                                            'patientordermaster.patient_id' => $val->patient_id,
                                            'iTreatmentId' => $val->treatment_id
                                        ])
                                        ->join('patientorderdetail', 'patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')
                                        ->first();*/
                                        

                        $session=PatientSuggestedTreatment::where(['patient_id'=>$val->patient_id,'treatment_id' => $val->treatment_id])->first();  
                        
                        
                       // $paid_session=Plan::select('plan_master.per_session_amount','plan_master.plan_id')->where(['plan_id'=>$detail->iPlanId])->first();
                         if (!empty($val->iPlanId)) {
                                $paid_session = Plan::select('plan_master.per_session_amount', 'plan_master.plan_id')->where('plan_id', $val->iPlanId)
                    ->first();
                    } else {
                        // Handle the case where $val->iPlanId is null
                        $paid_session = (object) [
                            'per_session_amount' => 0, // Default value
                            'plan_id' => null,        // Default value
                        ];
                    }
        
                    $schedule = PatientSchedule::select('day')->where(['patient_schedule_id' => $val->scheduleid])->first();
                    
                    $consumeAmount = 0;
                    $totalPaid = (optional($val)->total ?? 0) - (optional($val)->due ?? 0);
                    
                    if ($session->iUsedSession == null) {
                        $consumeAmount = 0;
                    } else {
                        $consumeAmount = $paid_session->per_session_amount * $session->iUsedSession;
                    }
                    
                    $totalpaidSession = $paid_session->per_session_amount > 0 
                        ? $totalPaid / $paid_session->per_session_amount 
                        : 0;
                    
                    $availableAmount = $totalPaid - $consumeAmount;
                    $due_session = optional($session)->iSessionBuy - $totalpaidSession;
                    
                    $availableSession = $totalpaidSession - optional($session)->iUsedSession;
                    
                    if ($totalPaid != null && $totalPaid != 0) {
                        $balanceAmount = $totalPaid - $consumeAmount;
                    } else {
                        $balanceAmount = 0;
                    }

                                
                                if($val->session_status == 1)
                                {
                                    $status='session start';
                                }
                                else if($val->session_status == 2)
                                {
                                    $status='session end';
                                }else{
                                    $status='session cancle';
                                }

                                $createdDate = Carbon::parse($val->created_at);     
                        
                        $treatmentKey = $val->patient_id . '-' . $val->treatment_id; // Unique key for each patient-treatment
                        if (!isset($treatmentCounter[$treatmentKey])) {
                            $treatmentCounter[$treatmentKey] = 1; // Start counting from 1
                        } else {
                            $treatmentCounter[$treatmentKey]++; // Increment count
                        }
                                $scheduleList[] = array(
                                    "sr_no" => $treatmentCounter[$treatmentKey], // Add serial number
                                    "patient_schedule_id" => $val->scheduleid,
                                    "patient_session_id" => $val->iSessionTakenId,
                                    "days" =>  $createdDate->dayOfWeekIso,
                                    "days_name" => $day = date('l', strtotime($val->created_at)),
                                    "therapist_id" => $val->therapist_id,
                                    "therpist_name" => $val->therpist_name,
                                    "clinic_id" => $val->clinic_id,
                                    "start_time" => $val->SessionStartTime,
                                    "end_time" => $val->SessionEndTime,
                                    "treatment_id" => $val->treatment_id,
                                    "treatment_name" => $val->treatment_name,
                                    "total_amount" => optional($val)->total ?? 0,
                                    "paid_amount" => $totalPaid ?? 0,
                                    "consume_amount" => $consumeAmount ?? 0,
                                    "available_amount" => $availableAmount ?? 0,
                                    "remain_balance" => $balanceAmount ?? 0,
                                    "due_amount" => optional($val)->due ?? 0,
                                    "total_session" => optional($session)->iSessionBuy ?? 0,
                                    "paid_session" => number_format($totalpaidSession, 1) ?? 0,
                                    "due_session" => number_format($due_session,1) ?? 0,
                                    "consumed_session" => optional($session)->iUsedSession ?? 0,
                                    "available_session" =>number_format($availableSession,1) ?? 0,
                                    "session_consume_date" =>date('d-F-Y',strtotime($val->created_at)),
                                    "session_status" =>$status,

                                );
                            }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Patient schedule List',
                                    'Patient Schedule' => $scheduleList
                                ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Patient Schedule' => []
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
    public function history(Request $request)
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


                     $detail = Order::select(
                                'patientorderdetail.iOrderId',
                                'patientorderdetail.iOrderDetailId',
                                'patientorderdetail.iAmount as total',
                                'patientorderdetail.iDueAmount as due',
                                'patientorderdetail.iPlanId',
                                'patientorderdetail.iSession',
                                'patientordermaster.patient_id',
                                'patientorderdetail.iTreatmentId',
                                'patient_suggested_treatment.iUsedSession',
                                'patient_suggested_treatment.iSessionBuy',
                                DB::raw("(SELECT plan_name FROM plan_master WHERE plan_master.plan_id = patientorderdetail.iPlanId LIMIT 1) AS plan_name"),
                                DB::raw("(SELECT treatment_name FROM treatment_master WHERE treatment_master.treatment_id = patientorderdetail.iTreatmentId LIMIT 1) AS treatment_name")
                            )
                            ->join('patientorderdetail', 'patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')
                            ->join('patient_suggested_treatment', function ($join) {
                                $join->on('patientordermaster.patient_id', '=', 'patient_suggested_treatment.patient_id')
                                    ->on('patientorderdetail.iOrderDetailId', '=', 'patient_suggested_treatment.iOrderDetailId')
                                     ->on('patientorderdetail.iTreatmentId', '=', 'patient_suggested_treatment.treatment_id');
                            })
            
                            ->where('patientordermaster.patient_id', $request->patient_id)->where('isActive',0)
                            ->get();

                            if ($detail->isEmpty()) 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'History' => []
                                ]);
                            }
                            
                            $scheduleList = [];
                            
                            foreach ($detail as $val) 
                            {
                                
                                $paid_session = Plan::select('plan_master.per_session_amount')
                                    ->where('plan_id', $val->iPlanId)
                                    ->first();

                                $consumeAmount = optional($val)->iUsedSession ? ($paid_session->per_session_amount * $val->iUsedSession) : 0;

                                $totalPaid = max(($val->total ?? 0) - ($val->due ?? 0), 0);
                                $totalpaidSession = $paid_session->per_session_amount ? ($totalPaid / $paid_session->per_session_amount) : 0;
                                $availableAmount = $totalPaid - $consumeAmount;


                                $due_session = optional($val)->iSessionBuy - $totalpaidSession;
                                $availableSession = $totalpaidSession - optional($val)->iUsedSession;
                                $balanceAmount = max($totalPaid - $consumeAmount, 0);
                            
                                $status = match($val->session_status) {
                                    1 => 'session start',
                                    2 => 'session end',
                                    default => 'session cancel',
                                };
                            
                                
                            if((optional($val)->iSessionBuy <= optional($val)->iUsedSession))
                            {
                                //&& (optional($session)->iUsedSession !=  optional($session)->iSessionBuy)

                                    $scheduleList[] = [
                                        "order_id" => $val->iOrderDetailId,
                                        "treatment_id" => $val->iTreatmentId,
                                        "treatment_name" => $val->treatment_name,
                                        "plan_id" => $val->iPlanId,
                                        "plan_name" => $val->plan_name,
                                        "total_amount" => $val->total ?? 0,
                                        "paid_amount" => $totalPaid,
                                        "consume_amount" => $consumeAmount,
                                        "available_amount" => $availableAmount,
                                        "remain_balance" => $balanceAmount,
                                        "due_amount" => $val->due ?? 0,
                                        "total_session" => optional($val)->iSessionBuy ?? 0,
                                        "paid_session" => number_format($totalpaidSession, 1),
                                        "due_session" => number_format($due_session, 1),
                                        "consumed_session" => optional($val)->iUsedSession ?? 0,
                                        "available_session" => number_format($availableSession, 1),
                                    ];
                                }
                            }
                            
                            if (empty($scheduleList)) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found!',
                                    'History' => []
                                ]);
                            }
                            
                            return response()->json([
                                'status' => 'success',
                                'message' => 'History',
                                'History' => $scheduleList
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
    public function tomorrow_birthday_list(Request $request)
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

                    $tomorrow = Carbon::tomorrow()->format('m-d'); // Get tomorrow's month and day

                    // Fetch Patients
                    $Patient = Patient::where(['iStatus' => 1, 'isDelete' => 0, 'clinic_id' => $request->clinic_id])
                        ->whereRaw('DATE_FORMAT(dob, "%m-%d") = ?', [$tomorrow])
                        ->orderBy('patient_id', 'desc')
                        ->get();
                    
                    $PatientList = [];
                    if (sizeof($Patient) != 0) {
                        foreach ($Patient as $val) {
                            $PatientList[] = array(
                                "type" => "patient", // Add a type field to differentiate
                                "id" => $val->patient_id,
                                "case_no" => $val->patient_case_no,
                                "name" => $val->patient_first_name . ' ' . $val->patient_last_name,
                                "age" => $val->patient_age,
                                "dob" => $val->dob,
                                "phone" => $val->phone,
                            );
                        }
                    }
                    
                    // Fetch Employees
                    $Employee = User::select('users.*', DB::raw("(select name from roles where roles.id=users.role_id limit 1) as designation"))
                        ->where(['clinic_id' => $request->clinic_id, 'status' => 1])
                        ->whereRaw('DATE_FORMAT(dob, "%m-%d") = ?', [$tomorrow])
                        ->orderBy('id', 'desc')
                        ->get();
                    
                    $EmployeeList = [];
                    if (sizeof($Employee) != 0) {
                        foreach ($Employee as $val) {
                            $EmployeeList[] = array(
                                "type" => "employee", // Add a type field to differentiate
                                "id" => $val->id,
                                "name" => $val->name,
                                "email" => $val->email ?? '-',
                                "mobile" => $val->mobile_number,
                                "address" => $val->address,
                                "designation_id" => $val->role_id,
                                "designation_name" => $val->designation,
                                "dob" => date('d-m-Y', strtotime($val->dob)),
                                "status" => $val->status,
                            );
                        }
                    }
                    
                    // Merge both lists
                    $CombinedList = array_merge($EmployeeList ,$PatientList);
                    
                    if (!empty($CombinedList)) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Birthday List',
                            'data' => $CombinedList
                        ]);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'data' => []
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
    public function today_birthday_list(Request $request)
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

                    $today = Carbon::today()->format('m-d'); // Get today's month and day

                    $Patient = Patient::where(['iStatus' => 1, 'isDelete' => 0, 'clinic_id' => $request->clinic_id])
                        ->whereRaw('DATE_FORMAT(dob, "%m-%d") = ?', [$today])
                        ->orderBy('patient_id', 'desc')
                        ->get();
                    
                    $PatientList = [];
                    if (sizeof($Patient) != 0) {
                        foreach ($Patient as $val) {
                            $PatientList[] = array(
                                "type" => "patient", // Add a type field to differentiate
                                "id" => $val->patient_id,
                                "case_no" => $val->patient_case_no,
                                "name" => $val->patient_first_name . ' ' . $val->patient_last_name,
                                "age" => $val->patient_age,
                                "dob" => $val->dob,
                                "phone" => $val->phone,
                            );
                        }
                    }
                    
                    // Fetch Employees
                    $Employee = User::select('users.*', DB::raw("(select name from roles where roles.id=users.role_id limit 1) as designation"))
                        ->where(['clinic_id' => $request->clinic_id, 'status' => 1])
                        ->whereRaw('DATE_FORMAT(dob, "%m-%d") = ?', [$today])
                        ->orderBy('id', 'desc')
                        ->get();
                    
                    $EmployeeList = [];
                    if (sizeof($Employee) != 0) {
                        foreach ($Employee as $val) {
                            $EmployeeList[] = array(
                                "type" => "employee", // Add a type field to differentiate
                                "id" => $val->id,
                                "name" => $val->name,
                                "email" => $val->email ?? '-',
                                "mobile" => $val->mobile_number,
                                "address" => $val->address,
                                "designation_id" => $val->role_id,
                                "designation_name" => $val->designation,
                                "dob" => date('d-m-Y', strtotime($val->dob)),
                                "status" => $val->status,
                            );
                        }
                    }
                    
                    // Merge both lists
                    $CombinedList = array_merge($EmployeeList,$PatientList);
                    
                    if (!empty($CombinedList)) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Birthday List',
                            'data' => $CombinedList
                        ]);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'data' => []
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
   public function today_patient_list(Request $request)
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
    
                    
                    $dayNumber =  $request->day ?? date('N'); // 1 (Monday) to 7 (Sunday)


                    $schedule=PatientSchedule::select('patient_schedule.*','patient_master.clinic_id','patientorderdetail.iAmount as total', 'patientorderdetail.iDueAmount as due','patientorderdetail.iPlanId','patientorderdetail.iSession','patientorderdetail.iOrderDetailId','patientorderdetail.iOrderId',DB::raw("(select name from users where users.id=patient_schedule.therapist_id limit 1) as therpist_name"),
                    DB::raw("(select treatment_name from treatment_master where treatment_master.treatment_id=patient_schedule.treatment_id limit 1) as treatment_name")
                    ,DB::raw("(SELECT CONCAT(patient_master.patient_first_name, ' ', patient_master.patient_last_name) 
                  FROM patient_master 
                  WHERE patient_master.patient_id = patient_schedule.patient_id LIMIT 1) AS patient_name")
                    )->where(['patient_master.clinic_id'=>$request->clinic_id,'patient_schedule.day'=>$dayNumber,'cancel_package' => 0])
                        ->when($request->therapist_id, fn($query, $therapist_id) => $query->where('patient_schedule.therapist_id', '=', $therapist_id))
                        ->when($request->treatment_id, fn($query, $treatment_id) => $query->where('patient_schedule.treatment_id', '=', $treatment_id))
                        ->when($request->search, function ($query, $Search) {
                                    return $query->where(function ($query) use ($Search) {
                                        $query->where('patient_first_name', 'LIKE', "%{$Search}%")
                                              ->orWhere('patient_last_name', 'LIKE', "%{$Search}%")
                                              ->orWhere('phone', 'LIKE', "%{$Search}%");
                                    });
                                })
                        //->when($request->date, fn($query, $date) => $query->whereDate('patient_schedule.schedule_date', '=', $date)) // Filter by date
                        ->join('patient_master', 'patient_master.patient_id', '=', 'patient_schedule.patient_id')
                        ->join('patientordermaster', 'patientordermaster.iOrderId', '=', 'patient_schedule.orderId')
                                ->join('patientorderdetail', function ($join) {
                                    $join->on('patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')
                                         ->on('patientorderdetail.iTreatmentId', '=', 'patient_schedule.treatment_id');
                                })
                            ->groupBy('patientorderdetail.iOrderDetailId')
                        ->orderBy('patient_schedule_id', 'desc')->get();

                        if(sizeof($schedule) != 0)
                        {
                           
                            $scheduleList = []; // Initialize the schedule list array

                            foreach($schedule as $val)
                            {

                                $therapistList=User::select('users.*','treatment_master.treatment_id','treatment_therapist.therapist_id','treatment_master.treatment_name')->where(['treatment_master.treatment_id'=>$val->treatment_id])
                                    ->join('treatment_therapist', 'users.id', '=', 'treatment_therapist.therapist_id')
                                    ->join('treatment_master', 'treatment_master.treatment_id', '=', 'treatment_therapist.treatment_id')->get();
                               
                               
                                    $date = date('Y-m-d');
                                    $inPatient = PatientIn::where('patient_id', $val->patient_id)
                                    ->where('treatment_id', $val->treatment_id)
                                    ->whereDate('inDateTime', $date)->where(function ($query) 
                                    {
                                        $query->where('status', 0)
                                          ->orWhere('leave', 0);
                                        })
                                    ->first();
                                    
                                    if(!empty($inPatient) && $inPatient->status== 0 && $inPatient->leave== 0)
                                    {
                                        $inpatient=1;
                                    }else{
                                        $inpatient=0;
                                    }
                                    
                                    $session=PatientSuggestedTreatment::where(['patient_id'=>$val->patient_id,'iOrderId'=>$val->iOrderId,'iOrderDetailId'=>$val->iOrderDetailId,'treatment_id' => $val->treatment_id])->first();  
                                    $paid_session=Plan::select('plan_master.per_session_amount')->where(['plan_id'=>$val->iPlanId])->first();
                                    
                                    $consumeAmount=0;
                                    $totalPaid = (optional($val)->total ?? 0) - (optional($val)->due ?? 0);
                                   // $totalPaid = max(($val->total ?? 0) - ($val->due ?? 0), $val->total);
                                    
                                    if($session->iUsedSession == null)
                                    {
                                        $consumeAmount = 0;
                                    }else{
                                         $consumeAmount=$paid_session->per_session_amount  * $session->iUsedSession;
                                    }
                                        
                                        
                                    $totalpaidSession=$totalPaid / $paid_session->per_session_amount;
                                    $availableAmount=$totalPaid - $consumeAmount;
                                    $due_session= optional($session)->iSessionBuy - $totalpaidSession;
                                    
                                    $availableSession= $totalpaidSession-optional($session)->iUsedSession;
                                    if($totalPaid != null || $totalPaid != 0)
                                    {
                                        $balanceAmount=$totalPaid - $consumeAmount;
                                    }else{
                                       $balanceAmount=0; 
                                    }
                                    
                                     $tlist = []; // Initialize the therapist list array
                                    foreach ($therapistList as $val1) 
                                    {
                                        // Populate each therapist entry
                                        $tlist[] = array(
                                            "therapist_id" => $val1->id,
                                            "therpist_name" => $val1->name,
                                        );
                                    }
    
                                    $daysOfWeek = [
                                        1 => 'Monday',
                                        2 => 'Tuesday',
                                        3 => 'Wednesday',
                                        4 => 'Thursday',
                                        5 => 'Friday',
                                        6 => 'Saturday',
                                        7 => 'Sunday',
                                    ];
                                    
                                if((optional($session)->iSessionBuy >= optional($session)->iUsedSession) && (optional($session)->iUsedSession !=  optional($session)->iSessionBuy))
                                {
    
                                    $scheduleList[] = array(
                                        "iOrderDetailId" => $val->iOrderDetailId,
                                        "patient_schedule_id" => $val->patient_schedule_id,
                                        "days" => $val->day,
                                        "days_name" => $daysOfWeek[$val->day] ?? 'Invalid Day',
                                        "patient_id" => $val->patient_id,
                                        "patient_name" => $val->patient_name,
                                        "therapist_id" => $val->therapist_id,
                                        "therpist_name" => $val->therpist_name,
                                        "clinic_id" => $val->clinic_id,
                                        "start_time" => $val->schedule_start_time,
                                        "end_time" => $val->schedule_end_time,
                                        "treatment_id" => $val->treatment_id,
                                        "treatment_name" => $val->treatment_name,
                                        "total_amount" => optional($val)->total ?? 0,
                                        "paid_amount" => $totalPaid ?? 0,
                                        "consume_amount" => $consumeAmount ?? 0,
                                        "available_amount" => $availableAmount ?? 0,
                                        "remain_balance" => $balanceAmount ?? 0,
                                        "due_amount" => optional($val)->due ?? 0,
                                        "total_session" => optional($session)->iSessionBuy ?? 0,
                                        "paid_session" => number_format($totalpaidSession, 1) ?? 0,
                                        "due_session" => number_format($due_session,1) ?? 0,
                                        "consumed_session" => optional($session)->iUsedSession ?? 0,
                                        "available_session" =>number_format($availableSession,1) ?? 0,
                                         "inpatient"=>$inpatient,
                                        "therapist_list"=>$tlist
                                    );
                                 }
                             }
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'Patient schedule List',
                                        'Patient Schedule' => $scheduleList
                                    ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Patient Schedule' => []
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
     public function today_cancel_appointment_list(Request $request)
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
    
                    
                    $date=today();
                    
                 $schedule =PatientIn::select('patientin.*','patient_schedule.day','patient_schedule.patient_schedule_id','patient_master.clinic_id','patient_schedule.schedule_start_time','patient_schedule.schedule_end_time',DB::raw("(SELECT name FROM users WHERE users.id = patient_schedule.therapist_id LIMIT 1) AS therapist_name"),
                    DB::raw("(SELECT treatment_name FROM treatment_master WHERE treatment_master.treatment_id = patient_schedule.treatment_id LIMIT 1) AS treatment_name"),
                    DB::raw("(SELECT CONCAT(patient_master.patient_first_name, ' ', patient_master.patient_last_name) 
                              FROM patient_master 
                              WHERE patient_master.patient_id = patient_schedule.patient_id LIMIT 1) AS patient_name"))

                     ->where([
                            'patientin.status' => 1,
                            'patientin.leave'=>0,
                            'patient_master.clinic_id' => $request->clinic_id
                        ])
                        ->leftJoin('patient_schedule', function ($join) {
                            $join->on('patientin.treatment_id', '=', 'patient_schedule.treatment_id')
                                 ->on('patientin.therapist_id', '=', 'patient_schedule.therapist_id')
                                 ->on('patientin.patient_id', '=', 'patient_schedule.patient_id');
                        }) 
                     ->join('patient_master', 'patient_master.patient_id', '=', 'patientin.patient_id')
                    ->when($request->fromdate || $request->todate || $request->month || $request->year, function ($query) use ($request) {
                    if ($request->fromdate) {
                        $query->where('patientin.inDateTime', '>=', date('Y-m-d 00:00:00', strtotime($request->fromdate)));
                    }
                    if ($request->todate) {
                        $query->where('patientin.inDateTime', '<=', date('Y-m-d 23:59:59', strtotime($request->todate)));
                    }
                    if ($request->month) {
                        $query->whereMonth('patientin.inDateTime', $request->month);
                    }
                    if ($request->year) {
                        $query->whereYear('patientin.inDateTime', $request->year);
                    }
                }, function ($query) {
                    $query->whereDate('patient_schedule.inDateTime', today());
                })->groupBy('iPatientInId')->get();



                        if(sizeof($schedule) != 0)
                        {
                            $scheduleList = []; // Initialize the schedule list array

                            foreach($schedule as $val)
                            {
                                
                                $daysOfWeek = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday',
                                ];
                                
                                
                                $scheduleList[] = array(
                                    "patient_schedule_id" => $val->patient_schedule_id,
                                    // "session_id" => $val->iSessionTakenId,
                                    "days" => $val->day,
                                    "days_name" => $daysOfWeek[$val->day] ?? 'Invalid Day',
                                    "patient_id" => $val->patient_id,
                                    "patient_name" => $val->patient_name,
                                    "therapist_id" => $val->therapist_id,
                                    "therpist_name" => $val->therapist_name,
                                    "clinic_id" => $val->clinic_id,
                                    "start_time" => $val->schedule_start_time,
                                    "end_time" => $val->schedule_end_time,
                                    "treatment_id" => $val->treatment_id,
                                    "treatment_name" => $val->treatment_name,

                                );
                                
                                 $scheduleList2[] = array(
                                    // "session_id" => $val->iSessionTakenId,
                                    "date" => date('d-m-Y', strtotime($val->inDateTime)),
                                    "start_time" => $val->schedule_start_time,
                                    "end_time" => $val->schedule_end_time,
                                    "patient_name" => $val->patient_name,
                                    "therpist_name" => $val->therapist_name,
                                    "treatment_name" => $val->treatment_name,

                                );
                            }
                            if($request->status == 1)
                    {
                        
                    
                      $export = new CancelAppointment($scheduleList2, $request->fromdate, $request->todate, $request->month, $request->year);

                    // Define the target directory and ensure it exists
                    $basePath = '/home3/vrajdahj/vrajphysioapp.vrajdentalclinic.com/reports';
                    if (!file_exists($basePath)) {
                        mkdir($basePath, 0755, true); // Create the directory with appropriate permissions
                    }
                    
                    // Define the file path
                    $fileName = 'Today_Cancel_Appointment_List_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
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
                                    'message' => 'Cancel Appointment List',
                                    'Cancel Appointment' => $scheduleList
                                ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Cancel Appointment' => []
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
   public function today_new_patient(Request $request)
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
                    $date=today();

                    $Patient = Patient::where(['iStatus' => 1, 'isDelete' => 0, 'clinic_id' => $request->clinic_id])
                                ->when($request->fromdate || $request->todate || $request->month || $request->year, function ($query) use ($request) 
                                {
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
                                ->get();


                        if(sizeof($Patient) != 0)
                        {
                            foreach($Patient as $val)
                            {
                                $PatientList[] = array(
                                    "patient_id" => $val->patient_id,
                                    // "patient_user_id" => $val->patient_user_id,
                                    "patient_case_no" => $val->patient_case_no,
                                    "patient_name" => $val->patient_first_name.' '.$val->patient_last_name,
                                    "patient_age" => $val->patient_age,
                                    "dob" => $val->dob ? date('d-m-Y', strtotime($val->dob)) : '-',
                                    "phone" => $val->phone,
                                    "other_mobile" => $val->other_mobile_no,
                                );
                            }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Patient List',
                                    'Patient' => $PatientList
                                ]);

                        } else 
                        {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No Data Found!',
                                'Patient' => []
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
    public function today_collection(Request $request)
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
                        })->groupBy('orderpayment.orderDetailId','patientorderdetail.iOrderDetailId')
                    ->get();
                    
                    if(sizeof($OrderPayment) != 0)
                    {
                        $paid=0;
                        foreach ($OrderPayment as $key => $val) 
                        {
                             $totalPaid = OrderPayment::where(['orderDetailId' => $val->orderDetailId])->sum('Amount');
                             
                                $pList[] = array(
                                    "iOrderId" => $val->iOrderId,
                                    "order_detail_id" => $val->orderDetailId,
                                    "patient_name" => $val->patient_name,
                                    "paid_amount"=>$totalPaid ?? 0,
                                    "due_amount"=>$val->DueAmount,
                                    "Amount" => $val->iAmount,
                                    "payment_mode" => $val->payment_mode,
                                    "bad_dept" => $val->bad_dept,
                                    "payment_date" => date('d-M-Y',strtotime($val->PaymentDateTime)),
                                );
                                
                                $paid +=$totalPaid;
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
     public function today_appointment_treatment(Request $request)
    {
        try
        {

             $dayNumber =  $request->day ?? date('N'); // 1 (Monday) to 7 (Sunday)


            $treatmentList = PatientSchedule::selectRaw(
                    'MAX(patient_schedule.patient_schedule_id) as patient_schedule_id, 
                     treatment_master.treatment_id, 
                     MAX(patient_schedule.therapist_id) as therapist_id, 
                     MAX(treatment_master.treatment_name) as treatment_name'
                )
                ->join('treatment_master', 'treatment_master.treatment_id', '=', 'patient_schedule.treatment_id')
                ->where(['patient_schedule.day' => $dayNumber])
                ->groupBy('treatment_master.treatment_id')
                ->orderBy('patient_schedule_id', 'desc')
                ->get();

                    if(sizeof($treatmentList) != 0)
                    {

                        foreach ($treatmentList as $key => $val) 
                        {
                             
                             $total_patient=PatientSchedule::where(['patient_schedule.day'=>$dayNumber,'treatment_id'=>$val->treatment_id])->count();
                                $tList[] = array(
                                    "treatment_id" => $val->treatment_id,
                                    "treatment_name" => $val->treatment_name,
                                    "total_patient" => $total_patient,
                                );
                        }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Today Treatment List',
                                    'today_treatment_list' => $tList
                                ]);

                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No Data Found!',
                            'Payment' => []
                        ]);
                    }


                            

        }catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


}
