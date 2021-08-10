@if(config('gigo-pkg.DEV'))
    <?php $gigo_pkg_prefix = '/packages/abs/gigo-pkg/src';?>
@else
    <?php $gigo_pkg_prefix = '';?>
@endif

<script type="text/javascript">
	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    //Vehicle Segment
	    // when('/gigo-pkg/vehicle-segment/list', {
	    //     template: '<vehicle-segment-list></vehicle-segment-list>',
	    //     title: 'Vehicle segments',
	    // }).
	    // when('/gigo-pkg/vehicle-segment/add', {
	    //     template: '<vehicle-segment-form></vehicle-segment-form>',
	    //     title: 'Add Vehicle segment',
	    // }).
	    // when('/gigo-pkg/vehicle-segment/edit/:id', {
	    //     template: '<vehicle-segment-form></vehicle-segment-form>',
	    //     title: 'Edit Vehicle segment',
	    // }).

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
	     when('/gigo-pkg/material-gate-pass/list', {
	        template: '<material-gate-pass-list></material-gate-pass-list>',
	        title: 'Material Gate Pass',
	    }).

	    when('/gigo-pkg/material-gate-pass/view/:id', {
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

	// //Vehicle Segments
 //    var vehicle_segment_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-segment/list.html')}}";
 //    var vehicle_segment_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-segment/form.html')}}";

	//Vehicle Primary Applications
    var vehicle_primary_application_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-primary-application/list.html')}}";
    var vehicle_primary_application_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-primary-application/form.html')}}";

	//Faults
    var fault_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/fault/list.html')}}";
    var fault_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/fault/form.html')}}";

	//Repair Orders
    var repair_order_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order/list.html')}}";
    var repair_order_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order/form.html')}}";

    //Repair Order Types
    var repair_order_type_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/list.html')}}";
    var repair_order_type_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/form.html')}}";
    var repair_order_type_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/view.html')}}";

    //Service Types
    var service_type_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-type/list.html')}}";
    var service_type_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-type/form.html')}}";


    //Service Order Types
    var service_order_type_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-order-type/list.html')}}";
    var service_order_type_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-order-type/form.html')}}";

    //Quote Types
    var quote_type_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/list.html')}}";
    var quote_type_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/form.html')}}";

     //Complaint Group
    var complaint_group_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/complaint-group/list.html')}}";
    var complaint_group_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/complaint-group/form.html')}}";

    //Complaint
    var complaint_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/complaint/list.html')}}";
    var complaint_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/complaint/form.html')}}";

     //Part Supplier
    var part_supplier_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/part-supplier/list.html')}}";
    var part_supplier_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/part-supplier/form.html')}}";

    //Vehicle Secoundary Aplication
    var vehicle_secoundary_app_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-secoundary-application/list.html')}}";
    var vehicle_secoundary_app_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-secoundary-application/form.html')}}";

     //Vehicle
    var vehicle_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/list.html')}}";
    var vehicle_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/form.html')}}";
    var vehicle_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/view.html')}}";

    //Pause Work Reason
    var pause_work_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/pause-work-reason/list.html')}}";
    var pause_work_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/pause-work-reason/form.html')}}";

    //Material Gate pass
    var material_gate_pass_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/material-gate-pass/list.html')}}";
    var material_gate_pass_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/material-gate-pass/form.html')}}";
    var material_gate_pass_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/material-gate-pass/view.html')}}";
    //Kanban App
     var kanban_app_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/kanban-app/dashboard.html')}}";
     var kanban_app_attendance_sacn_qr_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/kanban-app/attendance-qr.html')}}";
     var kanban_app_my_job_card_sacn_qr_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/kanban-app/my-job-card-qr.html')}}";
     var kanban_app_my_time_sheet_sacn_qr_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/kanban-app/my-time-sheet-qr.html')}}";
     //Parts Indent
     var parts_indent_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/list.html')}}";
     var parts_indent_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/view.html')}}";
     var parts_indent_customer_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/customer.html')}}";
     var parts_indent_repair_order_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/repair-order.html')}}";
     var parts_indent_parts_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/parts.html')}}";
     var parts_indent_issue_part_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/issue-part.html')}}";
     var parts_indent_issue_bulk_part_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/issue-bulk-part.html')}}";
     var parts_indent_edit_parts_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/edit-parts.html')}}";

</script>
<!-- <script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-segment/controller.js')}}"></script> -->
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-primary-application/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/fault/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/lv-main-type/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-type/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-order-type/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/pause-work-reason/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/material-gate-pass/controller.js')}}"></script>

<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/kanban-app/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/complaint-group/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/complaint/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-secoundary-application/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/part-supplier/controller.js')}}"></script>


<script type='text/javascript'>
	/*app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
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
	    when('/gigo-pkg/service-type/card-list', {
	        template: '<service-type-card-list></service-type-card-list>',
	        title: 'Service Type Card List',
	    });
	}]);*/

	//Service Types
    /*var service_type_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-type/list.html')}}';
    var service_type_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-type/form.html')}}';
    var service_type_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-type/card-list.html')}}';
    var service_type_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/service-type-modal-form.html')}}';*/
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-type/controller.js')}}'></script> -->


<!-- <script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //Vehicle
	    when('/gigo-pkg/vehicle/list', {
	        template: '<vehicle-list></vehicle-list>',
	        title: 'Vehicles',
	    }).
	    when('/gigo-pkg/vehicle/add', {
	        template: '<vehicle-form></vehicle-form>',
	        title: 'Add Vehicle',
	    }).
	    when('/gigo-pkg/vehicle/edit/:id', {
	        template: '<vehicle-form></vehicle-form>',
	        title: 'Edit Vehicle',
	    }).
	    when('/gigo-pkg/vehicle/card-list', {
	        template: '<vehicle-card-list></vehicle-card-list>',
	        title: 'Vehicle Card List',
	    });
	}]);

	//Vehicles
    var vehicle_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/list.html')}}';
    var vehicle_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/form.html')}}';
    var vehicle_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/card-list.html')}}';
    var vehicle_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/controller.js')}}'></script> -->


