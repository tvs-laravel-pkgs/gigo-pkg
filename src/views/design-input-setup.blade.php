@if(config('gigo-pkg.DEV'))
    <?php $gigo_pkg_prefix = '/packages/abs/gigo-pkg/src';?>
@else
    <?php $gigo_pkg_prefix = '';?>
@endif



{{-- Warranty Job Order Requests --}}
<script type="text/javascript">

    var designInputWarrantyJobOrderRequest = "{{asset($gigo_pkg_prefix.'/public/design-input/warranty-job-order-request/list.html')}}";
    var warrantyJobOrderRequestPPRForm = "{{asset($gigo_pkg_prefix.'/public/design-input/warranty-job-order-request/ppr-form.html')}}";
    var warrantyJobOrderRequestEstimateForm = "{{asset($gigo_pkg_prefix.'/public/design-input/warranty-job-order-request/estimate-form.html')}}";

	//partials
    var warrantyJobOrderRequestFormTabs = "{{asset($gigo_pkg_prefix.'/public/design-input/warranty-job-order-request/partials/warranty-job-order-request-form-tabs.html')}}";	
</script>

<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/design-input/warranty-job-order-request/controller.js')}}"></script>