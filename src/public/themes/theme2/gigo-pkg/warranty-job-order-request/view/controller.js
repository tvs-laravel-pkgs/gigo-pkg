app.component('warrantyJobOrderRequestView', {
    templateUrl: warrantyJobOrderRequestView,
    controller: function($http, $location, $ngBootbox, HelperService, WarrantyJobOrderRequestSvc, ServiceTypeSvc, ConfigSvc, PartSupplierSvc, VehicleSecondaryApplicationSvc, VehiclePrimaryApplicationSvc, ComplaintSvc, FaultSvc, JobOrderSvc, $scope, $routeParams, $rootScope, $element, $mdSelect, $q, RequestSvc) {
        $rootScope.loading = true;
        var self = this;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $scope.user = HelperService.getLoggedUser();

        $scope.init = function() {
            $rootScope.loading = true;

            let promises = {
                warranty_job_order_request_read: WarrantyJobOrderRequestSvc.read($routeParams.request_id),
            };

            $q.all(promises)
                .then(function(responses) {
                    $scope.warranty_job_order_request = responses.warranty_job_order_request_read.data.warranty_job_order_request;
                    $scope.calculateLabourTotal();
                    $scope.calculatePartTotal();
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
        };
        $scope.init();

        $scope.sendToApproval = function(warranty_job_order_request) {
            $ngBootbox.confirm({
                    message: 'Are you sure you want to send to approval?',
                    title: 'Confirm',
                    size: "small",
                    className: 'text-center',
                })
                .then(function() {
                    $rootScope.loading = true;
                    WarrantyJobOrderRequestSvc.sendToApproval(warranty_job_order_request)
                        .then(function(response) {
                            $rootScope.loading = false;
                            if (!response.data.success) {
                                showErrorNoty(response.data);
                                return;
                            }
                            showNoty('success', 'Warranty job order request initiated successfully');
                            $location.path('/warranty-job-order-request/card-list');
                            $scope.$apply();
                        });
                });
            return;
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
                            $location.path('/warranty-job-order-request/card-list');
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
                WarrantyJobOrderRequestSvc.approve($scope.warranty_job_order_request)
                    .then(function(response) {
                        if (!response.data.success) {
                            showErrorNoty(response.data);
                            return;
                        }
                        $('#approve_modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        showNoty('success', 'Warranty job order request approved successfully');
                        $location.path('/warranty-job-order-request/card-list');
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
                        $location.path('/warranty-job-order-request/card-list');
                    });
            }
        });


        $scope.calculateLabourTotal = function() {
            $same_state = $scope.isSameState();
            
            var total = 0;
            angular.forEach($scope.warranty_job_order_request.repair_orders, function(repair_order) {
                // var amount = repair_order.amount;
                var tax_total = 0;

                amount = repair_order.pivot.net_amount;
                tax_total = repair_order.pivot.tax_total;

                if (repair_order.tax_code) {
                    angular.forEach(repair_order.tax_code.taxes, function(tax) {
                        if ($same_state==true) {
                            if (tax.type_id != 1160) {
                                tax.pivot.percentage = 0;
                            }
                        }else{
                            if (tax.type_id == 1160) {
                                tax.pivot.percentage = 0;
                            }
                        }
                        // tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                    })
                }
                repair_order.net_amount = parseFloat(amount) + parseFloat(tax_total);

                repair_order.net_amount_without_tax = parseFloat(amount);
                repair_order.tax_amount = parseFloat(tax_total);
                repair_order.net_amount_with_tax = parseFloat(repair_order.net_amount);

                total += parseFloat(amount) + parseFloat(tax_total);
            });

            $scope.warranty_job_order_request.repair_order_total = total;
            $scope.calculateEstimateTotal()
            /*
            var total = 0;
            angular.forEach($scope.warranty_job_order_request.repair_orders, function(repair_order) {
                total += parseFloat(repair_order.amount);
            });
            $scope.warranty_job_order_request.repair_order_total = total;
            $scope.calculateEstimateTotal()*/
        }

        $scope.calculatePartTotal = function() {
            $same_state = $scope.isSameState();

            var total = 0;

            angular.forEach($scope.warranty_job_order_request.parts, function(part) {
                var amount = part.rate;
                var tax_total = 0;
                if (part.tax_code) {
                    angular.forEach(part.tax_code.taxes, function(tax) {
                        if ($same_state==true) {
                            if (tax.type_id == 1160) {
                                tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                            }else{
                                tax.pivot.percentage = 0;
                                tax_total += 0;
                            }
                        }else{
                            if (tax.type_id != 1160) {
                                tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                            }else{
                                tax.pivot.percentage = 0;
                                tax_total += 0;
                            }
                        }
                        // tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                    })
                }
                part.net_amount = parseFloat(amount) + tax_total;
                part.net_amount_without_tax = parseFloat(amount);
                part.tax_amount = parseFloat(tax_total);
                part.net_amount_with_tax = part.net_amount;

                console.log(tax_total);
                total += parseFloat(amount) + tax_total;
                part.quantity = Math.trunc(part.pivot.quantity);
            });
            $scope.warranty_job_order_request.part_total = total.toFixed(2);
            $scope.calculateEstimateTotal()

            /*var total = 0;
            angular.forEach($scope.warranty_job_order_request.parts, function(part) {
                total += parseFloat(part.amount);
            });
            $scope.warranty_job_order_request.part_total = total.toFixed(2);
            $scope.calculateEstimateTotal()*/
        }

        $scope.calculateEstimateTotal = function() {
            $scope.warranty_job_order_request.estimate_total = parseFloat($scope.warranty_job_order_request.repair_order_total) + parseFloat($scope.warranty_job_order_request.part_total);
        }
        $scope.isSameState = function(){
            $same_state = false;
            if ($scope.warranty_job_order_request.job_order != undefined) {
                var customer_state = $scope.warranty_job_order_request.job_order.customer.state_id;
                var job_order_state = $scope.warranty_job_order_request.job_order.outlet.state_id;
                if (customer_state == job_order_state) {
                    $same_state = true;
                }else{
                    $same_state = false;
                }
            }
            return $same_state;
        }
    }
});