<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\ListingController;
use App\Http\Controllers\api\CRUDController;
use App\Http\Controllers\api\OrderController;
use App\Http\Controllers\api\InvoiceController;
use App\Http\Controllers\api\PatientSessionController;
use App\Http\Controllers\api\ReportController;
use App\Http\Controllers\api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/change_password', [AuthController::class, 'change_password'])->name('change_password');

/*---------------------------- ALL Listing START----------------------------------***/

Route::post('/patient_list', [ListingController::class, 'patient_list'])->name('patient_list');
Route::post('/patient_detail', [ListingController::class, 'patient_detail'])->name('patient_detail');
Route::post('/patient_documents', [ListingController::class, 'patient_documents'])->name('patient_documents');
Route::post('/patient_daily_activity', [ListingController::class, 'patient_daily_activity'])->name('patient_daily_activity');
Route::post('/patient_consumed_history', [ListingController::class, 'patient_consumed_history'])->name('patient_consumed_history');
Route::post('/history', [ListingController::class, 'history'])->name('history');


Route::post('/plan_list', [ListingController::class, 'plan_list'])->name('plan_list');
Route::post('/employee_list', [ListingController::class, 'employee_list'])->name('employee_list');
Route::post('/inactive_employee_list', [ListingController::class, 'inactive_employee_list'])->name('inactive_employee_list');
Route::post('/designation_list', [ListingController::class, 'designation_list'])->name('designation_list');
Route::post('/refrenceBy_list', [ListingController::class, 'refrenceBy_list'])->name('refrenceBy_list');

Route::post('/tretment_list', [ListingController::class, 'tretment_list'])->name('tretment_list');
Route::post('/treatment_package_list', [ListingController::class, 'treatment_package_list'])->name('treatment_package_list');
Route::post('/get_therepist_from_treatement', [ListingController::class, 'get_therepist_from_treatement'])->name('get_therepist_from_treatement');
Route::post('/therapist_schedule', [ListingController::class, 'therapist_schedule'])->name('therapist_schedule');
Route::post('/login_therapist_schedule', [ListingController::class, 'login_therapist_schedule'])->name('login_therapist_schedule');
Route::post('/team_schedule', [ListingController::class, 'team_schedule'])->name('team_schedule');
Route::post('/therapist_patient_list', [ListingController::class, 'therapist_patient_list'])->name('therapist_patient_list');
Route::post('/therapist_treatment_list', [ListingController::class, 'therapist_treatment_list'])->name('therapist_treatment_list');


Route::post('/my_package', [ListingController::class, 'my_package'])->name('my_package');
Route::post('/my_tratement_list', [ListingController::class, 'my_tratement_list'])->name('my_tratement_list');

Route::post('/schedule', [ListingController::class, 'schedule'])->name('schedule');
Route::post('/caseno_list', [ListingController::class, 'caseno_list'])->name('caseno_list');
Route::post('/notes_list', [ListingController::class, 'notes_list'])->name('notes_list');
Route::post('/consent_list', [ListingController::class, 'consent_list'])->name('consent_list');
Route::post('/bill_list', [ListingController::class, 'bill_list'])->name('bill_list');

Route::post('/inPatient_list', [ListingController::class, 'inPatient_list'])->name('inPatient_list');

Route::post('/tomorrow_birthday_list', [ListingController::class, 'tomorrow_birthday_list'])->name('tomorrow_birthday_list');
Route::post('/today_birthday_list', [ListingController::class, 'today_birthday_list'])->name('today_birthday_list');

Route::post('/today_patient_list', [ListingController::class, 'today_patient_list'])->name('today_patient_list');

Route::post('/today_cancel_appointment_list', [ListingController::class, 'today_cancel_appointment_list'])->name('today_cancel_appointment_list');
Route::post('/today_new_patient', [ListingController::class, 'today_new_patient'])->name('today_new_patient');
Route::post('/today_collection', [ListingController::class, 'today_collection'])->name('today_collection');
Route::post('/today_appointment_treatment', [ListingController::class, 'today_appointment_treatment'])->name('today_appointment_treatment');

