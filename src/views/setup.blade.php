@if(config('gigo-pkg.DEV'))
    <?php $gigo_pkg_prefix = '/packages/abs/gigo-pkg/src';?>
@else
    <?php $gigo_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    var gigo_pkg_url = "{{url($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg')}}";

	//Vehicle Segments
    var vehicle_segment_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-segment/list.html')}}";
    var vehicle_segment_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-segment/form.html')}}";

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
     //Parts Indent
     var parts_indent_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/list.html')}}";
     var parts_indent_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/view.html')}}";
     var parts_indent_edit_parts_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/parts-indent/edit-parts.html')}}";

</script>
<!-- <script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-segment/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-primary-application/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/fault/controller.js')}}"></script>
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
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/part-supplier/controller.js')}}"></script> -->


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

	//Vehicle Owners
    var vehicle_owner_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-owner/list.html')}}';
    var vehicle_owner_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-owner/form.html')}}';
    var vehicle_owner_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-owner/card-list.html')}}';
    var vehicle_owner_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-owner-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-owner/controller.js')}}'></script> -->


<script type='text/javascript'>

	//Amc Members
    var amc_member_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/amc-member/list.html')}}';
    var amc_member_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/amc-member/form.html')}}';
    var amc_member_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/amc-member/card-list.html')}}';
    var amc_member_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/amc-member-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/amc-member/controller.js')}}'></script> -->


<script type='text/javascript'>
	//Vehicle Warranty Members
    var vehicle_warranty_member_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-warranty-member/list.html')}}';
    var vehicle_warranty_member_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-warranty-member/form.html')}}';
    var vehicle_warranty_member_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-warranty-member/card-list.html')}}';
    var vehicle_warranty_member_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-warranty-member-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-warranty-member/controller.js')}}'></script> -->


<script type='text/javascript'>

	//Insurance Members
    var insurance_member_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/insurance-member/list.html')}}';
    var insurance_member_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/insurance-member/form.html')}}';
    var insurance_member_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/insurance-member/card-list.html')}}';
    var insurance_member_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/insurance-member-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/insurance-member/controller.js')}}'></script> -->


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

	//Vehicle Inventory Items
    var vehicle_inventory_item_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inventory-item/list.html')}}';
    var vehicle_inventory_item_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inventory-item/form.html')}}';
    var vehicle_inventory_item_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inventory-item/card-list.html')}}';
    var vehicle_inventory_item_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-inventory-item-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inventory-item/controller.js')}}'></script> -->


<script type='text/javascript'>


	//Vehicle Inspection Item Groups
    var vehicle_inspection_item_group_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/list.html')}}";
    var vehicle_inspection_item_group_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/form.html')}}";
    var vehicle_inspection_item_group_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/card-list.html')}}";
    var vehicle_inspection_item_group_modal_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-inspection-item-group-modal-form.html')}}";
</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/controller.js')}}"></script> -->


<script type='text/javascript'>

	//Vehicle Inspection Items
    var vehicle_inspection_item_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/list.html')}}";
    var vehicle_inspection_item_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/form.html')}}";
    var vehicle_inspection_item_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/card-list.html')}}";
    var vehicle_inspection_item_modal_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-inspection-item-modal-form.html')}}";
</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/controller.js')}}"></script> -->


<script type='text/javascript'>

	//Customer Voices
    var customer_voice_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/list.html')}}";
    var customer_voice_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/form.html')}}";
    var customer_voice_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/card-list.html')}}";
    var customer_voice_modal_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/customer-voice-modal-form.html')}}";
</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/controller.js')}}"></script> -->


<script type='text/javascript'>


	//Split Order Types
    var split_order_type_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/list.html')}}';
    var split_order_type_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/form.html')}}';
    var split_order_type_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/card-list.html')}}';
    var split_order_type_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/split-order-type-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/controller.js')}}"></script> -->


<script type='text/javascript'>


	//Bays
    var bay_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/bay/list.html')}}';
    var bay_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/bay/form.html')}}';
    var bay_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/bay/card-list.html')}}';
    var bay_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/bay-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/bay/controller.js')}}"></script> -->


<script type='text/javascript'>

	//Campaigns
    var campaigns_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/campaign/list.html')}}';
    var campaigns_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/campaign/form.html')}}';
</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/campaign/controller.js?v=3')}}"></script> -->


