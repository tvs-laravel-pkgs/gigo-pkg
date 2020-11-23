app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.

    //Vehicle Primary Application
    when('/gigo-pkg/vehicle-primary-application/list', {
        template: '<vehicle-primary-application-list></vehicle-primary-application-list>',
        title: 'Vehicle Primary Applications',
    }).
    when('/gigo-pkg/vehicle-primary-application/add', {
        template: '<vehicle-primary-application-form></vehicle-primary-application-form>',
        title: 'Add Vehicle Primary Application',
    }).
    when('/gigo-pkg/vehicle-primary-application/edit/:id', {
        template: '<vehicle-primary-application-form></vehicle-primary-application-form>',
        title: 'Edit Vehicle Primary Application',
    }).

    //Fault
    when('/gigo-pkg/fault/list', {
        template: '<fault-list></fault-list>',
        title: 'Faults',
    }).
    when('/gigo-pkg/fault/add', {
        template: '<fault-form></fault-form>',
        title: 'Add Fault',
    }).
    when('/gigo-pkg/fault/edit/:id', {
        template: '<fault-form></fault-form>',
        title: 'Edit Fault',
    }).

    //LV Main Type
    when('/gigo-pkg/lv-main-type/list', {
        template: '<lv-main-type-list></lv-main-type-list>',
        title: 'LV Main Types',
    }).
    when('/gigo-pkg/lv-main-type/add', {
        template: '<lv-main-type-form></lv-main-type-form>',
        title: 'Add LV Main Type',
    }).
    when('/gigo-pkg/lv-main-type/edit/:id', {
        template: '<lv-main-type-form></lv-main-type-form>',
        title: 'Edit LV Main Type',
    }).

    //Repair Order Types
    when('/gigo-pkg/repair-order-type/list', {
        template: '<repair-order-type-list></repair-order-type-list>',
        title: 'Repair Order Types',
    }).
    when('/gigo-pkg/repair-order-type/add', {
        template: '<repair-order-type-form></repair-order-type-form>',
        title: 'Add Repair Order Type',
    }).
    when('/gigo-pkg/repair-order-type/edit/:id', {
        template: '<repair-order-type-form></repair-order-type-form>',
        title: 'Edit Repair Order Type',
    }).
    when('/gigo-pkg/repair-order-type/view/:id', {
        template: '<repair-order-type-view></repair-order-type-view>',
        title: 'View Repair Order Type',
    }).

    //Repair Order
    when('/gigo-pkg/repair-order/list', {
        template: '<repair-order-list></repair-order-list>',
        title: 'Repair Orders',
    }).
    when('/gigo-pkg/repair-order/add', {
        template: '<repair-order-form></repair-order-form>',
        title: 'Add Repair Order',
    }).
    when('/gigo-pkg/repair-order/edit/:id', {
        template: '<repair-order-form></repair-order-form>',
        title: 'Edit Repair Order',
    }).
    when('/gigo-pkg/repair-order/view/:id', {
        template: '<repair-order-view></repair-order-view>',
        title: 'View Repair Order',
    }).

    //Service Type
    when('/gigo-pkg/service-type/list', {
        template: '<service-type-list></service-type-list>',
        title: 'Service Types',
    }).
    when('/gigo-pkg/service-type/add', {
        template: '<service-type-form></service-type-form>',
        title: 'Add Service Type',
    }).
    when('/gigo-pkg/service-type/edit/:id', {
        template: '<service-type-form></service-type-form>',
        title: 'Edit Service Type',
    }).

    //Service Order Type
    when('/gigo-pkg/service-order-type/list', {
        template: '<service-order-type-list></service-order-type-list>',
        title: 'Service Order Types',
    }).
    when('/gigo-pkg/service-order-type/add', {
        template: '<service-order-type-form></service-order-type-form>',
        title: 'Add Service Order Type',
    }).
    when('/gigo-pkg/service-order-type/edit/:id', {
        template: '<service-order-type-form></service-order-type-form>',
        title: 'Edit Service Order Type',
    }).

    //Quote Type
    when('/gigo-pkg/quote-type/list', {
        template: '<quote-type-list></quote-type-list>',
        title: 'Quote Types',
    }).
    when('/gigo-pkg/quote-type/add', {
        template: '<quote-type-form></quote-type-form>',
        title: 'Add Quote Type',
    }).
    when('/gigo-pkg/quote-type/edit/:id', {
        template: '<quote-type-form></quote-type-form>',
        title: 'Edit Quote Type',
    }).

    //Pause Work Reason Master
    when('/gigo-pkg/pasuse-work-reason/list', {
        template: '<pause-work-reason-list></pause-work-reason-list>',
        title: 'Pause Work',
    }).
    when('/gigo-pkg/pause-work-reason/add', {
        template: '<pause-work-reason-form></pause-work-reason-form>',
        title: 'Add Pause Work',
    }).
    when('/gigo-pkg/pause-work-reason/edit/:id', {
        template: '<pause-work-reason-form></pause-work-reason-form>',
        title: 'Edit Pause Work',
    }).

    //Material Gate Pass
    when('/material-gate-pass/table-list', {
        template: '<material-gate-pass-table-list></material-gate-pass-table-list>',
        title: 'Material Gate Pass - Table List',
    }).
    when('/material-gate-pass/card-list', {
        template: '<material-gate-pass-card-list></material-gate-pass-card-list>',
        title: 'Material Gate Pass - Card List',
    }).
    when('/material-gate-pass/view/:id', {
        template: '<material-gate-pass-view></material-gate-pass-view>',
        title: 'View Material Gate Pass',
    }).

    //Vehicle Master
    when('/vehicle/list', {
        template: '<vehicle-list></vehicle-list>',
        title: 'Vehicles',
    }).
    when('/vehicle/add', {
        template: '<vehicle-form></vehicle-form>',
        title: 'Add Vehicle',
    }).
    when('/vehicle/edit/:id', {
        template: '<vehicle-form></vehicle-form>',
        title: 'Edit Vehicle',
    }).
    when('/vehicle/view/:id', {
        template: '<vehicle-data-view></vehicle-data-view>',
        title: 'View Vehicle',
    }).

    //Parts Indent
    when('/part-indent/list', {
        template: '<parts-indent-list></parts-indent-list>',
        title: 'Parts Indent',
    }).
    when('/part-indent/vehicle/view/:job_order_id', {
        template: '<parts-indent-vehicle-view></parts-indent-vehicle-view>',
        title: 'View Vehicle',
    }).
    when('/part-indent/customer/view/:job_order_id', {
        template: '<parts-indent-customer-view></parts-indent-customer-view>',
        title: 'View Customer',
    }).
    when('/part-indent/repair-order/view/:job_order_id', {
        template: '<parts-indent-repair-order-view></parts-indent-repair-order-view>',
        title: 'View Repair Order',
    }).
    when('/part-indent/parts/view/:job_order_id', {
        template: '<parts-indent-parts-view></parts-indent-parts-view>',
        title: 'View Parts',
    }).
    when('/part-indent/issue-part/form/:job_order_id/:id?', {
        template: '<parts-indent-issue-part-form></parts-indent-issue-part-form>',
        title: 'Issue Part',
    }).
    when('/part-indent/issue-bulk-part/form/:job_order_id', {
        template: '<parts-indent-issue-bulk-part-form></parts-indent-issue-bulk-part-form>',
        title: 'Issue Bulk Part',
    }).

    //Complaint Group
    when('/gigo-pkg/complaint-group/list', {
        template: '<complaint-group-list></complaint-group-list>',
        title: 'Complaint Groups',
    }).
    when('/gigo-pkg/complaint-group/add', {
        template: '<complaint-group-form></complaint-group-form>',
        title: 'Add Complaint Group',
    }).
    when('/gigo-pkg/complaint-group/edit/:id', {
        template: '<complaint-group-form></complaint-group-form>',
        title: 'Edit Complaint Group',
    }).

    //Complaint
    when('/gigo-pkg/complaint/list', {
        template: '<complaint-list></complaint-list>',
        title: 'Complaint',
    }).
    when('/gigo-pkg/complaint/add', {
        template: '<complaint-form></complaint-form>',
        title: 'Add Complaint',
    }).
    when('/gigo-pkg/complaint/edit/:id', {
        template: '<complaint-form></complaint-form>',
        title: 'Edit Complaint',
    }).

    //Part Supplier
    when('/gigo-pkg/part-supplier/list', {
        template: '<part-supplier-list></part-supplier-list>',
        title: 'Part Supplier',
    }).
    when('/gigo-pkg/part-supplier/add', {
        template: '<part-supplier-form></part-supplier-form>',
        title: 'Add Part Supplier',
    }).
    when('/gigo-pkg/part-supplier/edit/:id', {
        template: '<part-supplier-form></part-supplier-form>',
        title: 'Edit Part Supplier',
    }).


    //Vehicle Secoundary Application
    when('/gigo-pkg/vehicle-secoundary-application/list', {
        template: '<vehicle-secoundary-application-list></vehicle-secoundary-application-list>',
        title: 'Vehicle Secoundary Application',
    }).
    when('/gigo-pkg/vehicle-secoundary-application/add', {
        template: '<vehicle-secoundary-application-form></vehicle-secoundary-application-form>',
        title: 'Add Vehicle Secoundary Application',
    }).
    when('/gigo-pkg/vehicle-secoundary-application/edit/:id', {
        template: '<vehicle-secoundary-application-form></vehicle-secoundary-application-form>',
        title: 'Edit Vehicle Secoundary Application',
    }).

    //Vehicle Service Schedule
    when('/gigo-pkg/vehicle-service-schedule/list', {
        template: '<vehicle-service-schedule-list></vehicle-service-schedule-list>',
        title: 'Vehicle Service Schedules',
    }).
    when('/gigo-pkg/vehicle-service-schedule/add', {
        template: '<vehicle-service-schedule-form></vehicle-service-schedule-form>',
        title: 'Add Vehicle Service Schedule',
    }).
    when('/gigo-pkg/vehicle-service-schedule/edit/:id', {
        template: '<vehicle-service-schedule-form></vehicle-service-schedule-form>',
        title: 'Edit Vehicle Service Schedule',
    }).
    when('/gigo-pkg/vehicle-service-schedule/view/:id', {
        template: '<vehicle-service-schedule-view></vehicle-service-schedule-view>',
        title: 'View Vehicle Service Schedule',
    }).

    //Kanban App
    when('/kanban-app', {
        template: '<kanban-app></kanban-app>',
        title: 'Kanban App',
    }).
    //Kanban Attendance Scan Qr
    when('/kanban-app/attendance/scan-qr', {
        template: '<kanban-app-attendance-scan-qr></kanban-app-attendance-scan-qr>',
        title: 'Attendance - Scan Qr',
    }).
    //Kanban My Job Card Scan Qr
    when('/kanban-app/my-job-card/scan-qr', {
        template: '<kanban-app-my-job-card-scan-qr></kanban-app-my-job-card-scan-qr>',
        title: 'My Job Card - Scan Qr',
    }).
    //Kanban My Time Sheet Scan Qr
    when('/kanban-app/my-time-sheet/scan-qr', {
        template: '<kanban-app-my-time-sheet-scan-qr></kanban-app-my-time-sheet-scan-qr>',
        title: 'My Time Sheet - Scan Qr',
    });

}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Vehicle Owner
    when('/gigo-pkg/vehicle-owner/list', {
        template: '<vehicle-owner-list></vehicle-owner-list>',
        title: 'Vehicle Owners',
    }).
    when('/gigo-pkg/vehicle-owner/add', {
        template: '<vehicle-owner-form></vehicle-owner-form>',
        title: 'Add Vehicle Owner',
    }).
    when('/gigo-pkg/vehicle-owner/edit/:id', {
        template: '<vehicle-owner-form></vehicle-owner-form>',
        title: 'Edit Vehicle Owner',
    }).
    when('/gigo-pkg/vehicle-owner/card-list', {
        template: '<vehicle-owner-card-list></vehicle-owner-card-list>',
        title: 'Vehicle Owner Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Amc Member
    when('/gigo-pkg/amc-member/list', {
        template: '<amc-member-list></amc-member-list>',
        title: 'Amc Members',
    }).
    when('/gigo-pkg/amc-member/add', {
        template: '<amc-member-form></amc-member-form>',
        title: 'Add Amc Member',
    }).
    when('/gigo-pkg/amc-member/edit/:id', {
        template: '<amc-member-form></amc-member-form>',
        title: 'Edit Amc Member',
    }).
    when('/gigo-pkg/amc-member/card-list', {
        template: '<amc-member-card-list></amc-member-card-list>',
        title: 'Amc Member Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Vehicle Warranty Member
    when('/gigo-pkg/vehicle-warranty-member/list', {
        template: '<vehicle-warranty-member-list></vehicle-warranty-member-list>',
        title: 'Vehicle Warranty Members',
    }).
    when('/gigo-pkg/vehicle-warranty-member/add', {
        template: '<vehicle-warranty-member-form></vehicle-warranty-member-form>',
        title: 'Add Vehicle Warranty Member',
    }).
    when('/gigo-pkg/vehicle-warranty-member/edit/:id', {
        template: '<vehicle-warranty-member-form></vehicle-warranty-member-form>',
        title: 'Edit Vehicle Warranty Member',
    }).
    when('/gigo-pkg/vehicle-warranty-member/card-list', {
        template: '<vehicle-warranty-member-card-list></vehicle-warranty-member-card-list>',
        title: 'Vehicle Warranty Member Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Insurance Member
    when('/gigo-pkg/insurance-member/list', {
        template: '<insurance-member-list></insurance-member-list>',
        title: 'Insurance Members',
    }).
    when('/gigo-pkg/insurance-member/add', {
        template: '<insurance-member-form></insurance-member-form>',
        title: 'Add Insurance Member',
    }).
    when('/gigo-pkg/insurance-member/edit/:id', {
        template: '<insurance-member-form></insurance-member-form>',
        title: 'Edit Insurance Member',
    }).
    when('/gigo-pkg/insurance-member/card-list', {
        template: '<insurance-member-card-list></insurance-member-card-list>',
        title: 'Insurance Member Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Vehicle Inventory Item
    when('/gigo-pkg/vehicle-inventory-item/list', {
        template: '<vehicle-inventory-item-list></vehicle-inventory-item-list>',
        title: 'Vehicle Inventory Items',
    }).
    when('/gigo-pkg/vehicle-inventory-item/add', {
        template: '<vehicle-inventory-item-form></vehicle-inventory-item-form>',
        title: 'Add Vehicle Inventory Item',
    }).
    when('/gigo-pkg/vehicle-inventory-item/edit/:id', {
        template: '<vehicle-inventory-item-form></vehicle-inventory-item-form>',
        title: 'Edit Vehicle Inventory Item',
    }).
    when('/gigo-pkg/vehicle-inventory-item/card-list', {
        template: '<vehicle-inventory-item-card-list></vehicle-inventory-item-card-list>',
        title: 'Vehicle Inventory Item Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Vehicle Inspection Item Group
    when('/gigo-pkg/vehicle-inspection-item-group/list', {
        template: '<vehicle-inspection-item-group-list></vehicle-inspection-item-group-list>',
        title: 'Vehicle Inspection Item Groups',
    }).
    when('/gigo-pkg/vehicle-inspection-item-group/add', {
        template: '<vehicle-inspection-item-group-form></vehicle-inspection-item-group-form>',
        title: 'Add Vehicle Inspection Item Group',
    }).
    when('/gigo-pkg/vehicle-inspection-item-group/edit/:id', {
        template: '<vehicle-inspection-item-group-form></vehicle-inspection-item-group-form>',
        title: 'Edit Vehicle Inspection Item Group',
    }).
    when('/gigo-pkg/vehicle-inspection-item-group/card-list', {
        template: '<vehicle-inspection-item-group-card-list></vehicle-inspection-item-group-card-list>',
        title: 'Vehicle Inspection Item Group Card List',
    });
}]);
app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Vehicle Inspection Item
    when('/gigo-pkg/vehicle-inspection-item/list', {
        template: '<vehicle-inspection-item-list></vehicle-inspection-item-list>',
        title: 'Vehicle Inspection Items',
    }).
    when('/gigo-pkg/vehicle-inspection-item/add', {
        template: '<vehicle-inspection-item-form></vehicle-inspection-item-form>',
        title: 'Add Vehicle Inspection Item',
    }).
    when('/gigo-pkg/vehicle-inspection-item/edit/:id', {
        template: '<vehicle-inspection-item-form></vehicle-inspection-item-form>',
        title: 'Edit Vehicle Inspection Item',
    }).
    when('/gigo-pkg/vehicle-inspection-item/card-list', {
        template: '<vehicle-inspection-item-card-list></vehicle-inspection-item-card-list>',
        title: 'Vehicle Inspection Item Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Customer Voice
    when('/gigo-pkg/customer-voice/list', {
        template: '<customer-voice-list></customer-voice-list>',
        title: 'Customer Voices',
    }).
    when('/gigo-pkg/customer-voice/add', {
        template: '<customer-voice-form></customer-voice-form>',
        title: 'Add Customer Voice',
    }).
    when('/gigo-pkg/customer-voice/edit/:id', {
        template: '<customer-voice-form></customer-voice-form>',
        title: 'Edit Customer Voice',
    }).
    when('/gigo-pkg/customer-voice/card-list', {
        template: '<customer-voice-card-list></customer-voice-card-list>',
        title: 'Customer Voice Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Split Order Type
    when('/gigo-pkg/split-order-type/list', {
        template: '<split-order-type-list></split-order-type-list>',
        title: 'Split Order Types',
    }).
    when('/gigo-pkg/split-order-type/add', {
        template: '<split-order-type-form></split-order-type-form>',
        title: 'Add Split Order Type',
    }).
    when('/gigo-pkg/split-order-type/edit/:id', {
        template: '<split-order-type-form></split-order-type-form>',
        title: 'Edit Split Order Type',
    }).
    when('/gigo-pkg/split-order-type/card-list', {
        template: '<split-order-type-card-list></split-order-type-card-list>',
        title: 'Split Order Type Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Split Order Type
    when('/gigo-pkg/bay/list', {
        template: '<bay-list></bay-list>',
        title: 'Bays',
    }).
    when('/gigo-pkg/bay/add', {
        template: '<bay-form></bay-form>',
        title: 'Add Bay',
    }).
    when('/gigo-pkg/bay/edit/:id', {
        template: '<bay-form></bay-form>',
        title: 'Edit Bay',
    }).
    when('/gigo-pkg/bay/card-list', {
        template: '<bay-card-list></bay-card-list>',
        title: 'Bay Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Compaigns
    when('/gigo-pkg/campaign/list', {
        template: '<campaign-list></campaign-list>',
        title: 'Campaigns',
    }).
    when('/gigo-pkg/campaign/add', {
        template: '<campaign-form></campaign-form>',
        title: 'Add Campaign',
    }).
    when('/gigo-pkg/campaign/edit/:id', {
        template: '<campaign-form></campaign-form>',
        title: 'Edit Campaign',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Estimation Type
    when('/gigo-pkg/estimation-type/list', {
        template: '<estimation-type-list></estimation-type-list>',
        title: 'Estimation Types',
    }).
    when('/gigo-pkg/estimation-type/add', {
        template: '<estimation-type-form></estimation-type-form>',
        title: 'Add Estimation Type',
    }).
    when('/gigo-pkg/estimation-type/edit/:id', {
        template: '<estimation-type-form></estimation-type-form>',
        title: 'Edit Estimation Type',
    }).
    when('/gigo-pkg/estimation-type/card-list', {
        template: '<estimation-type-card-list></estimation-type-card-list>',
        title: 'Estimation Type Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Vehicle Gate Pass
    when('/vehicle-gate-pass/card-list', {
        template: '<vehicle-gate-pass-card-list></vehicle-gate-pass-card-list>',
        title: 'Vehicle Gate Pass - Card List',
    }).
    when('/vehicle-gate-pass/table-list', {
        template: '<vehicle-gate-pass-table-list></vehicle-gate-pass-table-list>',
        title: 'Vehicle Gate Pass - Table List',
    }).
    when('/vehicle-gate-pass/view/:id', {
        template: '<vehicle-gate-pass-view></vehicle-gate-pass-view>',
        title: 'View Vehicle Gate Pass',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Dashboard
    when('/gigo/dashboard', {
        template: '<gigo-dashboard></gigo-dashboard>',
        title: 'Dashboard',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Gate Log
    when('/gate-log/list', {
        template: '<gate-log-list></gate-log-list>',
        title: 'Gate Logs',
    }).
    when('/gate-log/add', {
        template: '<gate-log-form></gate-log-form>',
        title: 'Gate In Vehicle',
    }).
    when('/gate-log/edit/:id', {
        template: '<gate-log-form></gate-log-form>',
        title: 'Edit Gate Log',
    }).
    when('/gate-log/card-list', {
        template: '<gate-log-card-list></gate-log-card-list>',
        title: 'Gate Log Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/inward-vehicle/card-list', {
        template: '<inward-vehicle-card-list></inward-vehicle-card-list>',
        title: 'Inward Vehicle - Card List',
    }).
    when('/inward-vehicle/table-list', {
        template: '<inward-vehicle-table-list></inward-vehicle-table-list>',
        title: 'Inward Vehicle - Table List',
    }).
    when('/job-order/view/:job_order_id', {
        template: '<job-order-view></job-order-view>',
        title: 'Job Order - View',
    }).
    when('/inward-vehicle/view/:job_order_id', {
        template: '<inward-vehicle-view></inward-vehicle-view>',
        title: 'Inward Vehicle - View',
    }).
    when('/inward-vehicle/vehicle-detail/:job_order_id/:type_id?', {
        template: '<inward-vehicle-vehicle-detail></inward-vehicle-vehicle-detail>',
        title: 'Inward Vehicle',
    }).
    // when('/inward-vehicle/vehicle-detail/:job_order_id', {
    //     template: '<inward-vehicle-vehicle-detail></inward-vehicle-vehicle-detail>',
    //     title: 'Inward Vehicle - Vehicle Detail',
    // }).
    when('/inward-vehicle/customer-detail/:job_order_id/:type_id?', {
        template: '<inward-vehicle-customer-detail></inward-vehicle-customer-detail>',
        title: 'Inward Vehicle - Customer Detail',
    }).
    when('/inward-vehicle/vehicle/photos/:job_order_id', {
        template: '<inward-vehicle-photos></inward-vehicle-photos>',
        title: 'Inward Vehicle - Vehicle Photos',
    }).
    when('/inward-vehicle/order-detail/form/:job_order_id', {
        template: '<inward-vehicle-order-detail-form></inward-vehicle-order-detail-form>',
        title: 'Inward Vehicle - Order Detail Form',
    }).

    when('/inward-vehicle/inventory-detail/form/:job_order_id', {
        template: '<inward-vehicle-inventory-detail-form></inward-vehicle-inventory-detail-form>',
        title: 'Inward Vehicle - Inventory Detail Form',
    }).

    when('/inward-vehicle/order-detail/view/:job_order_id', {
        template: '<inward-vehicle-order-detail-view></inward-vehicle-order-detail-view>',
        title: 'Inward Vehicle - Order Detail',
    }).
    when('/inward-vehicle/inventory-detail/form/:job_order_id', {
        template: '<inward-vehicle-inventory-detail-form></inward-vehicle-inventory-detail-form>',
        title: 'Inward Vehicle - Inventory Detail Form',
    }).

    when('/inward-vehicle/voc-detail/form/:job_order_id', {
        template: '<inward-vehicle-voc-detail-form></inward-vehicle-voc-detail-form>',
        title: 'Inward Vehicle - VOC Detail',
    }).
    when('/inward-vehicle/road-test-detail/form/:job_order_id', {
        template: '<inward-vehicle-road-test-detail-form></inward-vehicle-road-test-detail-form>',
        title: 'Inward Vehicle - Road Test Observations',
    }).
    when('/inward-vehicle/expert-diagnosis-detail/form/:job_order_id', {
        template: '<inward-vehicle-expert-diagnosis-detail-form></inward-vehicle-expert-diagnosis-detail-form>',
        title: 'Inward Vehicle - Expert Diagnosis Detail',
    }).
    when('/inward-vehicle/inspection-detail/form/:job_order_id', {
        template: '<inward-vehicle-inspection-detail-form></inward-vehicle-inspection-detail-form>',
        title: 'Inward Vehicle - Inspection Detail',
    }).
    when('/inward-vehicle/scheduled-maintenance/form/:job_order_id', {
        template: '<inward-vehicle-scheduled-maintenance-form></inward-vehicle-scheduled-maintenance-form>',
        title: 'Inward Vehicle - Scheduled Maintenance',
    }).
    when('/inward-vehicle/customer-confirmation/:job_order_id', {
        template: '<inward-vehicle-customer-confirmation-form></inward-vehicle-customer-confirmation-form>',
        title: 'Inward Vehicle - Customer Confirmation',
    }).
    when('/inward-vehicle/estimate/:job_order_id', {
        template: '<inward-vehicle-estimate-form></inward-vehicle-estimate-form>',
        title: 'Inward Vehicle - Estimate',
    }).
    when('/inward-vehicle/dms-checklist/form/:job_order_id', {
        template: '<inward-vehicle-dms-check-list-form></inward-vehicle-dms-check-list-form>',
        title: 'Inward Vehicle - DMS Check List',
    }).
    when('/inward-vehicle/payable-labour-part-detail/form/:job_order_id', {
        template: '<inward-vehicle-payable-labour-part-form></inward-vehicle-payable-labour-part-form>',
        title: 'Inward Vehicle - Payable Labour Part',
    }).
    when('/inward-vehicle/payable-labour-part/add-part/form/:job_order_id', {
        template: '<inward-vehicle-payable-add-part-form></inward-vehicle-payable-add-part-form>',
        title: 'Inward Vehicle - Payable Add Part',
    }).
    when('/inward-vehicle/payable-labour-part/add-part/form/edit/:job_order_id/:job_order_part_id', {
        template: '<inward-vehicle-payable-add-part-form></inward-vehicle-payable-add-part-form>',
        title: 'Inward Vehicle - Payable Edit Part',
    }).
    when('/inward-vehicle/payable-labour-part/add-labour/form/edit/:job_order_id/:job_order_repair_order_id', {
        template: '<inward-vehicle-payable-add-labour-form></inward-vehicle-payable-add-labour-form>',
        title: 'Inward Vehicle - Payable Edit Labour',
    }).
    when('/inward-vehicle/payable-labour-part/add-labour/form/:job_order_id', {
        template: '<inward-vehicle-payable-add-labour-form></inward-vehicle-payable-add-labour-form>',
        title: 'Inward Vehicle - Payable Add Labour',
    }).
    when('/inward-vehicle/estimation-denied/form/:job_order_id', {
        template: '<inward-vehicle-estimation-status-detail-form></inward-vehicle-estimation-status-detail-form>',
        title: 'Inward Vehicle - Estimation Status',
    }).
    when('/inward-vehicle/update-jc/form/:job_order_id', {
        template: '<inward-vehicle-updatejc-form></inward-vehicle-updatejc-detail-form>',
        title: 'Inward Vehicle - Update Job Card',
    }).
    when('/inward-vehicle/gate-in-detail-view/:job_order_id', {
        template: '<inward-vehicle-gate-in-detail-view></inward-vehicle-gate-in-detail-view>',
        title: 'Inward Vehicle - Vehicle Detail',
    }).
    when('/inward-vehicle/vehicle-detail-view/:job_order_id', {
        template: '<inward-vehicle-vehicle-detail-view></inward-vehicle-vehicle-detail-view>',
        title: 'Inward Vehicle - Vehicle Detail View',
    }).
    when('/inward-vehicle/customer-detail-view/:job_order_id', {
        template: '<inward-vehicle-customer-detail-view></inward-vehicle-customer-detail-view>',
        title: 'Inward Vehicle - Customer Detail View',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/my-jobcard/card-list/:user_id', {
        template: '<my-jobcard-card-list></my-jobcard-card-list>',
        title: 'My Job Card List',
    }).
    when('/my-jobcard/table-list/:user_id', {
        template: '<my-jobcard-table-list></my-jobcard-table-list>',
        title: 'My Job Table List',
    }).
    when('/my-jobcard/timesheet-list/:user_id', {
        template: '<my-jobcard-timesheet-list></my-jobcard-timesheet-list>',
        title: 'My Job Time Sheet List',
    }).
    when('/my-jobcard/view/:user_id/:job_card_id', {
        template: '<my-jobcard-view></my-jobcard-view>',
        title: 'My Job Card View',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Job Order Repair Order
    when('/gigo-pkg/job-order-repair-order/list', {
        template: '<job-order-repair-order-list></job-order-repair-order-list>',
        title: 'Job Order Repair Orders',
    }).
    when('/gigo-pkg/job-order-repair-order/add', {
        template: '<job-order-repair-order-form></job-order-repair-order-form>',
        title: 'Add Job Order Repair Order',
    }).
    when('/gigo-pkg/job-order-repair-order/edit/:id', {
        template: '<job-order-repair-order-form></job-order-repair-order-form>',
        title: 'Edit Job Order Repair Order',
    }).
    when('/gigo-pkg/job-order-repair-order/card-list', {
        template: '<job-order-repair-order-card-list></job-order-repair-order-card-list>',
        title: 'Job Order Repair Order Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Repair Order Mechanic
    when('/gigo-pkg/repair-order-mechanic/list', {
        template: '<repair-order-mechanic-list></repair-order-mechanic-list>',
        title: 'Repair Order Mechanics',
    }).
    when('/gigo-pkg/repair-order-mechanic/add', {
        template: '<repair-order-mechanic-form></repair-order-mechanic-form>',
        title: 'Add Repair Order Mechanic',
    }).
    when('/gigo-pkg/repair-order-mechanic/edit/:id', {
        template: '<repair-order-mechanic-form></repair-order-mechanic-form>',
        title: 'Edit Repair Order Mechanic',
    }).
    when('/gigo-pkg/repair-order-mechanic/card-list', {
        template: '<repair-order-mechanic-card-list></repair-order-mechanic-card-list>',
        title: 'Repair Order Mechanic Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Mechanic Time Log
    when('/gigo-pkg/mechanic-time-log/list', {
        template: '<mechanic-time-log-list></mechanic-time-log-list>',
        title: 'Mechanic Time Logs',
    }).
    when('/gigo-pkg/mechanic-time-log/add', {
        template: '<mechanic-time-log-form></mechanic-time-log-form>',
        title: 'Add Mechanic Time Log',
    }).
    when('/gigo-pkg/mechanic-time-log/edit/:id', {
        template: '<mechanic-time-log-form></mechanic-time-log-form>',
        title: 'Edit Mechanic Time Log',
    }).
    when('/gigo-pkg/mechanic-time-log/card-list', {
        template: '<mechanic-time-log-card-list></mechanic-time-log-card-list>',
        title: 'Mechanic Time Log Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Job Order Part
    when('/gigo-pkg/job-order-part/list', {
        template: '<job-order-part-list></job-order-part-list>',
        title: 'Job Order Parts',
    }).
    when('/gigo-pkg/job-order-part/add', {
        template: '<job-order-part-form></job-order-part-form>',
        title: 'Add Job Order Part',
    }).
    when('/gigo-pkg/job-order-part/edit/:id', {
        template: '<job-order-part-form></job-order-part-form>',
        title: 'Edit Job Order Part',
    }).
    when('/gigo-pkg/job-order-part/card-list', {
        template: '<job-order-part-card-list></job-order-part-card-list>',
        title: 'Job Order Part Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Job Order Issued Part
    when('/gigo-pkg/job-order-issued-part/list', {
        template: '<job-order-issued-part-list></job-order-issued-part-list>',
        title: 'Job Order Issued Parts',
    }).
    when('/gigo-pkg/job-order-issued-part/add', {
        template: '<job-order-issued-part-form></job-order-issued-part-form>',
        title: 'Add Job Order Issued Part',
    }).
    when('/gigo-pkg/job-order-issued-part/edit/:id', {
        template: '<job-order-issued-part-form></job-order-issued-part-form>',
        title: 'Edit Job Order Issued Part',
    }).
    when('/gigo-pkg/job-order-issued-part/card-list', {
        template: '<job-order-issued-part-card-list></job-order-issued-part-card-list>',
        title: 'Job Order Issued Part Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Job Card
    when('/job-card/table-list', {
        template: '<job-card-table-list></job-card-table-list>',
        title: 'Job Cards - Table List',
    }).
    when('/gigo-pkg/job-card/add', {
        template: '<job-card-form></job-card-form>',
        title: 'Add Job Card',
    }).
    when('/gigo-pkg/job-card/edit/:id', {
        template: '<job-card-form></job-card-form>',
        title: 'Edit Job Card',
    }).
    when('/job-card/card-list', {
        template: '<job-card-card-list></job-card-card-list>',
        title: 'Job Card- Card List',
    }).

    when('/job-card/assign-bay/:id', {
        template: '<job-card-bay-form></job-card-bay-form>',
        title: 'Assign Bay',
    }).

    when('/job-card/bay-view/:job_card_id', {
        template: '<job-card-bay-view></job-card-bay-view>',
        title: 'Job Card Bay View',
    }).

    when('/job-card/order-view/:job_card_id', {
        template: '<job-card-order-view></job-card-order-view>',
        title: 'Job Card View',
    }).

    when('/job-card/split-order/:job_card_id', {
        template: '<job-card-split-order></job-card-split-order>',
        title: 'Job Card Split Order',
    }).

    when('/job-card/returnable-item/:job_card_id', {
        template: '<job-card-returnable-item-list></job-card-returnable-item-list>',
        title: 'Job Card Returnable Items',
    }).

    when('/job-card/returnable-item/add/:job_card_id', {
        template: '<job-card-returnable-item-form></job-card-returnable-item-form>',
        title: 'Add Returnable Item',
    }).
    when('/job-card/returnable-item/edit/:job_card_id/:id', {
        template: '<job-card-returnable-item-form></job-card-returnable-item-form>',
        title: 'Edit Returnable Item',
    }).
    when('/job-card/returnable-part/add/:job_card_id', {
        template: '<job-card-returnable-part-form></job-card-returnable-part-form>',
        title: 'Add Returnable Part',
    }).

    when('/job-card/gatein-detail/:job_card_id', {
        template: '<job-card-gatein-detail-form></job-card-gatein-detail-form>',
        title: 'Job Card Gate In Details',
    }).

    when('/job-card/pdf/:job_card_id', {
        template: '<job-card-pdf></job-card-pdf>',
        title: 'Job Card PDF',
    }).

    when('/job-card/material-gatepass/:job_card_id', {
        template: '<job-card-material-gatepass-form></job-card-material-gatepass-form>',
        title: 'Job Card Material Gate Pass',
    }).
    when('/job-card/material-outward/:job_card_id/:gatepass_id', {
        template: '<job-card-material-outward-form></job-card-material-outward-form>',
        title: 'Job Card Material Outward',

    }).
    when('/job-card/material-outward/:job_card_id', {
        template: '<job-card-material-outward-form></job-card-material-outward-form>',
        title: 'Job Card Material Outward',

    }).
    when('/job-card/road-test-observation/:job_card_id', {
        template: '<job-card-road-test-observation-form></job-card-road-test-observation-form>',
        title: 'Job Card Road Test Observation',

    }).
    when('/job-card/road-test/form/:job_card_id', {
        template: '<job-card-road-test-form></job-card-road-test-form>',
        title: 'Job Card Road Test Form',

    }).
    when('/job-card/road-test/form/:job_card_id/:road_test_id', {
        template: '<job-card-road-test-form></job-card-road-test-form>',
        title: 'Job Card Road Test Form',

    }).
    when('/job-card/vehicle-inspection/:job_card_id', {
        template: '<job-card-vehicle-inspection-form></job-card-vehicle-inspection-form>',
        title: 'Job Card Vehicle Inspection',

    }).
    when('/job-card/dms-checklist/:job_card_id', {
        template: '<job-card-dms-checklist-form></job-card-dms-checklist-form>',
        title: 'Job Card DMS Check List',

    }).
    when('/job-card/part-indent/:job_card_id', {
        template: '<job-card-part-indent-form></job-card-part-indent-form>',
        title: 'Job Card Part Indent',

    }).
    when('/job-card/schedule-maintenance/:job_card_id', {
        template: '<job-card-schedule-maintenance-form></job-card-schedule-maintenance-form>',
        title: 'Job Card Scheduled Maintenance',

    }).
    when('/job-card/payable-labour-parts/:job_card_id', {
        template: '<job-card-payable-labour-parts-form></job-card-payable-labour-parts-form>',
        title: 'Job Card Payable Labour Parts',

    }).
    when('/job-card/payable/labour/form/:job_order_id', {
        template: '<jobcard-payable-labour-form></jobcard-payable-labour-form>',
        title: 'Jobcard - Add Labour',
    }).
    when('/job-card/payable/labour/form/edit/:job_order_id/:job_order_repair_order_id', {
        template: '<jobcard-payable-labour-form></jobcard-payable-labour-form>',
        title: 'Jobcard - Edit Labour',
    }).

    when('/job-card/payable/part/form/:job_order_id', {
        template: '<jobcard-payable-part-form></jobcard-payable-part-form>',
        title: 'Jobcard - Payable Add Part',
    }).
    when('/job-card/payable/part/form/edit/:job_order_id/:job_order_part_id', {
        template: '<jobcard-payable-part-form></jobcard-payable-part-form>',
        title: 'Jobcard - Payable Edit Part',
    }).

    when('/job-card/estimate/:job_card_id', {
        template: '<job-card-estimate-form></job-card-estimate-form>',
        title: 'Job Card Estimate',
    }).
    when('/job-card/estimate-status/:job_card_id', {
        template: '<job-card-estimate-status-form></job-card-estimate-status-form>',
        title: 'Job Card Estimate Status',
    }).
    when('/job-card/expert-diagnosis/:job_card_id', {
        template: '<job-card-expert-diagnosis-form></job-card-expert-diagnosis-form>',
        title: 'Job Card Export Diagnosis',
    }).
    when('/job-card/floating-work/:job_card_id', {
        template: '<job-card-floating-form></job-card-floating-form>',
        title: 'Job Card Floating Works',
    }).
    when('/job-card/schedule/:job_card_id', {
        template: '<job-card-schedule-form></job-card-schedule-form>',
        title: 'Job Card Schedules',
    }).
    when('/job-card/labour-review/:job_card_id/:job_order_repair_order_id', {
        template: '<job-card-labour-review></job-card-labour-review>',
        title: 'Job Card Schedules',
    }).
    when('/job-card/bill-detail/:job_card_id', {
        template: '<job-card-bill-detail-view></job-card-bill-detail-view>',
        title: 'Job Card Bill Detail',
    }).
    when('/job-card/vehicle-detail/:job_card_id', {
        template: '<job-card-vehicle-detail-view></job-card-vehicle-detail-view>',
        title: 'Job Card Vehicle Detail',
    }).
    when('/job-card/customer-detail/:job_card_id', {
        template: '<job-card-customer-detail-view></job-card-customer-detail-view>',
        title: 'Job Card Customer Detail',
    }).
    when('/job-card/order-detail/:job_card_id', {
        template: '<job-card-order-detail-view></job-card-order-detail-view>',
        title: 'Job Card Order Detail',
    }).
    when('/job-card/inventory/:job_card_id', {
        template: '<job-card-inventory-view></job-card-inventory-view>',
        title: 'Job Card Inventory',
    }).
    when('/job-card/capture-voc/:job_card_id', {
        template: '<job-card-capture-voc-view></job-card-capture-voc-view>',
        title: 'Job Card Capture Voc',
    }).
    when('/job-card/bill-detail-update/:job_card_id', {
        template: '<job-card-update-bill-detail></job-card-update-bill-detail>',
        title: 'Job Card Bill Detail Update',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Mobile Common Pages
    when('/gigo-pkg/mobile/login', {
        template: '<mobile-login></mobile-login>',
        title: 'Mobile Login',
    }).
    when('/gigo-pkg/mobile/dashboard', {
        template: '<mobile-dashboard></mobile-dashboard>',
        title: 'Mobile Dashboard',
    }).
    when('/gigo-pkg/mobile/menus', {
        template: '<mobile-menus></mobile-menus>',
        title: 'Mobile Menus',
    }).
    when('/gigo-pkg/mobile/kanban-dashboard', {
        template: '<mobile-kanban-dashboard></mobile-kanban-dashboard>',
        title: 'KANBAN Dashboard',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/gigo-pkg/mobile/attendance/scan-qr', {
        template: '<mobile-attendance-scan-qr></mobile-attendance-scan-qr>',
        title: 'Attendance - Scan QR Code',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/gigo-pkg/mobile/gate-in-vehicle', {
        template: '<mobile-gate-in-vehicle></mobile-gate-in-vehicle>',
        title: 'Mobile - Gate In Vehicle',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/gigo-pkg/mobile/vehicle-gate-passes', {
        template: '<mobile-vehicle-gate-pass-list></mobile-vehicle-gate-pass-list>',
        title: 'Mobile Vehicle Gate Passes',
    })
    // when('/gigo-pkg/mobile/vehicle-gate-pass/add', {
    //     template: '<vehicle-gate-pass-form></vehicle-gate-pass-form>',
    //     title: 'Add Vehicle Gate Pass',
    // }).
    // when('/gigo-pkg/mobile/vehicle-gate-pass/edit/:id', {
    //     template: '<vehicle-gate-pass-form></vehicle-gate-pass-form>',
    //     title: 'Edit Vehicle Gate Pass',
    // }).
    // when('/gigo-pkg/mobile/vehicle-gate-pass/card-list', {
    //     template: '<vehicle-gate-pass-card-list></vehicle-gate-pass-card-list>',
    //     title: 'Vehicle Gate Pass Card List',
    // })
    ;
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/gigo-pkg/mobile/inward-vehicle/list', {
        template: '<mobile-inward-vehicle-list></mobile-inward-vehicle-list>',
        title: 'Inward Vehicles',
    }).
    when('/gigo-pkg/mobile/inward-vehicle/vehicle-detail', {
        template: '<mobile-inward-vehicle-detail></mobile-inward-vehicle-detail>',
        title: 'Inward Vehicle - Vehicle Details',
    }).
    when('/gigo-pkg/mobile/inward-vehicle/vehicle-form', {
        template: '<mobile-inward-vehicle-form></mobile-inward-vehicle-form>',
        title: 'Inward Vehicle - Vehicle Form',
    }).
    when('/gigo-pkg/mobile/inward-vehicle/customer-detail', {
        template: '<mobile-inward-customer-detail></mobile-inward-customer-detail>',
        title: 'Inward Vehicle - Customer Details',
    }).
    when('/gigo-pkg/mobile/inward-vehicle/customer-form', {
        template: '<mobile-inward-customer-form></mobile-inward-customer-form>',
        title: 'Inward Vehicle - Customer Form',
    }).
    when('/gigo-pkg/mobile/inward-vehicle/order-detail-form', {
        template: '<mobile-inward-order-detail-form></mobile-inward-order-detail-form>',
        title: 'Inward Vehicle - Customer Form',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Job Card
    when('/gigo-pkg/mobile/job-card/list', {
        template: '<mobile-job-card-list></mobile-job-card-list>',
        title: 'Job Cards',
    }).
    when('/gigo-pkg/mobile/job-card/add', {
        template: '<mobile-job-card-form></mobile-job-card-form>',
        title: 'Add Job Card',
    }).
    when('/gigo-pkg/mobile/job-card/edit/:id', {
        template: '<mobile-job-card-form></mobile-job-card-form>',
        title: 'Edit Job Card',
    }).
    when('/gigo-pkg/mobile/job-card/card-list', {
        template: '<mobile-job-card-card-list></mobile-job-card-card-list>',
        title: 'Job Card Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Material Gate Pass
    when('/gigo-pkg/mobile/material-gate-pass/list', {
        template: '<mobile-material-gate-pass-list></mobile-material-gate-pass-list>',
        title: 'Material Gate Passes',
    }).
    when('/gigo-pkg/mobile/material-gate-pass/add', {
        template: '<mobile-material-gate-pass-form></mobile-material-gate-pass-form>',
        title: 'Add Material Gate Pass',
    }).
    when('/gigo-pkg/mobile/material-gate-pass/edit/:id', {
        template: '<mobile-material-gate-pass-form></mobile-material-gate-pass-form>',
        title: 'Edit Material Gate Pass',
    }).
    when('/gigo-pkg/mobile/material-gate-pass/card-list', {
        template: '<mobile-material-gate-pass-card-list></mobile-material-gate-pass-card-list>',
        title: 'Material Gate Pass Card List',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/warranty-job-order-request/card-list', {
        template: '<warranty-job-order-request-card-list></warranty-job-order-request-card-list>',
        title: 'Product Performance Reports'
    }).
    when('/warranty-job-order-request/table-list', {
        template: '<warranty-job-order-request-table-list></warranty-job-order-request-table-list>',
        title: 'Product Performance Reports'
    }).
    when('/warranty-job-order-request/form/:request_id?', {
        template: '<warranty-job-order-request-form></warranty-job-order-request-form>',
        title: 'Product Performance Report - Form'
    }).
    when('/warranty-job-order-request/view/:request_id', {
        template: '<warranty-job-order-request-view></warranty-job-order-request-view>',
        title: 'Product Performance Report - View'
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Trade Plate Number
    when('/trade-plate-number/list', {
        template: '<trade-plate-number-list></trade-plate-number-list>',
        title: 'Trade Plate Number',
    }).
    when('/trade-plate-number/add', {
        template: '<trade-plate-number-form></trade-plate-number-form>',
        title: 'Add Trade Plate Number',
    }).
    when('/trade-plate-number/edit/:id', {
        template: '<trade-plate-number-form></trade-plate-number-form>',
        title: 'Edit Trade Plate Number',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Road Test Gate Pass
    when('/road-test-gate-pass/table-list', {
        template: '<road-test-gate-pass-table-list></road-test-gate-pass-table-list>',
        title: 'Road Test Gate Pass - Table List',
    }).
    when('/road-test-gate-pass/card-list', {
        template: '<road-test-gate-pass-card-list></road-test-gate-pass-card-list>',
        title: 'Road Test Gate Pass - Card List',
    }).
    when('/road-test-gate-pass/view/:id', {
        template: '<road-test-gate-pass-view></road-test-gate-pass-view>',
        title: 'View Road Test Gate Pass',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Floating Gate Pass
    when('/floating-gate-pass/table-list', {
        template: '<floating-gate-pass-table-list></floating-gate-pass-table-list>',
        title: 'Floating Gate Pass - Table List',
    }).
    when('/floating-gate-pass/card-list', {
        template: '<floating-gate-pass-card-list></floating-gate-pass-card-list>',
        title: 'Floating Gate Pass - Card List',
    }).
    when('/floating-gate-pass/view/:id', {
        template: '<floating-gate-pass-view></floating-gate-pass-view>',
        title: 'View Floating Gate Pass',
    });
}]);


app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //Survey
    when('/survey-type/list', {
        template: '<survey-type-list></survey-type-list>',
        title: 'Survey Types',
    }).
    when('/survey-type/add', {
        template: '<survey-type-form></survey-type-form>',
        title: 'Add Survey Type',
    }).
    when('/survey-type/edit/:id', {
        template: '<survey-type-form></survey-type-form>',
        title: 'Edit Survey Type',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //OTP
    when('/otp/list', {
        template: '<otp-list></otp-list>',
        title: 'OTP',
    });
}]);

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //GatePass
    when('/gate-pass/list', {
        template: '<gate-pass-list></gate-pass-list>',
        title: 'GatePass',
    }).
    when('/gate-pass/add', {
        template: '<gate-pass-form></gate-pass-form>',
        title: 'Add GatePass',
    }).
    when('/gate-pass/edit/:id', {
        template: '<gate-pass-form></gate-pass-form>',
        title: 'Edit GatePass',
    }).
    when('/gate-pass/view/:id', {
        template: '<gate-pass-view></gate-pass-view>',
        title: 'View GatePass',
    });
}]);
