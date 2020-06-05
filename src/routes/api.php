<?php
Route::group(['namespace' => 'App\Http\Controllers\Api', 'middleware' => ['auth:api']], function () {
	Route::group(['prefix' => 'api'], function () {

		//SAVE GATE IN ENTRY
		Route::post('gate-in-entry/create', 'GateInController@createGateInEntry');

		//VEHICLE INWARD
		Route::post('vehicle-inward/get', 'VehicleInwardController@getGateInList');

		//VEHICLE INWARD VIEW DATA
		Route::post('vehicle-inward/get-view-data', 'VehicleInwardController@getVehicleInwardView');

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
		Route::post('vehicle-inward/inventory/get-form-data', 'VehicleInwardController@getInventoryFormData');
		Route::post('vehicle-inward/inventory/save', 'VehicleInwardController@saveInventoryItem');

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
		Route::post('vehicle-inward/dms-checklist/get-form-data', 'VehicleInwardController@getDmsCheckListFormData');

		//SCHEDULE MANINTENCE GET FORM DATA AND SAVE
		Route::post('vehicle-inward/schedule-maintenance/get-form-data', 'VehicleInwardController@getScheduleMaintenanceFormData');
		Route::post('vehicle-inward/schedule-maintenance/save', 'VehicleInwardController@saveScheduleMaintenance');

		//ADDTIONAL ROT AND PART GET FORM DATA AND SAVE
		Route::post('vehicle-inward/addtional-rot-part/get-form-data', 'VehicleInwardController@addtionalRotPartGetList');
		//ROT
		Route::post('vehicle-inward/repair-order-type-list/get', 'VehicleInwardController@getRepairOrderTypeList');
		Route::post('vehicle-inward/get-repair-order-list/get', 'VehicleInwardController@getAddtionalRotList');
		Route::get('vehicle-inward/repair-order/get-form-data', 'VehicleInwardController@getRepairOrderData');

		Route::post('vehicle-inward/job-order-repair-order/get-form-data', 'VehicleInwardController@getJobOrderRepairOrderData');
		Route::post('vehicle-inward/add-repair-order/save', 'VehicleInwardController@saveAddtionalLabour');
		//PART
		Route::post('vehicle-inward/part-list/get', 'VehicleInwardController@getPartList');
		Route::get('vehicle-inward/part/get-form-data', 'VehicleInwardController@getPartData');

		Route::post('vehicle-inward/job_order-part/get-form-data', 'VehicleInwardController@getJobOrderPartData');
		Route::post('vehicle-inward/add-part/save', 'VehicleInwardController@saveAddtionalPart');

		Route::post('vehicle-inward/save-addtional-rot-part', 'VehicleInwardController@saveAddtionalRotPart');

		Route::post('vehicle-inward/web/addtional-rot-part/save', 'VehicleInwardController@saveWebAddtionalRotPart');

		//ESTIMATE GET FORM DATA AND SAVE
		Route::post('vehicle-inward/estimate/get-form-data', 'VehicleInwardController@getEstimateFormData');
		Route::post('vehicle-inward/estimate/save', 'VehicleInwardController@saveEstimate');

		//ESTIMATION DENIED GET FORM DATA AND SAVE
		Route::post('vehicle-inward/estimation-denied/get-form-data', 'VehicleInwardController@getEstimationDeniedFormData');
		Route::post('vehicle-inward/estimation-denied/save', 'VehicleInwardController@saveEstimateDenied');

		//CUSTOMER CONFIRMATION SAVE
		Route::post('vehicle-inward/customer-confirmation/save', 'VehicleInwardController@saveCustomerConfirmation');

		//INITIATE JOB SAVE
		Route::post('vehicle-inward/initiate-job/save', 'VehicleInwardController@saveInitiateJob');

		//GTE STATE BASED COUNTRY
		Route::get('get-state/country-id/{country_id}', 'VehicleInwardController@getState');

		//GET CITY BASED STATE
		Route::get('get-city/state-id/{state_id}', 'VehicleInwardController@getcity');

		//Update Job Card
		Route::post('vehicle-inward/job-card/save', 'JobCardController@saveJobCard');

		//GET BAY ASSIGNMENT FORM DATA
		Route::post('job-card/bay/get-form-data', 'JobCardController@getBayFormData');

		//MY JOB CARD
		Route::post('get-my-job-card-list', 'MyJobCardController@getMyJobCardList');

		//SAVE BAY ASSIGNMENT
		Route::post('job-card/bay/save', 'JobCardController@saveBay');

		//Jobcard View Labour Assignment
		Route::get('get-labour-assignment-form-data/{jobcard_id}', 'JobCardController@LabourAssignmentFormData');

		//JobOrder Repair order form save
		Route::post('labour-assignment-form-save', 'JobCardController@LabourAssignmentFormSave');

		//Material-GatePass Vendor list
		Route::post('get-vendor-list', 'JobCardController@VendorList');

		//Material-GatePass Vendor Details
		Route::get('get-vendor-details/{vendor_id}', 'JobCardController@VendorDetails');

		// JOB CARD LIST
		Route::post('job-card/get', 'JobCardController@getJobCardList');

		// JOB CARD TIME LOG
		Route::get('get-job-card-time-log/{job_card_id}', 'JobCardController@getJobCardTimeLog');

		// JOB CARD MATRIAL GATE PASS VIEW
		Route::post('view-material-gate-pass', 'JobCardController@viewMeterialGatePass');

		//Job Card get OutwardDetail

		Route::post('view-material-gate-pass-detail', 'JobCardController@getMeterialGatePassOutwardDetail');

		// MY JOB CARD DATA
		Route::post('my-job-card-view', 'JobCardController@getMyJobCardData');

		//VIEW JOB CARD
		Route::get('view-job-card/{id}', 'JobCardController@viewJobCard');

		Route::post('save-my-job-card', 'JobCardController@saveMyJobCard');

		//JOB CARD LABOUR REVIEW
		Route::get('get-labour-review/{id}', 'JobCardController@getLabourReviewData');

		//JOB CARD RETURNABLE ITEM SAVE
		Route::post('labour-review-save', 'JobCardController@LabourReviewSave');

		//JOB CARD RETURNABLE ITEM SAVE
		Route::post('job-card/returnable-items/get', 'JobCardController@getReturnableItems');
		Route::post('job-card/returnable-items/get-form-data', 'JobCardController@getReturnableItemFormdata');

		Route::post('job-card/returnable-item/save', 'JobCardController@ReturnableItemSave');

		//Job Card View
		Route::post('jobcard/road-test-observation/get', 'JobCardController@getRoadTestObservation');
		Route::post('jobcard/expert-diagnosis/get', 'JobCardController@getExpertDiagnosis');
		Route::post('jobcard/dms-checklist/get', 'JobCardController@getDmsCheckList');
		Route::post('jobcard/vehicle-inspection/get', 'JobCardController@getVehicleInspection');
		Route::post('jobcard/part-indent/get', 'JobCardController@getPartsIndent');

		
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
