<?php
Route::group(['namespace' => 'App\Http\Controllers\Api', 'middleware' => ['auth:api']], function () {
	Route::group(['prefix' => 'api'], function () {

		//SAVE GATE IN ENTRY
		Route::post('gate-in-entry/create', 'GateInController@createGateInEntry');

		//VEHICLE INWARD
		Route::post('vehicle-inward/get', 'VehicleInwardController@getGateInList');

		//CUSTOMER DETAIL FORM DATA AND SAVE
		Route::post('vehicle-inward/view', 'VehicleInwardController@getVehicleInwardViewData');
		Route::post('vehicle-inward/get-vehicle-detail', 'VehicleInwardController@getVehicleDetail');

		//CUSTOMER DETAIL FORM DATA AND SAVE
		Route::post('vehicle-inward/get-customer-detail', 'VehicleInwardController@getCustomerDetail');
		Route::post('vehicle-inward/save-customer-detail', 'VehicleInwardController@saveCustomerDetail');

		//ORDER DETAIL FORM DATA AND SAVE
		Route::post('vehicle-inward/order-detail/get-form-data', 'VehicleInwardController@getOrderFormData');
		Route::post('vehicle-inward/order-detail/save', 'VehicleInwardController@saveOrderDetail');

		//VEHICLE GET INVENTORY FORM DATA AND SAVE
		Route::get('get-inventory-form-data/gate-log/{id}', 'VehicleInwardController@getInventoryFormData');
		Route::post('save-inventory-item', 'VehicleInwardController@saveInventoryItem');

		//VOC GET FORM DATA AND SAVE
		Route::post('vehicle-inward/voc/get-form-data', 'VehicleInwardController@getVocFormData');
		Route::post('vehicle-inward/voc/save', 'VehicleInwardController@saveVoc');

		//ROAD TEST OBSERVATION GET FORM DATA AND SAVE
		Route::post('vehicle-inward/road-test-observation/get-form-data', 'VehicleInwardController@getRoadTestObservationFormData');
		Route::post('vehicle-inward/road-test-observation/save', 'VehicleInwardController@saveRoadTestObservation');

		//EXPERT DIAGNOSIS REPORT GET FORM DATA AND SAVE
		Route::post('vehicle-inward/expert-diagnosis-report/get-form-data', 'VehicleInwardController@getExpertDiagnosisReportFormData');
		Route::post('vehicle-inward/expert-diagnosis-report/save', 'VehicleInwardController@saveExpertDiagnosisReport');

		//VEHICLE INSPECTION GET FORM DATA AND SAVE
		Route::post('vehicle-inward/vehicle-inspection/get-form-data', 'VehicleInwardController@getVehicleInspectiongetFormData');
		Route::post('vehicle-inward/vehicle-inspection/save', 'VehicleInwardController@saveVehicleInspection');

		//DMS CHECKLIST SAVE
		Route::post('vehicle-inward/dms-checklist/save', 'VehicleInwardController@saveDmsCheckList');

		//SCHEDULE MANINTENCE GET FORM DATA AND SAVE
		Route::post('vehicle-inward/schedule-maintenance/get-form-data', 'VehicleInwardController@getScheduleMaintenanceFormData');
		Route::post('vehicle-inward/schedule-maintenance/save', 'VehicleInwardController@saveScheduleMaintenance');

		//ADDTIONAL ROT AND PART GET FORM DATA AND SAVE
		Route::get('get-addtional-rot-part/{id}', 'VehicleInwardController@addtionalRotPartGetList');
		//ROT
		Route::get('get-repair-order-type-list/{id}', 'VehicleInwardController@getAddtionalRotFormData');
		Route::get('get-repair-order-list/repair-order-type-id/{repair_order_type_id}', 'VehicleInwardController@getAddtionalRotList');
		Route::get('get-repair-order-data/{id}', 'VehicleInwardController@getRepairOrderData');
		//PART
		Route::get('get-part-list/{id}', 'VehicleInwardController@getPartList');
		Route::get('get-part-data/{id}', 'VehicleInwardController@getPartData');

		Route::post('save-addtional-rot-part', 'VehicleInwardController@saveAddtionalRotPart');

		//ESTIMATE GET FORM DATA AND SAVE
		//issue: Route naming
		Route::get('get-estimate-form-data/{id}', 'VehicleInwardController@getEstimateFormData');
		Route::post('save-estimate', 'VehicleInwardController@saveEstimate');

		//ESTIMATION DENIED GET FORM DATA AND SAVE
		//issue: Route naming
		Route::get('get-estimation-denied-form-data/{id}', 'VehicleInwardController@getEstimationDeniedFormData');
		Route::post('save-estimation-denied', 'VehicleInwardController@saveEstimateDenied');

		//CUSTOMER CONFIRMATION SAVE AND GET DATA
		Route::post('save-customer-confirmation', 'VehicleInwardController@saveCustomerConfirmation');

		//INITIATE JOB SAVE
		Route::post('save-initiate-job', 'VehicleInwardController@saveInitiateJob');

		//GTE STATE BASED COUNTRY
		Route::get('get-state/country-id/{country_id}', 'VehicleInwardController@getState');

		//GET CITY BASED STATE
		Route::get('get-city/state-id/{state_id}', 'VehicleInwardController@getcity');

		//Save Job Card
		//issue: Route naming
		Route::post('save-job-card', 'JobCardController@saveJobCard');

		//GET BAY ASSIGNMENT FORM DATA
		Route::get('get-bay-form-data/{job_card_id}', 'JobCardController@getBayFormData');

		//MY JOB CARD
		Route::post('get-my-job-card-list', 'MyJobCardController@getMyJobCardList');

		//SAVE BAY ASSIGNMENT
		Route::post('save-bay', 'JobCardController@saveBay');

		//Jobcard View Labour Assignment
		Route::get('get-labour-assignment-form-data/{jobcard_id}', 'JobCardController@LabourAssignmentFormData');

		//JobOrder Repair order form save
		Route::post('labour-assignment-form-save', 'JobCardController@LabourAssignmentFormSave');

		//Material-GatePass Vendor list
		Route::post('get-vendor-list', 'JobCardController@VendorList');

		//Material-GatePass Vendor Details
		Route::get('get-vendor-details/{vendor_id}', 'JobCardController@VendorDetails');

		// JOB CARD LIST
		Route::post('get-job-card-list', 'JobCardController@getJobCardList');

		// JOB CARD TIME LOG
		Route::get('get-job-card-time-log/{job_card_id}', 'JobCardController@getJobCardTimeLog');

		// JOB CARD MATRIAL GATE PASS VIEW
		Route::get('view-material-gate-pass/{job_card_id}', 'JobCardController@viewMetirialGatePass');

		// MY JOB CARD DATA
		Route::post('get-my-job-card-data', 'JobCardController@getMyJobCardData');

		//VIEW JOB CARD
		Route::get('view-job-card/{id}', 'JobCardController@viewJobCard');

		Route::post('save-my-job-card', 'JobCardController@saveMyJobCard');

		//JOB CARD LABOUR REVIEW
		Route::get('get-labour-review/{id}', 'JobCardController@getLabourReviewData');

		//JOB CARD RETURNABLE ITEM SAVE
		Route::post('labour-review-save', 'JobCardController@LabourReviewSave');

		//JOB CARD RETURNABLE ITEM SAVE
		Route::post('job-card-returnable-item-save', 'JobCardController@ReturnableItemSave');

		//Material-GatePass Details Save
		Route::post('save-material-gate-pass-detail', 'JobCardController@saveMaterialGatePassDetail');

		//Material-GatePass Items Save
		Route::post('save-material-gate-pass-item', 'JobCardController@saveMaterialGatePassItem');

		//Material-GatePass Details List
		Route::post('get-material-gate-pass-list', 'MaterialGatePassController@getMaterialGatePass');

		//Material-GatePass Detail
		Route::get('get-material-gate-pass-detail/{id}', 'MaterialGatePassController@getMaterialGatePassViewData');

		//Material-GatePass Gate in and out
		Route::post('save-gate-in-out-material-gate-pass', 'MaterialGatePassController@materialGateInAndOut');
		//Save Material Gate Out Confirm
		Route::post('save-gate-out-confirm-material-gate-pass', 'MaterialGatePassController@materialGateOutConfirm');
		//Resend OTP for Material Gate Pass
		Route::get('material-gate-out-otp-resend/{id}', 'MaterialGatePassController@materialCustomerOtp');

		//VEHICLE GATE PASS LIST
		Route::post('get-vehicle-gate-pass-list', 'VehicleGatePassController@getVehicleGatePassList');
		Route::get('view-vehicle-gate-pass/{gate_log_id}', 'VehicleGatePassController@viewVehicleGatePass');
		Route::post('gate-out-vehicle/save', 'VehicleGatePassController@saveVehicleGateOutEntry');

	});
});
