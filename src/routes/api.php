<?php
Route::get('pdf', 'Abs\GigoPkg\Api\WarrantyJobOrderRequestController@pdf')->name('pdf');
Route::group(['namespace' => 'App\Http\Controllers\Api', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'api'], function () {

        Route::group(['prefix' => 'job-order'], function () {
            $controller = 'JobOrderController';
            Route::get('index', $controller . '@index');
            Route::get('read/{id}', $controller . '@read');
        });

        Route::get('job-card/index', JobCardController::class . '@index');

        Route::get('service-type/options', ServiceTypeController::class . '@options');

        Route::group(['prefix' => 'complaint-group'], function () {
            $controller = 'ComplaintGroup';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('save-from-form-data', $controller . 'Controller@saveFromFormData');
            Route::post('save-from-ng-data', $controller . 'Controller@saveFromNgData');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
        });

        Route::group(['prefix' => 'wjor-repair-order'], function () {
            $controller = 'WjorRepairOrder';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('save-from-form-data', $controller . 'Controller@saveFromFormData');
            Route::post('save-from-ng-data', $controller . 'Controller@saveFromNgData');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
        });

        Route::group(['prefix' => 'wjor-part'], function () {
            $controller = 'WjorPart';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('save-from-form-data', $controller . 'Controller@saveFromFormData');
            Route::post('save-from-ng-data', $controller . 'Controller@saveFromNgData');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
        });

        Route::group(['prefix' => 'repair-order'], function () {
            $controller = 'RepairOrder';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
        });

        Route::group(['prefix' => 'split-order-type'], function () {
            $controller = 'SplitOrderType';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
        });

        Route::group(['prefix' => 'part'], function () {
            $controller = 'Part';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
            Route::post('stock_data', $controller . 'Controller@stock_data');
            Route::post('get-from-data', $controller . 'Controller@getFormData');
        });

        Route::group(['prefix' => 'job-order'], function () {
            $controller = 'JobOrder';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('save-it', $controller . 'Controller@saveIt');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
        });

        Route::group(['prefix' => 'warranty-job-order-request'], function () {
            $controller = 'WarrantyJobOrderRequest';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('save-it', $controller . 'Controller@saveIt');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
            Route::post('send-to-approval', $controller . 'Controller@sendToApproval');
            Route::post('approve', $controller . 'Controller@approve');
            Route::post('reject', $controller . 'Controller@reject');
            Route::get('list', $controller . 'Controller@list');
            Route::post('get-form-data', $controller . 'Controller@getFormData');
            Route::post('get-temp-data', $controller . 'Controller@getTempData');
            Route::post('save-temp-data', $controller . 'Controller@saveTempData');
        });

        Route::group(['prefix' => 'vehicle-primary-application'], function () {
            $controller = 'VehiclePrimaryApplication';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
        });

        Route::group(['prefix' => 'vehicle-secondary-application'], function () {
            $controller = 'VehicleSecondaryApplicationController';
            Route::get('index', $controller . '@index');
            Route::get('read/{id}', $controller . '@read');
            Route::post('save', $controller . '@save');
            Route::post('remove', $controller . '@remove');
            Route::get('options', $controller . '@options');
        });

        Route::group(['prefix' => 'vehicle-service-schedule'], function () {
            $controller = 'VehicleServiceScheduleController';
            Route::get('index', $controller . '@index');
            Route::get('read/{id}/{withtrashed?}', $controller . '@read');
            Route::post('save', $controller . '@save');
            Route::get('remove', $controller . '@remove');
            Route::get('options', $controller . '@options');
            Route::get('list', $controller . '@list');
        });

        Route::group(['prefix' => 'vehicle-service-schedule-service-type'], function () {
            $controller = 'VehicleServiceScheduleServiceTypeController';
            Route::get('read/{id}', $controller . '@read');
        });

        Route::group(['prefix' => 'part-supplier'], function () {
            $controller = 'PartSupplierController';
            Route::get('index', $controller . '@index');
            Route::get('read/{id}', $controller . '@read');
            Route::post('save', $controller . '@save');
            Route::post('remove', $controller . '@remove');
            Route::get('options', $controller . '@options');
        });

        Route::group(['prefix' => 'complaint'], function () {
            $controller = 'ComplaintController';
            Route::get('index', $controller . '@index');
            Route::get('read/{id}', $controller . '@read');
            Route::post('save', $controller . '@save');
            Route::post('remove', $controller . '@remove');
            Route::get('options', $controller . '@options');
        });

        Route::group(['prefix' => 'fault'], function () {
            $controller = 'FaultController';
            Route::get('index', $controller . '@index');
            Route::get('read/{id}', $controller . '@read');
            Route::post('save', $controller . '@save');
            Route::post('remove', $controller . '@remove');
            Route::get('options', $controller . '@options');
        });

        //Dashboard
        Route::post('gigo/dashboard', 'DashboardController@getWebDashboard');
        Route::post('state-based/outlet', 'DashboardController@getOutletData');

        //SAVE GATE IN ENTRY
        Route::get('gate-in-entry/get-form-data', 'GateInController@getFormData');
        Route::post('gate-in-entry/create', 'GateInController@createGateInEntry');
        Route::post('gatelog/vehicle/search', 'GateInController@getVehicleSearchList');

        // GATE IN CARD LIST
        Route::post('gate-in-entry/card-list', 'GateInController@getGateInList');

        //PART SEARCH
        Route::post('vehicle-inward/part/search', 'VehicleInwardController@getPartsSearchList');

        //VOC SEARCH
        Route::post('vehicle-inward/customer/voice/search', 'VehicleInwardController@getCustomerVoiceSearchList');

        //Customer SEARCH
        Route::post('vehicle-inward/customer/search', 'VehicleInwardController@getCustomerSearchList');

        //Vehicle Modal SEARCH
        Route::post('vehicle-inward/vehicle/model/search', 'VehicleInwardController@getVehicleModelSearchList');

        //REPAIR ORDER SEARCH
        Route::post('vehicle-inward/repair-order/search', 'VehicleInwardController@getRepairOrderSearchList');

        //VEHICLE INWARD
        Route::post('vehicle-inward/get', 'VehicleInwardController@getGateInList');

        //VEHICLE INWARD VIEW DATA
        Route::post('part-indent/get-vehicle-detail', 'VehicleInwardController@getPartIndentVehicleDetail');
        Route::post('part-indent/get-repair-orders', 'VehicleInwardController@getRepairOrders');
        Route::post('vehicle-inward/get-view-data', 'VehicleInwardController@getVehicleInwardView');
        Route::post('inward-part-indent/get-view-data', 'VehicleInwardController@getInwardPartIndentViewData');
        Route::post('inward-part-indent/save-return-part', 'VehicleInwardController@saveReturnPart');
        Route::post('vehicle-inward/part-logs/delete', 'VehicleInwardController@deletePartLogs');

        Route::post('inward-part-indent/get-issue-part-form-data', 'VehicleInwardController@getInwardPartIndentIssuePartFormData');
        Route::post('inward-part-indent/bulk-form-data', 'VehicleInwardController@getBulkIssuePartFormData');
        Route::post('inward-part-indent/save-issued-part', 'VehicleInwardController@saveIssuedPart');
        Route::post('part-indent/get-part-form-data', 'VehicleInwardController@getPartDetailPias');

        Route::group(['prefix' => 'vendor'], function () {
            $controller = 'Vendor';
            Route::get('index', $controller . 'Controller@index');
            Route::get('read/{id}', $controller . 'Controller@read');
            Route::post('save', $controller . 'Controller@save');
            Route::post('remove', $controller . 'Controller@remove');
            Route::get('options', $controller . 'Controller@options');
        });

        //CUSTOMER DETAIL FORM DATA AND SAVE
        Route::post('vehicle-inward/view', 'VehicleInwardController@getVehicleInwardViewData');
        Route::post('vehicle-inward/get-vehicle-detail', 'VehicleInwardController@getVehicleDetail');

        //CUSTOMER DETAIL FORM DATA AND SAVE
        Route::post('vehicle-inward/get-customer-detail', 'VehicleInwardController@getCustomerDetail');
        Route::post('vehicle-inward/save-customer-detail', 'VehicleInwardController@saveCustomerDetail');

        //VEHICLE PHOTOS FORM DATA AND SAVE
        Route::post('vehicle-inward/vehicle/photos/get-form-data', 'VehicleInwardController@getVehiclePhotosFormData');
        Route::post('vehicle-inward/vehicle/photos/save', 'VehicleInwardController@saveVehiclePhotos');

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
        Route::post('vehicle-inward/repair-order/get-form-data', 'VehicleInwardController@getRepairOrderData');

        Route::post('vehicle-inward/job-order-repair-order/get-form-data', 'VehicleInwardController@getJobOrderRepairOrderData');
        Route::post('vehicle-inward/add-repair-order/save', 'VehicleInwardController@saveAddtionalLabour');
        //PART
        Route::post('vehicle-inward/part-list/get', 'VehicleInwardController@getPartList');
        Route::post('vehicle-inward/part/get-form-data', 'VehicleInwardController@getPartData');

        Route::post('vehicle-inward/job_order-part/get-form-data', 'VehicleInwardController@getJobOrderPartData');
        Route::post('vehicle-inward/add-part/save', 'VehicleInwardController@saveAddtionalPart');

        Route::post('vehicle-inward/addtional-rot-part/save', 'VehicleInwardController@saveAddtionalRotPart');

        //SEND REQUEST TO STOCK INCHARGE FOR ADD PART
        Route::post('vehicle-inward/stock-incharge/request/parts', 'VehicleInwardController@sendRequestPartsIntent');

        //ESTIMATE GET FORM DATA AND SAVE
        Route::post('vehicle-inward/estimate/get-form-data', 'VehicleInwardController@getEstimateFormData');
        Route::post('vehicle-inward/estimate/save', 'VehicleInwardController@saveEstimate');

        //ESTIMATION DENIED GET FORM DATA AND SAVE
        Route::post('vehicle-inward/estimation-denied/get-form-data', 'VehicleInwardController@getEstimationDeniedFormData');
        Route::post('vehicle-inward/estimation-denied/save', 'VehicleInwardController@saveEstimateDenied');

        //CUSTOMER CONFIRMATION GET FORM DATA SAVE
        Route::post('vehicle-inward/customer-confirmation/get-form-data', 'VehicleInwardController@getCustomerConfirmationFormData');
        Route::post('vehicle-inward/customer-confirmation/save', 'VehicleInwardController@saveCustomerConfirmation');

        //CUSTOMER OTP
        Route::post('vehicle-inward/send/customer/otp', 'VehicleInwardController@sendCustomerOtp');
        Route::post('vehicle-inward/verify/otp', 'VehicleInwardController@verifyOtp');
        Route::post('vehicle-inward/estimate/link/generate', 'VehicleInwardController@generateUrl');

        //INITIATE JOB SAVE
        Route::post('vehicle-inward/cancel', 'VehicleInwardController@inwardCancel');

        //INITIATE JOB SAVE
        Route::post('vehicle-inward/initiate-job/save', 'VehicleInwardController@saveInitiateJob');

        //LABOUR PARTS DELETE STATUS UPADET
        Route::post('vehicle-inward/labour-parts/delete', 'VehicleInwardController@deleteLabourParts');

        //GTE STATE BASED COUNTRY
        Route::get('get-state/country-id/{country_id}', 'VehicleInwardController@getState');

        //GET CITY BASED STATE
        Route::get('get-city/state-id/{state_id}', 'VehicleInwardController@getcity');

        //Update Job Card
        Route::post('vehicle-inward/update-jc/get-form-data', 'JobCardController@getUpdateJcFormData');
        Route::post('vehicle-inward/job-card/save', 'JobCardController@saveJobCard');

        //GET BAY ASSIGNMENT FORM DATA
        Route::post('job-card/bay/get-form-data', 'JobCardController@getBayFormData');
        //SAVE BAY ASSIGNMENT
        Route::post('job-card/bay/save', 'JobCardController@saveBay');

        //GET BAY DATA
        Route::post('job-card/bay-view/get', 'JobCardController@getBayViewData');
        Route::post('job-card/order-view/get', 'JobCardController@getOrderViewData');

        //MY JOB CARD
        Route::post('myjobcard/list', 'MyJobCardController@getMyJobCardList');
        // MY JOB CARD DATA
        Route::post('my-job-card-view', 'MyJobCardController@getMyJobCardData');
        //Save Start Worklog
        Route::post('save-my-job-card', 'MyJobCardController@saveMyStartWorkLog');
        //Save Finish Worklog
        Route::post('save-work-log', 'MyJobCardController@saveMyFinishWorkLog');

        //MY TIME SHEET LIST
        Route::post('mytimesheet/list', 'MyJobCardController@getTimeSheetQrCheck');

        //Jobcard View Labour Assignment
        Route::post('job-card/labour-assignment/get-form-data', 'JobCardController@LabourAssignmentFormData');
        Route::post('job-card/get-mechanic', 'JobCardController@getMechanic');
        Route::post('job-card/save-mechanic', 'JobCardController@saveMechanic');
        Route::post('job-card/mechanic-time-log', 'JobCardController@getMechanicTimeLog');

        //JobOrder Repair order form save
        Route::post('labour-assignment-form-save', 'JobCardController@LabourAssignmentFormSave');

        //Jobcard Flaoting Work
        Route::post('job-card/floating-work/get-form-data', 'JobCardController@floatingWorkFormData');
        Route::post('job-card/floating-gatepass/status/update', 'JobCardController@updateFloatingGatePassStatus');

        //Material-GatePass Vendor list
        Route::post('get-vendor-list', 'JobCardController@VendorList');

        //Material-GatePass Vendor Details
        Route::get('get-vendor-details/{vendor_id}', 'JobCardController@VendorDetails');

        // JOB CARD LIST
        Route::post('job-card/get', 'JobCardController@getJobCardList');

        // JOB CARD TIME LOG
        Route::post('get-job-card-time-log', 'JobCardController@getJobCardTimeLog');

        //JOB CARD WORK COMPLETED
        Route::post('job-card/update-status', 'JobCardController@updateJobCardStatus');

        //JOB CARD EMPLOYEE PAYEMNT
        Route::post('job-card/customer/approval', 'JobCardController@sendCustomerApproval');

        //JOB CARD MATRIAL GATE PASS
        Route::post('material-gatepass/view', 'JobCardController@viewMeterialGatePass');
        Route::post('material-gatepass/get-form-data', 'JobCardController@getMeterialGatePassData');
        Route::post('material-gatepass/save', 'JobCardController@saveMaterialGatePass');
        Route::post('material-gatepass/update/bill', 'JobCardController@saveMaterialGatePassBill');

        //VIEW JOB CARD
        Route::get('view-job-card/{id}', 'JobCardController@viewJobCard');

        //JOB CARD LABOUR REVIEW
        Route::post('get-labour-review', 'JobCardController@getLabourReviewData');

        //JOB CARD RETURNABLE ITEM SAVE
        Route::post('labour-review-save', 'JobCardController@LabourReviewSave');

        //JOB CARD RETURNABLE ITEM SAVE
        Route::post('job-card/returnable-items/get', 'JobCardController@getReturnableItems');
        Route::post('job-card/returnable-items/get-form-data', 'JobCardController@getReturnableItemFormdata');

        Route::post('job-card/returnable-item/save', 'JobCardController@returnableItemSave');

        //JOB CARD RETURNABLE PARTS SAVE
        Route::post('job-card/returnable-parts/get-form-data', 'JobCardController@getReturnablePartsFormdata');
        Route::post('job-card/returnable-part/save', 'JobCardController@returnablePartSave');

        //Job Card View
        Route::post('jobcard/road-test-observation/get', 'JobCardController@getRoadTestObservation');
        Route::post('jobcard/road-test-observation/save', 'JobCardController@saveRoadTestObservation');

        Route::post('jobcard/expert-diagnosis/get', 'JobCardController@getExpertDiagnosis');
        Route::post('jobcard/dms-checklist/get', 'JobCardController@getDmsCheckList');
        Route::post('jobcard/vehicle-inspection/get', 'JobCardController@getVehicleInspection');
        Route::post('jobcard/part-indent/get', 'JobCardController@getPartsIndent');
        Route::post('jobcard/schedule-maintenance/get', 'JobCardController@getScheduleMaintenance');
        Route::post('jobcard/payable-labour-part/get', 'JobCardController@getPayableLabourPart');
        Route::post('jobcard/payable/delete', 'JobCardController@deletePayable');
        Route::post('job-card/send/confirmation', 'JobCardController@sendConfirmation');
        Route::post('job-card/verify/otp', 'JobCardController@verifyOtp');
        Route::post('jobcard/estimate/get', 'JobCardController@getEstimate');
        Route::post('jobcard/estimate-status/get', 'JobCardController@getEstimateStatus');
        Route::post('jobcard/outward-item/delete', 'JobCardController@deleteOutwardItem');
        Route::post('jobcard/gate-in-detial/get', 'JobCardController@getGateInDetail');
        Route::post('jobcard/vehicle-detial/get', 'JobCardController@getVehicleDetail');
        Route::post('jobcard/customer-detial/get', 'JobCardController@getCustomerDetail');
        Route::post('jobcard/order-detial/get', 'JobCardController@getOrderDetail');
        Route::post('jobcard/inventory/get', 'JobCardController@getInventory');
        Route::post('jobcard/capture-voc/get', 'JobCardController@getCaptureVoc');

        //Material-GatePass Details Save
        Route::post('save-material-gate-pass-detail', 'JobCardController@saveMaterialGatePassDetail');

        //Material-GatePass Details List
        Route::post('material-gate-pass/get', 'MaterialGatePassController@getMaterialGatePass');

        //Material-GatePass Detail
        Route::post('material-gate-pass/view/get-data', 'MaterialGatePassController@getMaterialGatePassViewData');

        //Material-GatePass Gate in and out
        Route::post('material-gate-pass/gate-in-out/save', 'MaterialGatePassController@saveMaterialGateInAndOut');
        //Save Material Gate Out Confirm
        Route::post('material-gate-pass/gate-out/confirm', 'MaterialGatePassController@materialGateOutConfirm');
        //Resend OTP for Material Gate Pass
        Route::get('material-gate-pass/gate-out/otp-resend/{id}', 'MaterialGatePassController@sendOtpToCustomer');

        //Road Test Gate Pass
        Route::post('road-test-gate-pass/get', 'RoadTestGatePassController@getRoadTestGatePass');
        Route::post('road-test-gate-pass/view/get-data', 'RoadTestGatePassController@getRoadTestGatePassViewData');
        Route::post('road-test-gate-pass/gate-in-out/save', 'RoadTestGatePassController@saveRoadTestGateInAndOut');
        Route::post('road-test-gate-pass/gate-out/confirm', 'RoadTestGatePassController@roadTestGateOutConfirm');

        //Floating Gate Pass
        Route::post('floating-gate-pass/get', 'FloatingGatePassController@getFloatingGatePass');
        Route::post('floating-gate-pass/view/get-data', 'FloatingGatePassController@getFloatingGatePassViewData');
        Route::post('floating-gate-pass/gate-in-out/save', 'FloatingGatePassController@saveFloatingGateInAndOut');

        //VIEW BILL DETAILS
        Route::post('job-card/bill-detail/view', 'JobCardController@viewBillDetails');
        Route::post('job-card/bill-update/get-form-data', 'JobCardController@getBillDetailFormData');
        Route::post('job-card/bill-update/', 'JobCardController@updateBillDetails');

        //SPLIT ORDER DETAILS
        Route::post('job-card/split-order/view', 'JobCardController@viewSplitOrderDetails');
        Route::post('job-card/split-order/update', 'JobCardController@splitOrderUpdate');

        //VEHICLE GATE PASS LIST
        Route::post('vehicle-gate-pass/get-list', 'VehicleGatePassController@getVehicleGatePassList');
        Route::post('vehicle-gate-pass/view', 'VehicleGatePassController@viewVehicleGatePass');
        Route::post('gate-out-vehicle/save', 'VehicleGatePassController@saveVehicleGateOutEntry');

        //PDF
        Route::post('job-card/pdf/get', 'JobCardController@getPdf');

        // Aggregates
        Route::post('aggregates/get-sub-aggregates-list', 'AggregateController@getSubAggregates');

        //GatePass
        Route::post('gate-pass/get-form-data', 'GatePassController@getFormData');
        Route::post('gate-pass/save', 'GatePassController@save');

        Route::post('/gate-pass/jobcard/get-search-list', 'GatePassController@getJobCardSearchList');
        Route::post('/gate-pass/user/get-search-list', 'GatePassController@getUserSearchList');

        //Manual Vehicle Delivery
        Route::post('/manual-vehicle-delivery/get-form-data', 'ManualVehicleDeliveryController@getFormData');
        Route::post('/manual-vehicle-delivery/save', 'ManualVehicleDeliveryController@save');
        Route::post('/manual-vehicle-delivery/update/vehicle-status', 'ManualVehicleDeliveryController@updateVehicleStatus');

        //TVS One Discount Request
        Route::post('/tvs-one/discount/save', 'ManualVehicleDeliveryController@tvsOneDiscountSave');

        //OnSite Vist
        Route::post('/on-site-visit/get-form-data', 'OnSiteVisitController@getFormData');
        Route::post('/on-site-visit/repair-order/save', 'OnSiteVisitController@saveLabourDetail');
        Route::post('/on-site-visit/parts/save', 'OnSiteVisitController@savePartsDetail');
        Route::post('/on-site-visit/save', 'OnSiteVisitController@save');
        Route::post('on-site-visit/get-bulk-form-data', 'OnSiteVisitController@getBulkIssuePartFormData');
        Route::post('on-site-visit/bulk-form-data/save', 'OnSiteVisitController@saveIssuedPart');
        Route::post('on-site-visit/get-parts-data', 'OnSiteVisitController@getPartsData');
        Route::post('on-site-visit/status/update', 'OnSiteVisitController@updateStatus');
        Route::post('on-site-visit/request/save', 'OnSiteVisitController@saveRequest');
        Route::post('on-site-visit/return/parts', 'OnSiteVisitController@returnParts');
        Route::post('on-site-visit/get/time-log', 'OnSiteVisitController@getTimeLog');
        Route::post('on-site-visit/save/time-log', 'OnSiteVisitController@saveTimeLog');
        Route::post('on-site-visit/labour-parts/delete', 'OnSiteVisitController@deleteLabourParts');
        Route::post('on-site-visit/request/otp', 'OnSiteVisitController@sendCustomerOtp');
        Route::post('on-site-visit/verify/otp', 'OnSiteVisitController@verifyOtp');
        Route::post('on-site-visit/labour-part/process', 'OnSiteVisitController@processLabourPart');
        Route::post('/on-site-visit/amc-customer/save', 'OnSiteVisitController@amcCustomerSave');
        Route::post('/on-site-visit/get/part/stock-details', 'OnSiteVisitController@getPartStockDetails');
        Route::post('/on-site-visit/get/customer/address', 'OnSiteVisitController@getCustomerAddress');

        //Battery
        Route::post('/battery/get-form-data', 'BatteryController@getFormData');
        Route::post('/battery/save', 'BatteryController@save');
        Route::post('/battery/payment/save', 'BatteryController@paymentSave');
    });
});
