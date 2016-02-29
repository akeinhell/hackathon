<?php

Route::get('/', function () {
    return view('index');
});

Route::group(['prefix' => 'api'], function () {
    Route::get('suggest/address/{query?}', 'SuggestController@address');
    Route::group(['prefix' => 'list'], function () {
        Route::post('/', 'ListController@create');
        Route::post('/{id}/update', 'ListController@update');
        Route::get('/{id}', 'ListController@show');
        Route::get('/{id}/way', 'WayController@show');
    });
});