/*---------------------------- ALL Listing END----------------------------------***/

/*---------------------------- ALL CRUD START----------------------------------***/


Route::post('/refrenceBy_create', [CRUDController::class, 'refrenceBy_create'])->name('refrenceBy_create');
Route::post('/refrenceBy_update', [CRUDController::class, 'refrenceBy_update'])->name('refrenceBy_update');
Route::post('/refrenceBy_delete', [CRUDController::class, 'refrenceBy_delete'])->name('refrenceBy_delete');


Route::post('/patient_create', [CRUDController::class, 'patient_create'])->name('patient_create');
Route::post('/patient_update', [CRUDController::class, 'patient_update'])->name('patient_update');
Route::post('/patient_delete', [CRUDController::class, 'patient_delete'])->name('patient_delete');
Route::post('/upload_patient_document', [CRUDController::class, 'upload_patient_document'])->name('upload_patient_document');
Route::post('/delete_patient_document', [CRUDController::class, 'delete_patient_document'])->name('delete_patient_document');

Route::post('/employee_create', [CRUDController::class, 'employee_create'])->name('employee_create');
Route::post('/employee_update', [CRUDController::class, 'employee_update'])->name('employee_update');
Route::post('/employee_update_status', [CRUDController::class, 'employee_update_status'])->name('employee_update_status');
Route::post('/employee_delete', [CRUDController::class, 'employee_delete'])->name('employee_delete');
Route::post('/employee_change_password', [CRUDController::class, 'employee_change_password'])->name('employee_change_password');
Route::post('/clear_token', [CRUDController::class, 'clear_device_token'])->name('clear_device_token');


Route::post('/designation_create', [CRUDController::class, 'designation_create'])->name('designation_create');
Route::post('/designation_update', [CRUDController::class, 'designation_update'])->name('designation_update');
Route::post('/designation_delete', [CRUDController::class, 'designation_delete'])->name('designation_delete');


Route::post('/plan_create', [CRUDController::class, 'plan_create'])->name('plan_create');
Route::post('/plan_update', [CRUDController::class, 'plan_update'])->name('plan_update');
Route::post('/plan_delete', [CRUDController::class, 'plan_delete'])->name('plan_delete');


Route::post('/treatment_create', [CRUDController::class, 'treatment_create'])->name('treatment_create');
Route::post('/treatment_update', [CRUDController::class, 'treatment_update'])->name('treatment_update');
Route::post('/treatment_delete', [CRUDController::class, 'treatment_delete'])->name('treatment_delete');

Route::post('/add_schedule', [CRUDController::class, 'add_schedule'])->name('add_schedule');
Route::post('/update_schedule', [CRUDController::class, 'update_schedule'])->name('update_schedule');
Route::post('/delete_schedule', [CRUDController::class, 'delete_schedule'])->name('delete_schedule');


Route::post('/add_patient_package', [CRUDController::class, 'add_patient_package'])->name('add_patient_package');
Route::post('/delete_patient_package', [CRUDController::class, 'delete_patient_package'])->name('delete_patient_package');


Route::post('/caseno_create', [CRUDController::class, 'caseno_create'])->name('caseno_create');
Route::post('/caseno_update', [CRUDController::class, 'caseno_update'])->name('caseno_update');
Route::post('/caseno_delete', [CRUDController::class, 'caseno_delete'])->name('caseno_delete');


Route::post('/notes_create', [CRUDController::class, 'notes_create'])->name('notes_create');
Route::post('/notes_delete', [CRUDController::class, 'notes_delete'])->name('notes_delete');

Route::post('/consent_create', [CRUDController::class, 'consent_create'])->name('consent_create');
Route::post('/consent_update', [CRUDController::class, 'consent_update'])->name('consent_update');
Route::post('/consent_delete', [CRUDController::class, 'consent_delete'])->name('consent_delete');


