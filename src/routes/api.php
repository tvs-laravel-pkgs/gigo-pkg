<?php
Route::group(['namespace' => 'Abs\GigoPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'gigo-pkg'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			Route::post('save-vehicle-gate-in-entry', 'VehicleGatePassController@saveVehicleGateInEntry');
		});
	});
});