angular.module('app').requires.push('angularBootstrapFileinput');

app.component('warrantyJobOrderRequestForm', {
    templateUrl: warrantyJobOrderRequestForm,
    controller: function($http, $location, HelperService, RepairOrderSvc, PartSvc, WarrantyJobOrderRequestSvc, ServiceTypeSvc, ConfigSvc, PartSupplierSvc, VehicleSecondaryApplicationSvc, VehiclePrimaryApplicationSvc, ComplaintSvc, FaultSvc, JobOrderSvc, $scope, $routeParams, $rootScope, $element, $mdSelect, $q, RequestSvc, VehicleSvc, OutletSvc, CustomerSvc, ComplaintGroupSvc) {
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
        $scope.hasPerm = HelperService.hasPerm;
        self.hasPermission = HelperService.hasPermission;

        $scope.page = 'form';
        $scope.customer_search_type = true;
        $scope.vehicle_search_type = true;

        $scope.init = function() {
            $rootScope.loading = true;

            $scope.form_type = 'manual';
            $.ajax({
                    url: base_url + '/api/warranty-job-order-request/get-form-data',
                    method: "POST",
                    data: {},
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                })
                .done(function(res) {
                    $rootScope.loading = false;
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.extras = res.extras;

                    employee_outlets_arr = [];
                    angular.forEach(res.extras.employee_outlets, function(value, key) {
                        employee_outlets_arr.push(value.id);
                    });
                    $scope.employee_outlets = employee_outlets_arr;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
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
                        $scope.customer = $scope.warranty_job_order_request.job_order.customer;
                        $scope.customerChanged($scope.customer);
                        self.country = $scope.warranty_job_order_request.job_order.vehicle.current_owner.customer.address.country;
                        $scope.countryChanged();
                        $scope.calculateTotals();
                        $scope.soldDateChange($scope.warranty_job_order_request.job_order.vehicle.sold_date);
                    } else {
                        self.is_registered = 1;
                        $scope.warranty_job_order_request = {
                            wjor_repair_orders: [],
                            wjor_parts: [],
                            has_warranty: 1,
                            has_amc: 0,
                            repair_order_total: 0,
                            part_total: 0,
                            attachments: [],
                            job_order: {
                                vehicle: {},
                                customer: {},
                                outlet: {},
                            },
                            photos: [],
                            attachments: [],
                            bharat_stages: [],
                        }

                        //for quick test
                        // $scope.warranty_job_order_request = {
                        //     wjor_repair_orders: [],
                        //     wjor_parts: [],
                        //     failure_date: '01-06-2020',
                        //     has_warranty: 1,
                        //     has_amc: 0,
                        //     unit_serial_number: 'UNIT0001',
                        //     service_types: [],
                        //     // complaint: {
                        //     //     id: 1
                        //     // },
                        //     // fault: {
                        //     //     id: 2
                        //     // },
                        //     // supplier: {
                        //     //     id: 1
                        //     // },
                        //     primary_segment: {
                        //         id: 1
                        //     },
                        //     secondary_segment: {
                        //         id: 1
                        //     },
                        //     operating_condition: {
                        //         id: 9002,
                        //     },
                        //     normal_road_condition: {
                        //         id: 9020,
                        //     },
                        //     failure_road_condition: {
                        //         id: 9020,
                        //     },
                        //     load_range: {
                        //         id: 9062,
                        //     },
                        //     terrain_at_failure: {
                        //         id: 9081,
                        //     },
                        //     load_carried_type_id: 9041,
                        //     reading_type_id: 8041,
                        //     has_goodwill: 1,
                        //     load_at_failure: 100,
                        //     runs_per_day: 1000,
                        //     last_lube_changed: 800,
                        //     load_carried: 1200,
                        //     failed_at: 1200,
                        //     complaint_reported: 'Engine Noise',
                        //     failure_observed: 'Engine screw is missing',
                        //     investigation_findings: 'Engine screw is missing',
                        //     cause_of_failure: 'Engine screw is missing',
                        //     repair_order_total: 0,
                        //     part_total: 0,
                        //     attachments: [],
                        //     job_order: {
                        //         vehicle: [],
                        //         customer: [],
                        //         // outlet: {},
                        //     },
                        //     photos: [],
                        // };
                        $scope.warranty_job_order_request.job_order.vehicle.is_sold = true;
                        if (self.hasPermission('own-outlet-warranty-job-order-request')) {
                            $scope.warranty_job_order_request.job_order.outlet = $scope.user.employee.outlet;
                        }
                    }
                    $scope.customer = $scope.warranty_job_order_request.job_order.customer;

                    $scope.bfiConfig = {
                        theme: 'fas',
                        overwriteInitial: true,
                        // minFileCount: 1,
                        maxFileSize: 2048,
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

                    if ($scope.warranty_job_order_request.photos.length == 0) {
                        $("#file-1").addClass("required");
                    }
                    $rootScope.loading = false;
                });
        };
        $scope.init();

        setTimeout(function() {
            $('div[data-provide="datepicker"]').bootstrapDP({
                format: "dd-mm-yyyy",
                autoclose: "true",
                endDate: new Date()
            });
        }, 5000);

        $scope.soldDateChange = function(sold_date) {
            // $sold_date = $scope.warranty_job_order_request.job_order.vehicle.sold_date;

            $sold_date = new Date(sold_date.replace(/(\d{2})-(\d{2})-(\d{4})/, "$2/$1/$3"))
            $bs6_date = new Date("01-04-2020".replace(/(\d{2})-(\d{2})-(\d{4})/, "$2/$1/$3"))
            $bs3_date = new Date("01-04-2017".replace(/(\d{2})-(\d{2})-(\d{4})/, "$2/$1/$3"))
            // console.log($sold_date, $bs6_date);

            if ($sold_date > $bs6_date) { // BS6
                $scope.warranty_job_order_request.bharat_stages = $scope.extras.bharat_stages[2];
            } else if ($sold_date < $bs3_date) { // BS3
                $scope.warranty_job_order_request.bharat_stages = $scope.extras.bharat_stages[0];
            } else {
                $scope.warranty_job_order_request.bharat_stages = $scope.extras.bharat_stages[1];
            }
        }

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

        $scope.vehicleSelected = function(vehicle) {
            // console.log(vehicle);
            if (vehicle.vehicle_owners) {
                $scope.warranty_job_order_request.job_order.customer = vehicle.vehicle_owners[0].customer;
            }
        }

        $scope.searchCustomer = function(query) {
            return new Promise(function(resolve, reject) {
                CustomerSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.searchOutlet = function(query) {
            var params = {};
            if (self.hasPermission('own-outlet-warranty-job-order-request')) {
                params = {
                    filter: {
                        search: query,
                        IdIn: [$scope.user.employee.outlet_id]
                    },
                };
            } else if (self.hasPermission('mapped-outlets-warranty-job-order-request')) {
                console.log($scope.employee_outlets);
                params = {
                    filter: {
                        search: query,
                        IdIn: $scope.employee_outlets
                    },
                };
            } else {
                params = {
                    filter: {
                        search: query,
                    },
                };
            }
            return new Promise(function(resolve, reject) {
                OutletSvc.options(params)
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.searchCompaintGroup = function(query) {
            return new Promise(function(resolve, reject) {
                ComplaintGroupSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.searchCompaints = function(query) {
            return new Promise(function(resolve, reject) {
                ComplaintSvc.options({ filter: { search: query, complaintGroup: $scope.warranty_job_order_request.complaint_group.id } })
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


        $scope.isSameState = function() {
            if ($scope.warranty_job_order_request.job_order && $scope.warranty_job_order_request.job_order.customer && $scope.warranty_job_order_request.job_order.outlet) {
                var customer_state = $scope.warranty_job_order_request.job_order.customer.state_id;
                var job_order_state = $scope.warranty_job_order_request.job_order.outlet.state_id;
                return customer_state == job_order_state;
            }
            return false;
        }

        // Labours   ---------------------------------------------

        $scope.showLabourForm = function(index) {

            if (index !== false) {
                $scope.wjor_repair_order = $scope.warranty_job_order_request.wjor_repair_orders[index];
                HelperService.calculateTaxAndTotal($scope.wjor_repair_order, $scope.isSameState());
            } else {
                $scope.wjor_repair_order = {}
            }

            document.querySelector('#repairOrderAutoCompleteId').focus();
            $scope.index = index;
            $scope.modal_action = index === false ? 'Add' : 'Edit';
            $('#labour_form_modal').modal('show');
        }


        $scope.repairOrderSelected = function(repair_order) {
            if (!repair_order) {
                return;
            }

            $scope.wjor_repair_order.qty = 1;
            $scope.wjor_repair_order.rate = repair_order.claim_amount;
            $scope.wjor_repair_order.tax_code = repair_order.tax_code;
            HelperService.calculateTaxAndTotal($scope.wjor_repair_order, $scope.isSameState());

            console.log($scope.wjor_repair_order);
        }

        $scope.claimAmountChange = function(wjor_repair_order) {
            if (parseFloat(wjor_repair_order.rate) > parseFloat(wjor_repair_order.repair_order.maximum_claim_amount)) {
                custom_noty('error', 'Claim Amount should not exceed ' + wjor_repair_order.repair_order.maximum_claim_amount);
                return false;
            } else {
                HelperService.calculateTaxAndTotal(wjor_repair_order, $scope.isSameState());
            }
        }

        $scope.removeRepairOrder = function(index) {
            $scope.warranty_job_order_request.wjor_repair_orders.splice(index, 1);
            $scope.calculateTotals();
        }

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
                if ($scope.modal_action == 'Add') {
                    $scope.warranty_job_order_request.wjor_repair_orders.push($scope.wjor_repair_order);
                } else {
                    $scope.warranty_job_order_request.wjor_repair_orders[$scope.index] = $scope.wjor_repair_order;
                }
                $scope.calculateTotals();
                $('#labour_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });

        // Part related -------------------------------------

        $scope.showPartForm = function(index) {
            if (index === false) {
                $scope.wjor_part = {};
            } else {
                $scope.wjor_part = $scope.warranty_job_order_request.wjor_parts[index];
                $scope.calculatePartAmount();
            }

            document.querySelector('#partAutoCompleteId').focus();
            $scope.index = index;
            $scope.modal_action = index === false ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
        }

        $scope.partSelected = function(part) {
            if (!part) {
                return;
            }
            PartSvc.read(part.id)
                .then(function(response) {
                    $scope.wjor_part.qty = 1;
                    $scope.wjor_part.rate = part.mrp;
                    $scope.wjor_part.part.tax_code = response.data.part.tax_code;
                    $scope.wjor_part.tax_code = response.data.part.tax_code;
                    $scope.wjor_part.purchase_type = 8480;
                    $scope.calculatePartAmount();
                });

        }

        $scope.calculatePartAmount = function() {
            HelperService.calculateTaxAndTotal($scope.wjor_part, $scope.isSameState());
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

                console.log($scope.wjor_part);
                if ($scope.modal_action == 'Add') {
                    $scope.warranty_job_order_request.wjor_parts.push($scope.wjor_part);
                } else {
                    $scope.warranty_job_order_request.wjor_parts[$scope.index] = $scope.wjor_part;
                }
                $scope.calculateTotals();
                $('#part_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });

        $scope.removePart = function(index) {
            $scope.warranty_job_order_request.wjor_parts.splice(index, 1);
            $scope.calculateTotals();
        }

        // Common -------------------------------------

        $scope.calculateTotals = function() {
            $scope.warranty_job_order_request.repair_order_total = HelperService.calculateTotal($scope.warranty_job_order_request.wjor_repair_orders);
            $scope.warranty_job_order_request.part_total = HelperService.calculateTotal($scope.warranty_job_order_request.wjor_parts);
            $scope.warranty_job_order_request.estimate_total = $scope.warranty_job_order_request.repair_order_total + $scope.warranty_job_order_request.part_total;
        }

        // Main Form Submit -------------------------------------

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
        self.searchCity = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getCitySearchList'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }
        $scope.countryChanged = function() {
            $.ajax({
                    url: base_url + '/api/state/get-drop-down-List',
                    method: "POST",
                    data: {
                        country_id: $scope.warranty_job_order_request.job_order.vehicle.current_owner.customer.address.country.id,
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.extras.state_list = res.state_list;

                    //ADD NEW OWNER TYPE
                    /*if ($scope.type_id == 2) {
                        self.state = $scope.job_order.state;
                    } else {
                        if (!$scope.job_order.vehicle.current_owner) {
                            self.state = $scope.job_order.state;
                        } else {
                            self.state = $scope.job_order.vehicle.current_owner.customer.address.state;
                        }
                    }*/

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $(document).on('keyup', ".registration_number", function() {
            if ($(this).val().length == 2) {
                $('.registration_number').val($(this).val() + '-');
            }
            if ($(this).val().length == 5) {
                $('.registration_number').val($(this).val() + '-');
            }
            if ($(this).val().length == 8) {
                var regis_num = $(this).val().substr(7, 1);
                if ($.isNumeric(regis_num)) {
                    //Check Previous Character Number or String
                    var previous_char = $(this).val().substr(6, 1);
                    if (!$.isNumeric(previous_char)) {
                        var regis_number = $(this).val().slice(0, -1);
                        $('.registration_number').val(regis_number + '-' + regis_num);
                    }
                } else {
                    $('.registration_number').val($(this).val() + '-');
                }
            }
        });
    }
});