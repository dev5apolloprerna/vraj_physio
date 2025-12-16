<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

use App\Models\CaseNo;
use App\Models\ConsentMaster;
use App\Models\Designation;
use App\Models\MyPackage;
use App\Models\Notes;
use App\Models\Plan;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\RefrenceBy;
use App\Models\Schedule;
use App\Models\ScheduleTreatment;
use App\Models\Treatment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\TreatmentTherapist;
use App\Models\SessionMaster;
use App\Models\SettingBillId;
use App\Models\PatientSchedule;
use App\Models\PatientSuggestedTreatment;
use App\Models\PatientIn;
use App\Models\OrderPayment;
use App\Models\PatientTreatmentLedger;
use App\Models\User;
use Carbon\Carbon;

class CRUDController extends Controller
{
    
    public function patient_create(Request $request)
    {
         try {
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
                      /*$request->validate([
                        'email' => 'required|email|unique:patient_master,email',
                        'mobile' => [
                            'required',
                            'unique:patient_master,phone',
                            'regex:/^(\+?\d{1,3})?(\d{10})$/', // Example regex for a 10-digit number with optional country code
                        ],
                    ]);*/
                     $existPatient = Patient::where('patient_case_no', '=', $request->case_no)->first();
                if(!empty($existPatient))
                {
                        
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Patient already exist.'
                    ]);
                    
                }else
                {
                    
                  $nextCaseNo = DB::table('patient_master')->where('clinic_id', 1)->max('autocase_no') + 1;

                    $age = Carbon::parse($request->dob)->age;

                    $patient=new Patient();
                    $patient->clinic_id=$request->clinic_id;
                    $patient->patient_first_name=$request->first_name;
                    $patient->patient_last_name=$request->last_name;
                    $patient->phone=$request->mobile;
                    $patient->other_mobile_no=$request->other_mobile;
                    $patient->patient_case_no=$request->patient_case_no;
                    $patient->autocase_no=$nextCaseNo;
                    $patient->email=$request->email;
                    $patient->gender=$request->gender;
                    $patient->dob=$request->dob;
                    $patient->patient_age=$age;
                    $patient->address=$request->address;
                    $patient->ref_by=$request->ref_by;
                    $patient->save();
                        
                        $key = $_ENV['WHATSAPPKEY'];
                    	$users = new User();
                    	$msg = "Welcome to Vraj PHYSIOTHERAPY AND CHILD DEVELOPMENT CENTER! We’re excited to have you with us and look forward to supporting your child's growth and development. Our dedicated team is here to help every step of the way!";
						$status = $users->sendWhatsappMessage($request->mobile,$key,$msg, $someOtherParam = null);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Patient Created Successfully',

                    ], 401);
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
    public function patient_update(Request $request)
    {
         try {

                $id=$request->patient_id;
                            // Perform the validation
                /*$request->validate([
                    'email' => 'unique:patient_master,email,' . $id . ',patient_id',
                    'mobile' => [
                        'unique:patient_master,phone,' . $id . ',patient_id',
                        'regex:/^(\+?\d{1,3})?(\d{10})$/'
                    ],
                ]);*/

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
                    
                   // Find the patient by patient_id
                    // Find the patient by patient_id
                    $patient = Patient::where('patient_id', $id)->first();

                    // Update the fields if patient exists
                    if ($patient) {
                        $patient->clinic_id = $request->clinic_id;
                        $patient->patient_first_name = $request->first_name;
                        $patient->patient_last_name = $request->last_name;
                        $patient->phone = $request->mobile;
                        $patient->other_mobile_no=$request->other_mobile;
                        $patient->patient_case_no = $request->patient_case_no;
                        $patient->email = $request->email;
                        $patient->gender = $request->gender;
                        $patient->dob = $request->dob;
                        $patient->ref_by = $request->ref_by;

                        // Recalculate age if dob is updated
                        $age = Carbon::parse($request->dob)->age; 
                        $patient->patient_age = $age;

                        $patient->address = $request->address;

                        // Save the updated record
                        $patient->save();
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Patient Updated Successfully',

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
     public function patient_delete(Request $request)
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
                        
                        $id=$request->patient_id;
                            //$udata = Patient::where(["patient_id"=>$request->patient_id])->delete();
                         $patient = Patient::where('patient_id', $id)->first();

                                // Update the fields if patient exists
                                if ($patient) 
                                {
                                    $patient->isDelete = 1;
                                    $patient->save();
                                }
                         
                         $odata=Order::where(["patient_id"=>$id])->get();
                        foreach ($odata as $key => $value) 
                        {

                            OrderPayment::where(['iOrderId'=>$value->iOrderId])->delete();

                            OrderDetail::where(['iOrderId'=>$value->iOrderId])->delete();

                        }
                            Order::where(["patient_id"=>$id])->delete();
                            PatientSchedule::where(["patient_id"=>$id])->delete();
                            PatientSuggestedTreatment::where(["patient_id"=>$id])->delete();
                            SessionMaster::where(["patient_id"=>$id])->delete();
                            PatientTreatmentLedger::where(["patient_id"=>$id])->delete();
                             return response()->json([
                                'status' => 'success',
                                'message' => 'Patient Deleted successfully'
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
    public function upload_patient_document(Request $request)
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
                    $patient=Patient::where(['patient_id'=>$request->patient_id,'iStatus'=>1,'isDelete'=>0])->first();
                    if($patient)
                    {
                        $request->validate([
                            'document' => 'required|mimes:jpeg,png,jpg,gif,pdf',
                        ]);


                            $userImg = '';
                            $img = '';
                            $root = $_SERVER['DOCUMENT_ROOT'];
                            if($request->document)
                            {
                                $imageName = rand(1000, 9999) ."_". time() . '.' . $request->document->extension();
            
                                // $EntrDate = date('Y-m-d',strtotime($application->created_at));
                                $EntrDate = $patient->created_at;
            
                                $arr = explode(' ', $EntrDate);
                                $dateArrar = explode('-', $arr[0]);
                                $root = $_SERVER['DOCUMENT_ROOT'];
                                $destinationPath = $root .'/'.$request->patient_id.'/'.'PatientDocument/';
                                if (!file_exists($destinationPath)) 
                                {
                                    mkdir($destinationPath, 0755, true);
                                }
            
                                $target_path = $destinationPath ."/";
                                $request->document->move($target_path, $imageName);
            

                            }else{
                               $userImg=$customer->document; 
                            }
                            $PatientDocument=new PatientDocument();
                            $PatientDocument->patient_id=$request->patient_id;
                            $PatientDocument->document=$imageName;
                            $PatientDocument->save();

                        $patient_name=$patient->patient_first_name.' '.$patient->patient_last_name;
                        $key = $_ENV['WHATSAPPKEY'];
                        $users = new User();
                        $msg = "Dear Admin,\n\n"
                            . "Patient Name:".$patient_name."\n\n"
                            . "Date:".date('d-M-Y')."\n\n"
                            . "Add document."."\n\n"
                            // . $patient_name."has attached a new document."."\n\n"
                            . "Thank you!";
                            
                        $status = $users->sendWhatsappMessage(8866203090,$key,$msg, $someOtherParam = null);

                            return response()->json([
                                'status' => 'success',
                                'message' => 'Patient Document added successfully'
                            ]);
                        }else{
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Patient Not Found'
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


     public function employee_create(Request $request)
    {
         try {
            $request->validate([
                    'mobile' => [
                        'unique:users,mobile_number',
                        'regex:/^(\+?\d{1,3})?(\d{10})$/'
                    ],
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
                    $user=new User();
                    $user->name=$request->name;
                    $user->email=$request->email;
                    $user->mobile_number=$request->mobile;
                    $user->password=Hash::make($request->password);
                    $user->address=$request->address;
                    $user->designation_id=$request->designation_id;
                    $user->role_id=$request->designation_id;
                    $user->dob=$request->dob;
                    $user->save();


                    return response()->json([
                        'status' => 'success',
                        'message' => 'Employee Created Successfully',

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
    public function employee_update(Request $request)
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

                        $id=$request->employee_id;

                        $doctor = User::where('id', $id)->first();
                        
                            // Perform the validation
                            $request->validate([
                                'mobile' => [
                                    'unique:users,mobile_number,' . $id . ',id',
                                    'regex:/^(\+?\d{1,3})?(\d{10})$/'
                                ],
                            ]);
           
                    
                        $doctor = User::where('id', $id)->first();

                        if ($doctor) {
                            $doctor->name=$request->name;
                            $doctor->mobile_number=$request->mobile;
                            $doctor->email=$request->email;
                            $doctor->address=$request->address;
                            $doctor->designation_id=$request->designation_id;
                            $doctor->role_id=$request->designation_id;
                            $doctor->dob=$request->dob;
                            $doctor->save();
                        }

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Employee Updated Successfully',

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
    public function employee_update_status(Request $request)
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
                    $udata = User::where(["id"=>$request->employee_id])->update([
                         "status"=>$request->status
                        ]);

                     if($request->status == 1){
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Employee  Activated successfully'
                        ]);
                     }else{
                         return response()->json([
                            'status' => 'success',
                            'message' => 'Employee Inactivated successfully'
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
    public function clear_device_token(Request $request)
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
            
                    $id=$request->employee_id;

                    $userdata=User::where(['id'=>$id])->first();
                    if(!empty($userdata))
                    {

                        $data = array(
                            'device_token'=>NULL,
                            );
                        User::where("id","=",$id)->update($data);

                        return response()->json([
                                    'status' => 'success',
                                    'message' => 'Device Token Clear Successfully',
                                ], 401);
                        }
                        else{
                           

                            return response()->json([
                                    'status' => 'error',
                                    'message' => 'No Data Found',
                                ], 401);
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

     public function employee_delete(Request $request)
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
                $data=User::select('role_id')->where(["id"=>$request->employee_id])->first();
                if($data->role_id == 1)
                {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Admin Not Allowed to Deleted'
                    ]);

                }else{

                    $udata = User::where(["id"=>$request->employee_id])->delete();

                 
                     return response()->json([
                        'status' => 'success',
                        'message' => 'Employee Deleted successfully'
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
    public function employee_change_password(Request $request)
    {

        try
        {
            $request->validate([
                    'new_password' => 'required',
                    'confirm_password' => 'required'
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
            
                    $newpassword = $request->new_password;
                    $confirmpassword = $request->confirm_password;

                
                        if($newpassword == $confirmpassword) 
                        {
                            $users = DB::table('users')
                                ->where(['status' => 1, 'id' => $request->employee_id])
                                ->update([
                                    'password' => Hash::make($confirmpassword),
                                ]);
                                return response()->json([
                                        'status' => 'success',
                                        'message' => 'Password Updated Successfully'
                                    ]);

                        }else 
                        {
                            return response()->json([
                                    'status' => 'error',
                                    'message' => 'password and confirm password does not match',
                                ], 401);
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
    public function designation_create(Request $request)
    {
        try
        {
            $request->validate([
                    'designation_name' => 'required'
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

                    $designation=new Designation();
                    $designation->designation_name=$request->designation_name;
                    $designation->save();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Designation Created Successfully',

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

     public function designation_update(Request $request)
    {
        try
        {
            $id=$request->designation_id;

            $request->validate([
                    'designation_name' => 'unique:designation,designation_name,' . $id . ',designation_id',
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

                    $designation = Designation::where('designation_id', $id)->first();

                    if ($designation) {
                        $designation->designation_name=$request->designation_name;
                        $designation->save();
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Designation Updated Successfully',

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
   public function designation_delete(Request $request)
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
                    
                    $udata = Designation::where(["designation_id"=>$request->designation_id])->delete();

                     
                         return response()->json([
                            'status' => 'success',
                            'message' => 'Designation Deleted successfully'
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


//************************* Plan CRUD **************************//

    public function plan_create(Request $request)
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

                    $per_session_amount = ($request->amount / $request->no_of_session);
                    $plan=new Plan();
                    $plan->clinic_id=$request->clinic_id;
                    $plan->plan_name=$request->plan_name;
                    $plan->no_of_session=$request->no_of_session;
                    $plan->treatment_id=$request->treatment_id;
                    $plan->NotificatoToPatientOnRemainignSession=$request->remaining_session_notification;
                    $plan->base_amount=$request->base_amount;
                    $plan->discount_amount=$request->discount_amount;
                    $plan->amount=$request->amount;
                    $plan->per_session_amount=$per_session_amount;
                    $plan->save();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Plan Created Successfully',

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

     public function plan_update(Request $request)
    {
        try
        {
            $id=$request->plan_id;

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

                    $plan = Plan::where('plan_id', $id)->first();

                    if ($plan) 
                    {
                        $per_session_amount = ($request->amount /  $request->no_of_session);

                        $plan->clinic_id=$request->clinic_id;
                        $plan->plan_name=$request->plan_name;
                        $plan->no_of_session=$request->no_of_session;
                        $plan->treatment_id=$request->treatment_id;
                        $plan->NotificatoToPatientOnRemainignSession=$request->remaining_session_notification;
                        $plan->base_amount=$request->base_amount;
                        $plan->discount_amount=$request->discount_amount;
                        $plan->amount=$request->amount;
                        $plan->per_session_amount=$per_session_amount;                        
                        $plan->save();
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Plan Updated Successfully',

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
   public function plan_delete(Request $request)
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
                    
                   $order=OrderDetail::where(['iPlanId'=>$request->plan_id])->count();
                    if($order == 0)
                    {
                           $udata = Plan::where(["plan_id"=>$request->plan_id])->delete();
                         
                             return response()->json([
                                'status' => 'success',
                                'message' => 'Plan Deleted successfully'
                            ]);
                    }else
                         {
                             return response()->json([
                                'status' => 'success',
                                'message' => 'Plan can not deleted because it is use in another master table'
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
    //--------------------------- Refrence by ---------------------//
    public function refrenceBy_create(Request $request)
    {
        try
        {
          $request->validate([
                'refrence_name' => 'required|unique:refrence_by,refrence_name',
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

                    $RefrenceBy=new RefrenceBy();
                    $RefrenceBy->refrence_name=$request->refrence_name;
                    $RefrenceBy->save();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'RefrenceBy Created Successfully',

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

     public function refrenceBy_update(Request $request)
    {
        try
        {
            $id=$request->refrence_id;

            $request->validate([
                    'refrence_name' => 'unique:refrence_by,refrence_name,' . $id . ',refrence_id',
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

                    $RefrenceBy = RefrenceBy::where('refrence_id', $id)->first();

                    if ($RefrenceBy) {
                        $RefrenceBy->refrence_name=$request->refrence_name;
                        $RefrenceBy->save();
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'RefrenceBy Updated Successfully',

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
     public function refrenceBy_delete(Request $request)
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
                    
                    $udata = RefrenceBy::where(["refrence_id"=>$request->refrence_id])->delete();

                     
                         return response()->json([
                            'status' => 'success',
                            'message' => 'RefrenceBy Deleted successfully'
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

//--------------------------------treatment -------------------------------------------------//

 public function treatment_create(Request $request)
    {
        try
        {
            $request->validate([
                'treatment_name' => 'required|string|unique:treatment_master,treatment_name',
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

                     $treatment=new Treatment();
                    $treatment->clinic_id=$request->clinic_id;
                    $treatment->treatment_name=$request->treatment_name;
                    $treatment->amount=$request->amount;
                    $treatment->save();

                    if ($request->has('therapist_id') && is_array($request->therapist_id)) 
                    {

                        foreach ($request->therapist_id as $therapistId) 
                        {
                            $therapist=new TreatmentTherapist();
                            $therapist->treatment_id=$treatment->treatment_id;
                            $therapist->therapist_id=$therapistId;
                            $therapist->save();
                        }
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Treatment Created Successfully',

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
    public function treatment_update(Request $request)
    {
        try
        {
            $id=$request->treatment_id;

             $request->validate([
                'treatment_name' => 'required|string|unique:treatment_master,treatment_name,' . $id . ',treatment_id',
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

                    $treatment = Treatment::where('treatment_id', $id)->first();

                    if ($treatment) 
                    {
                        $treatment->clinic_id=$request->clinic_id;
                        $treatment->treatment_name=$request->treatment_name;
                        $treatment->amount=$request->amount;
                        $treatment->save();

                        if ($request->has('therapist_id') && is_array($request->therapist_id)) 
                        {
                            $delete=TreatmentTherapist::where(["treatment_id"=>$id])->delete();

                            /*$therapist=TreatmentTherapist::whereIn('therapist_id', $request->therapist_id)->where(['treatment_id'=>$id])->get();
                            if(sizeof($therapist) == 0)
                            {*/
                                foreach ($request->therapist_id as $therapistId) 
                                {
                                    $therapist=new TreatmentTherapist();
                                    $therapist->treatment_id=$treatment->treatment_id;
                                    $therapist->therapist_id=$therapistId;
                                    $therapist->save();
                                }

                            //}
                        }
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Treatment Updated Successfully',

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
     public function treatment_delete(Request $request)
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
                    
                    $data=Plan::select('treatment_id')->where(['treatment_id'=>$request->treatment_id])->count();
                    if($data == 0)
                    {
                        $udata = Treatment::where(["treatment_id"=>$request->treatment_id])->delete();

                        $delete=TreatmentTherapist::where(["treatment_id"=>$request->treatment_id])->delete();
                             return response()->json([
                                'status' => 'success',
                                'message' => 'Treatment Deleted successfully'
                            ]);
                    }else
                    {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Treatment can not deleted because it is use in another master table'
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
    public function add_patient_package(Request $request)
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
                   $get=MyPackage::where(['patient_id'=>$request->patient_id,'treatment_id'=>$request->treatment_id,'clinic_id'=>$request->clinic_id])->get();
                    if(sizeof($get) == 0)
                    {

                        $MyPackage=new MyPackage();
                        $MyPackage->patient_id=$request->patient_id;
                        $MyPackage->treatment_id=$request->treatment_id;
                        $MyPackage->plan_id=$request->plan_id;
                        $MyPackage->no_of_session=$request->no_of_session;
                        $MyPackage->amount=$request->amount;
                        $MyPackage->clinic_id=$request->clinic_id;
                        $MyPackage->save();

                            return response()->json([
                                'status' => 'success',
                                'message' => 'Package added successfully'
                            ]);
                        }else{
                            return response()->json([
                                'status' => 'error',
                                'message' => 'You have already selected package from this treatment'
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
     public function delete_patient_package(Request $request)
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
                    $data=MyPackage::where(["tempid"=>$request->tempid])->first();
                    if($data)
                    {
                        $udata = MyPackage::where(["tempid"=>$request->tempid])->delete();

                         
                             return response()->json([
                                'status' => 'success',
                                'message' => 'Package Deleted successfully'
                            ]);
                    }else{
                        return response()->json([
                                'status' => 'success',
                                'message' => 'No Data Found'
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
    public function add_schedule(Request $request)
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
                    $data = $request->all();

                    foreach ($data['days'] as $day) 
                    {
                        $schedule=new Schedule();
                        $schedule->days=$day;
                        $schedule->therapist_id=$data['therapist_id'];
                        $schedule->clinic_id=$data['clinic_id'];
                        $schedule->start_time=$data['start_time'];
                        $schedule->end_time=$data['end_time'];
                        $schedule->treatment_id=NULL;
                        $schedule->maximum_patient=$data['maximum_patient'];
                        $schedule->save();  
                        
                        
                            foreach ($data['treatment_id'] as $treatmentId) 
                            {                                 
                                $streatment=ScheduleTreatment::where(['schedule_id'=>$schedule->scheduleid,'therapist_id'=>$data['therapist_id'],'treatment_id'=>$treatmentId])->get();
                                 if(sizeof($streatment) == 0)
                                 {
                                    $streatment=new ScheduleTreatment();
                                    $streatment->schedule_id=$schedule->scheduleid;
                                    $streatment->treatment_id=$treatmentId;
                                    $streatment->therapist_id=$data['therapist_id'];
                                    $streatment->save();
                                 }

                            }
                    }


                     return response()->json([
                            'status' => 'success',
                            'message' => 'Schedule added successfully'
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
     public function update_schedule(Request $request)
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
                    $id=$request->scheduleid;

                    $schedule = Schedule::where('scheduleid', $id)->first();

                    if ($schedule) {
                        $schedule->days=$request->days;
                        $schedule->therapist_id=$request->therapist_id;
                        $schedule->clinic_id=$request->clinic_id;
                        $schedule->start_time=$request->start_time;
                        $schedule->end_time=$request->end_time;
                        $schedule->treatment_id=NULL;
                        $schedule->maximum_patient=$request->maximum_patient;
                        $schedule->save();
                    
                        $existingTreatments = ScheduleTreatment::where('schedule_id', $id)->get();
    
                        if ($existingTreatments->isNotEmpty()) {
                            ScheduleTreatment::where('schedule_id', $id)->delete();
                        }

                        // Insert new ScheduleTreatment records
                        $data = $request->all();
                        foreach ($data['treatment_id'] as $treatmentId) 
                        {
                            $streatment = new ScheduleTreatment();
                            $streatment->schedule_id = $id;
                            $streatment->treatment_id = $treatmentId;
                            $streatment->therapist_id = $data['therapist_id'];
                            $streatment->save();
                        }

                    }
                    
        
                     return response()->json([
                            'status' => 'success',
                            'message' => 'Schedule updated successfully'
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
     public function delete_schedule(Request $request)
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
                    
                    $schedule=PatientSchedule::where(['scheduleid'=>$request->scheduleid])->count();
                    if($schedule == 0)
                    {
                        $udata = Schedule::where(["scheduleid"=>$request->scheduleid])->delete();

                     
                         return response()->json([
                            'status' => 'success',
                            'message' => 'Schedule Deleted successfully'
                        ]);
                    }else{
                         return response()->json([
                            'status' => 'error',
                            'message' => 'Schedule Assigned , Can not Deleted'
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
    public function caseno_create(Request $request)
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
                    $case=new CaseNo();
                    $case->case_prefix=$request->case_prefix;
                    $case->case_number=$request->case_number;
                    $case->case_suffix=$request->case_suffix;
                    $case->clinic_id=$request->clinic_id;
                    $case->save();  

                     return response()->json([
                            'status' => 'success',
                            'message' => 'Case No added successfully'
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
     public function caseno_update(Request $request)
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
                    $id=$request->case_id;
                    $CaseNo = CaseNo::where('case_id', $id)->first();

                    if ($CaseNo) 
                    {   
                        $CaseNo->case_prefix=$request->case_prefix;
                        $CaseNo->case_number=$request->case_number;
                        $CaseNo->case_suffix=$request->case_suffix;
                        $CaseNo->clinic_id=$request->clinic_id;
                        $CaseNo->save();  
                    }

                     return response()->json([
                            'status' => 'success',
                            'message' => 'Case No updated successfully'
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
    public function caseno_delete(Request $request)
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
                    
                    $udata = CaseNo::where(["case_id"=>$request->case_id])->delete();

                     
                         return response()->json([
                            'status' => 'success',
                            'message' => 'Caseno Deleted successfully'
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
     public function notes_create(Request $request)
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
                    $notes=new Notes();
                    $notes->title=$request->title;
                    $notes->description=$request->description;
                    $notes->clinic_id=$request->clinic_id;
                    $notes->patient_id=$request->patient_id;
                    $notes->save();  

                       $p=Patient::where(['patient_id'=>$request->patient_id])->first();
                       $patient_name=$p->patient_first_name.' '.$p->patient_last_name;
                        $key = $_ENV['WHATSAPPKEY'];
                        $users = new User();
                        $msg = "Dear Admin,\n\n"
                            . "Patient Name:".$patient_name."\n\n"
                            . "Title:".$request->title."\n\n"
                            . "Description:".$request->description."\n\n"
                            . "Thank you!";
                            
                        $status = $users->sendWhatsappMessage(8866203090,$key,$msg, $someOtherParam = null);

                     return response()->json([
                            'status' => 'success',
                            'message' => 'Notes added successfully'
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
    public function notes_delete(Request $request)
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
                    
                    $udata = Notes::where(["note_id"=>$request->note_id])->delete();

                     
                         return response()->json([
                            'status' => 'success',
                            'message' => 'Notes Deleted successfully'
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
    //Consent form CRUD
    public function consent_create(Request $request)
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

                    $consent=new ConsentMaster();
                    $consent->clinic_id=$request->clinic_id;
                    $consent->title=$request->title;
                    $consent->description=$request->description;
                    $consent->save();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Consent Created Successfully',

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

     public function consent_update(Request $request)
    {
        try
        {
            $id=$request->consent_id;


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

                    $consent = ConsentMaster::where('id', $id)->first();

                    if ($consent) {
                        $consent->clinic_id=$request->clinic_id;
                        $consent->title=$request->title;
                        $consent->description=$request->description;
                        $consent->save();
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Consent Updated Successfully',

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
   public function consent_delete(Request $request)
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
                    
                    $udata = ConsentMaster::where(["id"=>$request->consent_id])->delete();

                     
                         return response()->json([
                            'status' => 'success',
                            'message' => 'Consent Deleted successfully'
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
     public function add_patient_schedule(Request $request)
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

                $treatment_id = $request->treatment_id;
                // $therapist_id = $request->therapist_id;
                $patient_id = $request->patient_id;

                foreach ($request->data as $sessionData) 
                {
                    $schedule_id = $sessionData['schedule_id'];
                    
                    // Parse the start and end times with a 15-minute buffer
                     // Parse the start and end times with a 15-minute buffer
                    $start_time = Carbon::parse($sessionData['start_time'])->addMinutes(15)->format('H:i:s');
                    $end_time = Carbon::parse($sessionData['end_time'])->subMinutes(15)->format('H:i:s');
                    $day=$sessionData['day'];
                    // \DB::enableQueryLog(); // Enable query log

                    // Check for overlapping sessions with the 15-minute buffer applied

                    $totalSession=PatientSuggestedTreatment::select('iAvailableSession')->where(['patient_id'=>$patient_id,'treatment_id'=>$treatment_id])->first();
                    
                    if(!empty($totalSession))
                    {
                        if($totalSession->iAvailableSession != 0 && $totalSession->iAvailableSession !=null)
                        {
                            $overlap = PatientSchedule::where('patient_id', $patient_id)->where('orderId', $request->order_id)->where('treatment_id',$treatment_id)->where('therapist_id', $sessionData['therapist_id'])
                                                    ->where('schedule_start_time', '<=', $start_time)
                                                    ->where('schedule_end_time', '>=', $end_time)
                                                    ->where('day', '=', $day)->get();

                                                    
                            if (sizeof($overlap) != 0) 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'The selected schedule overlaps with an existing session for this patient.'
                                ]);
                            }
                        }
                        else
                        {

                            $overlap = PatientSchedule::where('patient_id', $patient_id)->where('orderId', $request->order_id)->where('treatment_id',$treatment_id)->where('therapist_id', $sessionData['therapist_id'])
                                                    ->where('schedule_start_time', '<=', $start_time)
                                                    ->where('schedule_end_time', '>=', $end_time)
                                                    ->where('day', '=', $day)->get();

                                            
                            if (sizeof($overlap) != 0) 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'The selected schedule overlaps with an existing session for this patient.'
                                ]);
                            }
                        }
                    }else
                    {
                        $overlap = PatientSchedule::where('patient_id', $patient_id)->where('orderId', $request->order_id)->where('treatment_id',$treatment_id)->where('therapist_id', $sessionData['therapist_id'])
                                                    ->where('schedule_start_time', '<=', $start_time)
                                                    ->where('schedule_end_time', '>=', $end_time)
                                                    ->where('day', '=', $day)->get();

                                                    
                            if (sizeof($overlap) != 0) 
                            {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'The selected schedule overlaps with an existing session for this patient.'
                                ]);
                            }
                    }


                    // If no overlap, proceed to save the session
                    $PatientSchedule = new PatientSchedule();
                    $PatientSchedule->orderId = $request->order_id;
                    $PatientSchedule->treatment_id = $treatment_id;
                    $PatientSchedule->therapist_id = $sessionData['therapist_id'];
                    $PatientSchedule->patient_id = $patient_id;
                    $PatientSchedule->scheduleid = $schedule_id;
                    $PatientSchedule->day = $sessionData['day'];
                    $PatientSchedule->schedule_start_time = $sessionData['start_time'];
                    $PatientSchedule->schedule_end_time = $sessionData['end_time'];
                    $PatientSchedule->save();
                
                    $id[]=$PatientSchedule->patient_schedule_id;
                    
                    $ledger=new PatientTreatmentLedger();
                    $ledger->patient_id=$patient_id;
                    $ledger->treatment_id=$treatment_id;
                    $ledger->therapist_id= $sessionData['therapist_id'];
                    $ledger->iOrderDetailId=0;
                    $ledger->opening_balance=0;
                    $ledger->credit_balance=0;
                    $ledger->debit_balance=0;
                    $ledger->closing_balance=0;
                    $ledger->save();
                    
                   
                  		
                }
                     return response()->json([
                        'status' => 'success',
                        'patient_schedule_id'=>$id,
                        'message' => 'Patient Schedule Added successfully'
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
    public function billId_create(Request $request)
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
                    $maxBillId = SettingBillId::max('billId');

                if ($request->billId > $maxBillId) 
                {

                    $billidd=new SettingBillId();
                    $billidd->bill_prefix=$request->bill_prefix;
                    $billidd->billId=$request->billId;
                    $billidd->save();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Billid Created Successfully',

                    ]);

                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'The billId must be greater than the current maximum value "'.$maxBillId.'" in the database.'
                    ], 401);
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
    public function billId_update(Request $request)
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
                    $maxBillId = SettingBillId::max('billId');

                if ($request->billId > $maxBillId) 
                {
                    $id=$request->id;
                    
                     $billidd = SettingBillId::where('id', $id)->first();

                    if ($billidd) {
                        $billidd->bill_prefix=$request->bill_prefix;
                        $billidd->billId=$request->billId;
                        $billidd->save();
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Billid Updated Successfully',

                    ]);

                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'The billId must be greater than the current maximum value "'.$maxBillId.'" in the database.'
                    ], 401);
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
    public function billId_delete(Request $request)
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
                    
                    $udata = SettingBillId::where(["id"=>$request->id])->delete();

                     
                         return response()->json([
                            'status' => 'success',
                            'message' => 'Billid Deleted successfully'
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
    public function delete_patient_document(Request $request)
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
                    
                    $doc=PatientDocument::where(["document_id"=>$request->document_id])->first();
                    if(!empty($doc))
                    {

                        $udata = PatientDocument::where(["document_id"=>$request->document_id])->delete();

                         
                             return response()->json([
                                'status' => 'success',
                                'message' => 'Patient Document Deleted successfully'
                            ]);
                     }else{
                         return response()->json([
                                'status' => 'error',
                                'message' => 'Document id not Found',
                        ], 401);
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
    public function patient_in_store(Request $request)
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
                     $today = date('Y-m-d');

                // Fetch the schedule linked to this request
                $schedule = PatientSchedule::where(['patient_schedule_id' => $request->patient_schedule_id])->first();

                // Fetch all schedules under the same orderId
                $relatedSchedules = PatientSchedule::where(['orderId' => $schedule->orderId])->pluck('patient_schedule_id');
                // Count all inpatients under these schedules for today's date
                $inpatientcount = PatientIn::whereIn('patientin.patient_schedule_id', $relatedSchedules)
                    ->whereDate('inDateTime', $today)
                    ->count();

                // Fetch the suggested treatment to get the available sessions
                $suggested = PatientSuggestedTreatment::where('patient_id', $schedule->patient_id)
                    ->where(['treatment_id' => $schedule->treatment_id, 'iOrderId' => $schedule->orderId, 'isActive' => 1])
                    ->first();

                // Check if the patient is already checked in today
                $inpatient = PatientIn::where([
                        'patient_id' => $request->patient_id,
                        'therapist_id' => $request->therapist_id,
                        'treatment_id' => $request->treatment_id
                    ])
                    ->whereDate('inDateTime', $today)
                    ->where('status', 1)
                    ->where('leave', 0)
                    ->first();

                // Allow check-in only if the total inpatient count is within the session limit
                if ($inpatientcount < $suggested->iAvailableSession) 
                {
                    if (!$inpatient) {
                        $GroupSession = $request->isGroupSession == 1 ? 1 : 0;

                        $PatientIn = new PatientIn();
                        $PatientIn->patient_id = $request->patient_id;
                        $PatientIn->inDateTime = date('Y-m-d H:i:s');
                        $PatientIn->therapist_id = $request->therapist_id;
                        $PatientIn->treatment_id = $request->treatment_id;
                        $PatientIn->isGroupSession = $GroupSession;
                        $PatientIn->status = 0;
                        $PatientIn->leave = 0;
                        $PatientIn->patient_schedule_id = $request->patient_schedule_id;
                        $PatientIn->save();

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Patient In successfully'
                        ]);
                    }

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Patient is already in or not eligible for check-in'
                    ]);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Patient Session Not Available'
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
    public function delete_treatment(Request $request)
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
                    
                    $detail =OrderDetail::where(["iOrderId"=>$request->order_id,'iOrderDetailId'=>$request->order_detail_id])->first();
                    $udata = Order::where(["iOrderId"=>$request->order_id])->first();
                    $orderdata = OrderDetail::where(["iOrderId"=>$request->order_id])->count();
                    
                    if($orderdata != 0)
                    {
                        $total=$udata->iNetAmount - $detail->iAmount;
                        $iAmount=$udata->iAmount - $detail->iAmount;
                        $DueAmount=$udata->DueAmount - $detail->iAmount;
                    
                         $data = array(
                        'iNetAmount'=>$total,
                        'iAmount'=>$iAmount,
                        'DueAmount'=>$DueAmount,
                        );
                        Order::where("iOrderId","=",$request->order_id)->update($data);
        
                    }
                    if($orderdata == 1)
                    {
                        $udata = Order::where(["iOrderId"=>$request->order_id])->delete();
                    }
                    $suggested=PatientSuggestedTreatment::where(['iOrderId'=>$request->order_id,'iOrderDetailId'=>$request->order_detail_id])->delete();

                    $detaildata = OrderDetail::where(["iOrderId"=>$request->order_id,'iOrderDetailId'=>$request->order_detail_id])->delete();
                    $paymentdata = OrderPayment::where(["iOrderId"=>$request->order_id,'orderDetailId'=>$request->order_detail_id])->delete();
                    
                    $patientScheduleIds = $request->patient_schedule_id; // Get array of schedule IDs from the request

                    if (!empty($patientScheduleIds) && is_array($patientScheduleIds)) {
                        $schedule_delete = PatientSchedule::whereIn('patient_schedule_id', $patientScheduleIds)->delete(); // Replace 'id' with the correct column name
                    }

                         return response()->json([
                            'status' => 'success',
                            'message' => 'Patient Treatment Deleted successfully'
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
     public function edit_my_treatment_list(Request $request)
    {
        try
        {
             $id=$request->patient_schedule_id;


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

                    $schedule = PatientSchedule::where('patient_schedule_id', $id)->first();

                    if ($schedule) {
                        $schedule->schedule_start_time=$request->start_time;
                        $schedule->schedule_end_time=$request->end_time;
                        $schedule->day=$request->day;
                        $schedule->save();
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Schedule Updated Successfully',

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

        public function renew_package(Request $request)
        {
            try {
                $user = auth()->guard('api')->user();

                if (!$user) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User is not Authorised.',
                    ], 401);
                }

                $request->validate([
                    'device_token' => 'required|string',
                    'order_id' => 'required|integer',
                    'order_detail_id' => 'required|integer',
                ]);

                if ($request->device_token != $user->device_token) {
                    return response()->json([
                        "ErrorCode" => "1",
                        'Status' => 'Failed',
                        'Message' => 'Device Token Not Match',
                    ], 401);
                }

                $orderId = (int) $request->order_id;
                $orderDetailId = (int) $request->order_detail_id;

                $result = DB::transaction(function () use ($orderId, $orderDetailId) {

                    // ✅ 1) Fetch base order + detail
                    $order = Order::where('iOrderId', $orderId)->firstOrFail();

                    $orderDetail = OrderDetail::where('iOrderDetailId', $orderDetailId)
                        ->where('iOrderId', $orderId)
                        ->firstOrFail();

                    // ✅ 2) Fetch ALL schedules + ALL treatments
                    $patientSchedules = PatientSchedule::where('orderId', $orderId)->get();

                    $patientTreatments = PatientSuggestedTreatment::where('iOrderId', $orderId)
                        ->where('iOrderDetailId', $orderDetailId)
                        ->get();

                    // ✅ 3) Create new order
                    $newOrder = $order->replicate();
                    $newOrder->iOrderId = null;
                    $newOrder->iOrderId = null;
                    $newOrder->DueAmount = $order->iAmount;
                    $newOrder->created_at = now();
                    $newOrder->updated_at = now();
                    $newOrder->save();

                    // ✅ 4) Create new order detail
                    $newOrderDetail = $orderDetail->replicate();
                    $newOrderDetail->iOrderDetailId = null;
                    $newOrderDetail->iOrderId = $newOrder->iOrderId;
                    $newOrderDetail->iDueAmount = $orderDetail->iAmount;
                    $newOrderDetail->save();

                    // ✅ 5) Duplicate ALL PatientSchedule rows
                    $newScheduleIds = [];
                    foreach ($patientSchedules as $schedule) {
                        $newSchedule = $schedule->replicate();
                        $newSchedule->patient_schedule_id = null; // <-- change if your PK name differs
                        $newSchedule->orderId = $newOrder->iOrderId;
                        $newSchedule->save();

                        $newScheduleIds[] = $newSchedule->patient_schedule_id;
                    }

                    // ✅ 6) Duplicate ALL PatientSuggestedTreatment rows
                    $newTreatmentIds = [];
                    foreach ($patientTreatments as $treatment) {
                        $newTreatment = $treatment->replicate();
                        $newTreatment->PatientSTreatmentId = null; // <-- change if your PK name differs
                        $newTreatment->iOrderId = $newOrder->iOrderId;
                        $newTreatment->iOrderDetailId = $newOrderDetail->iOrderDetailId;
                        $newTreatment->iUsedSession = 0;
                        $newTreatment->iAvailableSession = $orderDetail->iSession;
                        $newTreatment->save();

                        $newTreatmentIds[] = $newTreatment->PatientSTreatmentId;
                    }

                    return [
                        'new_order_id' => $newOrder->iOrderId,
                        'new_order_detail_id' => $newOrderDetail->iOrderDetailId,
                        'new_patient_schedule_ids' => $newScheduleIds,
                        'new_patient_treatment_ids' => $newTreatmentIds,
                    ];
                });

                return response()->json([
                    'status' => 'success',
                    'message' => 'Package Renew Successfully',
                    'data' => $result,
                ], 200);

            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Record not found for given ids.',
                ], 404);

            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 'error',
                    'message' => $th->getMessage(),
                ], 500);
            }
        }


}