<script type='text/javascript'>
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

	//Vehicle Owners
    var vehicle_owner_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-owner/list.html')}}';
    var vehicle_owner_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-owner/form.html')}}';
    var vehicle_owner_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-owner/card-list.html')}}';
    var vehicle_owner_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-owner-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-owner/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Amc Members
    var amc_member_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/amc-member/list.html')}}';
    var amc_member_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/amc-member/form.html')}}';
    var amc_member_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/amc-member/card-list.html')}}';
    var amc_member_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/amc-member-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/amc-member/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Vehicle Warranty Members
    var vehicle_warranty_member_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-warranty-member/list.html')}}';
    var vehicle_warranty_member_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-warranty-member/form.html')}}';
    var vehicle_warranty_member_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-warranty-member/card-list.html')}}';
    var vehicle_warranty_member_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-warranty-member-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-warranty-member/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Insurance Members
    var insurance_member_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/insurance-member/list.html')}}';
    var insurance_member_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/insurance-member/form.html')}}';
    var insurance_member_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/insurance-member/card-list.html')}}';
    var insurance_member_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/insurance-member-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/insurance-member/controller.js')}}'></script>


<!-- <script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
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
	    when('/gigo-pkg/quote-type/card-list', {
	        template: '<quote-type-card-list></quote-type-card-list>',
	        title: 'Quote Type Card List',
	    });
	}]);

	//Quote Types
    var quote_type_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/list.html')}}';
    var quote_type_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/form.html')}}';
    var quote_type_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/card-list.html')}}';
    var quote_type_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/quote-type-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/controller.js')}}'></script> -->


<script type='text/javascript'>
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

	//Vehicle Inventory Items
    var vehicle_inventory_item_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inventory-item/list.html')}}';
    var vehicle_inventory_item_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inventory-item/form.html')}}';
    var vehicle_inventory_item_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inventory-item/card-list.html')}}';
    var vehicle_inventory_item_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-inventory-item-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inventory-item/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Vehicle Inspection Item Groups
    var vehicle_inspection_item_group_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/list.html')}}";
    var vehicle_inspection_item_group_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/form.html')}}";
    var vehicle_inspection_item_group_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/card-list.html')}}";
    var vehicle_inspection_item_group_modal_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-inspection-item-group-modal-form.html')}}";
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/controller.js')}}"></script>


<script type='text/javascript'>
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

	//Vehicle Inspection Items
    var vehicle_inspection_item_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/list.html')}}";
    var vehicle_inspection_item_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/form.html')}}";
    var vehicle_inspection_item_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/card-list.html')}}";
    var vehicle_inspection_item_modal_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-inspection-item-modal-form.html')}}";
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/controller.js')}}"></script>


<script type='text/javascript'>
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

	//Customer Voices
    var customer_voice_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/list.html')}}";
    var customer_voice_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/form.html')}}";
    var customer_voice_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/card-list.html')}}";
    var customer_voice_modal_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/customer-voice-modal-form.html')}}";
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/controller.js')}}"></script>


<script type='text/javascript'>
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

	//Split Order Types
    var split_order_type_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/list.html')}}';
    var split_order_type_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/form.html')}}';
    var split_order_type_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/card-list.html')}}';
    var split_order_type_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/split-order-type-modal-form.html')}}';
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/controller.js')}}"></script>


