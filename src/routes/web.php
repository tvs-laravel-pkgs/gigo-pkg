<?php

Route::group(['namespace' => 'Abs\GigoPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'gigo-pkg'], function () {

    //Part Supplier
    Route::get('/part-supplier/get-list', 'PartSupplierController@getPartSupplierList')->name('getPartSupplierList');
    Route::get('/part-supplier/get-form-data', 'PartSupplierController@getPartSupplierFormData')->name('getPartSupplierFormData');
    Route::post('/part-supplier/save', 'PartSupplierController@savePartSupplier')->name('savePartSupplier');
    Route::get('/part-supplier/delete', 'PartSupplierController@deletePartSupplier')->name('deletePartSupplier');
    Route::get('/part-supplier/get-filter-data', 'PartSupplierController@getPartSupplierFilterData')->name('getPartSupplierFilterData');

    //Vehicle Primary Application
    Route::get('/vehicle-primary-application/get-list', 'VehiclePrimaryApplicationController@getVehiclePrimaryApplicationList')->name('getVehiclePrimaryApplicationList');
    Route::get('/vehicle-primary-application/get-form-data', 'VehiclePrimaryApplicationController@getVehiclePrimaryApplicationFormData')->name('getVehiclePrimaryApplicationFormData');
    Route::post('/vehicle-primary-application/save', 'VehiclePrimaryApplicationController@saveVehiclePrimaryApplication')->name('saveVehiclePrimaryApplication');
    Route::get('/vehicle-primary-application/delete', 'VehiclePrimaryApplicationController@deleteVehiclePrimaryApplication')->name('deleteVehiclePrimaryApplication');
    Route::get('/vehicle-primary-application/get-filter-data', 'VehiclePrimaryApplicationController@getVehiclePrimaryApplicationFilter')->name('getVehiclePrimaryApplicationFilter');

    //Vehicle Secoundary Application
    Route::get('/vehicle-secoundary-application/get-list', 'VehicleSecoundaryApplicationController@getVehicleSecoundaryAppList')->name('getVehicleSecoundaryAppList');
    Route::get('/vehicle-secoundary-application/get-form-data', 'VehicleSecoundaryApplicationController@getVehicleSecoundaryAppFormData')->name('getVehicleSecoundaryAppFormData');
    Route::post('/vehicle-secoundary-application/save', 'VehicleSecoundaryApplicationController@saveVehicleSecoundaryApp')->name('saveVehicleSecoundaryApp');
    Route::get('/vehicle-secoundary-application/delete', 'VehicleSecoundaryApplicationController@deleteVehicleSecoundaryApp')->name('deleteVehicleSecoundaryApp');
    Route::get('/vehicle-secoundary-application/get-filter-data', 'VehicleSecoundaryApplicationController@getVehicleSecoundaryAppFilterData')->name('getVehicleSecoundaryAppFilterData');

    //Vehicle Service Schedule
    Route::get('/vehicle-service-schedule/get-list', 'VehicleServiceScheduleController@getVehicleServiceScheduleList')->name('getVehicleServiceScheduleList');
    Route::get('/vehicle-service-schedule/get-form-data', 'VehicleServiceScheduleController@getVehicleServiceScheduleFormData')->name('getVehicleServiceScheduleFormData');
    Route::post('/vehicle-service-schedule/save', 'VehicleServiceScheduleController@saveVehicleServiceSchedule')->name('saveVehicleServiceSchedule');
    Route::get('/vehicle-service-schedule/delete', 'VehicleServiceScheduleController@deleteVehicleServiceSchedule')->name('deleteVehicleServiceSchedule');
    Route::get('/vehicle-service-schedule/get-filter-data', 'VehicleServiceScheduleController@getVehicleServiceScheduleFilterData')->name('getVehicleServiceScheduleFilterData');

    //Fault
    Route::get('/fault/get-list', 'FaultController@getFaultList')->name('getFaultList');
    Route::get('/fault/get-form-data', 'FaultController@getFaultFormData')->name('getFaultFormData');
    Route::post('/fault/save', 'FaultController@saveFault')->name('saveFault');
    Route::get('/fault/delete', 'FaultController@deleteFault')->name('deleteFault');
    Route::get('/fault/get-filter-data', 'FaultController@getFaultFilter')->name('getFaultFilter');

    //LV MAIN TYPES
    Route::get('/lv-main-type/get-list', 'LvMainTypeController@getLvMainTypeList')->name('getLvMainTypeList');
    Route::get('/lv-main-type/get-form-data', 'LvMainTypeController@getLvMainTypeFormData')->name('getLvMainTypeFormData');
    Route::post('/lv-main-type/save', 'LvMainTypeController@saveLvMainType')->name('saveLvMainType');
    Route::get('/lv-main-type/delete', 'LvMainTypeController@deleteLvMainType')->name('deleteLvMainType');
    Route::get('/lv-main-type/get-filter-data', 'LvMainTypeController@getLvMainTypeFilter')->name('getLvMainTypeFilter');

    //Complaint Group
    Route::get('/complaint-group/get-list', 'ComplaintGroupController@getComplaintGroupList')->name('getComplaintGroupList');
    Route::get('/complaint-group/get-form-data', 'ComplaintGroupController@getComplaintGroupFormData')->name('getComplaintGroupFormData');
    Route::post('/complaint-group/save', 'ComplaintGroupController@saveComplaintGroup')->name('saveComplaintGroup');
    Route::get('/complaint-group/delete', 'ComplaintGroupController@deleteComplaintGroup')->name('deleteComplaintGroup');
    Route::get('/complaint-group/get-filter-data', 'ComplaintGroupController@getComplaintGroupFilterData')->name('getComplaintGroupFilterData');

    //Complaint
    Route::get('/complaint/get-list', 'ComplaintController@getComplaintList')->name('getComplaintList');
    Route::get('/complaint/get-form-data', 'ComplaintController@getComplaintFormData')->name('getComplaintFormData');
    Route::post('/complaint/save', 'ComplaintController@saveComplaint')->name('saveComplaint');
    Route::get('/complaint/delete', 'ComplaintController@deleteComplaint')->name('deleteComplaint');
    Route::get('/complaint/get-filter-data', 'ComplaintController@getComplaintFilterData')->name('getComplaintFilterData');

    //Parts Indent
    Route::get('/parts-indent/get-list', 'PartsIndentController@getPartsindentList')->name('getPartsindentList');
    Route::get('/parts-indent/get-filter-data', 'PartsIndentController@getPartsIndentFilter')->name('getPartsIndentFilter');
    Route::get('/parts-indent/data', 'PartsIndentController@getPartsIndentData')->name('getPartsIndentData');
    Route::post('/parts/parts-details', 'PartsIndentController@getPartDetails')->name('getPartDetails');
    Route::post('/parts-indent/save', 'PartsIndentController@savePartsindent')->name('savePartsindent');
    Route::get('/parts-indent/issued-parts-details', 'PartsIndentController@getIssedParts')->name('getIssedParts');
    Route::get('/parts-indent/delete', 'PartsIndentController@deleteIssuedPart')->name('deleteIssuedPart');
    Route::get('/parts-indent-parts/data', 'PartsIndentController@getPartsIndentPartsData')->name('getPartsIndentPartsData');
    Route::post('/parts-indent/outlet/get-search-list', 'PartsIndentController@getOutletSearchList')->name('getOutletSearchList');

    //Pause Work Reason
    Route::get('/pause-work-reason/get-list', 'PauseWorkReasonController@getPauseWorkReasonList')->name('getPauseWorkReasonList');
    Route::get('/pause-work-reason/get-form-data', 'PauseWorkReasonController@getPauseWorkReasonFormData')->name('getPauseWorkReasonFormData');
    Route::post('/pause-work-reason/save', 'PauseWorkReasonController@savePauseWorkReason')->name('savePauseWorkReason');
    Route::get('/pause-work-reason/delete', 'PauseWorkReasonController@deletePauseWorkReason')->name('deletePauseWorkReason');
    Route::get('/pause-work-reason/get-filter-data', 'PauseWorkReasonController@getPauseWorkReasonFilterData')->name('getPauseWorkReasonFilterData');

    //Service Type
    Route::get('/service-type/get-list', 'ServiceTypeController@getServiceTypeList')->name('getServiceTypeList');
    Route::get('/service-type/get-form-data', 'ServiceTypeController@getServiceTypeFormData')->name('getServiceTypeFormData');
    Route::post('/service-type/save', 'ServiceTypeController@saveServiceType')->name('saveServiceType');
    Route::get('/service-type/delete', 'ServiceTypeController@deleteServiceType')->name('deleteServiceType');
    Route::get('/service-type/get-filter-data', 'ServiceTypeController@getServiceTypeFilterData')->name('getServiceTypeFilterData');
    Route::post('/service-type/labour/get-search-list', 'ServiceTypeController@getLabourSearchList')->name('getLabourSearchList');
    Route::post('/service-type/part/get-search-list', 'ServiceTypeController@getPartSearchList')->name('getServicePartSearchList');

    //Service Order Type
    Route::get('/service-order-type/get-list', 'ServiceOrderTypeController@getServiceOrderTypeList')->name('getServiceOrderTypeList');
    Route::get('/service-order-type/get-form-data', 'ServiceOrderTypeController@getServiceOrderTypeFormData')->name('getServiceOrderTypeFormData');
    Route::post('/service-order-type/save', 'ServiceOrderTypeController@saveServiceOrderType')->name('saveServiceOrderType');
    Route::get('/service-order-type/delete', 'ServiceOrderTypeController@deleteServiceOrderType')->name('deleteServiceOrderType');
    Route::get('/service-order-type/get-filter-data', 'ServiceOrderTypeController@getServiceOrderTypeFilterData')->name('getServiceOrderTypeFilterData');

    //Vehicle
    Route::get('/vehicle/get-list', 'VehicleController@getVehicleList')->name('getVehicleList');
    Route::get('/vehicle/get-form-data', 'VehicleController@getVehicleFormData')->name('getVehicleFormData');
    Route::post('/vehicle/save', 'VehicleController@saveVehicle')->name('saveVehicle');
    Route::get('/vehicle/delete', 'VehicleController@deleteVehicle')->name('deleteVehicle');
    Route::get('/vehicle/get-filter-data', 'VehicleController@getVehicleFilterData')->name('getVehicleFilterData');
    Route::post('/vehicle/get-model-list', 'VehicleController@getModelList')->name('getModelList');
    Route::get('/vehicle/view', 'VehicleController@getVehicles')->name('getVehicles');

    //Vehicle Owner
    Route::get('/vehicle-owner/get-list', 'VehicleOwnerController@getVehicleOwnerList')->name('getVehicleOwnerList');
    Route::get('/vehicle-owner/get-form-data', 'VehicleOwnerController@getVehicleOwnerFormData')->name('getVehicleOwnerFormData');
    Route::post('/vehicle-owner/save', 'VehicleOwnerController@saveVehicleOwner')->name('saveVehicleOwner');
    Route::get('/vehicle-owner/delete', 'VehicleOwnerController@deleteVehicleOwner')->name('deleteVehicleOwner');
    Route::get('/vehicle-owner/get-filter-data', 'VehicleOwnerController@getVehicleOwnerFilterData')->name('getVehicleOwnerFilterData');

    //Amc Member
    Route::get('/amc-member/get-list', 'AmcMemberController@getAmcMemberList')->name('getAmcMemberList');
    Route::get('/amc-member/get-form-data', 'AmcMemberController@getAmcMemberFormData')->name('getAmcMemberFormData');
    Route::post('/amc-member/save', 'AmcMemberController@saveAmcMember')->name('saveAmcMember');
    Route::get('/amc-member/delete', 'AmcMemberController@deleteAmcMember')->name('deleteAmcMember');
    Route::get('/amc-member/get-filter-data', 'AmcMemberController@getAmcMemberFilterData')->name('getAmcMemberFilterData');

    //Vehicle Warranty Member
    Route::get('/vehicle-warranty-member/get-list', 'VehicleWarrantyMemberController@getVehicleWarrantyMemberList')->name('getVehicleWarrantyMemberList');
    Route::get('/vehicle-warranty-member/get-form-data', 'VehicleWarrantyMemberController@getVehicleWarrantyMemberFormData')->name('getVehicleWarrantyMemberFormData');
    Route::post('/vehicle-warranty-member/save', 'VehicleWarrantyMemberController@saveVehicleWarrantyMember')->name('saveVehicleWarrantyMember');
    Route::get('/vehicle-warranty-member/delete', 'VehicleWarrantyMemberController@deleteVehicleWarrantyMember')->name('deleteVehicleWarrantyMember');
    Route::get('/vehicle-warranty-member/get-filter-data', 'VehicleWarrantyMemberController@getVehicleWarrantyMemberFilterData')->name('getVehicleWarrantyMemberFilterData');

    //Insurance Member
    Route::get('/insurance-member/get-list', 'InsuranceMemberController@getInsuranceMemberList')->name('getInsuranceMemberList');
    Route::get('/insurance-member/get-form-data', 'InsuranceMemberController@getInsuranceMemberFormData')->name('getInsuranceMemberFormData');
    Route::post('/insurance-member/save', 'InsuranceMemberController@saveInsuranceMember')->name('saveInsuranceMember');
    Route::get('/insurance-member/delete', 'InsuranceMemberController@deleteInsuranceMember')->name('deleteInsuranceMember');
    Route::get('/insurance-member/get-filter-data', 'InsuranceMemberController@getInsuranceMemberFilterData')->name('getInsuranceMemberFilterData');

    //Quote Type
    Route::get('/quote-type/get-list', 'QuoteTypeController@getQuoteTypeList')->name('getQuoteTypeList');
    Route::get('/quote-type/get-form-data', 'QuoteTypeController@getQuoteTypeFormData')->name('getQuoteTypeFormData');
    Route::post('/quote-type/save', 'QuoteTypeController@saveQuoteType')->name('saveQuoteType');
    Route::get('/quote-type/delete', 'QuoteTypeController@deleteQuoteType')->name('deleteQuoteType');
    Route::get('/quote-type/get-filter-data', 'QuoteTypeController@getQuoteTypeFilterData')->name('getQuoteTypeFilterData');

    //Vehicle Inventory Item
    Route::get('/vehicle-inventory-item/get-list', 'VehicleInventoryItemController@getVehicleInventoryItemList')->name('getVehicleInventoryItemList');
    Route::get('/vehicle-inventory-item/get-form-data', 'VehicleInventoryItemController@getVehicleInventoryItemFormData')->name('getVehicleInventoryItemFormData');
    Route::post('/vehicle-inventory-item/save', 'VehicleInventoryItemController@saveVehicleInventoryItem')->name('saveVehicleInventoryItem');
    Route::get('/vehicle-inventory-item/delete', 'VehicleInventoryItemController@deleteVehicleInventoryItem')->name('deleteVehicleInventoryItem');
    Route::get('/vehicle-inventory-item/get-filter-data', 'VehicleInventoryItemController@getVehicleInventoryItemFilterData')->name('getVehicleInventoryItemFilterData');

    //Vehicle Inspection Item Group
    Route::get('/vehicle-inspection-item-group/get-list', 'VehicleInspectionItemGroupController@getVehicleInspectionItemGroupList')->name('getVehicleInspectionItemGroupList');
    Route::get('/vehicle-inspection-item-group/get-form-data', 'VehicleInspectionItemGroupController@getVehicleInspectionItemGroupFormData')->name('getVehicleInspectionItemGroupFormData');
    Route::post('/vehicle-inspection-item-group/save', 'VehicleInspectionItemGroupController@saveVehicleInspectionItemGroup')->name('saveVehicleInspectionItemGroup');
    Route::get('/vehicle-inspection-item-group/delete', 'VehicleInspectionItemGroupController@deleteVehicleInspectionItemGroup')->name('deleteVehicleInspectionItemGroup');
    Route::get('/vehicle-inspection-item-group/get-filter-data', 'VehicleInspectionItemGroupController@getVehicleInspectionItemGroupFilterData')->name('getVehicleInspectionItemGroupFilterData');

    //Vehicle Inspection Item
    Route::get('/vehicle-inspection-item/get-list', 'VehicleInspectionItemController@getVehicleInspectionItemList')->name('getVehicleInspectionItemList');
    Route::get('/vehicle-inspection-item/get-form-data', 'VehicleInspectionItemController@getVehicleInspectionItemFormData')->name('getVehicleInspectionItemFormData');
    Route::post('/vehicle-inspection-item/save', 'VehicleInspectionItemController@saveVehicleInspectionItem')->name('saveVehicleInspectionItem');
    Route::get('/vehicle-inspection-item/delete', 'VehicleInspectionItemController@deleteVehicleInspectionItem')->name('deleteVehicleInspectionItem');
    Route::get('/vehicle-inspection-item/get-filter-data', 'VehicleInspectionItemController@getVehicleInspectionItemFilterData')->name('getVehicleInspectionItemFilterData');

    //Customer Voice
    Route::get('/customer-voice/get-list', 'CustomerVoiceController@getCustomerVoiceList')->name('getCustomerVoiceList');
    Route::get('/customer-voice/get-form-data', 'CustomerVoiceController@getCustomerVoiceFormData')->name('getCustomerVoiceFormData');
    Route::post('/customer-voice/save', 'CustomerVoiceController@saveCustomerVoice')->name('saveCustomerVoice');
    Route::get('/customer-voice/delete', 'CustomerVoiceController@deleteCustomerVoice')->name('deleteCustomerVoice');
    Route::get('/customer-voice/get-filter-data', 'CustomerVoiceController@getCustomerVoiceFilterData')->name('getCustomerVoiceFilterData');

    //Split Order Types
    Route::get('/split-order-type/get-list', 'SplitOrderTypeController@getSplitOrderTypeList')->name('getSplitOrderTypeList');
    Route::get('/split-order-type/get-form-data', 'SplitOrderTypeController@getSplitOrderTypeFormData')->name('getSplitOrderTypeFormData');
    Route::post('/split-order-type/save', 'SplitOrderTypeController@saveSplitOrderType')->name('saveSplitOrderType');
    Route::get('/split-order-type/delete', 'SplitOrderTypeController@deleteSplitOrderType')->name('deleteSplitOrderType');
    Route::get('/split-order-type/get-filter-data', 'SplitOrderTypeController@getSplitOrderTypeFilter')->name('getSplitOrderTypeFilter');

    //Bays
    Route::get('/bay/get-list', 'BayController@getBayList')->name('getBayList');
    Route::get('/bay/get-form-data', 'BayController@getBayFormData')->name('getBayFormData');
    Route::post('/bay/save', 'BayController@saveBay')->name('saveBay');
    Route::get('/bay/delete', 'BayController@deleteBay')->name('deleteBay');
    Route::get('/bay/get-filter-data', 'BayController@getBayFilter')->name('getBayFilter');

    //Campaign
    Route::get('/campaign/get-list', 'CompaignController@getCampaignList')->name('getCampaignList');
    Route::get('/campaign/get-form-data', 'CompaignController@getCampaignFormData')->name('getCampaignFormData');
    Route::post('/campaign/save', 'CompaignController@saveCampaign')->name('saveCampaign');
    Route::get('/campaign/delete', 'CompaignController@deleteCampaign')->name('deleteCampaign');
    Route::get('/campaign/get-filter-data', 'CompaignController@getCampaignFilterData')->name('getCampaignFilterData');
    Route::post('/campaign/vehicle-model/get-search-list', 'VehicleInwardController@getVehicleModelSearchList')->name('getVehicleModelSearchList');
    Route::post('/campaign/delete-chassis', 'CompaignController@deleteCampignChassis')->name('deleteCampignChassis');

    //Estimation Type
    Route::get('/estimation-type/get-list', 'EstimationTypeController@getEstimationTypeList')->name('getEstimationTypeList');
    Route::get('/estimation-type/get-form-data', 'EstimationTypeController@getEstimationTypeFormData')->name('getEstimationTypeFormData');
    Route::post('/estimation-type/save', 'EstimationTypeController@saveEstimationType')->name('saveEstimationType');
    Route::get('/estimation-type/delete', 'EstimationTypeController@deleteEstimationType')->name('deleteEstimationType');
    Route::get('/estimation-type/get-filter-data', 'EstimationTypeController@getEstimationTypeFilter')->name('getEstimationTypeFilter');

    //Trade Plate Number
    Route::get('/trade-plate-number/get-list', 'TradePlateNumberController@getTradePlateNumberList')->name('getTradePlateNumberList');
    Route::get('/trade-plate-number/get-form-data', 'TradePlateNumberController@getTradePlateNumberFormData')->name('getTradePlateNumberFormData');
    Route::post('/trade-plate-number/save', 'TradePlateNumberController@saveTradePlateNumber')->name('saveTradePlateNumber');
    Route::get('/trade-plate-number/delete', 'TradePlateNumberController@deleteTradePlateNumber')->name('deleteTradePlateNumber');
    Route::get('/trade-plate-number/get-filter-data', 'TradePlateNumberController@getTradePlateNumberFilter')->name('getTradePlateNumberFilter');

    //Survey
    Route::get('/survey-type/get-list', 'SurveyTypeController@getSurveyTypeList')->name('getSurveyTypeList');
    Route::get('/survey-type/get-form-data', 'SurveyTypeController@getSurveyTypeFormData')->name('getSurveyTypeFormData');
    Route::post('/survey-type/save', 'SurveyTypeController@saveSurveyType')->name('saveSurveyType');
    Route::get('/survey-type/delete', 'SurveyTypeController@deleteSurveyType')->name('deleteSurveyType');
    Route::get('/survey-type/get-filter-data', 'SurveyTypeController@getSurveyTypeFilter')->name('getSurveyTypeFilter');

    //Gate Pass
    Route::get('/gate-pass/get-list', 'GatePassController@getGatePassList')->name('getGatePassList');
    Route::get('/gate-pass/get-form-data', 'GatePassController@getGatePassFormData')->name('getGatePassFormData');
    Route::post('/gate-pass/save', 'GatePassController@saveGatePass')->name('saveGatePass');
    Route::get('/gate-pass/delete', 'GatePassController@deleteGatePass')->name('deleteGatePass');
    Route::get('/gate-pass/get-filter-data', 'GatePassController@getGatePassFilterData')->name('getGatePassFilterData');
    Route::post('/gate-pass/jobcard/get-search-list', 'GatePassController@getJobCardSearchList')->name('getJobCardSearchList');
    Route::post('/gate-pass/user/get-search-list', 'GatePassController@getUserSearchList')->name('getUserSearchList');

    //Material Gate Pass
    Route::get('/material-gate-pass/get-list', 'MaterialGatePassController@getMaterialGatePassList')->name('getMaterialGatePassList');
    Route::get('/material-gate-pass/get-filter-data', 'MaterialGatePassController@getMaterialGatePassFilter')->name('getMaterialGatePassFilter');

    //Vehicle Gate Pass
    Route::get('/vehicle-gate-pass/get-list', 'VehicleGatePassController@getVehicleGatePassList')->name('getVehicleGatePassList');
    Route::get('/vehicle-gate-pass/get-filter-data', 'VehicleGatePassController@getVehicleGatePassFilter')->name('getVehicleGatePassFilter');

    //Road Test Gate Pass
    Route::get('/road-test-gate-pass/get-list', 'RoadTestGatePassController@getRoadTestGatePassList')->name('getRoadTestGatePassList');
    Route::get('/road-test-gate-pass/get-filter-data', 'RoadTestGatePassController@getRoadTestGatePassFilter')->name('getRoadTestGatePassFilter');

    //Floating Gate Pass
    Route::get('/floating-gate-pass/get-list', 'FloatingGatePassController@getFloatingGatePassList')->name('getFloatingGatePassList');
    Route::get('/floating-gate-pass/get-filter-data', 'FloatingGatePassController@getFloatingGatePassFilter')->name('getFloatingGatePassFilter');

    //Gate Log
    Route::get('gate-log/get-list', 'Api\GateInController@getGateLogList')->name('getGateLogList');
    Route::get('/gate-log/get-filter-data', 'Api\GateInController@getGateLogFilter')->name('getGateLogFilter');
    Route::post('/gate-log/delete', 'Api\GateInController@deleteGateLog')->name('deleteGateLog');
    Route::post('/gatelog/vehicle/search', 'Api\GateInController@getVehicleSearchList')->name('getVehicleSearchList');

    //Gate Log
    // Route::get('/gate-log/get-list', 'GateLogController@getGateLogList')->name('getGateLogList');
    // Route::get('/gate-log/get-form-data', 'GateLogController@getGateLogFormData')->name('getGateLogFormData');
    // Route::post('/gate-log/save', 'GateLogController@saveGateLog')->name('saveGateLog');
    // Route::get('/gate-log/get-filter-data', 'GateLogController@getGateLogFilterData')->name('getGateLogFilterData');

    //Vehicle Inward
    Route::get('/vehicle-inward/get-list', 'VehicleInwardController@getVehicleInwardList')->name('getVehicleInwardList');
    Route::get('/vehicle-inward/get-filter-data', 'VehicleInwardController@getVehicleInwardFilter')->name('getVehicleInwardFilter');
    Route::post('/vehicle-inward/customer/get-search-list', 'VehicleInwardController@getCustomerSearchList')->name('getCustomerSearchList');
    Route::post('/vehicle-inward/vehicle-model/get-search-list', 'VehicleInwardController@getVehicleModelSearchList')->name('getVehicleModelSearchList');

    Route::post('/vehicle-inward/city/get-search-list', 'VehicleInwardController@getCitySearchList')->name('getCitySearchList');
    Route::post('/vehicle-inward/part/get-search-list', 'VehicleInwardController@getPartSearchList')->name('getPartSearchList');

    //Manual Vehicle Deleivery
    Route::get('/manual-vehicle-delivery/get-list', 'ManualVehicleDeliveryController@getManualDeliveryVehicleList')->name('getManualDeliveryVehicleList');
    Route::get('/manual-vehicle-delivery/get-filter-data', 'ManualVehicleDeliveryController@getManualDeliveryVehicleFilter')->name('getManualDeliveryVehicleFilter');
    Route::post('/manual-vehicle-delivery/export', 'ManualVehicleDeliveryController@export')->name('getManualDeliveryVehicleExport');

    //TVS One Discount
    Route::get('/tvs-one/discount-request/get-list', 'ManualVehicleDeliveryController@getTVSOneRequestList')->name('getTVSOneRequestList');
    Route::get('/tvs-one/discount-request/get-filter-data', 'ManualVehicleDeliveryController@getTVSOneRequestFilter')->name('getTVSOneRequestFilter');
    Route::post('/tvs-one/discount-request/export', 'ManualVehicleDeliveryController@exportTvsOneDiscount')->name('exportTvsOneDiscount');

    //On Site Visit
    Route::get('/on-site-visit/get-list', 'OnSiteVisitController@getOnSiteVisitList')->name('getOnSiteVisitList');
    //On Site REPAIR ORDER SEARCH
    Route::post('on-site-visit/repair-order/search', 'OnSiteVisitController@getRepairOrderSearchList')->name('getRepairOrderSearchList');

    // Route::get('/manual-vehicle-delivery/get-filter-data', 'ManualVehicleDeliveryController@getManualDeliveryVehicleFilter')->name('getManualDeliveryVehicleFilter');
    // Route::post('/manual-vehicle-delivery/export', 'ManualVehicleDeliveryController@export')->name('getManualDeliveryVehicleExport');

    //Job Order
    Route::get('/job-order/get-list', 'JobOrderController@getJobOrderList')->name('getJobOrderList');
    Route::get('/job-order/get-form-data', 'JobOrderController@getJobOrderFormData')->name('getJobOrderFormData');
    Route::post('/job-order/save', 'JobOrderController@saveJobOrder')->name('saveJobOrder');
    Route::get('/job-order/delete', 'JobOrderController@deleteJobOrder')->name('deleteJobOrder');
    Route::get('/job-order/get-filter-data', 'JobOrderController@getJobOrderFilterData')->name('getJobOrderFilterData');

    //Job Order Repair Order
    Route::get('/job-order-repair-order/get-list', 'JobOrderRepairOrderController@getJobOrderRepairOrderList')->name('getJobOrderRepairOrderList');
    Route::get('/job-order-repair-order/get-form-data', 'JobOrderRepairOrderController@getJobOrderRepairOrderFormData')->name('getJobOrderRepairOrderFormData');
    Route::post('/job-order-repair-order/save', 'JobOrderRepairOrderController@saveJobOrderRepairOrder')->name('saveJobOrderRepairOrder');
    Route::get('/job-order-repair-order/delete', 'JobOrderRepairOrderController@deleteJobOrderRepairOrder')->name('deleteJobOrderRepairOrder');
    Route::get('/job-order-repair-order/get-filter-data', 'JobOrderRepairOrderController@getJobOrderRepairOrderFilterData')->name('getJobOrderRepairOrderFilterData');

    //Repair Order Mechanic
    Route::get('/repair-order-mechanic/get-list', 'RepairOrderMechanicController@getRepairOrderMechanicList')->name('getRepairOrderMechanicList');
    Route::get('/repair-order-mechanic/get-form-data', 'RepairOrderMechanicController@getRepairOrderMechanicFormData')->name('getRepairOrderMechanicFormData');
    Route::post('/repair-order-mechanic/save', 'RepairOrderMechanicController@saveRepairOrderMechanic')->name('saveRepairOrderMechanic');
    Route::get('/repair-order-mechanic/delete', 'RepairOrderMechanicController@deleteRepairOrderMechanic')->name('deleteRepairOrderMechanic');
    Route::get('/repair-order-mechanic/get-filter-data', 'RepairOrderMechanicController@getRepairOrderMechanicFilterData')->name('getRepairOrderMechanicFilterData');

    //Mechanic Time Log
    Route::get('/mechanic-time-log/get-list', 'MechanicTimeLogController@getMechanicTimeLogList')->name('getMechanicTimeLogList');
    Route::get('/mechanic-time-log/get-form-data', 'MechanicTimeLogController@getMechanicTimeLogFormData')->name('getMechanicTimeLogFormData');
    Route::post('/mechanic-time-log/save', 'MechanicTimeLogController@saveMechanicTimeLog')->name('saveMechanicTimeLog');
    Route::get('/mechanic-time-log/delete', 'MechanicTimeLogController@deleteMechanicTimeLog')->name('deleteMechanicTimeLog');
    Route::get('/mechanic-time-log/get-filter-data', 'MechanicTimeLogController@getMechanicTimeLogFilterData')->name('getMechanicTimeLogFilterData');

    //Job Order Part
    Route::get('/job-order-part/get-list', 'JobOrderPartController@getJobOrderPartList')->name('getJobOrderPartList');
    Route::get('/job-order-part/get-form-data', 'JobOrderPartController@getJobOrderPartFormData')->name('getJobOrderPartFormData');
    Route::post('/job-order-part/save', 'JobOrderPartController@saveJobOrderPart')->name('saveJobOrderPart');
    Route::get('/job-order-part/delete', 'JobOrderPartController@deleteJobOrderPart')->name('deleteJobOrderPart');
    Route::get('/job-order-part/get-filter-data', 'JobOrderPartController@getJobOrderPartFilterData')->name('getJobOrderPartFilterData');

    //Job Order Issued Part
    Route::get('/job-order-issued-part/get-list', 'JobOrderIssuedPartController@getJobOrderIssuedPartList')->name('getJobOrderIssuedPartList');
    Route::get('/job-order-issued-part/get-form-data', 'JobOrderIssuedPartController@getJobOrderIssuedPartFormData')->name('getJobOrderIssuedPartFormData');
    Route::post('/job-order-issued-part/save', 'JobOrderIssuedPartController@saveJobOrderIssuedPart')->name('saveJobOrderIssuedPart');
    Route::get('/job-order-issued-part/delete', 'JobOrderIssuedPartController@deleteJobOrderIssuedPart')->name('deleteJobOrderIssuedPart');
    Route::get('/job-order-issued-part/get-filter-data', 'JobOrderIssuedPartController@getJobOrderIssuedPartFilterData')->name('getJobOrderIssuedPartFilterData');

    //Job Card
    Route::get('/job-card/get-list', 'JobCardController@getJobCardList')->name('getJobCardTableList');
    Route::get('/job-card/table/get-filter-data', 'JobCardController@getJobCardFilter')->name('getJobCardFilter');

    Route::get('/job-card/get-form-data', 'JobCardController@getJobCardFormData')->name('getJobCardFormData');
    Route::post('/job-card/save', 'JobCardController@saveJobCard')->name('saveJobCard');
    Route::get('/job-card/delete', 'JobCardController@deleteJobCard')->name('deleteJobCard');
    Route::get('/job-card/get-filter-data', 'JobCardController@getJobCardFilterData')->name('getJobCardFilterData');
    Route::post('/job-card/get-vendor', 'JobCardController@getVendorCodeSearchList')->name('getVendorCodeSearchList');
    Route::post('/job-card/get-vendor-details', 'JobCardController@getVendorDetails')->name('getVendorDetails');

    //My jobcard Kanban app
    Route::get('/myjob-card-table/get-list/{id}', 'JobCardController@getMyJobCardtableList')->name('getMyJobCardtableList');

    Route::get('/myjob-card-time-sheet/get-list/{id}', 'Api\MyJobCardController@getMyJobCardtimeSheetList')->name('getMyJobCardtimeSheetList');
    Route::get('/myjob-card-time-sheet/user-details', 'Api\MyJobCardController@getMyJobCarduserDetails')->name('getMyJobCarduserDetails');

    //Job Card PDF
    Route::get('/pdf/gatepass/{id}', 'PDFController@gatePass')->name('gatePass');
    Route::get('/pdf/covering-letter/{id}', 'PDFController@coveringletter')->name('coveringletter');
    Route::get('/pdf/estimate/{id}', 'PDFController@estimate')->name('estimate');
    Route::get('/pdf/job-order/revised-estimate/{id}', 'PDFController@revisedEstimateJobOrder')->name('revisedEstimateJobOrder');
    Route::get('/pdf/insurance-estimate/{id}', 'PDFController@InsuranceEstimate')->name('InsuranceEstimate');
    Route::get('/pdf/revised-estimate/{id}', 'PDFController@RevisedEstimate')->name('RevisedEstimate');
    Route::get('/pdf/job-card/{id}', 'PDFController@JobCardPDF')->name('JobCardPDF');
    Route::get('/pdf/job-card-spare-requisition/{id}', 'PDFController@JobCardrequisitionPDF')->name('JobCardrequisitionPDF');
    Route::get('/pdf/work-order-outward/{id}/{gate_pass_id}', 'PDFController@WorkorderOutwardPDF')->name('WorkorderOutwardPDF');
    Route::get('/pdf/work-order-inward/{id}/{gate_pass_id}', 'PDFController@WorkorderInwardPDF')->name('WorkorderInwardPDF');
    Route::get('/pdf/warrenty-pick-list/{id}', 'PDFController@WarrentyPickListPDF')->name('WarrentyPickListPDF');
    Route::get('/pdf/vehicle-inward/{id}', 'PDFController@VehicleInwardPDF')->name('VehicleInwardPDF');
    Route::get('/pdf/vehicle-inspection/{id}', 'PDFController@VehicleInspectionPDF')->name('VehicleInspectionPDF');
    Route::get('/pdf/tax-invoice/{id}', 'PDFController@TaxInvoicePDF')->name('TaxInvoicePDF');
    Route::get('/pdf/service-proforma/{id}', 'PDFController@serviceProformaPDF')->name('serviceProformaPDF');
    Route::get('/pdf/service-proforma-cumulative/{id}', 'PDFController@serviceProformaCumulativePDF')->name('serviceProformaCumulativePDF');
    Route::get('/pdf/job-card/bill-detail/{id}/{split_order_type_id}', 'PDFController@JobCardBillDetailPDF')->name('JobCardBillDetailPDF');
    //LABOUR
    Route::get('/pdf/job-card/labour/bill-detail/{id}', 'PDFController@LabourBillDeatilPDF')->name('LabourBillDeatilPDF');
    //PART
    Route::get('/pdf/job-card/part/bill-detail/{id}', 'PDFController@PartBillDetailPDF')->name('PartBillDetailPDF');

    //Repair Order Types
    Route::get('/repair-order-type/get-list', 'RepairOrderTypeController@getRepairOrderTypeList')->name('getRepairOrderTypeList');
    Route::get('/repair-order-type/get-form-data', 'RepairOrderTypeController@getRepairOrderTypeFormData')->name('getRepairOrderTypeFormData');
    Route::post('/repair-order-type/form-save', 'RepairOrderTypeController@saveRepairOrderType')->name('saveRepairOrderType');
    Route::get('/repair-order-type/get-filter', 'RepairOrderTypeController@getRepairOrderTypeFilter')->name('getRepairOrderTypeFilter');
    Route::get('/repair-order-type/delete', 'RepairOrderTypeController@deleteRepairOrderType')->name('deleteRepairOrderType');
    Route::get('/repair-order-type/view', 'RepairOrderTypeController@getRepairOrderTypeView')->name('getRepairOrderTypeView');

    //Repair Order
    Route::get('/repair-order/get-form-data', 'RepairOrderController@getRepairOrderFormData')->name('getRepairOrderFormData');
    Route::post('/repair-order/form-save', 'RepairOrderController@saveRepairOrder')->name('saveRepairOrder');
    Route::get('/repair-order/get-list', 'RepairOrderController@getRepairOrderList')->name('getRepairOrderList');
    Route::get('/repair-order/get-filter', 'RepairOrderController@getRepairOrderFilter')->name('getRepairOrderFilter');
    Route::get('/repair-order/delete', 'RepairOrderController@deleteRepairOrder')->name('deleteRepairOrder');

    //OTP
    Route::get('/otp/get-list', 'OTPController@getOTPList')->name('getOTPList');
    Route::get('/otp/get-filter-data', 'OTPController@getOTPFilter')->name('getOTPFilter');

    //Battery
    Route::get('/battery/get-list', 'BatteryController@getBatteryList')->name('getBatteryList');
    Route::get('/battery/get-filter-data', 'BatteryController@getBatteryFilterData')->name('getBatteryFilterData');
    Route::post('/battery/export', 'BatteryController@export')->name('exportBatteryLoadTest');

    Route::post('gigo/job-card/export', 'VehicleInwardController@export')->name('exportJobCardReport');

});
