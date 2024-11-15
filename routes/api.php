<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MfilesController;
use Homeful\Paymate\Paymate;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get ('mfiles/token',  [MfilesController::class, 'get_token'])->name('authentication token');    
Route::get ('mfiles/user',   [MfilesController::class, 'user_information'])->name('user information');
Route::get ('mfiles/lists/{ID}'     ,  [MfilesController::class, 'get_value_list']   )->name('view value list');

// Route::post('mfiles/login',  [MfilesController::class, 'create_object']   )->name('create document');
Route::post('mfiles/document/create',  [MfilesController::class, 'create_object']   )->name('create document');
Route::post('mfiles/file/upload'    ,  [MfilesController::class, 'upload_file']     )->name('upload file');
Route::post('mfiles/file/upload/storage'    ,  [MfilesController::class, 'upload_file_url']     )->name('upload file');

Route::post('mfiles/document/search',  [MfilesController::class, 'get_object']   )->name('search document');
Route::post('mfiles/document/search/properties',  [MfilesController::class, 'get_document_property']   )->name('search document');

Route::post('mfiles/file/download'  ,  [MfilesController::class, 'download_file'] )->name('download file');
Route::post('mfiles/document/upload',  [MfilesController::class, 'upload_document'] )->name('upload document');
Route::post('mfiles/document/get'   ,  [MfilesController::class, 'read_document'] )->name('read document');
Route::post('mfiles/debug/request'  ,  [MfilesController::class, 'request_catcher'] )->name('debugger');

//quick links
Route::post('mfiles/document/search/properties/{objectID}/{propertyID}',  [MfilesController::class, 'get_document_property']   )->name('search document');
Route::get('mfiles/document/search/properties/{objectID}/{propertyID}/{propertyValue}/{getPropertyID}',  [MfilesController::class, 'get_document_property_single']   )->name('search document');
Route::get('mfiles/inventory/update/{property_unit}/{status}',  [MfilesController::class, 'update_inventory_status']   )->name('inventory update');
Route::get('mfiles/document/search/list/contract',  [MfilesController::class, 'get_contract_list']   )->name('Contract List');
Route::get('mfiles/document/search/list/property',  [MfilesController::class, 'get_inventory_list']   )->name('Inventory List');
Route::get('mfiles/document/search/list/document/{contract_id}',  [MfilesController::class, 'get_contract_document']   )->name('Document List');
