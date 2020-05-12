<?php
Route::group(['namespace' => 'Abs\GigoPkg\Api', 'middleware' => ['auth:api']], function () {
	Route::group(['prefix' => 'api/gigo-pkg'], function () {
		//Route::post('punch/status', 'PunchController@status');

		//SAVE GATE IN ENTRY
		Route::post('save-vehicle-gate-in-entry', 'VehicleGatePassController@saveVehicleGateInEntry');

		//VEHICLE INWARD
		Route::post('vehicle-inward-list/get', 'VehicleInwardController@getVehicleInwardList');

		//VEHICLE GET JOB ORDER FORM DATA AND SAVE
		Route::get('get-job-order-form-data/{id}', 'VehicleInwardController@getJobOrderFormData');
		Route::post('save-job-order', 'VehicleInwardController@saveJobOrder');

		//VEHICLE GET INVENTORY FORM DATA AND SAVE
		Route::get('get-inventory-form-data/{id}', 'VehicleInwardController@getInventoryFormData');
		Route::post('save-inventory-item', 'VehicleInwardController@saveInventoryItem');

		//VEHICLE GET FORM DATA AND SAVE
		Route::get('get-vehicle-form-data/{id}', 'VehicleInwardController@getVehicleFormData');
		Route::post('save-vehicle', 'VehicleInwardController@saveVehicle');

		//VEHICLE GET FORM DATA AND SAVE
		Route::get('get-customer-form-data/{id}', 'VehicleInwardController@getCustomerFormData');
		Route::post('save-customer', 'VehicleInwardController@saveCustomer');

		//VEHICLE INSPECTION GET FORM DATA AND SAVE
		Route::get('get-vehicle-inspection-form-data/{id}', 'VehicleInwardController@getVehicleInspectiongeFormData');
		Route::post('save-vehicle-inspection', 'VehicleInwardController@saveVehicleInspection');

		//VOC GET FORM DATA AND SAVE
		Route::get('get-voc-form-data/{id}', 'VehicleInwardController@getVocFormData');
		Route::post('save-voc', 'VehicleInwardController@saveVoc');

		//ROAD TEST OBSERVATION GET FORM DATA AND SAVE
		Route::get('get-road-test-observation-form-data/{id}', 'VehicleInwardController@getRoadTestObservationFormData');
		Route::post('save-road-test-observation', 'VehicleInwardController@saveRoadTestObservation');

		//DMS CHECKLIST SAVE
		Route::post('save-dms-checklist', 'VehicleInwardController@saveDmsCheckList');

		//SCHEDULE MANINTENCE GET FORM DATA AND SAVE
		Route::get('get-schedule-maintenance', 'VehicleInwardController@scheduleMaintenanceGetList');
		Route::post('save-schedule-maintenance', 'VehicleInwardController@saveScheduleMaintenance');

		//EXPERT DIAGNOSIS REPORT GET FORM DATA AND SAVE
		Route::get('get-expert-diagnosis-report-form-data/{id}', 'VehicleInwardController@getExpertDiagnosisReportFormData');
		Route::post('save-expert-diagnosis-report', 'VehicleInwardController@saveExpertDiagnosisReport');

		//ESTIMATE GET FORM DATA AND SAVE
		Route::get('get-estimate-form-data/{id}', 'VehicleInwardController@getEstimateFormData');
		Route::post('save-estimate', 'VehicleInwardController@saveEstimate');

		//ESTIMATION DENIED GET FORM DATA AND SAVE
		Route::get('get-estimation-denied-form-data/{id}', 'VehicleInwardController@getEstimationDeniedFormData');
		Route::post('save-estimation-denied', 'VehicleInwardController@saveEstimateDenied');

		//GTE STATE BASED COUNTRY
		Route::get('get-state/{id}', 'VehicleInwardController@getState');
		//GTE CITY BASED STATE
		Route::get('get-city/{id}', 'VehicleInwardController@getcity');
	});
});
