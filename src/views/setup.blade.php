
@if(config('gigo-pkg.DEV'))
    <?php $gigo_pkg_prefix = '/packages/abs/gigo-pkg/src';?>
@else
    <?php $gigo_pkg_prefix = '';?>
@endif

<script type="text/javascript">
	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
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
	    });

	}]);

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

    //Quote Types
    var quote_type_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/list.html')}}";
    var quote_type_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/form.html')}}";

</script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/service-type/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/quote-type/controller.js')}}"></script>


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


<script type='text/javascript'>
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
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle/controller.js')}}'></script>


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
    var vehicle_inspection_item_group_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/list.html')}}';
    var vehicle_inspection_item_group_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/form.html')}}';
    var vehicle_inspection_item_group_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/card-list.html')}}';
    var vehicle_inspection_item_group_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-inspection-item-group-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item-group/controller.js')}}'></script>


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
    var vehicle_inspection_item_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/list.html')}}';
    var vehicle_inspection_item_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/form.html')}}';
    var vehicle_inspection_item_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/card-list.html')}}';
    var vehicle_inspection_item_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/vehicle-inspection-item-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/vehicle-inspection-item/controller.js')}}'></script>


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
    var customer_voice_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/list.html')}}';
    var customer_voice_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/form.html')}}';
    var customer_voice_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/card-list.html')}}';
    var customer_voice_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/customer-voice-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/customer-voice/controller.js')}}'></script>


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
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/split-order-type/controller.js')}}'></script>


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
	    //Gate Pass
	    when('/gigo-pkg/gate-pass/list', {
	        template: '<gate-pass-list></gate-pass-list>',
	        title: 'Gate Passes',
	    }).
	    when('/gigo-pkg/gate-pass/add', {
	        template: '<gate-pass-form></gate-pass-form>',
	        title: 'Add Gate Pass',
	    }).
	    when('/gigo-pkg/gate-pass/edit/:id', {
	        template: '<gate-pass-form></gate-pass-form>',
	        title: 'Edit Gate Pass',
	    }).
	    when('/gigo-pkg/gate-pass/card-list', {
	        template: '<gate-pass-card-list></gate-pass-card-list>',
	        title: 'Gate Pass Card List',
	    });
	}]);

	//Gate Passes
    var gate_pass_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/list.html')}}';
    var gate_pass_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/form.html')}}';
    var gate_pass_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/card-list.html')}}';
    var gate_pass_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/gate-pass-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-pass/controller.js')}}'></script>


<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //Gate Log
	    when('/gigo-pkg/gate-log/list', {
	        template: '<gate-log-list></gate-log-list>',
	        title: 'Gate Logs',
	    }).
	    when('/gigo-pkg/gate-log/add', {
	        template: '<gate-log-form></gate-log-form>',
	        title: 'Add Gate Log',
	    }).
	    when('/gigo-pkg/gate-log/edit/:id', {
	        template: '<gate-log-form></gate-log-form>',
	        title: 'Edit Gate Log',
	    }).
	    when('/gigo-pkg/gate-log/card-list', {
	        template: '<gate-log-card-list></gate-log-card-list>',
	        title: 'Gate Log Card List',
	    });
	}]);

	//Gate Logs
    var gate_log_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/list.html')}}';
    var gate_log_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/form.html')}}';
    var gate_log_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/card-list.html')}}';
    var gate_log_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/gate-log-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/gate-log/controller.js')}}'></script>


<script type='text/javascript'>
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //Job Order
	    when('/gigo-pkg/job-order/list', {
	        template: '<job-order-list></job-order-list>',
	        title: 'Job Orders',
	    }).
	    when('/gigo-pkg/job-order/add', {
	        template: '<job-order-form></job-order-form>',
	        title: 'Add Job Order',
	    }).
	    when('/gigo-pkg/job-order/edit/:id', {
	        template: '<job-order-form></job-order-form>',
	        title: 'Edit Job Order',
	    }).
	    when('/gigo-pkg/job-order/card-list', {
	        template: '<job-order-card-list></job-order-card-list>',
	        title: 'Job Order Card List',
	    });
	}]);

	//Job Orders
    var job_order_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order/list.html')}}';
    var job_order_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order/form.html')}}';
    var job_order_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order/card-list.html')}}';
    var job_order_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-order-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-order/controller.js')}}'></script>


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
	    when('/gigo-pkg/job-card/list', {
	        template: '<job-card-list></job-card-list>',
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
	    });
	}]);

	//Job Cards
    var job_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/list.html')}}';
    var job_card_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/form.html')}}';
    var job_card_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/card-list.html')}}';
    var job_card_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/partials/job-card-modal-form.html')}}';
</script>
<script type='text/javascript' src='{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/controller.js')}}'></script>



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
	    }).
	    when('/gigo-pkg/mobile/attendance/scan-qr', {
	        template: '<mobile-attendance-scan-qr></mobile-attendance-scan-qr>',
	        title: 'Mobile Dashboard',
	    });
	}]);

    var mobile_login_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/login.html')}}";
    var mobile_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/dashboard.html')}}";
    var mobile_menus_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/menus.html')}}";
    var mobile_kanban_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/kanban-dashboard.html')}}";
    var mobile_header_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/header.html')}}";

    var mobile_attendance_scan_qr_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/attendance/scan-qr.html')}}";
</script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/controller.js')}}"></script>

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
	        template: '<mobile-inward-vehicle-detail-view></mobile-inward-vehicle-detail-view>',
	        title: 'Inward Vehicle - Vehicle Details',
	    });
	}]);

	//Inward Vehicles
    var mobile_inward_vehicle_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/list.html')}}';
    var mobile_inward_vehicle_detail_view_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/vehicle-detail-view.html')}}';
    var mobile_inward_vehicle_card_list_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/inward-vehicle/card-list.html')}}';
    var mobile_inward_vehicle_modal_form_template_url = '{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/partials/inward-vehicle-modal-form.html')}}';
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
