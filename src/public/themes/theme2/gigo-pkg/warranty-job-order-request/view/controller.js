app.component('warrantyJobOrderRequestView', {
    templateUrl: warrantyJobOrderRequestView,
    controller: function($http, $location, $ngBootbox, HelperService, OutletSvc, CustomerSvc, WarrantyJobOrderRequestSvc, ServiceTypeSvc, ConfigSvc, PartSupplierSvc, VehicleSecondaryApplicationSvc, VehiclePrimaryApplicationSvc, ComplaintSvc, FaultSvc, JobOrderSvc, $scope, $routeParams, $rootScope, $element, $mdSelect, $q, RequestSvc) {
        $rootScope.loading = true;
        var self = this;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $scope.user = HelperService.getLoggedUser();
        $scope.hasPerm = HelperService.hasPerm;

        $scope.form_type = 'manual';


        $scope.init = function() {
            $rootScope.loading = true;

            let promises = {
                warranty_job_order_request_read: WarrantyJobOrderRequestSvc.read($routeParams.request_id),
            };

            $scope.page = 'view';

            $q.all(promises)
                .then(function(responses) {
                    $scope.warranty_job_order_request = responses.warranty_job_order_request_read.data.warranty_job_order_request;

                    if ($scope.warranty_job_order_request.status_id == 9101) {
                        $scope.warranty_job_order_request.authorized_date = HelperService.getCurrentDate();
                    }
                    $scope.service_types = [];
                    angular.forEach($scope.warranty_job_order_request.service_types, function(service_type) {
                        $scope.service_types.push(service_type.name);
                    });

                    let promises2 = {
                        customer_service: CustomerSvc.read($scope.warranty_job_order_request.job_order.customer_id),
                        outlet_service: OutletSvc.getBusiness({ outletId: $scope.warranty_job_order_request.job_order.outlet.id, businessName: 'ALSERV' }),
                    };

                    $q.all(promises2)
                        .then(function(responses) {

                            $scope.warranty_job_order_request.job_order.customer = responses.customer_service.data.customer;
                            $scope.warranty_job_order_request.job_order.outlet.business = responses.outlet_service.data.business;

                            $scope.calculateTotals();
                            $("#file-1").fileinput({
                                theme: 'fas',
                                overwriteInitial: true,
                                // minFileCount: 1,
                                maxFileSize: 5000,
                                // required: true,
                                showUpload: false,
                                browseOnZoneClick: true,
                                removeFromPreviewOnError: true,
                                initialPreviewShowDelete: true,
                                deleteUrl: '',
                                // showRemove:true,
                                // maxFilesNum: 10,
                                // initialPreview: [
                                //     "<img src='/images/desert.jpg' class='file-preview-image' alt='Desert' title='Desert'>",
                                //     "<img src='/images/jellyfish.jpg' class='file-preview-image' alt='Jelly Fish' title='Jelly Fish'>",
                                // ],
                                // allowedFileTypes: ['image'],
                                slugCallback: function(filename) {
                                    return filename.replace('(', '_').replace(']', '_');
                                }
                            });

                            $rootScope.loading = false;
                        });
                });

        };
        $scope.init();

        $scope.sendToApproval = function(warranty_job_order_request) {
            $('#request_confirmation_modal').modal('show');
        }

        $scope.sendApproval = function(warranty_job_order_request) {
            console.log(warranty_job_order_request);
            $(".send_approval").button('loading');
            $('.send_approval').attr('readonly', true).text('loading');
            // $ngBootbox.confirm({
            //         message: 'Are you sure you want to send to approval?',
            //         title: 'Confirm',
            //         size: "small",
            //         className: 'text-center',
            //     })
            //     .then(function() {
            $rootScope.loading = true;
            WarrantyJobOrderRequestSvc.sendToApproval(warranty_job_order_request)
                .then(function(response) {
                    $rootScope.loading = false;
                    if (!response.data.success) {
                        showErrorNoty(response.data);
                        return;
                    }

                    $('#request_confirmation_modal').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $('.send_approval').button('reset');

                    showNoty('success', 'Warranty job order request initiated successfully');
                    $location.path('/warranty-job-order-request/table-list');
                    $scope.$apply();
                });
            //     });
            // return;
        }

        $scope.confirmDelete = function(warranty_job_order_request) {
            $ngBootbox.confirm({
                    message: 'Are you sure you want to delete this?',
                    title: 'Confirm',
                    size: "small",
                    className: 'text-center',
                })
                .then(function() {
                    WarrantyJobOrderRequestSvc.remove(warranty_job_order_request)
                        .then(function(response) {
                            if (!response.data.success) {
                                showErrorNoty(response.data);
                                return;
                            }
                            showNoty('success', 'Warranty job order request deleted successfully');
                            $location.path('/warranty-job-order-request/table-list');
                        });
                });
        }


        $scope.showApprovalForm = function(warranty_job_order_request) {
            $('#approve_modal').modal('show');
        }

        $scope.showRejectForm = function(warranty_job_order_request) {
            $('#reject_modal').modal('show');
        }

        var form_id = '#approval-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'authorization_number': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                showNoty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {
                $(".submit").button('loading');
                $('.sendToApproval').attr('readonly', true).text('loading');
                // $('.sendToApproval').button('loading');

                WarrantyJobOrderRequestSvc.approve($scope.warranty_job_order_request)
                    .then(function(response) {
                        if (!response.data.success) {
                            $('.sendToApproval').button('reset');
                            showErrorNoty(response.data);
                            return;
                        }
                        $('#approve_modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        $('.sendToApproval').button('reset');
                        $(".submit").button('reset');

                        showNoty('success', 'Warranty job order request approved successfully');
                        $location.path('/warranty-job-order-request/table-list');
                    });
            }
        });

        var form_id = '#rejection-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'rejected_reason': {
                    required: true,
                    minlength: 5,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                showNoty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {
                $(".submit").button('loading');
                WarrantyJobOrderRequestSvc.reject($scope.warranty_job_order_request)
                    .then(function(response) {
                        if (!response.data.success) {
                            showErrorNoty(response.data);
                            return;
                        }
                        $('#reject_modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        showNoty('success', 'Warranty job order request rejected successfully');
                        $location.path('/warranty-job-order-request/table-list');
                        $(".submit").button('reset');

                    });
            }
        });

        // Common -------------------------------------

        $scope.calculateTotals = function() {
            $scope.warranty_job_order_request.repair_order_total = HelperService.calculateTotal($scope.warranty_job_order_request.wjor_repair_orders);
            $scope.warranty_job_order_request.part_total = HelperService.calculateTotal($scope.warranty_job_order_request.wjor_parts);
            $scope.warranty_job_order_request.estimate_total = $scope.warranty_job_order_request.repair_order_total + $scope.warranty_job_order_request.part_total;
        }

    }
});