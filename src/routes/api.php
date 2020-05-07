<?php
Route::group(['namespace' => 'Abs\GigoPkg\Api', 'middleware' => ['auth:api']], function () {
	Route::group(['prefix' => 'gigo-pkg/api'], function () {
		//Route::post('punch/status', 'PunchController@status');

		//SAVE GATE IN ENTRY
		Route::post('save-vehicle-gate-in-entry', 'VehicleGatePassController@saveVehicleGateInEntry');

		//VEHICLE INWARD
		Route::post('get-vehicle-form-data', 'VehicleInwardController@getVehicleFomData');
		Route::post('save-vehicle', 'VehicleInwardController@saveVehicle');
		Route::post('get-customer-form-data', 'VehicleInwardController@getCustomerFomData');
	});
});