<script type='text/javascript'>
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

	//Bays
    var bay_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/bay/list.html')}}';
    var bay_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/bay/form.html')}}';
    var bay_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/bay/card-list.html')}}';
    var bay_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/bay-modal-form.html')}}';
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/bay/controller.js')}}"></script>


<script type='text/javascript'>
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

	//Campaigns
    var campaigns_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/campaign/list.html')}}';
    var campaigns_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/campaign/form.html')}}';
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/campaign/controller.js?v=3')}}"></script>


<script type='text/javascript'>
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

	//Estimation Types
    var estimation_type_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/estimation-type/list.html')}}';
    var estimation_type_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/estimation-type/form.html')}}';
    var estimation_type_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/estimation-type/card-list.html')}}';
    var estimation_type_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/estimation-type-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/estimation-type/controller.js')}}'></script>


<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //Vehicle Gate Pass
	    when('/gigo-pkg/vehicle-gate-pass/list', {
	        template: '<vehicle-gate-pass-list></vehicle-gate-pass-list>',
	        title: 'Vehicle Gate Passes',
	    }).
	    when('/gigo-pkg/vehicle-gate-pass/view/:id', {
	        template: '<vehicle-gate-pass-view></vehicle-gate-pass-view>',
	        title: 'View Vehicle Gate Pass',
	    });
	}]);

	var view_img = './public/theme/img/table/cndn/view.svg';
	var gate_out_img = './public/theme/img/table/cndn/gateout.svg';


	//Vehicle Gate Passes
var vehicle_gate_pass_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-gate-pass/list.html')}}";
var vehicle_gate_pass_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-gate-pass/view.html')}}";
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-gate-pass/controller.js')}}"></script>


<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //Dashboard
	    when('/gigo/dashboard', {
	        template: '<gigo-dashboard></gigo-dashboard>',
	        title: 'Dashboard',
	    });
	}]);

	//Dashboard
    var gigo_dashboard_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/dashboard/list.html')}}';
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/dashboard/controller.js')}}"></script>

<script type='text/javascript'>
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

	//Gate Logs

    var gate_log_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/list.html')}}';
    var gate_log_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/form.html')}}";
    var gate_log_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/card-list.html')}}';
    var gate_log_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/gate-log-modal-form.html')}}';
</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/controller.js')}}"></script>


