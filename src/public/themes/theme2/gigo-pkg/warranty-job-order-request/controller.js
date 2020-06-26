app.directive('warrantyJobOrderRequestFormTabs', function() {
    return {
        templateUrl: warrantyJobOrderRequestFormTabs,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorHeader', function() {
    return {
        templateUrl: wjorHeader,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorViewTabs', function() {
    return {
        templateUrl: wjorViewTabs,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorPprForm', function() {
    return {
        templateUrl: warrantyJobOrderRequestPprForm,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorEstimateForm', function() {
    return {
        templateUrl: warrantyJobOrderRequestEstimateForm,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorAttachmentForm', function() {
    return {
        templateUrl: warrantyJobOrderRequestAttachmentForm,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorApprovalAttachmentForm', function() {
    return {
        templateUrl: gigo_pkg_url +
            '/warranty-job-order-request/partials/wjor-approval-attachment-form.html',
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorPprView', function() {
    return {
        templateUrl: wjorPprView,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorEstimateView', function() {
    return {
        templateUrl: wjorEstimateView,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('wjorAttachmentView', function() {
    return {
        templateUrl: wjorAttachmentView,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('pprView', function() {
    return {
        templateUrl: pprView,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('labourModalForm', function() {
    return {
        templateUrl: labourModalForm,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('partModalForm', function() {
    return {
        templateUrl: partModalForm,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('warrantyJobOrderRequestCardList', {
    templateUrl: warrantyJobOrderRequestCardList,
    controller: function($http, $location, $ngBootbox, HelperService, WarrantyJobOrderRequestSvc, $scope, JobOrderSvc, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $scope.user = HelperService.getLoggedUser();

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        var params = {
            page: 1, // show first page
            count: 100, // count per page
            sorting: {
                created_at: 'asc' // initial sorting
            },
            filter: {
                // search: '',
                statusIn: [9100, 9101, 9103], //new, waiting for approval and rejected
            },
        };

        // typeIn: [2, 5], //warranty & free service orders

        //FETCH DATA
        $scope.fetchData = function() {
            WarrantyJobOrderRequestSvc.index(params)
                .then(function(response) {
                    $scope.warranty_job_order_requests = response.data.warranty_job_order_request_collection;
                    $rootScope.loading = false;
                });
        }
        $scope.fetchData();

        // $scope.sendToApproval = function(warranty_job_order_request) {

        //     console.log(warranty_job_order_request);
        //     WarrantyJobOrderRequestSvc.confirmSendToApproval(warranty_job_order_request)
        //         .then(function(status) {

        //             console.log(status);
        //             warranty_job_order_request.status = status;
        //         });
        // };

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
                            warranty_job_order_request.status = response.data.warranty_job_order_request.status;
                        });
                });
        }


        $scope.confirmDelete = function(warranty_job_order_request,key) {
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
                            // $location.path('/warranty-job-order-request/card-list');
                            $scope.warranty_job_order_requests.splice(key, 1);
                        });
                });
        }

    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------

app.component('warrantyJobOrderRequestTableList', {
    templateUrl: warrantyJobOrderRequestTableList,
    controller: function($http, $location, $ngBootbox, HelperService, WarrantyJobOrderRequestSvc, $scope, JobOrderSvc, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $scope.user = HelperService.getLoggedUser();

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        var params = {
            page: 1, // show first page
            count: 100, // count per page
            sorting: {
                created_at: 'asc' // initial sorting
            },
            filter: {
                // search: '',
                statusIn: [9100, 9101, 9103], //new, waiting for approval and rejected
            },
        };

        // typeIn: [2, 5], //warranty & free service orders

        //FETCH DATA
        $scope.fetchData = function() {
            WarrantyJobOrderRequestSvc.index(params)
                .then(function(response) {
                    $scope.warranty_job_order_requests = response.data.warranty_job_order_request_collection;
                    $rootScope.loading = false;
                });
        }
        $scope.fetchData();

        // $scope.sendToApproval = function(warranty_job_order_request) {

        //     console.log(warranty_job_order_request);
        //     WarrantyJobOrderRequestSvc.confirmSendToApproval(warranty_job_order_request)
        //         .then(function(status) {

        //             console.log(status);
        //             warranty_job_order_request.status = status;
        //         });
        // };

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
                            warranty_job_order_request.status = response.data.warranty_job_order_request.status;
                        });
                });
        }


        $scope.confirmDelete = function(warranty_job_order_request,key) {
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
                            // $location.path('/warranty-job-order-request/card-list');
                            $scope.warranty_job_order_requests.splice(key, 1);
                        });
                });
        }

    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------

app.component('warrantyJobOrderRequestForm', {
    templateUrl: warrantyJobOrderRequestForm,
    controller: function($http, $location, HelperService, RepairOrderSvc, PartSvc, WarrantyJobOrderRequestSvc, ServiceTypeSvc, ConfigSvc, PartSupplierSvc, VehicleSecondaryApplicationSvc, VehiclePrimaryApplicationSvc, ComplaintSvc, FaultSvc, JobOrderSvc, $scope, $routeParams, $rootScope, $element, $mdSelect, $q, RequestSvc) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $scope.user = HelperService.getLoggedUser();

        $scope.init = function() {
            $rootScope.loading = true;

            let promises = {
                service_type_options: ServiceTypeSvc.options(),
                vehicle_primary_application_options: VehiclePrimaryApplicationSvc.options(),
                vehicle_secondary_application_options: VehicleSecondaryApplicationSvc.options(),
                vehicle_operating_condition_options: ConfigSvc.options({ filter: { configType: 300 } }),
                road_condition_options: ConfigSvc.options({ filter: { configType: 301 } }),
                load_range_options: ConfigSvc.options({ filter: { configType: 303 } }),
                terrain_options: ConfigSvc.options({ filter: { configType: 304 } }),
            };

            if (typeof($routeParams.request_id) != 'undefined') {
                $scope.updating = true;
                promises.warranty_job_order_request_read = WarrantyJobOrderRequestSvc.read($routeParams.request_id);
            } else {
                $scope.updating = false;
            }

            $scope.options = {};
            $q.all(promises)
                .then(function(responses) {
                    $scope.options.service_types = responses.service_type_options.data.options;
                    $scope.options.vehicle_primary_applications = responses.vehicle_primary_application_options.data.options;
                    $scope.options.vehicle_secondary_applications = responses.vehicle_secondary_application_options.data.options;
                    $scope.options.vehicle_operating_conditions = responses.vehicle_operating_condition_options.data.options;
                    $scope.options.road_conditions = responses.road_condition_options.data.options;
                    $scope.options.load_ranges = responses.load_range_options.data.options;
                    $scope.options.terrains = responses.terrain_options.data.options;

                    if ($scope.updating) {
                        $scope.warranty_job_order_request = responses.warranty_job_order_request_read.data.warranty_job_order_request;
                    } else {
                        $scope.warranty_job_order_request = {
                            repair_orders: [],
                            parts: [],
                            repair_order_total: 0,
                            part_total: 0,
                        }
                        //for quick test
                        $scope.warranty_job_order_request = {
                            repair_orders: [],
                            parts: [],
                            failure_date: '01-06-2020',
                            has_warranty: 1,
                            has_amc: 0,
                            unit_serial_number: 'UNIT0001',
                            service_types: [{
                                    id: 2
                                },
                                {
                                    id: 3
                                },
                            ],
                            // complaint: {
                            //     id: 1
                            // },
                            // fault: {
                            //     id: 2
                            // },
                            // supplier: {
                            //     id: 1
                            // },
                            primary_segment: {
                                id: 1
                            },
                            secondary_segment: {
                                id: 1
                            },
                            operating_condition: {
                                id: 9002,
                            },
                            normal_road_condition: {
                                id: 9020,
                            },
                            failure_road_condition: {
                                id: 9020,
                            },
                            load_range: {
                                id: 9062,
                            },
                            terrain_at_failure: {
                                id: 9081,
                            },
                            load_carried_type_id: 9041,
                            reading_type_id: 8041,
                            has_goodwill: 1,
                            load_at_failure: 100,
                            runs_per_day: 1000,
                            last_lube_changed: 800,
                            load_carried: 1200,
                            failed_at: 1200,
                            complaint_reported: 'Engine Noise',
                            failure_observed: 'Engine screw is missing',
                            investigation_findings: 'Engine screw is missing',
                            cause_of_failure: 'Engine screw is missing',
                            repair_order_total: 0,
                            part_total: 0,

                        };
                    }
                    if ($scope.updating) {
                        $scope.calculateLabourTotal('update');
                        $scope.calculatePartTotal('update');
                    }else{
                        $scope.calculateLabourTotal();
                        $scope.calculatePartTotal();
                    }

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
                        allowedFileTypes: ['image'],
                        slugCallback: function(filename) {
                            return filename.replace('(', '_').replace(']', '_');
                        }
                    });
                    if ($scope.warranty_job_order_request.attachments.length==0) {
                        $("#file-1").addClass("required");
                    }
                    $rootScope.loading = false;
                });
        };
        $scope.init();

        $scope.searchJobOrders = function(query) {
            return new Promise(function(resolve, reject) {
                JobOrderSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.searchCompaints = function(query) {
            return new Promise(function(resolve, reject) {
                ComplaintSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.searchFaults = function(query) {
            return new Promise(function(resolve, reject) {
                FaultSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.searchPartSuppliers = function(query) {
            return new Promise(function(resolve, reject) {
                PartSupplierSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }


        $scope.searchRepairOrders = function(query) {
            return new Promise(function(resolve, reject) {
                RepairOrderSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }
        $scope.searchParts = function(query) {
            return new Promise(function(resolve, reject) {
                PartSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }
        $scope.partSelected = function(part) {
            var quantity = 1; 
            if(part.pivot!=undefined){
                quantity = part.pivot.quantity;
            }
            part.quantity = quantity;
            $scope.calculatePartAmount(part);
        }
        $scope.repairOrderSelected = function(repair_order) {
            var net_amount = 0;
            if(repair_order.pivot!=undefined){
                net_amount = repair_order.pivot.net_amount;
                repair_order.total_amount = repair_order.pivot.total_amount;
            }
            if (repair_order.category_id == 9140) {
                //Miscellaneous
                repair_order.net_amount = net_amount;
            } else {
                repair_order.net_amount = repair_order.amount;
                $scope.calculateRepairOrderAmount(repair_order);
            }
        }

        $scope.claimAmountChange = function (repair_order) {
            if (parseFloat(repair_order.net_amount) > parseFloat(repair_order.maximum_claim_amount)) {
                custom_noty('error', 'Claim Amount should not exceed '+repair_order.maximum_claim_amount);
                return false;
            }else{
                $scope.calculateRepairOrderAmount(repair_order);
            }
        }
        
        $scope.partQuantityChange = function (part) {
            $scope.calculatePartAmount(part);
        }
        $scope.partAmountChange = function (part) {
            $scope.calculatePartAmount(part);
        }
        
        $scope.calculateRepairOrderAmount = function(repair_order) {
            var total_amount = 0;
            var tax_total = 0;
            var amount = repair_order.amount;
            if (repair_order.category_id == 9140) {
                amount = repair_order.net_amount;
            }
            if (repair_order.tax_code) {
                angular.forEach(repair_order.tax_code.taxes, function(tax) {
                    tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                })
            }
            repair_order.total_amount = parseFloat(amount) + tax_total;
        }
        $scope.calculatePartAmount = function(part){
            var total_amount = 0;
            var tax_total = 0;

            var amount = part.rate * part.quantity;

            if (part.tax_code) {
                angular.forEach(part.tax_code.taxes, function(tax) {
                    tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                })
            }
            part.total_amount = parseFloat(amount) + tax_total;
        }

        $scope.searchParts = function(query) {
            return new Promise(function(resolve, reject) {
                PartSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        var form_id1 = '#form';
        var v = jQuery(form_id1).validate({
            ignore: '',
            rules: {
                'job_order_id': {
                    required: true,
                },
                'failure_date': {
                    required: true,
                },
                'has_warranty': {
                    required: true,
                },
                'has_amc': {
                    required: true,
                },
                'unit_serial_number': {
                    required: true,
                    minlength: 2,
                    maxlength: 32,
                },
                'complaint_id': {
                    required: true,
                },
                'fault_id': {
                    required: true,
                },
                'supplier_id': {
                    required: true,
                },
                'primary_segment_id': {
                    required: true,
                },
                'secondary_segment_id': {
                    required: true,
                },
                'has_goodwill': {
                    required: true,
                },
                'operating_condition_id': {
                    required: true,
                },
                'normal_road_condition_id': {
                    required: true,
                },
                'failure_road_condition_id': {
                    required: true,
                },
                'load_carried_type_id': {
                    required: true,
                },
                'load_carried': {
                    required: true,
                    // digits: true,
                    minlength: 2,
                    maxlength: 10,
                },
                'load_range_id': {
                    required: true,
                    // digits: true,
                },
                'load_at_failure': {
                    required: true,
                    // digits: true,
                },
                'last_lube_changed': {
                    required: true,
                    // digits: true,
                    minlength: 2,
                    maxlength: 10,
                },
                'load_carried': {
                    required: true,
                    // digits: true,
                },
                'load_carried_type_id': {
                    required: true,
                },
                'terrain_at_failure_id': {
                    required: true,
                },
                'reading_type_id': {
                    required: true,
                },
                'runs_per_day': {
                    required: true,
                    // digits: true,
                    minlength: 2,
                    maxlength: 10,
                },
                'failed_at': {
                    required: true,
                    // digits: true,
                    minlength: 2,
                    maxlength: 10,
                },
                'complaint_reported': {
                    required: true,
                    minlength: 5,
                },
                'failure_observed': {
                    required: true,
                    minlength: 5,
                },
                'investigation_findings': {
                    required: true,
                    minlength: 5,
                },
                'cause_of_failure': {
                    required: true,
                    minlength: 5,
                },
                /*'photos[]': {
                    required: true,
                },*/
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id1)[0]);
                
                $.ajax({
                        url: base_url + '/api/warranty-job-order-request/save',
                        method: "POST",
                        data: formData,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            showErrorNoty(res);
                            return;
                        }
                        showNoty('success', 'Warranty job order request saved successfully');
                        $location.path('/warranty-job-order-request/card-list');
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        custom_noty('error', 'Something went wrong at server');
                    });
                // WarrantyJobOrderRequestSvc.save($scope.warranty_job_order_request)
                //     .then(function(response) {
                //         showNoty('success', 'Warranty job order request saved successfully');
                //             $location.path('/warranty-job-order-request/card-list');
                //     });
            }
        });


        $scope.showLabourForm = function(repair_order, index) {
            $scope.repair_order = repair_order;
            $scope.index = index;
            $scope.modal_action = !repair_order ? 'Add' : 'Edit';
            $('#labour_form_modal').modal('show');
        }

        $scope.showPartForm = function(part, index) {
            $scope.part = part;
            $scope.index = index;
            $scope.modal_action = !part ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
        }
        var form_id3 = '#part-form';
        var v = jQuery(form_id3).validate({
            ignore: '',
            rules: {
                'part_id': {
                    required: true,
                },
                'quantity': {
                    required: true,
                },
                'rate': {
                    required: true,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {
                // console.log($scope.modal_action);
                if ($scope.modal_action == 'Add') {
                    $scope.warranty_job_order_request.parts.push($scope.part);
                } else {
                    $scope.warranty_job_order_request.parts[$scope.index] = $scope.part;
                }
                // $scope.calculatePartNetAmount();
                $scope.calculatePartTotal();
                $scope.part = '';
                $('#part_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });

        var form_id2 = '#labour-form';
        var v = jQuery(form_id2).validate({
            ignore: '',
            rules: {
                'repair_order_id': {
                    required: true,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {
                // console.log($scope.modal_action);
                if ($scope.modal_action == 'Add') {
                    $scope.warranty_job_order_request.repair_orders.push($scope.repair_order);
                } else {
                    $scope.warranty_job_order_request.repair_orders[$scope.index] = $scope.repair_order;
                }
                // console.log($scope.warranty_job_order_request.repair_orders);
                // $scope.calculateLabourNetAmount();
                $scope.calculateLabourTotal();
                $scope.repair_order = '';
                $('#labour_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });

        $scope.calculateLabourTotal = function(update=null) {
            var total = 0;
            angular.forEach($scope.warranty_job_order_request.repair_orders, function(repair_order) {
                var amount = repair_order.amount;
                var tax_total = 0;
                if (update!=null) {
                    amount = repair_order.pivot.net_amount;
                    tax_total = repair_order.pivot.tax_total;
                }else{
                    console.log(repair_order);
                    if (repair_order.category_id == 9140) {
                        amount = repair_order.net_amount;
                    }
                    if (repair_order.tax_code) {
                        angular.forEach(repair_order.tax_code.taxes, function(tax) {
                            tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                        })
                    }
                }
                repair_order.net_amount = parseFloat(amount) + parseFloat(tax_total);
                
                repair_order.net_amount_without_tax = parseFloat(amount);
                repair_order.tax_amount = parseFloat(tax_total);
                repair_order.net_amount_with_tax = parseFloat(repair_order.net_amount);

                total += parseFloat(amount) + parseFloat(tax_total);
            });

            $scope.warranty_job_order_request.repair_order_total = total;
            $scope.calculateEstimateTotal()
        }

        $scope.calculatePartTotal = function(update=null) {
            console.log($scope.warranty_job_order_request.job_order.customer.state_id);
            console.log($scope.warranty_job_order_request.job_order.customer.pivot);
            var total = 0;
            angular.forEach($scope.warranty_job_order_request.parts, function(part) {
                if (update) {
                    $quantity = part.pivot.quantity;
                }else{
                    $quantity = part.quantity;
                }
                var amount = part.rate * $quantity;
                var tax_total = 0;
                if (part.tax_code) {
                    angular.forEach(part.tax_code.taxes, function(tax) {
                        tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                    })
                }
                if (update!=null) {
                    part.quantity = Math.trunc(part.pivot.quantity);
                }
                part.net_amount = parseFloat(amount) + tax_total;
                part.net_amount_without_tax = parseFloat(amount);
                part.tax_amount = parseFloat(tax_total);
                part.net_amount_with_tax = part.net_amount;

                total += parseFloat(amount) + tax_total;

            });
            $scope.warranty_job_order_request.part_total = total.toFixed(2);
            $scope.calculateEstimateTotal()
        }

        $scope.calculateEstimateTotal = function() {
            $scope.warranty_job_order_request.estimate_total = parseFloat($scope.warranty_job_order_request.repair_order_total) + parseFloat($scope.warranty_job_order_request.part_total);
        }

        $scope.removeRepairOrder = function(index,job_order_labour) {
            $scope.warranty_job_order_request.repair_orders.splice(index, 1);
            if (job_order_labour.pivot!=null) {
                $scope.calculateLabourTotal('update');
            }else{
                $scope.calculateLabourTotal();
            }
        }

        $scope.removePart = function(index) {
            $scope.warranty_job_order_request.parts.splice(index, 1);
            $scope.calculatePartTotal();
        }


    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------

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
            var total = 0;
            angular.forEach($scope.warranty_job_order_request.repair_orders, function(repair_order) {
                var amount = repair_order.amount;
                var tax_total = 0;
                
                amount = repair_order.pivot.net_amount;
                tax_total = repair_order.pivot.tax_total;
                
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
            var total = 0;

            angular.forEach($scope.warranty_job_order_request.parts, function(part) {
                var amount = part.rate;
                var tax_total = 0;
                if (part.tax_code) {
                    angular.forEach(part.tax_code.taxes, function(tax) {
                        tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                    })
                }
                part.net_amount = parseFloat(amount) + tax_total;
                part.net_amount_without_tax = parseFloat(amount);
                part.tax_amount = parseFloat(tax_total);
                part.net_amount_with_tax = part.net_amount;

                total += parseFloat(amount) + tax_total;

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

    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------