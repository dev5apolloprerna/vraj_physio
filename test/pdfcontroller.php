	use Barryvdh\DomPDF\Facade\Pdf;


	

	public function treatmentDataForPrintScreen(Request $request){
				$treatments = new Treatments();
				
				  $suggestedTreatmentIds = $request->suggestedIds;
				  $clinic_id = $request->clinic_id;
				  $branch_id = $request->branch_id;
				  $patient_id = $request->patient_id;

				  $patientData = Patient::where(['patient_id'=> $patient_id])->first();
				  $patientMobileNo = $patientData->mobile_no;
				  $netAmount = 0;
				  $discountAmount = 0;
				  $totalAmount = 0;
				  foreach($suggestedTreatmentIds as $suggestedTreatmentId){


						$TreatmentData = SuggestedTreatments::select(
						'users.user_name as doctor_name',
						'users.address as address',
						'patients.name_prefix as name_prefix',
						'patients.name as patient_name',
						'patients.mobile_no as mobile_no',
						'patients.case_no as case_no',
						'patients.address as patient_address',
						 DB::raw('DATE_FORMAT(suggested_treatments.treatment_date, "%d-%M-%Y") as treatment_date'),
						'treatments.name as treatment_name',
						'suggested_treatments.selected_teeth',
						'suggested_treatments.selected_teeth_count',
						'suggested_treatments.rate',
						'suggested_treatments.discount',
						'suggested_treatments.discount_type',
						'suggested_treatments.total_amount',
						'suggested_treatments.discount_amount',
						'suggested_treatments.amount',
						'suggested_treatments.treatment_status',
						'suggested_treatments.strnote'
						
						)
						->where(['suggested_treatments.suggested_treatment_id' => $suggestedTreatmentId])
						->join('users', 'suggested_treatments.SuggestedBydoctor_id', '=', 'users.user_id')
						->join('treatments', 'suggested_treatments.treatment_id', '=', 'treatments.treatment_id')
						->join('patients', 'suggested_treatments.patient_id', '=', 'patients.patient_id')
						->first();
						
						if(!empty($TreatmentData)){	
						
						$netAmount += intval($TreatmentData->rate);
						$discountAmount += intval($TreatmentData->discount_amount);
						$totalAmount += intval($TreatmentData->total_amount);
						$arr[] = array(
							"doctor_name" => $TreatmentData->doctor_name,
							"address" => $TreatmentData->address,
							"patient_name" => $TreatmentData->patient_name,
							"name_prefix" => $TreatmentData->name_prefix,
							"mobile_no" => $TreatmentData->mobile_no,
							"case_no" => $TreatmentData->case_no,
							"patient_address" => $TreatmentData->patient_address,
							"treatment_date" => $TreatmentData->treatment_date,
							"treatment_name" => $TreatmentData->treatment_name,
							"selected_teeth" => $TreatmentData->selected_teeth,
							"selected_teeth_count" => $TreatmentData->selected_teeth_count,
							"amount" => $TreatmentData->amount,
							"net_amount" => $TreatmentData->rate,
							"discount" => $TreatmentData->discount,
							"discount_amount" => $TreatmentData->discount_amount,
							"total_amount" => $TreatmentData->total_amount
						);
						
						
						}else{
							$arr[] = "";
						}

				  }
				  
						$key = $_ENV['WHATSAPPKEY'];
						$msg = "Dear User, Please find attached bill of treatments.";
						$fileName = $patientData->case_no."_".date('d-m-Y');
						 

						$pdf = PDF::loadView('treatmentinvoice',['Treatments' => $arr,'netAmount' => $netAmount,'discountAmount' => $discountAmount,'totalAmount' => $totalAmount]);
						$content = $pdf->download()->getOriginalContent();
						Storage::put('public/bills/'.$fileName . '.pdf',$content);
						
						//$pdf->save(public_path('assets/bills/')  . $fileName. '.pdf');
						
						if($_SERVER['SERVER_NAME'] == "127.0.0.1"){
							$pdf->save(public_path('assets/bills/')  . $fileName. '.pdf');
						}else {
							$pdf->save(public_path('../../public_html/dentee/assets/bills/')  . $fileName. '.pdf');

						}

						$billFile = asset('assets/bills/'. $fileName. '.pdf');
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
				
						
					return response()->json([
								'status' => 'success',
								'Treatments' => $arr
							], 401);
		
		}