<!-- Inward Vehicle -->
<script type='text/javascript'>
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

	    when('/inward-vehicle/voc-detail/form/:job_order_id',{
	    	template: '<inward-vehicle-voc-detail-form></inward-vehicle-voc-detail-form>',
	        title: 'Inward Vehicle - VOC Detail',
	    }).
	    when('/inward-vehicle/road-test-detail/form/:job_order_id',{
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

    var inward_vehicle_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/card-list.html')}}';
    var inward_vehicle_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/list.html')}}';
    var job_order_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/job-order-view.html')}}';
    var inward_vehicle_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/view.html')}}';
    var inward_vehicle_vehicle_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/vehicle-detail.html')}}';
    var inward_vehicle_customer_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/customer-detail.html')}}';
    var inward_vehicle_photos_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/vehicle-photos.html')}}';
    var inward_vehicle_order_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/order-detail-form.html')}}';

     var inward_vehicle_inventory_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/inventory-detail-form.html')}}';

    var inward_vehicle_order_detail_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/order-detail-view.html')}}';
    var inward_vehicle_inventory_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/inventory-detail-form.html')}}';
    var inward_vehicle_voc_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/voc-detail-form.html')}}';
    var inward_vehicle_road_test_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/road-test-detail-form.html')}}';

    var inward_vehicle_export_diagnosis_details_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/expert-diagnosis-detail.html')}}';
    var inward_vehicle_dms_checklist_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/dms-check-list.html')}}';
    var inward_vehicle_inspection_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/inspection-detail.html')}}';
    var inward_vehicle_schedule_maintenance_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/schedule-maintenance.html')}}';
    var inward_vehicle_updatejc_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/update-jc.html')}}';

    var inward_vehicle_payable_labour_part_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/payable-labour-part-form.html')}}';

    var inward_vehicle_payable_labour_part_add_part_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/add-part.html')}}';
    var inward_vehicle_payable_labour_part_add_labour_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/add-labour.html')}}';


    var inward_vehicle_customer_confirmation_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/customer-confirmation.html')}}';

    var inward_vehicle_estimate_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/estimate.html')}}';
    var inward_vehicle_estimation_status_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/estimation-denied-form.html')}}';

    var inward_vehicle_gate_in_detail_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/gate-in-detail.html')}}';
    var inward_vehicle_vehicle_detail_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/vehicle-detail-view.html')}}';
    var inward_vehicle_customer_detail_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/customer-detail-view.html')}}';


    //PARTIALS
    var job_order_header_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-header.html')}}';
    var inward_tabs_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/inward-tabs.html')}}';
    var inward_view_tabs_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/inward-view-tabs.html')}}';

    //PART INDENT PARTIALS
    var part_indent_header_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/part-indent-header.html')}}';
    var part_indent_tabs_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/part-indent-tabs.html')}}';

</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/controller.js')}}"></script>

<!--My Job Card--->
<script type='text/javascript'>
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

	//Gate Logs
    var myjobcard_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/my-jobcard/card-list.html')}}';
    var myjobcard_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/my-jobcard/view.html')}}';
    var myjobcard_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/my-jobcard/list.html')}}';
    var myjobcard_timesheet_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/my-jobcard/time-sheet.html')}}';

</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/my-jobcard/controller.js')}}"></script>


<script type='text/javascript'>
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

	//Job Order Repair Orders
    var job_order_repair_order_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-repair-order/list.html')}}';
    var job_order_repair_order_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-repair-order/form.html')}}';
    var job_order_repair_order_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-repair-order/card-list.html')}}';
    var job_order_repair_order_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-repair-order-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-repair-order/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Repair Order Mechanics
    var repair_order_mechanic_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-mechanic/list.html')}}';
    var repair_order_mechanic_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-mechanic/form.html')}}';
    var repair_order_mechanic_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-mechanic/card-list.html')}}';
    var repair_order_mechanic_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/repair-order-mechanic-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-mechanic/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Mechanic Time Logs
    var mechanic_time_log_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mechanic-time-log/list.html')}}';
    var mechanic_time_log_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mechanic-time-log/form.html')}}';
    var mechanic_time_log_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mechanic-time-log/card-list.html')}}';
    var mechanic_time_log_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/mechanic-time-log-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mechanic-time-log/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Job Order Parts
    var job_order_part_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-part/list.html')}}';
    var job_order_part_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-part/form.html')}}';
    var job_order_part_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-part/card-list.html')}}';
    var job_order_part_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-part-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-part/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Job Order Issued Parts
    var job_order_issued_part_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-issued-part/list.html')}}';
    var job_order_issued_part_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-issued-part/form.html')}}';
    var job_order_issued_part_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-issued-part/card-list.html')}}';
    var job_order_issued_part_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-issued-part-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-issued-part/controller.js')}}'></script>


<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //Job Card
	    when('/gigo-pkg/job-card/table-list', {
	        template: '<job-card-table-list></job-card-table-list>',
	        title: 'Job Cards',
	    }).
	    when('/gigo-pkg/job-card/add', {
	        template: '<job-card-form></job-card-form>',
	        title: 'Add Job Card',
	    }).
	    when('/gigo-pkg/job-card/edit/:id', {
	        template: '<job-card-form></job-card-form>',
	        title: 'Edit Job Card',
	    }).
	    when('/gigo-pkg/job-card/card-list', {
	        template: '<job-card-card-list></job-card-card-list>',
	        title: 'Job Card Card List',
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

	    when('/gigo-pkg/job-card/returnable-item/:job_card_id', {
	        template: '<job-card-returnable-item-list></job-card-returnable-item-list>',
	        title: 'Job Card Returnable Items',
	    }).

	    when('/gigo-pkg/job-card/returnable-item/add/:job_card_id', {
	        template: '<job-card-returnable-item-form></job-card-returnable-item-form>',
	        title: 'Add Returnable Item',
	    }).
	    when('/job-card/returnable-part/add/:job_card_id', {
	        template: '<job-card-returnable-part-form></job-card-returnable-part-form>',
	        title: 'Add Returnable Part',
        }).
	    when('/gigo-pkg/job-card/returnable-item/edit/:job_card_id/:id', {
	        template: '<job-card-returnable-item-form></job-card-returnable-item-form>',
	        title: 'Edit Returnable Item',
	    }).

	    when('/gigo-pkg/job-card/gatein-detail/:job_card_id', {
	        template: '<job-card-gatein-detail-form></job-card-gatein-detail-form>',
	        title: 'Job Card Gate In Details',
	    }).

	    when('/gigo-pkg/job-card/material-gatepass/:job_card_id', {
	        template: '<job-card-material-gatepass-form></job-card-material-gatepass-form>',
	        title: 'Job Card Material Gate Pass',
	    }).
	    when('/gigo-pkg/job-card/material-outward/:job_card_id/:gatepass_id', {
	        template: '<job-card-material-outward-form></job-card-material-outward-form>',
	        title: 'Job Card Material Outward',
	    }).
	    when('/gigo-pkg/job-card/material-outward/:job_card_id', {
	        template: '<job-card-material-outward-form></job-card-material-outward-form>',
	        title: 'Job Card Material Outward',
	    }).
	    when('/gigo-pkg/job-card/road-test-observation/:job_card_id', {
	        template: '<job-card-road-test-observation-form></job-card-road-test-observation-form>',
	        title: 'Job Card Road Test Observation',
	    }).
	    when('/gigo-pkg/job-card/road-test/form/:job_card_id', {
	        template: '<job-card-road-test-form></job-card-road-test-form>',
	        title: 'Job Card Road Test Observation',
	    }).
	    when('/gigo-pkg/job-card/vehicle-inspection/:job_card_id', {
	        template: '<job-card-vehicle-inspection-form></job-card-vehicle-inspection-form>',
	        title: 'Job Card Vehicle Inspection',
	    }).
	    when('/gigo-pkg/job-card/dms-checklist/:job_card_id', {
	        template: '<job-card-dms-checklist-form></job-card-dms-checklist-form>',
	        title: 'Job Card DMS Check List',
	    }).
	    when('/gigo-pkg/job-card/part-indent/:job_card_id', {
	        template: '<job-card-part-indent-form></job-card-part-indent-form>',
	        title: 'Job Card Part Indent',
	    }).
		 //Added To Change Floor Supervisor
		when('/job-card/change-floor-supervisor/:job_card_id', {
			template: '<job-card-change-floor-supervisor-form></job-card-change-floor-supervisor-form>',
			title: 'Change Floor Supervisor',
		}).
	    when('/gigo-pkg/job-card/schedule-maintenance/:job_card_id', {
	        template: '<job-card-schedule-maintenance-form></job-card-schedule-maintenance-form>',
	        title: 'Job Card Scheduled Maintenance',
	    }).
	    when('/gigo-pkg/job-card/payable-labour-parts/:job_card_id', {
	        template: '<job-card-payable-labour-parts-form></job-card-payable-labour-parts-form>',
	        title: 'Job Card Payable Labour Parts',
	    }).
	    when('/gigo-pkg/job-card/estimate/:job_card_id', {
	        template: '<job-card-estimate-form></job-card-estimate-form>',
	        title: 'Job Card Estimate',
	    }).
	    when('/gigo-pkg/job-card/estimate-status/:job_card_id', {
	        template: '<job-card-estimate-status-form></job-card-estimate-status-form>',
	        title: 'Job Card Estimate Status',
	    }).
	    when('/gigo-pkg/job-card/expert-diagnosis/:job_card_id', {
	        template: '<job-card-expert-diagnosis-form></job-card-expert-diagnosis-form>',
	        title: 'Job Card Export Diagnosis',
	    }).
	    when('/job-card/schedule/:job_card_id', {
	        template: '<job-card-schedule-form></job-card-schedule-form>',
	        title: 'Job Card Schedules',
	    }).
	     when('/job-card/floating-work/:job_card_id', {
	        template: '<job-card-floating-form></job-card-floating-form>',
	        title: 'Job Card Floating Works',
	    }).
	    when('/gigo-pkg/job-card/labour-review/:job_card_id/:job_order_repair_order_id', {
	        template: '<job-card-labour-review></job-card-labour-review>',
	        title: 'Job Card Schedules',
	    }).
	    when('/gigo-pkg/job-card/bill-detail/:job_card_id', {
	        template: '<job-card-bill-detail-view></job-card-bill-detail-view>',
	        title: 'Job Card Bill Detail',
	    }).
	    when('/gigo-pkg/job-card/vehicle-detail/:job_card_id', {
	        template: '<job-card-vehicle-detail-view></job-card-vehicle-detail-view>',
	        title: 'Job Card Vehicle Detail',
	    }).
	    when('/gigo-pkg/job-card/customer-detail/:job_card_id', {
	        template: '<job-card-customer-detail-view></job-card-customer-detail-view>',
	        title: 'Job Card Customer Detail',
	    }).
	    when('/gigo-pkg/job-card/order-detail/:job_card_id', {
	        template: '<job-card-order-detail-view></job-card-order-detail-view>',
	        title: 'Job Card Order Detail',
	    }).
	    when('/gigo-pkg/job-card/inventory/:job_card_id', {
	        template: '<job-card-inventory-view></job-card-inventory-view>',
	        title: 'Job Card Inventory',
	    }).
	    when('/gigo-pkg/job-card/capture-voc/:job_card_id', {
	        template: '<job-card-capture-voc-view></job-card-capture-voc-view>',
	        title: 'Job Card Capture Voc',
	    }).
	    when('/gigo-pkg/job-card/bill-detail-update/:job_card_id', {
	        template: '<job-card-update-bill-detail></job-card-update-bill-detail>',
	        title: 'Job Card Bill Detail Update',
	    });
	}]);

	//Job Cards
    var job_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/list.html')}}';
    var job_card_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/form.html')}}';
    var job_card_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/card-list.html')}}';
    var job_card_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-card-modal-form.html')}}';

    var job_card_bay_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/bay-form.html')}}';

    var job_card_returnable_item_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/returnable-item-detail.html')}}';
     var job_card_returnable_item_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/returnable-item-form.html')}}';
    var job_card_returnable_item_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/returnable-part-form.html')}}';

    var job_card_material_gatepass_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/material-gatepass.html')}}';
    var job_card_material_outward_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/material-outward.html')}}';
    var job_card_material_road_test_observation_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/road-test-observation.html')}}';
    var job_card_road_test_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/road-test-form.html')}}';
    var job_card_export_diagonosis_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/expert-diagnosis.html')}}';
    var job_card_vehicle_inspection_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/vehicle-inspection.html')}}';
    var job_card_dms_checklist_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/dms-checklist.html')}}';
    var job_card_floating_work_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/floating-work.html')}}";
    var job_card_schedule_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/schedule.html')}}";
    var job_card_labour_review_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/labour-review.html')}}";
    var job_card_bil_detail_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/bill-detail.html')}}";
    var job_card_bil_detail_update_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/bill-detail-update.html')}}";
    var job_card_bay_view_template_url  = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/bay-view.html')}}";
    var job_card_order_view_template_url  = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/order-view.html')}}";
    var job_card_split_order_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/split-order.html')}}";


    var job_card_part_indent_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/parts-indent.html')}}';
    var job_card_schedule_maintendance_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/schedule-maintenance.html')}}';
    var job_card_parts_labour_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/payable-labour-part.html')}}';
    var job_card_parts_labour_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/labour.html')}}';
    var job_card_parts_part_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/part.html')}}';
    var job_card_estimate_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/estimate.html')}}';

	//Newly Added For Floor-Supervisor-Template By B.Surya from 09-08-2021
    var job_card_floor_supervisor_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/floor-supervisor-form.html')}}';
	
    var job_card_estimate_status_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/estimate-status.html')}}';
    var job_card_gatein_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/gatein-detail.html')}}';
    var job_card_vehicle_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/vehicle-detail.html')}}';
    var job_card_customer_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/customer-detail.html')}}';
    var job_card_order_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/order-detail.html')}}';
    var job_card_inventory_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/inventory.html')}}';
    var job_card_capture_voc_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/capture-voc.html')}}';


     //PARTIALS
    var job_card_header_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/jobcard-header.html')}}';
    var jobcard_tabs_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/jobcard-tabs.html')}}';



</script>
<script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/controller.js')}}"></script>



{{-- MOBILE PAGES --}}

{{-- MOBILE COMMON PAGES --}}
<script type='text/javascript'>
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

    var mobile_login_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/login.html')}}";
    var mobile_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/dashboard.html')}}";
    var mobile_menus_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/menus.html')}}";
    var mobile_kanban_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/kanban-dashboard.html')}}";
    var mobile_header_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/header.html')}}";

</script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/controller.js')}}"></script>

{{-- MOBILE ATTENDANCE --}}
<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    when('/gigo-pkg/mobile/attendance/scan-qr', {
	        template: '<mobile-attendance-scan-qr></mobile-attendance-scan-qr>',
	        title: 'Attendance - Scan QR Code',
	    });
	}]);
    var mobile_attendance_scan_qr_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/attendance/scan-qr.html')}}";

    var mobile_attendance_employee_card_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/employee-card.html')}}";


