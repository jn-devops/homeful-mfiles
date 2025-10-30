<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MfilesController;
use Homeful\Paymate\Paymate;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get ('mfiles/token',  [MfilesController::class, 'get_token'])->name('mfiles.token');
Route::get ('mfiles/user',   [MfilesController::class, 'user_information'])->name('mfiles.user.info');
Route::get ('mfiles/lists/{ID}'     ,  [MfilesController::class, 'get_value_list']   )->name('mfiles.value.list');

// Route::post('mfiles/login',  [MfilesController::class, 'create_object']   )->name('create document');
Route::post('mfiles/document/create',  [MfilesController::class, 'create_object']   )->name('mfiles.document.create');
Route::post('mfiles/file/upload'    ,  [MfilesController::class, 'upload_file']     )->name('mfiles.file.upload');
Route::post('mfiles/file/upload/storage'    ,  [MfilesController::class, 'upload_file_url']     )->name('mfiles.file.upload.storage');

Route::post('mfiles/document/search',  [MfilesController::class, 'get_object']   )->name('mfiles.document.search');
Route::post('mfiles/document/search/properties',  [MfilesController::class, 'get_document_property']   )->name('mfiles.document.properties');
Route::post('mfiles/document/search/properties-many',  [MfilesController::class, 'get_document_property_multi']   )->name('mfiles.document.properties.multi');

Route::post('mfiles/file/download'  ,  [MfilesController::class, 'download_file'] )->name('mfiles.file.download');
Route::post('mfiles/document/upload',  [MfilesController::class, 'upload_document'] )->name('mfiles.document.upload');
Route::post('mfiles/document/get'   ,  [MfilesController::class, 'read_document'] )->name('mfiles.document.read');
Route::post('mfiles/debug/request'  ,  [MfilesController::class, 'request_catcher'] )->name('mfiles.debug.request');

//quick links
Route::post('mfiles/document/search/properties/{objectID}/{propertyID}',  [MfilesController::class, 'get_document_property']   )->name('mfiles.document.properties.quick');
Route::get('mfiles/document/search/properties/{objectID}/{propertyID}/{propertyValue}/{getPropertyID}',  [MfilesController::class, 'get_document_property_single']   )->name('mfiles.document.property.single');
Route::get('mfiles/document/view/{objectID}/{documentID}',  [MfilesController::class, 'get_document_view']   )->name('mfiles.document.view');
Route::get('mfiles/document/download/{objectID}/{documentID}',  [MfilesController::class, 'get_document_download']   )->name('mfiles.document.download');

Route::get('mfiles/inventory/update/{property_unit}/{status}',  [MfilesController::class, 'update_inventory_status']   )->name('mfiles.inventory.update');
Route::get('mfiles/document/search/list/contract/{status}',  [MfilesController::class, 'get_contract_list']   )->name('mfiles.contract.list');
Route::get('mfiles/document/search/list/property/{project_code}/{status}',  [MfilesController::class, 'get_inventory_list']   )->name('mfiles.inventory.list');
Route::get('mfiles/document/search/list/document/{contract_id}',  [MfilesController::class, 'get_contract_document']   )->name('mfiles.document.list');

//custom link
Route::get('mfiles/technical-description/{propertyValue}',  [MfilesController::class, 'get_technical_description']   )->name('mfiles.technical.description');

Route::post('mfiles/storefront/upload',[MfilesController::class,'upload_storefront_file'])->name('mfiles.storefront.upload');
Route::get('mfiles/storefront/view/{fileId}',[MfilesController::class,'view_storefront_document'])->name('mfiles.storefront.view');

Route::post('mfiles/storefront/convert',[\App\Http\Controllers\DocumentController::class,'upload_and_view_storefront_file'])->name('mfiles.storefront.convert');
Route::get('mfiles/storefront/{id}/view/{filename}',[\App\Http\Controllers\DocumentController::class,'view'])->name('documents.storefront.view');