<script type='text/javascript'>

	//Estimation Types
    var estimation_type_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/estimation-type/list.html')}}';
    var estimation_type_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/estimation-type/form.html')}}';
    var estimation_type_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/estimation-type/card-list.html')}}';
    var estimation_type_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/estimation-type-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/estimation-type/controller.js')}}'></script> -->


<script type='text/javascript'>

	var view_img = './public/theme/img/table/cndn/view.svg';
	var gate_out_img = './public/theme/img/table/cndn/gateout.svg';


	//Vehicle Gate Passes
var vehicle_gate_pass_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-gate-pass/list.html')}}";
var vehicle_gate_pass_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-gate-pass/view.html')}}";
</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-gate-pass/controller.js')}}"></script> -->


<script type='text/javascript'>


	//Gate Logs

    var gate_log_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/list.html')}}';
    var gate_log_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/form.html')}}";
    var gate_log_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/card-list.html')}}';
    var gate_log_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/gate-log-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/controller.js')}}"></script> -->


<!-- Inward Vehicle -->
<script type='text/javascript'>


    var inward_vehicle_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/card-list.html')}}';
    var inward_vehicle_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/list.html')}}';
    var job_order_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/job-order-view.html')}}';
    var inward_vehicle_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/view.html')}}';
    var inward_vehicle_vehicle_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/vehicle-detail.html')}}';
    var inward_vehicle_customer_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/customer-detail.html')}}';
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
    var inward_vehicle_estimation_status_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/estimation-status-detail-form.html')}}';

    var inward_vehicle_gate_in_detail_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/gate-in-detail.html')}}';
    var inward_vehicle_vehicle_detail_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/vehicle-detail-view.html')}}';
    var inward_vehicle_customer_detail_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/customer-detail-view.html')}}';


    //PARTIALS
    var job_order_header_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-header.html')}}';
    var inward_tabs_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/inward-tabs.html')}}';
    var inward_view_tabs_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/inward-view-tabs.html')}}';

</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/inward-vehicle/controller.js')}}"></script> -->

<!--My Job Card--->
<script type='text/javascript'>

	//Gate Logs
    var myjobcard_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/my-jobcard/card-list.html')}}';
    var myjobcard_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/my-jobcard/view.html')}}';

</script>
<!-- <script type='text/javascript' src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/my-jobcard/controller.js')}}"></script> -->


<script type='text/javascript'>

	//Job Order Repair Orders
    var job_order_repair_order_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-repair-order/list.html')}}';
    var job_order_repair_order_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-repair-order/form.html')}}';
    var job_order_repair_order_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-repair-order/card-list.html')}}';
    var job_order_repair_order_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-repair-order-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-repair-order/controller.js')}}'></script> -->


<script type='text/javascript'>


	//Repair Order Mechanics
    var repair_order_mechanic_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-mechanic/list.html')}}';
    var repair_order_mechanic_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-mechanic/form.html')}}';
    var repair_order_mechanic_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-mechanic/card-list.html')}}';
    var repair_order_mechanic_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/repair-order-mechanic-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-mechanic/controller.js')}}'></script> -->


<script type='text/javascript'>

	//Mechanic Time Logs
    var mechanic_time_log_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mechanic-time-log/list.html')}}';
    var mechanic_time_log_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mechanic-time-log/form.html')}}';
    var mechanic_time_log_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mechanic-time-log/card-list.html')}}';
    var mechanic_time_log_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/mechanic-time-log-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mechanic-time-log/controller.js')}}'></script> -->


<script type='text/javascript'>


	//Job Order Parts
    var job_order_part_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-part/list.html')}}';
    var job_order_part_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-part/form.html')}}';
    var job_order_part_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-part/card-list.html')}}';
    var job_order_part_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-part-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-part/controller.js')}}'></script> -->


<script type='text/javascript'>


	//Job Order Issued Parts
    var job_order_issued_part_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-issued-part/list.html')}}';
    var job_order_issued_part_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-issued-part/form.html')}}';
    var job_order_issued_part_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-issued-part/card-list.html')}}';
    var job_order_issued_part_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-issued-part-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order-issued-part/controller.js')}}'></script> -->


