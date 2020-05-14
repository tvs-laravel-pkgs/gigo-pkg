<?php
Route::group(['namespace' => 'Abs\GigoPkg\Api', 'middleware' => ['auth:api']], function () {
	Route::group(['prefix' => 'api/gigo-pkg'], function () {
		//Route::post('punch/status', 'PunchController@status');

		//SAVE GATE IN ENTRY
		Route::post('save-vehicle-gate-in-entry', 'VehicleGatePassController@saveVehicleGateInEntry');

		//VEHICLE INWARD
		Route::post('vehicle-inward-list/get', 'VehicleInwardController@getVehicleInwardList');
		Route::get('get-vehicle-inward-view/{id}', 'VehicleInwardController@getVehicleInwardViewData');

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

		//ADDTIONAL ROT AND PART GET FORM DATA AND SAVE
		Route::get('get-addtional-rot-part/{id}', 'VehicleInwardController@addtionalRotPartGetList');
		//ROT
		Route::get('get-addtional-rot-form-data/{id}', 'VehicleInwardController@getAddtionalRotFormData');
		Route::get('get-addtional-rot-list/{id}', 'VehicleInwardController@getAddtionalRotList');
		Route::get('get-addtional-rot/{id}', 'VehicleInwardController@getAddtionalRot');
		//PART
		Route::get('get-addtional-part-form-data/{id}', 'VehicleInwardController@getAddtionalPartFormData');
		Route::get('get-addtional-part/{id}', 'VehicleInwardController@getAddtionalPart');

		Route::post('save-addtional-rot-part', 'VehicleInwardController@saveAddtionalRotPart');

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

		//CUSTOMER CONFIRMATION SAVE AND GET DATA
		Route::post('save-customer-confirmation', 'VehicleInwardController@saveCustomerConfirmation');

		//INITIATE JOB SAVE
		Route::post('save-initiate-job', 'VehicleInwardController@saveInitiateJob');

		//GTE STATE BASED COUNTRY
		Route::get('get-state/{id}', 'VehicleInwardController@getState');

		//GET CITY BASED STATE
		Route::get('get-city/{id}', 'VehicleInwardController@getcity');

		//Save Job Card
		Route::post('save-job-card', 'JobCardController@saveJobCard');

		//GET BAY FORM DATA
		Route::get('get-bay-form-data/{job_card_id}', 'JobCardController@getBayFormData');

		//Jobcard View Labour Assignment
		Route::get('get-job-card-labour-assignment-form-data/{jobcardid}', 'JobCardController@LabourAssignmentFormData');

		//VIEW JOB CARD
		Route::get('view-job-card/{id}', 'JobCardController@viewJobCard');
	});
});
