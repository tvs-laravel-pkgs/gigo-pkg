<?php
Route::group(['namespace' => 'Abs\GigoPkg\Api', 'middleware' => ['auth:api']], function () {
	Route::group(['prefix' => 'gigo-pkg/api'], function () {
		//Route::post('punch/status', 'PunchController@status');

		//SAVE GATE IN ENTRY
		Route::post('save-vehicle-gate-in-entry', 'VehicleGatePassController@saveVehicleGateInEntry');

		//VEHICLE INWARD
		//VEHICLE GET FORM DATA AND SAVE
		Route::get('get-vehicle-form-data/{id}', 'VehicleInwardController@getVehicleFomData');
		Route::post('save-vehicle', 'VehicleInwardController@saveVehicle');

		//VEHICLE GET FORM DATA AND SAVE
		Route::get('get-customer-form-data/{id}', 'VehicleInwardController@getCustomerFomData');
		Route::post('save-customer', 'VehicleInwardController@saveCustomer');

		//GTE STATE BASED COUNTRY
		Route::get('get-state/{id}', 'VehicleInwardController@getState');
		//GTE CITY BASED STATE
		Route::get('get-city/{id}', 'VehicleInwardController@getcity');
	});
});
