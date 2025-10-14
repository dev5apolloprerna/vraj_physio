<?php 
use Illuminate\Support\Facades\Route;
 use App\Http\Controllers\SignaturePadController;

Route::get('/greeting', function () {
    return 'Hello World';
});


Route::get('signaturepad', [SignaturePadController::class, 'index']);
Route::post('signaturepad', [SignaturePadController::class, 'upload'])->name('signaturepad.upload');

?>