Route::post('/add_patient_schedule', [CRUDController::class, 'add_patient_schedule'])->name('add_patient_schedule');
Route::post('/billId_create', [CRUDController::class, 'billId_create'])->name('billId_create');
Route::post('/billId_update', [CRUDController::class, 'billId_update'])->name('billId_update');
Route::post('/billId_delete', [CRUDController::class, 'billId_delete'])->name('billId_delete');
Route::post('/delete_treatment', [CRUDController::class, 'delete_treatment'])->name('delete_treatment');

Route::post('/patient_in_store', [CRUDController::class, 'patient_in_store'])->name('patient_in_store');
Route::post('/edit_my_treatment_list', [CRUDController::class, 'edit_my_treatment_list'])->name('edit_my_treatment_list');

/*----------------------------Ptient session crud----------------------------------***/

Route::post('/patient_session_start', [PatientSessionController::class, 'patient_session_start'])->name('patient_session_start');
Route::post('/patient_session_end', [PatientSessionController::class, 'patient_session_end'])->name('patient_session_end');
Route::post('/patient_session_cancel', [PatientSessionController::class, 'patient_session_cancel'])->name('patient_session_cancel');

Route::post('/add_patient_leave', [PatientSessionController::class, 'add_patient_leave'])->name('add_patient_leave');
Route::post('/add_consumed_session', [PatientSessionController::class, 'add_consumed_session'])->name('add_consumed_session');

/*---------------------------- ALL CRUD END----------------------------------***/


//----------------orders -------------------------//

Route::post('/buy_treatment_package', [OrderController::class, 'buy_treatment_package'])->name('buy_treatment_package');
Route::post('/order_total_payment', [OrderController::class, 'order_total_payment'])->name('order_total_payment');
Route::post('/order_payment', [OrderController::class, 'order_payment'])->name('order_payment');
Route::post('/payment_list', [OrderController::class, 'payment_list'])->name('payment_list');
Route::post('/order_payment_detail', [OrderController::class, 'order_payment_detail'])->name('order_payment_detail');
Route::post('/generate_invoice', [OrderController::class, 'generate_invoice'])->name('generate_invoice');
Route::post('/invoice_list', [OrderController::class, 'invoice_list'])->name('invoice_list');
Route::post('/cancel_patient', [OrderController::class, 'cancel_patient'])->name('cancel_patient');


Route::post('/invoice_pdf', [InvoiceController::class, 'invoice_pdf'])->name('invoice_pdf');
Route::post('/payment_detail_pdf', [InvoiceController::class, 'payment_detail_pdf'])->name('payment_detail_pdf');

//--------------------------reports-----------------------//
Route::post('/patient_attended_session', [ReportController::class, 'patient_attended_session'])->name('patient_attended_session');
Route::post('/total_session_report', [ReportController::class, 'total_session_report'])->name('total_session_report');
Route::post('/patient_payment_collection', [ReportController::class, 'patient_payment_collection'])->name('patient_payment_collection');
Route::post('/patient_due_amount_msg', [ReportController::class, 'patient_due_amount_msg'])->name('patient_due_amount_msg');
Route::post('/total_collection_report', [ReportController::class, 'total_collection_report'])->name('total_collection_report');
Route::post('/total_attended_session_report', [ReportController::class, 'total_attended_session_report'])->name('total_attended_session_report');
Route::post('/daily_collection_report', [ReportController::class, 'daily_collection_report'])->name('daily_collection_report');
Route::post('/total_patient_collection_report', [ReportController::class, 'total_patient_collection_report'])->name('total_patient_collection_report');
Route::post('/groupsession_report', [ReportController::class, 'groupsession_report'])->name('groupsession_report');

//-----------------------------Dashboard -------------------------------//
Route::post('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