</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/attendance/controller.js')}}'></script>

{{-- MOBILE GATE IN VEHICLE --}}
<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    when('/gigo-pkg/mobile/gate-in-vehicle', {
	        template: '<mobile-gate-in-vehicle></mobile-gate-in-vehicle>',
	        title: 'Mobile - Gate In Vehicle',
	    });
	}]);
    var mobile_gate_in_vehicle_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/gate-in-vehicle/form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/gate-in-vehicle/controller.js')}}'></script>

{{-- MOBILE VEHICLE GATE PASSES --}}
<script type='text/javascript'>
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
    var mobile_vehicle_gate_pass_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/vehicle-gate-pass/list.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/vehicle-gate-pass/controller.js')}}'></script>

{{-- MOBILE INWARD VEHICLE  --}}
<script type='text/javascript'>
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
	    })
	    ;
	}]);

	//Inward Vehicles
    var mobile_inward_vehicle_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/list.html')}}';
    var mobile_inward_vehicle_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/vehicle-detail.html')}}';
    var mobile_inward_vehicle_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/vehicle-form.html')}}';
    var mobile_inward_customer_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/customer-detail.html')}}';
    var mobile_inward_customer_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/customer-form.html')}}';
    var mobile_inward_order_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/order-detail-form.html')}}';

    var mobile_inward_header_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/partials/header.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Job Cards
    var mobile_job_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/job-card/list.html')}}';
    var mobile_job_card_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/job-card/form.html')}}';
    var mobile_job_card_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/job-card/card-list.html')}}';
    var mobile_job_card_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/job-card-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/job-card/controller.js')}}'></script>


