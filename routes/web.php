<?php 
use Illuminate\Support\Facades\Route;
 use App\Http\Controllers\SignaturePadController;

Route::get('/greeting', function () {
    return 'Hello World';
});

Route::get('/clear-cache', function () {
	Artisan::call('cache:clear');
	Artisan::call('view:clear');
	Artisan::call('route:clear');
	Artisan::call('config:clear');
	//Artisan::call('cache:forget spatie.permission.cache'); // Add this line
	//Artisan::call('storage:link');
	return 'Cache is cleared';
});

Route::get('signaturepad', [SignaturePadController::class, 'index']);
Route::post('signaturepad', [SignaturePadController::class, 'upload'])->name('signaturepad.upload');

?>