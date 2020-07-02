angular.module('app').requires.push('angularBootstrapFileinput');

app.component('warrantyJobOrderRequestForm', {
    templateUrl: warrantyJobOrderRequestForm,
    controller: function($http, $location, HelperService, RepairOrderSvc, PartSvc, WarrantyJobOrderRequestSvc, ServiceTypeSvc, ConfigSvc, PartSupplierSvc, VehicleSecondaryApplicationSvc, VehiclePrimaryApplicationSvc, ComplaintSvc, FaultSvc, JobOrderSvc, $scope, $routeParams, $rootScope, $element, $mdSelect, $q, RequestSvc, VehicleSvc, OutletSvc, CustomerSvc) {
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

            $scope.form_type = 'manual';

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
                            attachments: [],
                            job_order: {
                                vehicle: {},
                                customer: {},
                                outlet: {},
                            },
                            photos: [],
                        }

                        //for quick test
                        $scope.warranty_job_order_request = {
                            repair_orders: [],
                            parts: [],
                            failure_date: '01-06-2020',
                            has_warranty: 1,
                            has_amc: 0,
                            unit_serial_number: 'UNIT0001',
                            service_types: [],
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
                            attachments: [],
                            job_order: {
                                vehicle: {},
                                customer: {},
                                outlet: {},
                            },
                            photos: [],
                        };
                    }
                    $scope.customer = $scope.warranty_job_order_request.job_order.customer;

                    if ($scope.updating) {
                        $scope.calculateLabourTotal('update');
                        $scope.calculatePartTotal('update');
                    } else {
                        $scope.calculateLabourTotal();
                        $scope.calculatePartTotal();
                    }

                    $scope.bfiConfig = {
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
                        showCaption: false,
                        showCancel: false,
                        showBrowse: false,
                        showRemove: false,
                        // maxFilesNum: 10,
                        // initialPreview: [
                        //     "<img src='/images/desert.jpg' class='file-preview-image' alt='Desert' title='Desert'>",
                        //     "<img src='/images/jellyfish.jpg' class='file-preview-image' alt='Jelly Fish' title='Jelly Fish'>",
                        // ],
                        allowedFileTypes: ['image'],
                        slugCallback: function(filename) {
                            return filename.replace('(', '_').replace(']', '_');
                        }
                    };

                    // $("#file-1").fileinput(config);
                    // $("#file-2").fileinput(config);

                    if ($scope.warranty_job_order_request.attachments.length == 0) {
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

        $scope.searchVehicles = function(query) {
            return new Promise(function(resolve, reject) {
                VehicleSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.searchCustomer = function(query) {
            return new Promise(function(resolve, reject) {
                CustomerSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        // Methods   ---------------------------------------------

        $scope.searchOutlet = function(query) {
            return new Promise(function(resolve, reject) {
                OutletSvc.options({ filter: { search: query } })
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

        $scope.isSameState = function() {
            $same_state = false;
            if ($scope.warranty_job_order_request.job_order != undefined) {
                var customer_state = $scope.warranty_job_order_request.job_order.customer.state_id;
                var job_order_state = $scope.warranty_job_order_request.job_order.outlet.state_id;
                if (customer_state == job_order_state) {
                    $same_state = true;
                } else {
                    $same_state = false;
                }
            }
            // self.same_state = $same_state;
            return $same_state;
        }

        $scope.calculateRepairOrderAmount = function(repair_order) {
            $same_state = $scope.isSameState();

            var total_amount = 0;
            var tax_total = 0;
            var amount = repair_order.amount;
            if (repair_order.category_id == 9140) {
                amount = repair_order.net_amount;
            }
            if (repair_order.tax_code) {
                angular.forEach(repair_order.tax_code.taxes, function(tax) {
                    if ($same_state == true) {
                        if (tax.type_id == 1160) {
                            tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                        } else {
                            tax.pivot.percentage = 0;
                            tax_total += 0;
                        }
                    } else {
                        if (tax.type_id != 1160) {
                            tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                        } else {
                            tax.pivot.percentage = 0;
                            tax_total += 0;
                        }
                    }
                    // tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                })
            }
            repair_order.total_amount = parseFloat(amount) + tax_total;
        }

        $scope.calculatePartAmount = function(part) {
            $same_state = $scope.isSameState();

            var total_amount = 0;
            var tax_total = 0;

            var amount = part.rate * part.quantity;

            if (part.tax_code) {
                angular.forEach(part.tax_code.taxes, function(tax) {
                    if ($same_state == true) {
                        if (tax.type_id == 1160) {
                            tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                        } else {
                            tax.pivot.percentage = 0;
                            tax_total += 0;
                        }
                    } else {
                        if (tax.type_id != 1160) {
                            tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                        } else {
                            tax.pivot.percentage = 0;
                            tax_total += 0;
                        }
                    }
                    // tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                })
            }
            part.total_amount = parseFloat(amount) + tax_total;
        }

        // Handlers   ---------------------------------------------
        $scope.addPhoto = function() {
            $scope.warranty_job_order_request.photos.push($scope.warranty_job_order_request.photos.length + 1);
        }

        $scope.outletChanged = function(outlet) {
            if (!outlet) {
                return;
            }
            OutletSvc.getBusiness({ outletId: outlet.id, businessName: 'ALSERV' })
                .then(function(response) {
                    $scope.warranty_job_order_request.job_order.outlet.business = response.data.business;
                });
        }

        $scope.customerChanged = function(customer) {
            CustomerSvc.read(customer.id)
                .then(function(response) {
                    $scope.warranty_job_order_request.job_order.customer = response.data.customer;
                });
        }

        $scope.partSelected = function(part) {
            var quantity = 1;
            /*if (part.pivot != undefined) {
                quantity = part.pivot.quantity;
            }*/
            part.quantity = quantity;
            part.purchase_type = 8480;
            $scope.calculatePartAmount(part);
        }
        $scope.repairOrderSelected = function(repair_order) {
            var net_amount = 0;
            if (repair_order.pivot != undefined) {
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

        $scope.claimAmountChange = function(repair_order) {
            if (parseFloat(repair_order.net_amount) > parseFloat(repair_order.maximum_claim_amount)) {
                custom_noty('error', 'Claim Amount should not exceed ' + repair_order.maximum_claim_amount);
                return false;
            } else {
                $scope.calculateRepairOrderAmount(repair_order);
            }
        }

        $scope.partQuantityChange = function(part) {
            $scope.calculatePartAmount(part);
        }
        $scope.partAmountChange = function(part) {
            $scope.calculatePartAmount(part);
        }


        var form_id1 = '#form';
        var v = jQuery(form_id1).validate({
            ignore: '',
            rules: {
                // 'job_order_id': {
                //     required: true,
                // },
                'job_card_number': {
                    required: true,
                },
                'vehicle_id': {
                    required: true,
                },
                'is_sold': {
                    required: true,
                },
                'sold_date': {
                    required: function() {
                        return $scope.warranty_job_order_request.job_order.vehicle.is_sold;
                    },
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
                        $location.path('/warranty-job-order-request/table-list');
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

        $scope.calculateLabourTotal = function(update = null) {
            $same_state = $scope.isSameState();

            var total = 0;
            angular.forEach($scope.warranty_job_order_request.repair_orders, function(repair_order) {
                var amount = repair_order.amount;
                var tax_total = 0;
                if (update != null) {
                    amount = repair_order.pivot.net_amount;
                    tax_total = repair_order.pivot.tax_total;
                    if (repair_order.tax_code) {
                        angular.forEach(repair_order.tax_code.taxes, function(tax) {
                            if ($same_state == true) {
                                if (tax.type_id != 1160) {
                                    tax.pivot.percentage = 0;
                                }
                            } else {
                                if (tax.type_id == 1160) {
                                    tax.pivot.percentage = 0;
                                }
                            }
                        })
                    }
                } else {
                    // console.log(repair_order);
                    if (repair_order.category_id == 9140) {
                        amount = repair_order.net_amount;
                    }
                    if (repair_order.tax_code) {
                        angular.forEach(repair_order.tax_code.taxes, function(tax) {
                            if ($same_state == true) {
                                if (tax.type_id == 1160) {
                                    tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                                } else {
                                    tax.pivot.percentage = 0;
                                    tax_total += 0;
                                }
                            } else {
                                if (tax.type_id != 1160) {
                                    tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                                } else {
                                    tax.pivot.percentage = 0;
                                    tax_total += 0;
                                }
                            }
                            // tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
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


        $scope.calculatePartTotal = function(update = null) {

            $same_state = $scope.isSameState();

            var total = 0;
            angular.forEach($scope.warranty_job_order_request.parts, function(part) {
                if (update) {
                    $quantity = part.pivot.quantity;
                    $purchase_type = part.pivot.purchase_type;
                } else {
                    $quantity = part.quantity;
                    $purchase_type = part.purchase_type;
                }
                var amount = part.rate * $quantity;
                var tax_total = 0;
                if (part.tax_code) {
                    angular.forEach(part.tax_code.taxes, function(tax) {
                        if ($same_state == true) {
                            if (tax.type_id == 1160) {
                                tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                            } else {
                                tax.pivot.percentage = 0;
                                tax_total += 0;
                            }
                        } else {
                            if (tax.type_id != 1160) {
                                tax_total += parseFloat(amount) * parseFloat(tax.pivot.percentage) / 100;
                            } else {
                                tax.pivot.percentage = 0;
                                tax_total += 0;
                            }
                        }
                    })
                }
                if (update != null) {
                    part.quantity = Math.trunc(part.pivot.quantity);
                }
                part.net_amount = parseFloat(amount) + tax_total;
                part.net_amount_without_tax = parseFloat(amount);
                part.tax_amount = parseFloat(tax_total);
                part.net_amount_with_tax = part.net_amount;
                part.purchase_type = $purchase_type;

                total += parseFloat(amount) + tax_total;

            });
            $scope.warranty_job_order_request.part_total = total.toFixed(2);
            $scope.calculateEstimateTotal()
        }

        $scope.calculateEstimateTotal = function() {
            $scope.warranty_job_order_request.estimate_total = parseFloat($scope.warranty_job_order_request.repair_order_total) + parseFloat($scope.warranty_job_order_request.part_total);
        }

        $scope.removeRepairOrder = function(index, job_order_labour) {
            $scope.warranty_job_order_request.repair_orders.splice(index, 1);
            if (job_order_labour.pivot != null) {
                $scope.calculateLabourTotal('update');
            } else {
                $scope.calculateLabourTotal();
            }
        }

        $scope.removePart = function(index) {
            $scope.warranty_job_order_request.parts.splice(index, 1);
            $scope.calculatePartTotal();
        }


    }
});