<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CashCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CashLedger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\Branch;

class CashCollectionController extends Controller
{
    // List all cash collections
    public function index(Request $request)
    {
        if(auth()->guard('api')->user()){
            $cashCollections = CashCollection::select("cash_collections.*")
                ->where(['cash_collections.clinic_id' => $request->clinic_id,"isDelete" => 0])
                ->when($request->fromDate, function ($query) use ($request) {
                    return $query->where('cash_collections.collection_date', ">=", date('Y-m-d', strtotime($request->fromDate)));
                })
                ->when($request->toDate, function ($query) use ($request) {
                    return $query->where('cash_collections.collection_date', "<=", date('Y-m-d', strtotime($request->toDate)));
                })
                ->when($request->selected_date, function ($query) use ($request) {
                    return $query->where('cash_collections.collection_date', date('Y-m-d', strtotime($request->selected_date)));
                })
                ->when($request->month, function ($query) use ($request) {
                    return $query->whereMonth('cash_collections.collection_date', $request->month);
                })
                ->when($request->year, function ($query) use ($request) {
                    return $query->whereYear('cash_collections.collection_date', $request->year);
                })
                ->get();
            if (!$cashCollections->isEmpty()) {
                $data = [];
                $totalCollection = 0;
                foreach($cashCollections as $cashCollection){
                    $totalCollection += $cashCollection->amount;
                    $data[] = array(
                        "cash_collection_id" => $cashCollection->id,
                        'amount' => $cashCollection->amount,
                        'collection_date' => date('d-m-Y',strtotime($cashCollection->collection_date)),
                        'clinic_id' => $cashCollection->clinic_id,
                        "received_by" => $cashCollection->received_by
                    );
                }
                return response()->json([
    				'status' => 'success',
    				'message' => 'Cash Collection List',
    				'totalCollection' => $totalCollection,
    				'data' => $data
    		    ]);
            } else {
                return response()->json([
				    'status' => 'error',
    				'message' => 'No Data Found.',
    		    ], 401);      
            }
            //return response()->json(CashCollection::all(), 200);
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }

    // Store a new cash collection
    public function store(Request $request)
    {
        if(auth()->guard('api')->user()){
            $validated = $request->validate([
                'amount' => 'required|integer',
                'collection_date' => 'nullable|date'
            ]);
            $cashLedger = CashLedger::where(["clinic_id" => $request->clinic_id])->orderBy("id","desc")->first();
            if ($cashLedger->cl_amt <= $request->amount) {
                return response()->json([
				    'status' => 'error',
                    'message' => 'The closing amount must be greater than the requested amount.'
                ],401);
            }
            
           $existingCollection = CashCollection::where([
                'clinic_id' => $request->clinic_id,
                'received_by' => $request->received_by
            ])
            ->where('amount', $request->amount)
            ->whereDate('collection_date', $request->collection_date)
            ->first();
            
            if ($existingCollection) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This cash collection appears to be a duplicate. Are you sure you want to proceed?',
                    'duplicate_data' => [
                        'id' => $existingCollection->id,
                        'amount' => $existingCollection->amount,
                        'date' => $existingCollection->collection_date,
                        'received_by' => $existingCollection->received_by ?? 'Unknown'
                    ]
                ], 409); // 409 Conflict status code
            }
            
            $data = array(
                'amount' => $request->amount,
                'collection_date' => date('Y-m-d',strtotime($request->collection_date)),
                'clinic_id' => $request->clinic_id,
                'received_by' => $request->received_by,
                'created_by' => Auth::user()->id ?? 0
            );
    
            $collection = CashCollection::create($data);
            
            $op_amt = $cashLedger->cl_amt ?? 0;
            $cr_amt = 0;
            $dr_amt = $request->amount;
            $cl_amt = $op_amt - $request->amount;
            
