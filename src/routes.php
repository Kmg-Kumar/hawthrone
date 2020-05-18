<?php

Route::group(['namespace'=>'flexiPIM\Hawthorne\Controllers','middleware' => ['web','extension_auth']], function () {
    Route::get('plugin/hawthorne', 'HawthorneController@index')->name('hawthorne.index');
    Route::get('plugin/hawthorne/configuration', 'HawthorneController@viewConfig')->name('hawthorne.config');
    Route::post('plugin/hawthorne/configuration_save','HawthorneController@storeConfig')->name('hawthorne.configSave');
    Route::get('plugin/hawthorne/mapping','HawthorneController@getAttributeMapping')->name('hawthorne.mapping');
    Route::get('plugin/hawthorne/log','HawthorneController@getLog')->name('hawthorne.log');
    Route::get('plugin/hawthorne/sync_attribute','HawthorneController@syncAttribute')->name('hawthorne.sync');
    Route::post('plugin/hawthorne/set_attribute','HawthorneController@storeAttribute')->name('hawthorne.storeAttribute');
    Route::get('plugin/hawthorne/sync_product','HawthorneController@productSync')->name('hawthorne.syncProduct');
    Route::get('plugin/hawthorne/download_log/{file_name}',function($file_name){
        $file_path = storage_path('app/public/cron/hawthorne') . '/' . $file_name;
        if (file_exists($file_path)) {
            return Response::download($file_path, $file_name, [
                'Content-Length: ' . filesize($file_path)
            ]);
        } else {
            return response()->json(['status' => 'failed','message' => 'File Not Found!']);
        }
    });
});