<script type='text/javascript'>
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

	//Material Gate Passes
    var mobile_material_gate_pass_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/material-gate-pass/list.html')}}';
    var mobile_material_gate_pass_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/material-gate-pass/form.html')}}';
    var mobile_material_gate_pass_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/material-gate-pass/card-list.html')}}';
    var mobile_material_gate_pass_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/material-gate-pass-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/material-gate-pass/controller.js')}}'></script>
<link rel="stylesheet" type="text/css" href="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/mobile.css')}}">


<script type='text/javascript'>
	//WARRANTY JOB ORDER REQUEST
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    when('/warranty-job-order-request/card-list', {
	        template: '<warranty-job-order-request-card-list></warranty-job-order-request-card-list>',
	        title: 'Warranty Job Order Requests'
	    }).
	    when('/warranty-job-order-request/form/:request_id?', {
	        template: '<warranty-job-order-request-form></warranty-job-order-request-form>',
	        title: 'Warranty Job Order Request - Form'
	    }).
	    when('/warranty-job-order-request/view/:request_id', {
	        template: '<warranty-job-order-request-view></warranty-job-order-request-view>',
	        title: 'Warranty Job Order Request - View'
	    });
	}]);

    var warrantyJobOrderRequestCardList = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/card-list.html")}}';
    var warrantyJobOrderRequestForm = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/form.html")}}';
    var warrantyJobOrderRequestView = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/view.html")}}';

	//partials
    var warrantyJobOrderRequestFormTabs = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/partials/warranty-job-order-request-form-tabs.html")}}';
    var wjorViewTabs = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/partials/wjor-view-tabs.html")}}';
    var warrantyJobOrderRequestPprForm = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/partials/ppr-form.html")}}';
    var warrantyJobOrderRequestEstimateForm = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/partials/estimate-form.html")}}';
    var warrantyJobOrderRequestAttachmentForm = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/partials/wjor-attachment-form.html")}}';
    var wjorPprView = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/partials/wjor-ppr-view.html")}}';
    var wjorEstimateView = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/partials/wjor-estimate-view.html")}}';
    var wjorAttachmentView = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/partials/wjor-attachment-view.html")}}';
    var wjorHeader = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/partials/wjor-header.html")}}';

    var labourModalForm = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/partials/labour-modal-form.html")}}';
    var partModalForm = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/partials/part-modal-form.html")}}';