<script type='text/javascript'>


	//Job Cards
    var job_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/list.html')}}';
    var job_card_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/form.html')}}';
    var job_card_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/card-list.html')}}';
    var job_card_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-card-modal-form.html')}}';

    var job_card_bay_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/bay-form.html')}}';

    var job_card_returnable_item_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/returnable-item-detail.html')}}';
     var job_card_returnable_item_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/returnable-item-form.html')}}';

    var job_card_material_gatepass_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/material-gatepass.html')}}';
    var job_card_material_outward_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/material-outward.html')}}';
    var job_card_material_road_test_observation_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/road-test-observation.html')}}';
    var job_card_export_diagonosis_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/expert-diagnosis.html')}}';
    var job_card_vehicle_inspection_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/vehicle-inspection.html')}}';
    var job_card_dms_checklist_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/dms-checklist.html')}}';
    var job_card_schedule_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/schedule.html')}}";
    var job_card_labour_review_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/labour-review.html')}}";
    var job_card_bil_detail_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/bill-detail.html')}}";
    var job_card_bil_detail_update_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/bill-detail-update.html')}}";
    var job_card_bay_view_template_url  = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/bay-view.html')}}";

    var job_card_split_order_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/split-order.html')}}";


    var job_card_part_indent_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/parts-indent.html')}}';
    var job_card_schedule_maintendance_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/schedule-maintenance.html')}}';
    var job_card_parts_labour_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/payable-labour-part.html')}}';
    var job_card_estimate_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/estimate.html')}}';
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
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/controller.js')}}'></script> -->



{{-- MOBILE PAGES --}}

{{-- MOBILE COMMON PAGES --}}
<script type='text/javascript'>

    var mobile_login_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/login.html')}}";
    var mobile_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/dashboard.html')}}";
    var mobile_menus_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/menus.html')}}";
    var mobile_kanban_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/kanban-dashboard.html')}}";
    var mobile_header_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/header.html')}}";

</script>
<!-- <script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/controller.js')}}"></script> -->

{{-- MOBILE ATTENDANCE --}}
<script type='text/javascript'>
    var mobile_attendance_scan_qr_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/attendance/scan-qr.html')}}";

    var mobile_attendance_employee_card_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/employee-card.html')}}";


</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/attendance/controller.js')}}'></script> -->

{{-- MOBILE GATE IN VEHICLE --}}
<script type='text/javascript'>
    var mobile_gate_in_vehicle_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/gate-in-vehicle/form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/gate-in-vehicle/controller.js')}}'></script> -->

{{-- MOBILE VEHICLE GATE PASSES --}}
<script type='text/javascript'>

    var mobile_vehicle_gate_pass_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/vehicle-gate-pass/list.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/vehicle-gate-pass/controller.js')}}'></script> -->

{{-- MOBILE INWARD VEHICLE  --}}
<script type='text/javascript'>
	//Inward Vehicles
    var mobile_inward_vehicle_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/list.html')}}';
    var mobile_inward_vehicle_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/vehicle-detail.html')}}';
    var mobile_inward_vehicle_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/vehicle-form.html')}}';
    var mobile_inward_customer_detail_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/customer-detail.html')}}';
    var mobile_inward_customer_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/customer-form.html')}}';
    var mobile_inward_order_detail_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/order-detail-form.html')}}';

    var mobile_inward_header_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/partials/header.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/controller.js')}}'></script> -->


<script type='text/javascript'>


	//Job Cards
    var mobile_job_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/job-card/list.html')}}';
    var mobile_job_card_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/job-card/form.html')}}';
    var mobile_job_card_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/job-card/card-list.html')}}';
    var mobile_job_card_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/job-card-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/job-card/controller.js')}}'></script> -->


<script type='text/javascript'>


	//Material Gate Passes
    var mobile_material_gate_pass_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/material-gate-pass/list.html')}}';
    var mobile_material_gate_pass_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/material-gate-pass/form.html')}}';
    var mobile_material_gate_pass_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/material-gate-pass/card-list.html')}}';
    var mobile_material_gate_pass_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/material-gate-pass-modal-form.html')}}';
</script>
<!-- <script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/material-gate-pass/controller.js')}}'></script> -->
<link rel="stylesheet" type="text/css" href="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/mobile.css')}}">


<script type='text/javascript'>
	//WARRANTY JOB ORDER REQUEST

    
    var warrantyJobOrderRequestCardList = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/card-list/template.html")}}';
    var warrantyJobOrderRequestForm = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/form/template.html")}}';
    var warrantyJobOrderRequestView = '{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/view/template.html")}}';

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
<!-- <script type="text/javascript" src='{{asset($gigo_pkg_prefix."/public/themes/".$theme."/gigo-pkg/warranty-job-order-request/controller.js")}}'></script> -->
<!-- <script type="text/javascript" src='{{asset($gigo_pkg_prefix."/public/services/gigo-pkg-services.js")}}'></script> -->

