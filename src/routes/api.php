<?php
Route::group(['namespace' => 'Abs\GigoPkg\Api', 'middleware' => ['auth:api']], function () {
	Route::group(['prefix' => 'gigo-pkg/api'], function () {
		//Route::post('punch/status', 'PunchController@status');
		Route::post('save-vehicle-gate-in-entry', 'VehicleGatePassController@saveVehicleGateInEntry');
	});
});