            $ledger = array(
                "clinic_id" => $request->clinic_id, 
    	        "op_amt" => $op_amt,
            	"cr_amt" => $cr_amt,
            	"dr_amt" => $dr_amt,
            	"cl_amt" => $cl_amt,
            	"cash_collection_id" => $collection->id,
            	"strIP" => $request->ip(),
            	"created_at" =>date('Y-m-d H:i:s')
            );
            CashLedger::create($ledger);
            
            return response()->json([
				'status' => 'success',
				'message' => 'Cash Collection added successfully.',
		    ],200);
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }

    // Show a specific cash collection
    public function show($id)
    {
        $collection = CashCollection::find($id);

        if (!$collection) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($collection, 200);
    }

    // Update an existing cash collection
    public function update(Request $request)
    {
        if(auth()->guard('api')->user()){
            $collection = CashCollection::find($request->cash_collection_id);
    
            if (!$collection) {
                return response()->json(['message' => 'Not found'], 404);
            }
        
            $validated = $request->validate([
                'amount' => 'sometimes|required|integer',
                'collection_date' => 'nullable|date'
            ]);
            
            
            
            $data = array(
                'amount' => $request->amount,
                'collection_date' => date('Y-m-d',strtotime($request->collection_date)),
                'clinic_id' => $request->clinic_id,
                'received_by' => $request->received_by,
                'updated_by' => Auth::user()->id ?? 0
            );
            
            $collection->update($data);
    
            return response()->json([
    				'status' => 'success',
    				'message' => 'Cash Collection updated successfully.',
    		    ], 200);
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
        
    }

    // Delete a cash collection
    public function destroy(Request $request)
    {
        if(auth()->guard('api')->user()){
            $collection = CashCollection::find($request->cash_collection_id);
            
            $cashLedger = CashLedger::where(["clinic_id" => $collection->clinic_id])->orderBy("id","desc")->first();
            $op_amt = $cashLedger->cl_amt ?? 0;
            $cr_amt = $collection->amount;
            $dr_amt = 0;
            $cl_amt = $op_amt + $collection->amount;
            
            $ledger = array(
                "clinic_id" => $collection->clinic_id, 
    	        "op_amt" => $op_amt,
            	"cr_amt" => $cr_amt,
            	"dr_amt" => $dr_amt,
            	"cl_amt" => $cl_amt,
            	"cash_collection_id" => $collection->id,
            	"strIP" => $request->ip(),
            	"created_at" =>date('Y-m-d H:i:s')
            );
            CashLedger::create($ledger);
            
            if (!$collection) {
                return response()->json(['status' => 'error','message' => 'Not found'], 404);
            }
            $collection->isDelete = 1;
            $collection->save();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Deleted successfully'
            ], 200);
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }
    
    public function resetOpening(Request $request){
        if(auth()->guard('api')->user()){
            $cashLedger = CashLedger::where(["clinic_id" => $request->clinic_id])->orderBy("id","desc")->first();
            $op_amt = $request->amount ?? 0;
            $cr_amt = 0;
            $dr_amt = 0;
            $cl_amt = $request->amount;
            
            $ledger = array(
                "clinic_id" => $request->clinic_id, 
    	        "op_amt" => $op_amt,
            	"cr_amt" => $cr_amt,
            	"dr_amt" => $dr_amt,
            	"cl_amt" => $cl_amt,
            	"strIP" => $request->ip(),
            	"created_at" =>date('Y-m-d H:i:s')
            );
            CashLedger::create($ledger);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Reset Opening successfully'
            ], 200);
            
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }
    
    public function CashCollectionReport(Request $request){
        if(auth()->guard('api')->user()){
            $cashCollections = CashCollection::select()
                ->where(['cash_collections.clinic_id' => $request->clinic_id])
                ->get();
            $OrderWhere = " 1=1 ";
            $ExpensesWhere = " 1=1 ";
            $CollectionWhere = " 1=1";
           
            if(isset($request->clinic_id) && $request->clinic_id != ""){
                $OrderWhere .= " and clinic_id='".$request->clinic_id."'";
                $ExpensesWhere .= " and clinic_id='".$request->clinic_id."'";
                $CollectionWhere .= " and clinic_id='".$request->clinic_id."'";
            }
            
            if(isset($request->fromDate) && $request->fromDate != ""){
                $OrderWhere .= " and DATE_FORMAT(payment_date,'%Y-%m-%d')>=DATE_FORMAT('".date('Y-m-d',strtotime($request->fromDate))."','%Y-%m-%d')";
                $ExpensesWhere .= " and DATE_FORMAT(expense_date,'%Y-%m-%d')>=DATE_FORMAT('".date('Y-m-d',strtotime($request->fromDate))."','%Y-%m-%d')";
                $CollectionWhere .= " and DATE_FORMAT(collection_date,'%Y-%m-%d')>=DATE_FORMAT('".date('Y-m-d',strtotime($request->fromDate))."','%Y-%m-%d')";
            }
            
            if(isset($request->toDate) && $request->toDate != ""){
                $OrderWhere .= " and DATE_FORMAT(payment_date,'%Y-%m-%d')<=DATE_FORMAT('".date('Y-m-d',strtotime($request->toDate))."','%Y-%m-%d')";
                $ExpensesWhere .= " and DATE_FORMAT(expense_date,'%Y-%m-%d')<=DATE_FORMAT('".date('Y-m-d',strtotime($request->toDate))."','%Y-%m-%d')";
                $CollectionWhere .= " and DATE_FORMAT(collection_date,'%Y-%m-%d')<=DATE_FORMAT('".date('Y-m-d',strtotime($request->toDate))."','%Y-%m-%d')";
            }
            if(isset($request->selected_date) && $request->selected_date != ""){
                $OrderWhere .= " and DATE_FORMAT(payment_date,'%Y-%m-%d')=DATE_FORMAT('".date('Y-m-d',strtotime($request->selected_date))."','%Y-%m-%d')";
                $ExpensesWhere .= " and DATE_FORMAT(expense_date,'%Y-%m-%d')=DATE_FORMAT('".date('Y-m-d',strtotime($request->selected_date))."','%Y-%m-%d')";
                $CollectionWhere .= " and DATE_FORMAT(collection_date,'%Y-%m-%d')=DATE_FORMAT('".date('Y-m-d',strtotime($request->selected_date))."','%Y-%m-%d')";
            }
            if(isset($request->month) && $request->month != ""){
                $OrderWhere .= " and MONTH(payment_date)='".$request->month."'";
                $ExpensesWhere .= " and MONTH(expense_date)='".$request->month."'";
                $CollectionWhere .= " and MONTH(collection_date)='".$request->month."'";
            }
            if(isset($request->year) && $request->year != ""){
                $OrderWhere .= " and YEAR(payment_date)='".$request->year."'";
                $ExpensesWhere .= " and YEAR(expense_date)='".$request->year."'";
                $CollectionWhere .= " and YEAR(collection_date)='".$request->year."'";
            }
            
    
                $result = DB::table('branches')
                    ->select([
                        'branch_id',
                        'branch_name',
                        DB::raw("(
                            SELECT IFNULL(SUM(amount), 0)
                            FROM cash_collections
                            WHERE ".$CollectionWhere."
                              AND cash_collections.branch_id = branches.branch_id
                        ) as AdminCashCollection"),
                        DB::raw("(
                            SELECT IFNULL(SUM(amount), 0)
                            FROM order_payment_detail
                            WHERE ".$OrderWhere."
                              AND payment_mode = 1
                              AND order_payment_detail.branch_id = branches.branch_id
                        ) as patientCashCollection"),
                        DB::raw("(
                            SELECT IFNULL(SUM(amount), 0)
                            FROM cash_expenses
                            WHERE ".$ExpensesWhere."
                              AND cash_expenses.branch_id = branches.branch_id
                        ) as CashExpense")
                    ])
                    ->where('clinic_id', $request->clinic_id)
                    ->when($request->branch_id, function ($query) use ($request) {
    					$query->whereIn("branch_id",$request->branch_id);
    				})
                    ->whereNull('deleted_at')
                    ->get();
                //->toSql();
                // dd($result);
            
                // ->where(["cash_collections.branch_id" => $request->branch_id])->get();
            if (!$result->isEmpty()) {
                $data = [];
                foreach($result as $res){
                    $cashOnHand = ($res->patientCashCollection ?? 0) - ($res->AdminCashCollection ?? 0)  - ($res->CashExpense ?? 0);
                    $data[] = array(
                        
                        "AdminCashCollection" => $res->AdminCashCollection ?? 0,
                        'patientCashCollection' => $res->patientCashCollection ?? 0,
                        'CashExpense' => $res->CashExpense ?? 0,
                        'clinic_id' => $res->clinic_id ?? 0,
                        'branch_id' => $res->branch_id ?? 0,
                        'branch_name' => $res->branch_name ?? "",
                        'cashOnHand' => $cashOnHand
                    );
                }
                return response()->json([
    				'status' => 'success',
    				'message' => 'Cash Collection Report List',
    				'data' => $data
    		    ]);
            } else {
                return response()->json([
				    'status' => 'error',
    				'message' => 'No Data Found.',
    		    ], 401);      
            }
            //return response()->json(CashCollection::all(), 200);
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }
    
    public function CashLedger(Request $request){
        if(auth()->guard('api')->user())
        {
                $datas = DB::table('cash_ledgers')
                ->select(
                    DB::raw("DATE(created_at) AS transaction_date"),
                    DB::raw("SUM(
                        CASE WHEN order_id > 0 THEN cr_amt - dr_amt ELSE 0 END 
                    ) AS cash_collection"),
                    DB::raw("SUM(CASE WHEN cash_expense_id > 0 THEN dr_amt - cr_amt ELSE 0 END) AS cash_expense"),
                    DB::raw("SUM(CASE WHEN cash_collection_id > 0 THEN dr_amt - cr_amt ELSE 0 END) AS cash_pickup"),
                    DB::raw("(
                            SELECT cl.cl_amt 
                            FROM cash_ledgers AS cl 
                            WHERE cl.clinic_id = cash_ledgers.clinic_id 
                            AND DATE(cl.created_at) = DATE(cash_ledgers.created_at)
                            ORDER BY cl.id DESC 
                            LIMIT 1
                        ) AS cash_on_hand")
                )
                ->where('clinic_id', $request->clinic_id)
				->when($request->fromDate, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),'>=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->fromDate))."','%Y-%m-%d')"));
			    })
			    ->when($request->toDate, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),'<=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->toDate))."','%Y-%m-%d')"));
			    })
			    ->when($request->selected_date, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),'=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->selected_date))."','%Y-%m-%d')"));
			    })
			    ->when($request->month, function ($query) use ($request) {
					$query->where(DB::raw("MONTH(created_at)"),'=',$request->month);
				})
    			->when($request->year, function ($query) use ($request) {
					$query->where(DB::raw("YEAR(created_at)"),'=',$request->year);
				})
				->groupBy(DB::raw('DATE(created_at)'))
				->whereRaw("id >= (
                    SELECT MAX(id) 
                    FROM cash_ledgers AS cl 
                    WHERE cl.clinic_id = cash_ledgers.clinic_id 
                    AND order_id = 0 
                    AND cash_expense_id = 0 
                    AND cash_collection_id = 0
                )")
                //->orderByDesc('transaction_date')
                ->orderBy('transaction_date','asc')
                ->get()->toArray();
                
                $pdffile = $request->pdffile;
                $Duration = "";
    			if(isset($request->fromDate) && $request->toDate != ""){
    			    $Duration .= date('d-m-Y',strtotime($request->fromDate)) ." To ". date('d-m-Y',strtotime($request->toDate));
    			}
    			
    			if(isset($request->selected_date) && $request->selected_date != ""){
    			    $Duration .= $request->selected_date;
    			}
    			
    			if((isset($request->month) && $request->month != "") && isset($request->year) && $request->year != ""){
    			    $Duration .= $request->month ."-".$request->year;
    			}
    			
                if(!empty($datas)){
                    $data = [];
                    $cash_on_hand = 0;
                    foreach($datas as $details){
                        //$cash_on_hand += $details->cash_collection - $details->cash_expense - $details->cash_pickup + $details->cash_on_hand;
                        $cash_on_hand = $details->cash_on_hand;
                        $data[] = array(
                            "transaction_date" => $details->transaction_date,
                            "cash_collection" => $details->cash_collection,
                            "cash_expense" => $details->cash_expense,
                            "cash_pickup" => $details->cash_pickup,
                            "cash_on_hand" => $cash_on_hand
                        );
                    }
                	if($pdffile == 1 && !empty($data)){
                	    $branch = [];
                       
        				$pdf = PDF::loadView('cash_ledger',['Collection' => $data,"Duration" => $Duration,"branch" => $branch]);
        				$fileName = date('d-m-Y')."_cash_ledger";
        				$content = $pdf->download()->getOriginalContent();
        				Storage::put('public/assets/cash_ledger/'.$fileName . '.pdf',$content);
        				
        				if($_SERVER['SERVER_NAME'] == "127.0.0.1"){
        					$pdf->save(public_path('assets/cash_ledger/')  . $fileName. '.pdf');	
        				}else {
        					$pdf->save(public_path('../../vgdcapp.vrajdentalclinic.com/assets/cash_ledger/')  . $fileName. '.pdf');
        				}
        		
        				$dailycollectionFile = asset('assets/cash_ledger/'. $fileName. '.pdf');
        				return response()->json([
    						'status' => 'success',
    						'dailycollectionFile' => $dailycollectionFile,
    						'message' => 'Cash Ledger List',
            				'data' => $data
    					]);
            				
        			}else{
                        return response()->json([
            				'status' => 'success',
            				'message' => 'Cash Ledger List',
            				'data' => $data
            		    ]); 
        			}
                } else {
                    return response()->json([
    				    'status' => 'error',
        				'message' => 'No Data Found.',
        		    ], 401);   
                }
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }
    
    
    public function CashList(Request $request){
        if(auth()->guard('api')->user())
            {
                 $User = auth()->guard('api')->user();
                 
                    

            $data = DB::table('cash_ledgers')
                ->select( DB::raw("DATE(created_at) AS transaction_date"),DB::raw("op_amt AS cash_on_hand")
                )
                ->where('clinic_id', $request->clinic_id)
                ->where('cash_collection_id', 0)
                ->where('order_id', 0)
                ->where('cash_expense_id', 0)
                ->where('cash_collection_id', 0)
                
				->when($request->fromDate, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),'>=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->fromDate))."','%Y-%m-%d')"));
			    })
			    ->when($request->toDate, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),'<=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->toDate))."','%Y-%m-%d')"));
			    })
			    ->when($request->selected_date, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),'=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->selected_date))."','%Y-%m-%d')"));
			    })
			    ->when($request->month, function ($query) use ($request) {
					$query->where(DB::raw("MONTH(created_at)"),'=',$request->month);
				})
    			->when($request->year, function ($query) use ($request) {
					$query->where(DB::raw("YEAR(created_at)"),'=',$request->year);
				})
                ->orderBy('transaction_date','asc')
                ->get()->toArray();

                if(!empty($data)){
                    return response()->json([
        				'status' => 'success',
        				'message' => 'Cash Reset List',
        				'data' => $data
        		    ]); 
                } else {
                     return response()->json([
    				    'status' => 'error',
        				'message' => 'No Data Found.',
        		    ], 401);   
                }
        }else{
                return response()->json([
                        'status' => 'error',
                        'message' => 'User is not Authorised.',
                ], 401);
            }
    }
}
