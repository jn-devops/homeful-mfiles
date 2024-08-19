<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MfilesController;
use Homeful\Paymate\Paymate;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('token',  [MfilesController::class, 'get_token'])->name('authentication token');    
Route::post('mfiles/file/upload'    ,  [MfilesController::class, 'upload_file']     )->name('upload file');
Route::post('mfiles/file/download'  ,  [MfilesController::class, 'upload_document'] )->name('download file');
Route::post('mfiles/document/create',  [MfilesController::class, 'create_object']   )->name('create document');
Route::post('mfiles/document/upload',  [MfilesController::class, 'upload_document'] )->name('upload document');
Route::post('mfiles/document/read'  ,  [MfilesController::class, 'upload_document'] )->name('read document');


Route::post('homeful-cashier', function (Request $request) { $response = (new Paymate())->payment_cashier($request) ; return $response;});
Route::post('homeful-online' , function (Request $request) { $response = (new Paymate())->payment_online($request)  ; return $response;});
Route::post('homeful-qrph'   , function (Request $request) { $response = (new Paymate())->payment_qrph($request)    ; return $response;});
Route::post('homeful-wallet' , function (Request $request) { $response = (new Paymate())->payment_wallet($request)  ; return $response;});
