<?php
namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Session;

use App\Models\Order;
use App\Models\Patient;
use App\Models\OrderDetail;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use Barryvdh\DomPDF\Facade\Pdf;


class InvoiceController extends Controller
{

	public function invoice_pdf(Request $request)
	{
				
	try
        {
      	$clinic_id = $request->clinic_id;
	  	$patient_id = $request->patient_id;
	  	$order_id = $request->order_id;

		$patientData = Patient::where(['patient_id'=> $patient_id])->first();

		$patientMobileNo = $patientData->phone;
	    $netAmount = 0;
	    $discountAmount = 0;
	    $totalAmount = 0;


		$TreatmentData = Order::where(['patientordermaster.patient_id' => $patient_id,'patientordermaster.iOrderId'=>$order_id])
		->join('patient_master', 'patient_master.patient_id', '=', 'patientordermaster.patient_id')
		->join('patientorderdetail', 'patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')
		->join('treatment_master', 'treatment_master.treatment_id', '=', 'patientorderdetail.iTreatmentId')
		->first();
		
		if(!empty($TreatmentData))
		{	
			$orderDetail=OrderDetail::select('patientorderdetail.*',DB::raw("(select treatment_name from treatment_master where patientorderdetail.iTreatmentId=treatment_master.treatment_id limit 1) as treatment_name")
				,DB::raw("(select per_session_amount from plan_master where patientorderdetail.iPlanId=plan_master.plan_id limit 1) as per_session_amount"))->where(['iOrderId'=>$TreatmentData->iOrderId])->get();
			foreach ($orderDetail as $key => $val) 
			{
				 $totalPaid = OrderPayment::where('iOrderId', $order_id)->sum('Amount');

						$netAmount += intval($TreatmentData->iAmount);
						$discountAmount += intval($TreatmentData->iDiscount);
						$totalAmount += intval($TreatmentData->iNetAmount);

						 $payment = OrderPayment::where(['iOrderId' => $order_id])->get();

				        $arr2 = [];
				        foreach ($payment as $p) {
				            $arr2[]= array(
				                "payment_date" => date('d-m-Y', strtotime($p->PaymentDateTime)),
				                "receipt_no" => $p->receipt_no,
				                "payment_type" => $p->payment_mode,
				                "paid_amount" => $p->Amount,
				           );
				        }
						$arr[] = array(
							"patient_name" => $TreatmentData->patient_first_name .' '.$TreatmentData->patient_last_name,
							"mobile_no" => $TreatmentData->phone,
							"case_no" => $TreatmentData->patient_case_no	,
							"patient_address" => $TreatmentData->address,
							// "treatment_date" => $TreatmentData->treatment_date,
							"treatment_name" => $val->treatment_name,
							"amount" => $val->per_session_amount,
							"net_amount" => $TreatmentData->iAmount,
							"discount_amount" => $TreatmentData->iDiscount,
							"total_amount" => $TreatmentData->iNetAmount,
							"no_of_session" => $val->iSession,
							//"payments" => $arr2  // Nesting payments inside each treatment entry
							"bill_date" => $TreatmentData->created_at,

						);
			}
		}else{
			$arr[] = "";
		}						
						
				  
						//$key = $_ENV['WHATSAPPKEY'];
						$msg = "Dear User, Please find attached bill of treatments.";
						$fileName = $patientData->patient_case_no."_".date('d-m-Y');
						 

						$pdf = PDF::loadView('treatmentinvoice',['Treatments' => $arr,'netAmount' => $netAmount,'discountAmount' => $discountAmount,'totalAmount' => $totalAmount,"total_paid" => $totalPaid,"payments"=>$arr2]);


						$content = $pdf->download()->getOriginalContent();

                        Storage::disk('public')->put('bills/' . $fileName . '.pdf', $content);

                        // Ensure the directory exists on the production server
                        $productionPath = '/home1/getdemo/public_html/vrajPhysio/bills/' . $fileName . '.pdf';
                        if (!file_exists(dirname($productionPath))) {
                            mkdir(dirname($productionPath), 0777, true);  // Create directory if it doesn't exist
                        }
                        $pdf->save($productionPath);

				        $billFile = asset('bills/'. $fileName. '.pdf');
    						
                        if($request->flag == 1)
						{
    						//return $pdf->download($fileName . '.pdf');
    						$users = new User();
    						$status = $users->sendWhatsappMessage($patientMobileNo,$key,$msg,$billFile);
    						//dd($status->status);
    					
    						$statusofMessage = $status->status;
    						//$Response = $status->response;
    					
    						if($statusofMessage == "success"){
    							return response()->json([
    								'status' => 'success',
    								'pdfFileUrl' => $billFile,
    								'message' => 'Quotation sent on your registered mobile number.',
    							], 401);
    						}else{
    							
    							return response()->json([
    								'status' => 'error',
    								'message' => $Response.'.Please contact admin.',
    							], 401);
    						}
					   }if($request->flag == 0)
					   {
					       return response()->json([
    								'status' => 'success',
    								'pdfFileUrl' => $billFile,
    								'message' => 'Pdf Downloaded Successfully.',
    							], 401);
					  }
						
					return response()->json([
								'status' => 'success',
								'Treatments' => $arr
							], 401);


		} catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
	}
	public function payment_detail_pdf(Request $request)
	{
		try
       {
      	$clinic_id = $request->clinic_id;
	  	$patient_id = $request->patient_id;
	  	$order_id = $request->order_id;

		$patientData = Patient::where(['patient_id'=> $patient_id])->first();

		$patientMobileNo = $patientData->phone;
	    $netAmount = 0;
	    $discountAmount = 0;
	    $totalAmount = 0;


		$TreatmentData = Order::where(['patientordermaster.patient_id' => $patient_id,'patientordermaster.iOrderId'=>$order_id])
		->join('patient_master', 'patient_master.patient_id', '=', 'patientordermaster.patient_id')
		->join('patientorderdetail', 'patientorderdetail.iOrderId', '=', 'patientordermaster.iOrderId')
		->join('treatment_master', 'treatment_master.treatment_id', '=', 'patientorderdetail.iTreatmentId')
		->first();
		
		if(!empty($TreatmentData))
		{	
			$orderDetail=OrderDetail::select('patientorderdetail.*',DB::raw("(select treatment_name from treatment_master where patientorderdetail.iTreatmentId=treatment_master.treatment_id limit 1) as treatment_name"))->where(['iOrderId'=>$TreatmentData->iOrderId])->get();
			foreach ($orderDetail as $key => $val) 
			{
				 $totalPaid = OrderPayment::where('iOrderId', $order_id)->sum('Amount');

						$netAmount += intval($TreatmentData->iAmount);
						$discountAmount += intval($TreatmentData->iDiscount);
						$totalAmount += intval($TreatmentData->iNetAmount);

						 $payment = OrderPayment::where(['iOrderId' => $order_id])->get();

				        $arr2 = [];
				        foreach ($payment as $p) {
				            $arr2[]= array(
				                "payment_date" => date('d-m-Y', strtotime($p->PaymentDateTime)),
				                "receipt_no" => $p->receipt_no  ,
				                "payment_type" => $p->payment_mode,
				                "paid_amount" => $p->Amount,
				           );
				        }
						
			}
		}else{
			$arr2[] = "";
		}						
						
				  
						//$key = $_ENV['WHATSAPPKEY'];
						$msg = "Dear User, Please find attached bill of treatments.";
						$fileName = $patientData->patient_case_no."_".date('d-m-Y');
						 

						$pdf = PDF::loadView('paymentinvoice',['payments'=>$arr2]);


						$content = $pdf->download()->getOriginalContent();

                        Storage::disk('public')->put('bills/' . $fileName . '.pdf', $content);

                        // Ensure the directory exists on the production server
                        $productionPath = '/home1/getdemo/public_html/vrajPhysio/bills/' . $fileName . '.pdf';
                        if (!file_exists(dirname($productionPath))) {
                            mkdir(dirname($productionPath), 0777, true);  // Create directory if it doesn't exist
                        }
                        $pdf->save($productionPath);

				        $billFile = asset('bills/'. $fileName. '.pdf');
    						
                        if($request->flag == 1)
						{
    						//return $pdf->download($fileName . '.pdf');
    						$users = new User();
    						$status = $users->sendWhatsappMessage($patientMobileNo,$key,$msg,$billFile);
    						//dd($status->status);
    					
    						$statusofMessage = $status->status;
    						//$Response = $status->response;
    					
    						if($statusofMessage == "success"){
    							return response()->json([
    								'status' => 'success',
    								'pdfFileUrl' => $billFile,
    								'message' => 'Quotation sent on your registered mobile number.',
    							], 401);
    						}else{
    							
    							return response()->json([
    								'status' => 'error',
    								'message' => $Response.'.Please contact admin.',
    							], 401);
    						}
					   }if($request->flag == 0)
					   {
					       return response()->json([
    								'status' => 'success',
    								'pdfFileUrl' => $billFile,
    								'message' => 'Pdf Downloaded Successfully.',
    							], 401);
					  }
						
					return response()->json([
								'status' => 'success',
								'Payments' => $arr2
							], 401);


		} catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }

	}
}