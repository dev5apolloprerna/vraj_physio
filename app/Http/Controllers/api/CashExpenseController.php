<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CashExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CashLedger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CashExpenseController extends Controller
{
    public function index(Request $request)
    {
        if(auth()->guard('api')->user()){
            $cashExpenses = CashExpense::select("cash_expenses.*")
                ->where(["cash_expenses.clinic_id" => $request->clinic_id,"isDelete" => 0])
                
				->when($request->fromDate, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(expense_date,'%Y-%m-%d')"),'>=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->fromDate))."','%Y-%m-%d')"));
			    })
			    ->when($request->toDate, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(expense_date,'%Y-%m-%d')"),'<=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->toDate))."','%Y-%m-%d')"));
			    })
			    ->when($request->selected_date, function ($query) use ($request) {
						$query->where(DB::raw("DATE_FORMAT(expense_date,'%Y-%m-%d')"),'=',DB::raw("DATE_FORMAT('".date('Y-m-d',strtotime($request->selected_date))."','%Y-%m-%d')"));
			    })
			    ->when($request->month, function ($query) use ($request) {
					$query->where(DB::raw("MONTH(expense_date)"),'=',$request->month);
				})
    			->when($request->year, function ($query) use ($request) {
					$query->where(DB::raw("YEAR(expense_date)"),'=',$request->year);
				})
				->orderBy('cash_expenses.expense_date','asc')
				// ->toSql();
                ->get();
				// dd($cashExpenses);
            if (!$cashExpenses->isEmpty()) {
                $data = [];
                foreach($cashExpenses as $cashExpense){
                    $data[] = array(
                        "cash_expense_id" => $cashExpense->id,
                        'cash_expense' => $cashExpense->cash_expense,
                        'amount' => $cashExpense->amount,
                        'expense_date' => date('d-m-Y',strtotime($cashExpense->expense_date)),
                        'clinic_id' => $cashExpense->clinic_id,
                        'strDescription' => $cashExpense->strDescription
                    );
                }
                
                $pdffile = $request->pdffile;
                if($pdffile == 1 && !empty($data)){
                    
                    $pdf = PDF::loadView('cash_expenses_voucher_list',['Collection' => $data]);
    				$fileName = date('d-m-Y')."_cash_expenses_voucher";
    				$content = $pdf->download()->getOriginalContent();
    				Storage::put('public/assets/cash_expenses_voucher/'.$fileName . '.pdf',$content);
    				
    				if($_SERVER['SERVER_NAME'] == "127.0.0.1"){
    					$pdf->save(public_path('assets/cash_expenses_voucher/')  . $fileName. '.pdf');	
    				}else {
    					$pdf->save(public_path('../../vgdcapp.vrajdentalclinic.com/assets/cash_expenses_voucher/')  . $fileName. '.pdf');
    				}
    		
    				$dailycollectionFile = asset('assets/cash_expenses_voucher/'. $fileName. '.pdf');
    				return response()->json([
    					'status' => 'success',
    					'dailycollectionFile' => $dailycollectionFile,
    					'data' => $data,
    					'message' => 'Cash Expense List',
    				]);
                } else {
                    return response()->json([
        				'status' => 'success',
        				'message' => 'Cash Expense List',
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

    public function store(Request $request)
    {
        if(auth()->guard('api')->user()){
            $validated = $request->validate([
                'cash_expense' => 'nullable|string|max:250',
                'amount' => 'required|integer',
                'expense_date' => 'nullable|date',
                'clinic_id' => 'required|integer',
            ]);
            
            $cashExpense =CashExpense::where([
                'cash_expense' => $request->cash_expense,
                'clinic_id' => $request->clinic_id,
                'strDescription' => $request->strDescription
                ])
                ->where('amount', $request->amount)
                ->whereDate('expense_date', $request->expense_date)
                ->first();
            if ($cashExpense) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This Cash Expense appears to be a duplicate. Are you sure you want to proceed?',
                    'duplicate_data' => [
                        'id' => $cashExpense->id,
                        'amount' => $cashExpense->amount,
                        'date' => $cashExpense->expense_date,
                        'received_by' => $cashExpense->cash_expense ?? 'Unknown'
                    ]
                ], 409); // 409 Conflict status code
            }
            
            $data = array(
                'cash_expense' => $request->cash_expense,
                'amount' => $request->amount,
                'expense_date' => date('Y-m-d',strtotime($request->expense_date)),
                'clinic_id' => $request->clinic_id,
                'strDescription' => $request->strDescription,
                'created_by' => Auth::user()->id ?? 0
            );
            $expense = CashExpense::create($data);
            
            $cashLedger = CashLedger::where(["clinic_id" => $request->clinic_id])->orderBy("id","desc")->first();
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
            	"cash_expense_id" => $expense->id,
            	"strIP" => $request->ip(),
            	"created_at" =>date('Y-m-d H:i:s')
            );
            CashLedger::create($ledger);
    
            return response()->json([
				'status' => 'success',
				'message' => 'Cash Expense added successfully.',
		    ]);
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }

    public function show(Request $request,$id)
    {
        $expense = CashExpense::find($id);

        if (!$expense) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($expense, 200);
    }

    public function update(Request $request)
    {
        if(auth()->guard('api')->user()){
            $expense = CashExpense::find($request->cash_expense_id);
    
            if (!$expense) {
                return response()->json(['message' => 'Not found'], 404);
            }
    
            $validated = $request->validate([
                'cash_expense' => 'nullable|string|max:250',
                'amount' => 'sometimes|required|integer',
                'expense_date' => 'nullable|date',
                'clinic_id' => 'sometimes|required|integer',
            ]);
            
            $data = array(
                'cash_expense' => $request->cash_expense,
                'amount' => $request->amount,
                'expense_date' => date('Y-m-d',strtotime($request->expense_date)),
                'clinic_id' => $request->clinic_id,
                'strDescription' => $request->strDescription,
                'updated_by' => Auth::user()->id ?? 0
            );
    
            $expense->update($validated);
    
            return response()->json([
				'status' => 'success',
				'message' => 'Cash Expense updated successfully.',
		    ], 200);
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }

    public function destroy(Request $request)
    {
        if(auth()->guard('api')->user()){
            $expense = CashExpense::find($request->cash_expense_id);
            
            $cashLedger = CashLedger::where(["clinic_id" => $expense->clinic_id])->orderBy("id","desc")->first();
            $op_amt = $cashLedger->cl_amt ?? 0;
            $cr_amt = $expense->amount;
            $dr_amt = 0;
            $cl_amt = $op_amt + $expense->amount;
            
            $ledger = array(
                "clinic_id" => $expense->clinic_id, 
    	        "op_amt" => $op_amt,
            	"cr_amt" => $cr_amt,
            	"dr_amt" => $dr_amt,
            	"cl_amt" => $cl_amt,
            	"cash_expense_id" => $expense->id,
            	"strIP" => $request->ip(),
            	"created_at" =>date('Y-m-d H:i:s')
            );
            CashLedger::create($ledger);
            
            if (!$expense) {
                return response()->json(['message' => 'Not found','status' => 'error'], 404);
            }
            $expense->isDelete = 1;
            $expense->save();
    
            return response()->json(['message' => 'Deleted','status' => 'success'], 200);
        } else {
            return response()->json([
				'status' => 'error',
				'message' => 'User is not Authorised.',
		    ], 401);
        }
    }
    
    public function CashExpensesVoucher(Request $request){
        
        if(auth()->guard('api')->user()){
            $cashExpense = CashExpense::select("cash_expenses.*")
                ->where(["isDelete" => 0,"id" => $request->cash_expense_id])->first();
            if (is_object($cashExpense)) {
                $data = array(
                    "cash_amount" => $cashExpense->amount,
                    "paid_to" => $cashExpense->cash_expense,
                    "date" => date('d-M-y',strtotime($cashExpense->expense_date)),
                );
                $pdf = PDF::loadView('cash_expenses_voucher',['Collection' => $data]);
				$fileName = date('d-m-Y')."_cash_expenses_voucher";
				$content = $pdf->download()->getOriginalContent();
				Storage::put('public/assets/cash_expenses_voucher/'.$fileName . '.pdf',$content);
				
				if($_SERVER['SERVER_NAME'] == "127.0.0.1"){
					$pdf->save(public_path('assets/cash_expenses_voucher/')  . $fileName. '.pdf');	
				}else {
					$pdf->save(public_path('../../vgdcapp.vrajdentalclinic.com/assets/cash_expenses_voucher/')  . $fileName. '.pdf');
				}
		
				$dailycollectionFile = asset('assets/cash_expenses_voucher/'. $fileName. '.pdf');
				return response()->json([
					'status' => 'success',
					'dailycollectionFile' => $dailycollectionFile,
					'message' => 'cash_expenses_voucher'
				]);
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
}