</script>
<script type="text/javascript" src='{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/controller.js")}}'></script>
<script type="text/javascript" src='{{asset($gigo_pkg_prefix."/public/services/gigo-pkg-services.js")}}'></script>

<script type='text/javascript'>
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

	//Trade Plate Number
    var trade_plate_number_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/trade-plate-number/list.html')}}';
    var trade_plate_number_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/trade-plate-number/form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/trade-plate-number/controller.js')}}'></script>

<script type='text/javascript'>
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

	var road_test_gate_pass_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/road-test-gate-pass/list.html')}}";
    var road_test_gate_pass_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/road-test-gate-pass/card-list.html')}}";
    var road_test_gate_pass_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/road-test-gate-pass/view.html')}}";
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/road-test/controller.js')}}'></script>

<script type='text/javascript'>
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

	var floating_gate_pass_table_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/floating-gate-pass/list.html')}}";
    var floating_gate_pass_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/floating-gate-pass/card-list.html')}}";
    var floating_gate_pass_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/floating-gate-pass/view.html')}}";
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/floating-gate-pass/controller.js')}}'></script>

<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //Survey Type
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

	//Survey Type
    var survey_type_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/survey-type/list.html')}}';
    var survey_type_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/survey-type/form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/survey-type/controller.js')}}'></script>

<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //OTP
	    when('/otp/list', {
	        template: '<otp-list></otp-list>',
	        title: 'OTP',
	    });
	}]);

	//Otp
    var otp_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/otp/list.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/otp/controller.js')}}'></script>

