<?php
Route::group(['namespace' => 'Abs\GigoPkg\Api', 'middleware' => ['auth:api']], function () {
	Route::group(['prefix' => 'gigo-pkg/api'], function () {
		//Route::post('punch/status', 'PunchController@status');

		//SAVE GATE IN ENTRY
		Route::post('save-vehicle-gate-in-entry', 'VehicleGatePassController@saveVehicleGateInEntry');

		//VEHICLE INWARD
		Route::post('vehicle-inward-list/get', 'VehicleInwardController@getVehicleInwardList');

		//VEHICLE GET JOB ORDER DETAILS
		Route::get('get-job-order-details/{id}', 'VehicleInwardController@getJobOrderData');

		//VEHICLE GET FORM DATA AND SAVE
		Route::get('get-vehicle-form-data/{id}', 'VehicleInwardController@getVehicleFormData');
		Route::post('save-vehicle', 'VehicleInwardController@saveVehicle');

		//VEHICLE GET FORM DATA AND SAVE
		Route::get('get-customer-form-data/{id}', 'VehicleInwardController@getCustomerFormData');
		Route::post('save-customer', 'VehicleInwardController@saveCustomer');

		//VEHICLE INSPECTION GET FORM DATA AND SAVE
		Route::get('get-vehicle-inspection-form-data/{id}', 'VehicleInwardController@getVehicleInspectiongeFormData');

		//VOC GET FORM DATA AND SAVE
		Route::get('get-voc-form-data/{id}', 'VehicleInwardController@getVocFormData');
		Route::post('save-voc', 'VehicleInwardController@saveVoc');

		//ROAD TEST OBSERVATION GET FORM DATA AND SAVE
		Route::get('get-road-test-observation-form-data/{id}', 'VehicleInwardController@getRoadTestObservationFormData');
		Route::post('save-road-test-observation', 'VehicleInwardController@saveRoadTestObservation');

		//GTE STATE BASED COUNTRY
		Route::get('get-state/{id}', 'VehicleInwardController@getState');
		//GTE CITY BASED STATE
		Route::get('get-city/{id}', 'VehicleInwardController@getcity');
	});
});
