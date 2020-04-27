<?php
Route::group(['namespace' => 'Abs\GigoPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'gigo-pkg'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});