<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
		//GatePass
		when('/gate-pass/table-list', {
			template: '<gate-pass-list></gate-pass-list>',
			title: 'GatePass',
		}).
		when('/gate-pass/card-list', {
			template: '<gate-pass-card-list></gate-pass-card-list>',
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
		}).
		when('/gate-pass/approve/view/:id', {
			template: '<gate-pass-approve-view></gate-pass-approve-view>',
			title: 'View GatePass',
		}).
		when('/gate-pass/verify/view/:id', {
			template: '<gate-pass-verify-view></gate-pass-verify-view>',
			title: 'View GatePass',
		});
	}]);

	//GatePass
    var gate_pass_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/list.html')}}';
    var gate_pass_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/card-list.html')}}';
	var gate_pass_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/form.html')}}';
	var gate_pass_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/view.html')}}';
    var gate_pass_approve_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/approve-view.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/controller.js')}}'></script>


<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
		//Vehicle Delivery
		when('/manual-vehicle-delivery/table-list', {
			template: '<manual-vehicle-delivery-list></manual-vehicle-delivery-list>',
			title: 'Manual Vehicle Delivery',
		}).
		
		when('/manual-vehicle-delivery/form/:id', {
			template: '<manual-vehicle-delivery-form></manual-vehicle-delivery-form>',
			title: 'Manual Vehicle Delivery Form',
		}).

		when('/manual-vehicle-delivery/view/:id', {
			template: '<manual-vehicle-delivery-view></manual-vehicle-delivery-view>',
			title: 'View Manual Vehicle Delivery',
		});
	}]);

	//Vehicle Delivery
    var manual_vehicle_delivery_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/manual-vehicle-delivery/list.html')}}';
	var manual_vehicle_delivery_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/manual-vehicle-delivery/view.html')}}';
	var manual_vehicle_delivery_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/manual-vehicle-delivery/form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/manual-vehicle-delivery/controller.js')}}'></script>

<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
		//TVS One Discount Request
		when('/tvs-one/discount-request/table-list', {
			template: '<tvs-one-discount-table-list></tvs-one-discount-table-list>',
			title: 'TVS One Discount List',
		}).

		when('/tvs-one/discount-request/view/:id', {
			template: '<tvs-one-discount-view></tvs-one-discount-view>',
			title: 'View TVS One Discount',
		});
	}]);

	//TVS One Discount Request
    var tvs_one_discount_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/tvs-one-discount/list.html')}}';
	var tvs_one_discount_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/tvs-one-discount/view.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/tvs-one-discount/controller.js')}}'></script>

<!-- On Site Visit Detail -->
<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
		//On Site Visit
		when('/on-site-visit/table-list', {
			template: '<on-site-visit-list></on-site-visit-list>',
			title: 'On Site Visit',
		}).
		when('/on-site-visit/form/:id?', {
			template: '<on-site-visit-form></on-site-visit-form>',
			title: 'On Site Visit Form',
		}).
		when('/on-site-visit/view/:id', {
			template: '<on-site-visit-view></on-site-visit-view>',
			title: 'On Site Visit View',
		}).
		when('/on-site-visit/issue-bulk-part/form/:id', {
			template: '<on-site-visit-issue-bulk-part></on-site-visit-issue-bulk-part>',
			title: 'On Site Visit Issue Parts',
		});
	}]);

	//On Site Visit
    var on_site_visit_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/on-site-visit/list.html')}}';
	var on_site_visit_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/on-site-visit/view.html')}}';
	var on_site_visit_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/on-site-visit/form.html')}}';
	var on_site_visit_part_bulk_issue_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/on-site-visit/issue-bulk-part.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/on-site-visit/controller.js')}}'></script>

<!-- Battery -->
<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
		//Battery
		when('/battery/table-list', {
			template: '<battery-list></battery-list>',
			title: 'Battery Status List',
		}).
		when('/battery/form/:id?', {
			template: '<battery-form></battery-form>',
			title: 'Battery Status Form',
		}).
		when('/battery/view/:id', {
			template: '<battery-view></battery-view>',
			title: 'Battery Status View',
		});
	}]);

	//Battery
    var battery_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/battery/list.html')}}';
    var battery_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/battery/form.html')}}';
    var battery_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/battery/view.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/battery/controller.js')}}'></script>

<!-- GIGO Report -->
<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
		//Report
		when('/mechanic/report', {
			template: '<mechanic-report></mechanic-report>',
			title: 'Mechanic Report',
		});
	}]);

	//Report
    var gigo_mechanic_report_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/report/mechanic-list.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/report/controller.js')}}'></script>

