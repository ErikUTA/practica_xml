<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\XMLController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('web.main');
});

Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
});

Route::prefix('xml')
    ->name('xml.')
    ->group(function () {
        Route::get('/get-xml-service-information', [XMLController::class, 'getXmlServiceInformation'])
            ->name('get_xml_service_information');

        Route::post('/create', [XMLController::class, 'convertInvoiceToXml'])
            ->name('create_xml');
});

Route::prefix('online-services')
    ->name('online-services.')
    ->group(function () {
        Route::post('/assign-service/{userId}', [ServiceController::class, 'assignService'])
            ->name('assign_service');
    });

//Basic User
Route::prefix('users')
    ->name('users.')
    ->group(function () {
        Route::post('store',[UserController::class,'store'])
            ->name('store');
        Route::post('storeFull',[UserController::class,'storeFull'])
            ->name('storeFull');
        Route::get('countries',[UserController::class,'getCountries'])
            ->name('countries');
    });

Route::get('csrf',function (){
    return csrf_token();
});


/***************** VUE ROUTER **********************/
Route::get('/{any}', function () {
    return view('web.main');
})->where('any', '.*');
