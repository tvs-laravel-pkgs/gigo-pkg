angular.module('app').requires.push('angularBootstrapFileinput');

app.component('warrantyJobOrderRequestForm', {
    templateUrl: warrantyJobOrderRequestForm,
    controller: function($http, $location, HelperService, RepairOrderSvc, PartSvc, WarrantyJobOrderRequestSvc, ServiceTypeSvc, ConfigSvc, PartSupplierSvc, VehicleSecondaryApplicationSvc, VehiclePrimaryApplicationSvc, ComplaintSvc, FaultSvc, JobOrderSvc, $scope, $routeParams, $rootScope, $element, $mdSelect, $q, RequestSvc, VehicleSvc, OutletSvc, CustomerSvc, ComplaintGroupSvc, $localstorage, $ngBootbox) {
        //FormFocus
        formFocus();
        // $localstorage.remove('ppr');
        $scope.remarksForNotChangingLube = true;
        $rootScope.loading = true;
        var pageLoaded = 0;
        $('#search').focus();
        var self = this;
        $scope.business_data = [];
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        self.is_temp_saved = true;
        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }
        $scope.$on('$locationChangeStart', function(event, next, current) {

            if (self.is_temp_saved == false) {
                event.preventDefault();
                $ngBootbox.confirm({
                        message: 'Are you sure you want to leave page without saving?',
                        title: 'Confirm',
                        size: "small",
                        className: 'text-center',
                    })
                    .then(function() {
                        console.log(next.replace(base_url + '/#!/', ""));
                        $target = next.replace(base_url + '/#!/', "");
                        $location.path($target);
                        self.is_temp_saved = true;
                    });
            }
        });

        $scope.saveTempData = function() {
            $scope.warranty_job_order_request.investigation_findings = $("#investigationFindings").val();
            $localstorage.setObject('ppr', $scope.warranty_job_order_request);
            self.is_temp_saved = true;
            showNoty('success', 'Draft Saved');
            $location.path('/warranty-job-order-request/table-list');

            /*$rootScope.loading = true;
            var form_id1 = '#form';
            let formData = new FormData($(form_id1)[0]);

            // $scope.form_type = 'manual';
            $.ajax({
                    url: base_url + '/api/warranty-job-order-request/save-temp-data',
                    method: "POST",
                    data: formData,
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                    processData: false,
                    contentType: false,
                })
                .done(function(res) {
                    console.log(res);
                    $rootScope.loading = false;
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    showNoty('success', res.message);
                    $location.path('/warranty-job-order-request/table-list');
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });*/
        };

        $scope.user = HelperService.getLoggedUser();
        $scope.hasPerm = HelperService.hasPerm;
        self.hasPermission = HelperService.hasPermission;

        $scope.page = 'form';
        // $scope.customer_search_type = true;
        // $scope.vehicle_search_type = true;

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

            var serviceTypeParams = {
                sorting: {
                    display_order: 'asc'
                },
                filter: {
                    isFree: 1,
                },
            };
            let promises = {
                service_type_options: ServiceTypeSvc.options(serviceTypeParams),
                vehicle_primary_application_options: VehiclePrimaryApplicationSvc.options(),
                vehicle_secondary_application_options: VehicleSecondaryApplicationSvc.options(),
                vehicle_operating_condition_options: ConfigSvc.options({ filter: { configType: 300 } }),
                road_condition_options: ConfigSvc.options({ filter: { configType: 301 } }),
                load_range_options: ConfigSvc.options({ filter: { configType: 303 } }),
                terrain_options: ConfigSvc.options({ filter: { configType: 304 } }),
                // temp_data: WarrantyJobOrderRequestSvc.getTempData(),
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
                    // $scope.temp_data = responses.temp_data.data.request;
                    // console.log($scope.temp_data, 'temp_data');
                    // console.log($scope.updating, 'updating');
                    /*if ($scope.temp_data != null && typeof($routeParams.request_id) == 'undefined') {
                        $scope.warranty_job_order_request = $scope.temp_data;
                        console.log($scope.warranty_job_order_request);
                        if ($scope.warranty_job_order_request.job_order.customer) {
                            $scope.customer = $scope.warranty_job_order_request.job_order.customer;
                            $scope.customerChanged($scope.customer);
                        }
                        if ($scope.warranty_job_order_request.job_order.vehicle) {
                            self.country = $scope.warranty_job_order_request.job_order.vehicle.current_owner.customer.address.country;
                            self.state = $scope.warranty_job_order_request.job_order.vehicle.current_owner.customer.address.state;
                            $scope.countryChanged(true);
                        }
                        if ($scope.warranty_job_order_request.complaint) {
                            $scope.aggregateChange($scope.warranty_job_order_request.complaint.sub_aggregate.aggregate);
                            $scope.warranty_job_order_request.aggregate = $scope.warranty_job_order_request.complaint.sub_aggregate.aggregate;
                            $scope.warranty_job_order_request.sub_aggregate = $scope.warranty_job_order_request.complaint.sub_aggregate;
                        }
                        setTimeout(function() {
                            $scope.calculateCushionCharges();
                        }, 2000);
                        $scope.calculateTotals();
                        self.requestTypeOnload = $scope.warranty_job_order_request.request_type_id;
                        $scope.warranty_job_order_request.photos1 = [];
                    } else {*/
                    if ($scope.updating) {
                        $scope.warranty_job_order_request = responses.warranty_job_order_request_read.data.warranty_job_order_request;
                        $scope.customer = $scope.warranty_job_order_request.job_order.customer;
                        $scope.customerChanged($scope.customer);
                        self.country = $scope.warranty_job_order_request.job_order.vehicle.current_owner.customer.address.country;
                        self.state = $scope.warranty_job_order_request.job_order.vehicle.current_owner.customer.address.state;
                        // $scope.soldDateChange($scope.warranty_job_order_request.job_order.vehicle.sold_date);
                        $scope.aggregateChange($scope.warranty_job_order_request.complaint.sub_aggregate.aggregate, true);
                        $scope.warranty_job_order_request.aggregate = $scope.warranty_job_order_request.complaint.sub_aggregate.aggregate;
                        $scope.warranty_job_order_request.sub_aggregate = $scope.warranty_job_order_request.complaint.sub_aggregate;
                        $scope.warranty_job_order_request.customer_search_type = true;
                        $scope.warranty_job_order_request.vehicle_search_type = true;

                        setTimeout(function() {
                            $scope.countryChanged(true);
                            $scope.calculateCushionCharges();
                            if ($scope.warranty_job_order_request.failure_type) {
                                $scope.warranty_job_order_request.is_analysis_report_required = true;
                            } else {
                                $scope.warranty_job_order_request.is_analysis_report_required = true;
                            }
                            $lubeChange = $scope.warranty_job_order_request.last_lube_changed;
                            if ($lubeChange == undefined || $lubeChange == null) {
                                $scope.remarksForNotChangingLube = false;
                            } else {
                                $scope.remarksForNotChangingLube = true;
                            }
                        }, 2000);
                        $scope.calculateTotals();
                        self.requestTypeOnload = $scope.warranty_job_order_request.request_type_id;
                    } else {
                        self.is_registered = 1;
                        self.is_temp_saved = false;
                        $scope.warranty_job_order_request = $localstorage.getObject('ppr');
                        console.log($scope.warranty_job_order_request);
                        if (!$scope.warranty_job_order_request) {
                            $scope.warranty_job_order_request = {
                                wjor_repair_orders: [],
                                wjor_parts: [],
                                has_warranty: 1,
                                has_amc: 0,
                                load_carried_type_id: 9041,
                                reading_type_id: 8041,
                                has_goodwill: 1,
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
                                is_analysis_report_required: false
                            };


                        }
                        if ($scope.warranty_job_order_request.is_analysis_report_required == undefined) {
                            $scope.warranty_job_order_request.is_analysis_report_required = false;
                        }
                        setTimeout(function() {
                            if ($scope.warranty_job_order_request.aggregate != null) {
                                $scope.aggregateChange($scope.warranty_job_order_request.aggregate, true);
                            }
                            $scope.countryChanged(true);
                        }, 2000);

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
                        if ($scope.warranty_job_order_request.job_order.vehicle) {
                            $scope.warranty_job_order_request.job_order.vehicle.is_sold = true;
                        }
                        if (!$scope.warranty_job_order_request.total_part_cushioning_percentage) {
                            $scope.warranty_job_order_request.total_part_cushioning_percentage = 0;
                        }
                        if (self.hasPermission('own-outlet-warranty-job-order-request')) {
                            $scope.warranty_job_order_request.job_order.outlet = $scope.user.employee.outlet;
                        }
                        if ($scope.warranty_job_order_request.customer_search_type == undefined) {
                            $scope.warranty_job_order_request.customer_search_type = true;
                        }
                        if ($scope.warranty_job_order_request.vehicle_search_type == undefined) {
                            $scope.warranty_job_order_request.vehicle_search_type = true;
                        }
                        // console.log($scope.warranty_job_order_request.customer_search_type, $scope.warranty_job_order_request.vehicle_search_type);
                        if ($scope.warranty_job_order_request.request_type_id == undefined) {
                            $scope.warranty_job_order_request.request_type_id = 9180;
                        }
                        $lubeChange = $scope.warranty_job_order_request.last_lube_changed;
                        if ($lubeChange == undefined) {
                            $scope.remarksForNotChangingLube = false;
                        } else {
                            $scope.remarksForNotChangingLube = true;
                        }
                    }
                    /*}*/
                    $scope.customer = $scope.warranty_job_order_request.job_order.customer;

                    $scope.bfiConfig = {
                        theme: 'fas',
                        overwriteInitial: true,
                        // minFileCount: 1,
                        maxFileSize: 5120, //2048,
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
                        // allowedFileTypes: ['image', 'video'],
                        slugCallback: function(filename) {
                            return filename.replace('(', '_').replace(']', '_');
                        }
                    };

                    $scope.bfiConfig1 = {
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
                        // allowedFileTypes: ['image', 'pdf'],
                        slugCallback: function(filename) {
                            return filename.replace('(', '_').replace(']', '_');
                        }
                    };

                    // $("#file-1").fileinput(config);
                    // $("#file-2").fileinput(config);

                    /*
                    if ($scope.warranty_job_order_request.photos) {
                        if ($scope.warranty_job_order_request.photos.length == 0) {
                            $("#file-1").addClass("required");
                        }
                    }
                    */
                    $scope.warranty_job_order_request.photos1 = [];
                    $rootScope.loading = false;
                });
        };
        $scope.init();

        setTimeout(function() {

            var toolbarOptions = [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'], // toggled buttons
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'align': [] }],
                [{ 'color': [] }, { 'background': [] }], // dropdown with defaults from theme
            ];

            /*var cause_of_failure_quill = new Quill('#editor2', {
                modules: {
                    toolbar: toolbarOptions
                },
                theme: 'snow',
            });
            cause_of_failure_quill.on('text-change', function() {
                if (pageLoaded == 1) {
                    // $data = cause_of_failure_quill.getContents();
                    $data = cause_of_failure_quill.root.innerHTML;
                    $("#causeOfFailure").val($data);
                }
            });*/
            var investigation_findings_quill = new Quill('#editor1', {
                modules: {
                    toolbar: toolbarOptions
                },
                theme: 'snow',
            });
            investigation_findings_quill.on('text-change', function() {
                if (pageLoaded == 1) {
                    $data = investigation_findings_quill.root.innerHTML; //investigation_findings_quill.getContents();
                    console.log(investigation_findings_quill.root.innerHTML);
                    $("#investigationFindings").val($data);
                }
            });
            setTimeout(function() {

                if ($scope.warranty_job_order_request.investigation_findings != undefined) {
                    var delta = investigation_findings_quill.clipboard.convert($scope.warranty_job_order_request.investigation_findings);
                    investigation_findings_quill.setContents(delta, 'silent');

                    // investigation_findings_quill.setContents([
                    //     { insert: $scope.warranty_job_order_request.investigation_findings }
                    // ]);
                }
                /*if ($scope.warranty_job_order_request.cause_of_failure != undefined) {
                    var delta = cause_of_failure_quill.clipboard.convert($scope.warranty_job_order_request.cause_of_failure);
                    cause_of_failure_quill.setContents(delta, 'silent');
                }*/
            }, 3000);

            var cause_of_failure_quill = new Quill('#editor2', {
                modules: {
                    toolbar: toolbarOptions
                },
                theme: 'snow',
            });
            cause_of_failure_quill.on('text-change', function() {
                if (pageLoaded == 1) {
                    $data = cause_of_failure_quill.root.innerHTML; //cause_of_failure_quill.getContents();
                    console.log(cause_of_failure_quill.root.innerHTML);
                    $("#causeOfFailure").val($data);
                }
            });
            setTimeout(function() {

                if ($scope.warranty_job_order_request.cause_of_failure != undefined) {
                    var delta = cause_of_failure_quill.clipboard.convert($scope.warranty_job_order_request.cause_of_failure);
                    cause_of_failure_quill.setContents(delta, 'silent');

                    // cause_of_failure_quill.setContents([
                    //     { insert: $scope.warranty_job_order_request.cause_of_failure }
                    // ]);
                }
                /*if ($scope.warranty_job_order_request.cause_of_failure != undefined) {
                    var delta = cause_of_failure_quill.clipboard.convert($scope.warranty_job_order_request.cause_of_failure);
                    cause_of_failure_quill.setContents(delta, 'silent');
                }*/
            }, 3000);

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
                $scope.warranty_job_order_request.job_order.vehicle.bharat_stage = $scope.extras.bharat_stages[2];
            } else if ($sold_date < $bs3_date) { // BS3
                $scope.warranty_job_order_request.job_order.vehicle.bharat_stage = $scope.extras.bharat_stages[0];
            } else {
                $scope.warranty_job_order_request.job_order.vehicle.bharat_stage = $scope.extras.bharat_stages[1];
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
            console.log(vehicle);
            if (vehicle) {
                if (vehicle.vehicle_owners) {
                    $len = vehicle.vehicle_owners.length - 1;
                    $scope.warranty_job_order_request.job_order.customer = vehicle.vehicle_owners[$len].customer;
                    $scope.customerChanged(vehicle.vehicle_owners[$len].customer);
                    // $scope.warranty_job_order_request.customer_address
                }
                if (vehicle.bharat_stage == null && vehicle.sold_date != null) {
                    $scope.soldDateChange(vehicle.sold_date);
                }
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
            if (self.hasPermission('all-outlet-warranty-job-order-request')) {
                params = {
                    filter: {
                        search: query,
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
            } else if (self.hasPermission('own-outlet-warranty-job-order-request')) {
                params = {
                    filter: {
                        search: query,
                        IdIn: [$scope.user.employee.outlet_id]
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
                ComplaintSvc.options({ filter: { search: query, subAggregate: $scope.warranty_job_order_request.sub_aggregate.id } })
                    //complaintGroup: $scope.warranty_job_order_request.complaint_group.id
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
            console.log($scope.warranty_job_order_request.job_order.outlet.id);
            return new Promise(function(resolve, reject) {
                RepairOrderSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                        OutletSvc.business_outlet({ filter: { outlet: $scope.warranty_job_order_request.job_order.outlet.id } })
                            .then(function(response) {
                                $scope.business_data = response.data.business_outlet;
                            }).catch(function(err) {
                                $scope.business_data = null;
                            });
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
            $scope.warranty_job_order_request.photos1.push($scope.warranty_job_order_request.photos1.length + 1);
            // $(".addPhotoBtn").hide();
        }
        $scope.removeUploader = function(key) {
            $scope.warranty_job_order_request.photos1.splice(key, 1);
            // $(".addPhotoBtn").show();
        }

        $scope.outletChanged = function(outlet) {
            if (!outlet) {
                return;
            }
            OutletSvc.getBusiness({ outletId: outlet.id, businessName: 'ALSERV' })
                .then(function(response) {
                    $scope.warranty_job_order_request.job_order.outlet.business = response.data.business;
                }).catch(function(error) {
                    console.log(error);
                });
            if (pageLoaded == 1) {
                $scope.requestTypeChanges();
            }
        }

        $scope.stateChanged = function() {
            if (pageLoaded == 1) {
                // $scope.warranty_job_order_request.customer_address.city = null;
                $scope.requestTypeChanges();
            }
        }

        $scope.customerChanged = function(customer) {
            $scope.warranty_job_order_request.customer_address = {};
            CustomerSvc.read(customer.id)
                .then(function(response) {
                    $scope.warranty_job_order_request.job_order.customer = response.data.customer;
                    if (typeof response.data.customer.address != null && typeof response.data.customer.address != 'string') {
                        $scope.warranty_job_order_request.customer_address = response.data.customer.address;
                    } else if (typeof response.data.customer.primary_address != null && typeof response.data.customer.primary_address != 'string') {
                        $scope.warranty_job_order_request.customer_address = response.data.customer.primary_address;
                    }
                    $scope.countryChanged();
                });
        }


        $scope.isSameState = function() {
            if ($scope.warranty_job_order_request.job_order && $scope.warranty_job_order_request.job_order.customer && $scope.warranty_job_order_request.job_order.outlet) {
                var customer_state = $scope.warranty_job_order_request.job_order.customer.state_id;
                if ($scope.warranty_job_order_request.customer_address.state != undefined) {
                    var customer_state = $scope.warranty_job_order_request.customer_address.state.id;
                }
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
            // console.log($scope.business_data);
            console.log($scope.warranty_job_order_request.request_type_id, $scope.business_data);
            $scope.wjor_repair_order.qty = 1;
            if ($scope.warranty_job_order_request.request_type_id == 9181) {
                $scope.wjor_repair_order.rate = ($scope.business_data != null) ? $scope.business_data.ewp_claim_rate_per_hour : 0;
            } else {
                $scope.wjor_repair_order.rate = ($scope.business_data != null) ? $scope.business_data.warranty_claim_rate_per_hour : 0;
            }
            $scope.wjor_repair_order.rate = ($scope.wjor_repair_order.rate == null) ? 0 : $scope.wjor_repair_order.rate;
            $scope.wjor_repair_order.rate = $scope.wjor_repair_order.rate * parseFloat($scope.wjor_repair_order.repair_order.hours);
            // $scope.wjor_repair_order.rate = repair_order.claim_amount;
            $scope.wjor_repair_order.rate = $scope.wjor_repair_order.rate.toFixed(2);
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
                    $rootScope.loading = true;

                    $scope.wjor_part.qty = 1;
                    $scope.wjor_part.rate = part.mrp;
                    $scope.wjor_part.part.tax_code = response.data.part.tax_code;
                    $scope.wjor_part.tax_code = response.data.part.tax_code;
                    $scope.wjor_part.purchase_type = 8480;
                    if ($scope.warranty_job_order_request.request_type_id == 9181) {
                        $scope.wjor_part.handling_charge_percentage = 0;
                    } else {
                        $scope.wjor_part.handling_charge_percentage = 4;
                    }
                    $scope.wjor_part.handling_charge = 0;
                    params = {
                        filter: {
                            part: part.id,
                            outlet: $scope.warranty_job_order_request.job_order.outlet.id
                        },
                    };
                    let formData = new FormData();
                    formData.append('part', part.id);
                    formData.append('outlet', $scope.warranty_job_order_request.job_order.outlet.id);
                    $.ajax({
                            url: base_url + '/api/part/stock_data',
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
                            // alert($scope.warranty_job_order_request.request_type_id);
                            $scope.wjor_part.saved_mrp = 0;
                            if (res.stock_data == null) {
                                $scope.wjor_part.rate = 0;
                                $scope.wjor_part.part.mrp = 0;
                                if ($scope.warranty_job_order_request.request_type_id == 9181) {
                                    $scope.wjor_part.part.tax_code = null;
                                    $scope.wjor_part.tax_code = null;
                                }
                            } else {
                                $scope.wjor_part.part.mrp = res.stock_data.mrp;
                                $scope.wjor_part.saved_mrp = res.stock_data.mrp;

                                console.log($scope.warranty_job_order_request.request_type_id);
                                if ($scope.warranty_job_order_request.request_type_id == 9181) {
                                    // EWP Request Type
                                    $scope.wjor_part.rate = res.stock_data.mrp;
                                    $scope.wjor_part.part.tax_code = null;
                                    $scope.wjor_part.tax_code = null;
                                    $scope.wjor_part.handling_charge_percentage = 0;
                                    $scope.wjor_part.handling_charge = 0;
                                } else {
                                    // AMC And Warranty Request Type
                                    $scope.wjor_part.rate = res.stock_data.cost_price;

                                    /*if ($scope.wjor_part.tax_code) {
                                        $scope.wjor_part.tax_code.taxes.push({
                                            'name': 'Handling Charges',
                                            'pivot': {
                                                'percentage': $scope.wjor_part.handling_charge_percentage,
                                            },
                                            'type_id': ($scope.isSameState()) ? 1160 : 1161,
                                        });
                                    } else {
                                        var handling_charge = [{
                                            'name': 'Handling Charges',
                                            'pivot': {
                                                'percentage': $handling_percentage,
                                                'amount': 0,
                                            },
                                            'type_id': ($scope.isSameState()) ? 1160 : 1161,
                                        }];
                                        part.tax_code = { 'taxes': handling_charge };
                                    }*/
                                }
                                // $scope.wjor_part.mrp = res.stock_data.mrp;

                            }
                            console.log($scope.wjor_part.part);
                            $scope.calculatePartAmount();
                            $scope.calculateHandlingCharge();
                            $rootScope.loading = false;
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            custom_noty('error', 'Something went wrong at server');
                        });

                });

        };

        $scope.purchaseTypeChange = function() {
            $scope.wjor_part.handling_charge_percentage = 0;
            $scope.wjor_part.handling_charge = 0;
            if ($scope.wjor_part.purchase_type == 8481 && parseFloat($scope.wjor_part.rate) > parseFloat($scope.wjor_part.saved_mrp)) {
                custom_noty('error', 'Rate Should not exceed MRP - ' + $scope.wjor_part.saved_mrp);
                $scope.wjor_part.rate = $scope.wjor_part.saved_mrp;
                $(".rateText").blur().focus();
            }
        };

        $scope.calculateHandlingCharge = function(field = null) {
            $handling_charge = parseFloat($scope.wjor_part.net_amount) * (parseFloat($scope.wjor_part.handling_charge_percentage) / 100);
            if (isNaN($handling_charge)) {
                $handling_charge = 0;
            }
            $scope.wjor_part.handling_charge = $handling_charge.toFixed(2);
            /*if ($scope.wjor_part.tax_code) {
                angular.forEach($scope.wjor_part.tax_code.taxes, function(tax) {
                    if (tax.name == 'Handling Charges') {
                        if ($scope.wjor_part.handling_charge_percentage == '') {
                            $scope.wjor_part.handling_charge_percentage = 0;
                        }
                        tax.pivot.percentage = $scope.wjor_part.handling_charge_percentage;
                    }
                });
            }*/
            if (field != null && field == 'qty') {
                $(".qtyText").blur().focus();

            }
            if (field != null && field == 'rate') {
                console.log($scope.wjor_part.saved_mrp);
                if ($scope.wjor_part.purchase_type == 8481 && parseFloat($scope.wjor_part.rate) > parseFloat($scope.wjor_part.saved_mrp)) {
                    custom_noty('error', 'Rate Should not exceed MRP - ' + $scope.wjor_part.saved_mrp);
                    $scope.wjor_part.rate = $scope.wjor_part.saved_mrp;
                }

                $(".rateText").blur().focus();

            }
            $scope.calculatePartAmount();
        }

        $scope.calculateCushionCharges = function(partcal = null) {

            $cushion_percentage = parseFloat($scope.warranty_job_order_request.total_part_cushioning_percentage);
            if (isNaN($cushion_percentage)) {
                $cushion_percentage = 0;
            }

            $cushioning_charges = ($cushion_percentage / 100) * parseFloat($scope.warranty_job_order_request.part_total);
            $scope.warranty_job_order_request.total_part_cushioning_charge = $cushioning_charges;
            $scope.warranty_job_order_request.total_part_amount = parseFloat($cushioning_charges) + parseFloat($scope.warranty_job_order_request.part_total);
            console.log($scope.warranty_job_order_request.part_total, $cushioning_charges);
            if (partcal == null) {
                $scope.calculatePartAmount();
            }
            // $scope.calculateTotals();
        }

        $scope.calculatePartAmount = function() {
            HelperService.calculateTaxAndTotal($scope.wjor_part, $scope.isSameState(), true);
            $scope.calculateTotals();
        }
        /*
                $scope.wjor_repair_order.qty = 1;
                if ($scope.warranty_job_order_request.request_type_id == 9181) {
                    $scope.wjor_repair_order.rate = ($scope.business_data) ? $scope.business_data.ewp_claim_rate_per_hour : 0;
                } else {
                    $scope.wjor_repair_order.rate = ($scope.business_data) ? $scope.business_data.warranty_claim_rate_per_hour : 0;
                }
                $scope.wjor_repair_order.rate = ($scope.wjor_repair_order.rate == null) ? 0 : $scope.wjor_repair_order.rate;
                $scope.wjor_repair_order.rate = $scope.wjor_repair_order.rate * parseFloat($scope.wjor_repair_order.repair_order.hours);
                // $scope.wjor_repair_order.rate = repair_order.claim_amount;
                $scope.wjor_repair_order.rate = $scope.wjor_repair_order.rate.toFixed(2);
                $scope.wjor_repair_order.tax_code = repair_order.tax_code;
                HelperService.calculateTaxAndTotal($scope.wjor_repair_order, $scope.isSameState());

                console.log($scope.wjor_repair_order);
        */
        $scope.getBusinessOutletData = function() {
            OutletSvc.business_outlet({ filter: { outlet: $scope.warranty_job_order_request.job_order.outlet.id } })
                .then(function(response) {
                    $scope.business_data = response.data.business_outlet;
                }).catch(function(err) {
                    $scope.business_data = null;
                });
        }

        $scope.reCalculateTotals = function() {
            console.log('reCalculateTotals');
            $(".pace").removeClass('pace-inactive').addClass('pace-active');
            /* Recalculating Repair Orders */
            $scope.getBusinessOutletData();
            var repair_order_rate = 0;
            var request_type_id = $scope.warranty_job_order_request.request_type_id;
            setTimeout(function() {
                if (request_type_id == 9181) {
                    repair_order_rate = ($scope.business_data != null) ? $scope.business_data.ewp_claim_rate_per_hour : 0;
                } else {
                    repair_order_rate = ($scope.business_data != null) ? $scope.business_data.warranty_claim_rate_per_hour : 0;
                }
                repair_order_rate = (repair_order_rate == null) ? 0 : repair_order_rate;
                if ($scope.warranty_job_order_request.wjor_repair_orders.length > 0) {
                    angular.forEach($scope.warranty_job_order_request.wjor_repair_orders, function(value, key) {
                        $scope.warranty_job_order_request.wjor_repair_orders[key].qty = 1;
                        $scope.warranty_job_order_request.wjor_repair_orders[key].rate = repair_order_rate * parseFloat(value.repair_order.hours);
                        $scope.warranty_job_order_request.wjor_repair_orders[key].rate = $scope.warranty_job_order_request.wjor_repair_orders[key].rate.toFixed(2);
                        $scope.warranty_job_order_request.wjor_repair_orders[key].tax_code = value.tax_code;
                        HelperService.calculateTaxAndTotal($scope.warranty_job_order_request.wjor_repair_orders[key], $scope.isSameState());
                    });
                    // $scope.calculateTotals();
                    // $(".pace").removeClass('pace-active').addClass('pace-inactive');
                }

            }, 2000);
            /* Recalculating Repair Orders End */

            /* Recalculating Parts Start*/
            $requestTypeOnload = $("#requestTypeOnload").val();
            if ($scope.warranty_job_order_request.wjor_parts.length > 0) {
                angular.forEach($scope.warranty_job_order_request.wjor_parts, function(value, key) {
                    // console.log(value);
                    $scope.warranty_job_order_request.wjor_parts[key].part.tax_code = value.tax_code;
                    $scope.warranty_job_order_request.wjor_parts[key].tax_code = value.tax_code;
                    $scope.warranty_job_order_request.wjor_parts[key].mrp = value.mrp;
                    // $scope.warranty_job_order_request.wjor_parts[key].purchase_type = value.purchase_type.id;
                    // $scope.warranty_job_order_request.wjor_parts[key].net_amount = value.net_amount;
                    // $scope.warranty_job_order_request.wjor_parts[key].rate = value.net_amount;
                    if (value.handling_charge_percentage == undefined) {
                        value.handling_charge_percentage = 0;
                    }
                    if (value.tax_code == undefined) {
                        value.tax_code = null;
                    }
                    if ($requestTypeOnload == 9181 && request_type_id != 9181) {
                        PartSvc.read(value.part.id)
                            .then(function(response) {
                                if ($scope.warranty_job_order_request.wjor_parts[key].tax_code == null) {
                                    $scope.warranty_job_order_request.wjor_parts[key].tax_code = [];
                                }
                                $scope.warranty_job_order_request.wjor_parts[key].tax_code = response.data.part.tax_code;
                                // console.log($scope.warranty_job_order_request.wjor_parts[key]);
                            });
                    }
                    // console.log(value.handling_charge_percentage);
                    $scope.warranty_job_order_request.wjor_parts[key].handling_charge_percentage = value.handling_charge_percentage;

                    if (request_type_id == 9181) {
                        // EWP Request Type
                        $scope.warranty_job_order_request.wjor_parts[key].pre_tax_code = value.tax_code;
                        $scope.warranty_job_order_request.wjor_parts[key].tax_code = null;
                        $scope.warranty_job_order_request.wjor_parts[key].prev_handling_charge_percentage = value.handling_charge_percentage;
                        $scope.warranty_job_order_request.wjor_parts[key].handling_charge_percentage = 0;
                    } else {
                        $scope.warranty_job_order_request.wjor_parts[key].tax_code = $scope.warranty_job_order_request.wjor_parts[key].pre_tax_code;
                        $scope.warranty_job_order_request.wjor_parts[key].handling_charge_percentage = $scope.warranty_job_order_request.wjor_parts[key].prev_handling_charge_percentage;
                    }
                    console.log($scope.warranty_job_order_request.wjor_parts[key], 'part');
                    setTimeout(function() {

                        $handling_charge = parseFloat($scope.warranty_job_order_request.wjor_parts[key].net_amount) * (parseFloat($scope.warranty_job_order_request.wjor_parts[key].handling_charge_percentage) / 100);
                        if (isNaN($handling_charge)) {
                            $handling_charge = 0;
                        }
                        $scope.warranty_job_order_request.wjor_parts[key].handling_charge = $handling_charge.toFixed(2);
                        console.log('calc hanling charge', $handling_charge);
                        HelperService.calculateTaxAndTotal($scope.warranty_job_order_request.wjor_parts[key], $scope.isSameState(), true);
                    }, 3000);

                });
            }
            /* Recalculating Parts End */
            setTimeout(function() {
                $scope.calculateTotals();
                setTimeout(function() {
                    console.log('calculate_cushion');
                    $scope.calculateCushionCharges();
                    $(".pace").removeClass('pace-active').addClass('pace-inactive');
                    $scope.$apply();
                }, 1500);
            }, 7000);

        }

        $scope.requestTypeChanges = function() {
            /*
            $scope.warranty_job_order_request.wjor_repair_orders = [];
            $scope.warranty_job_order_request.wjor_parts = [];
            $scope.warranty_job_order_request.total_part_cushioning_percentage = 0;
            $scope.warranty_job_order_request.total_part_cushioning_charge = 0;
            $scope.warranty_job_order_request.total_part_amount = 0;
            // $scope.calculatePartAmount();
            $scope.calculateTotals();
            */
            // if ($scope.warranty_job_order_request.wjor_parts.length > 0 || $scope.warranty_job_order_request.wjor_repair_orders.length > 0) {
            $scope.reCalculateTotals();
            // }
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
                // $scope.warranty_job_order_request.total_part_amount = parseFloat($cushioning_charges) + parseFloat($scope.warranty_job_order_request.part_total);

                $scope.calculateCushionCharges(true);
                $('#part_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });

        $scope.removePart = function(index) {
            $scope.warranty_job_order_request.wjor_parts.splice(index, 1);
            $scope.calculateTotals();
            $scope.calculateCushionCharges();
        }

        // Common -------------------------------------

        $scope.calculateTotals = function() {
            $scope.warranty_job_order_request.repair_order_total = HelperService.calculateTotal($scope.warranty_job_order_request.wjor_repair_orders);
            $scope.warranty_job_order_request.part_total = HelperService.calculateTotal($scope.warranty_job_order_request.wjor_parts);
            $scope.warranty_job_order_request.estimate_total = $scope.warranty_job_order_request.repair_order_total + $scope.warranty_job_order_request.part_total + parseFloat($scope.warranty_job_order_request.total_part_cushioning_charge);
            console.log($scope.warranty_job_order_request.estimate_total);
        }

        $scope.failedAtKeyup = function() {
            $isValidFailedAt = true;
            $failed_at = parseInt($scope.warranty_job_order_request.failed_at);
            $last_lube_changed = parseInt($scope.warranty_job_order_request.last_lube_changed);
            $reading_type_id = $scope.warranty_job_order_request.reading_type_id;
            if (!isNaN($last_lube_changed)) {
                if ($reading_type_id == 8040 && $failed_at < $last_lube_changed) {
                    custom_noty('error', 'Failed KM Value Should be Greater than Last lube change');
                    $isValidFailedAt = false;
                }
            }
            return false;
        }
        $scope.checkLabourPart = function() {
            if ($scope.warranty_job_order_request.wjor_parts.length > 0 || $scope.warranty_job_order_request.wjor_repair_orders.length > 0) {
                $isValidFailedAt = true;
            } else {
                custom_noty('error', 'One Part Or Labour atleast should be selected.');
                $isValidFailedAt = false;
            }
        }

        /* 
        var attachment_description_html = '<input maxlength="255" type="text" name="attachment_descriptions[]" class="form-control" placeholder="Enter Description" />';
        $(document).on("change", ".attachments", function() {
            var files = $(this)[0].files;
            $length = files.length - 1;
            for (var i = 1; i <= $length; i++) {
                $(".feild-wrap").append(attachment_description_html);
            }
        });
        */

        // Main Form Submit -------------------------------------

        jQuery.validator.addMethod('myLessThan', function(value) {
            if ($scope.warranty_job_order_request.reading_type_id != 8040) {
                if (value > 24 || value <= 0) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }, 'Hours Should be less than or equals to 24');
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
                /*'supplier_id': {
                    required: true,
                },*/
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
                    // required: true,
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
                    // maxlength: 10,
                    maxlength: function() {
                        if ($scope.warranty_job_order_request.reading_type_id == 8040) {
                            return 4;
                        } else {
                            return 2;
                        }
                    },
                },
                'failed_at': {
                    required: true,
                    // digits: true,
                    minlength: 2,
                    // maxlength: 10,
                    maxlength: function() {
                        if ($scope.warranty_job_order_request.reading_type_id == 8040) {
                            return 6;
                        } else {
                            return 4;
                        }
                        // return $scope.warranty_job_order_request.job_order.vehicle.is_sold;
                    },
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
                'failure_report_file': {
                    required: function() {
                        /*if ($("#failure_type_id").val() != '' && ($scope.updating == false) || ($scope.updating == true && $scope.warranty_job_order_request.failure_photo == null)) {
                            return true;
                        } else {
                            return false;
                        }*/
                        var failure_type_val = $("#failure_type_id").val();
                        console.log(failure_type_val);
                        if (failure_type_val == '') {
                            return false;
                        } else {
                            if (($scope.updating == false && $scope.warranty_job_order_request.failure_type.id != undefined) || ($scope.updating == true && $scope.warranty_job_order_request.failure_type.id != undefined && $scope.warranty_job_order_request.failure_photo == null)) {
                                return true;
                            }
                            return false;
                        }
                    }
                }

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
                // alert($scope.warranty_job_order_request.failure_type);
                // return false;
                $scope.failedAtKeyup();
                $scope.checkLabourPart();
                if ($isValidFailedAt == true) {
                    let formData = new FormData($(form_id1)[0]);
                    $('.submit').button('loading');
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
                                $('.submit').button('reset');

                                return;
                            }
                            $('.submit').button('reset');

                            showNoty('success', 'Warranty job order request saved successfully');
                            if ($routeParams.request_id == undefined) {
                                $localstorage.remove('ppr');
                                self.is_temp_saved = true;
                            }

                            $location.path('/warranty-job-order-request/table-list');
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');

                            custom_noty('error', 'Something went wrong at server');
                        });
                }
                // WarrantyJobOrderRequestSvc.save($scope.warranty_job_order_request)
                //     .then(function(response) {
                //         showNoty('success', 'Warranty job order request saved successfully');
                //             $location.path('/warranty-job-order-request/card-list');
                //     });
            }
        });

        self.searchCity = function(query) {
            /*if ($scope.warranty_job_order_request.customer_address.state) {
                $state_id = $scope.warranty_job_order_request.customer_address.state.id;
            }*/
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getCitySearchList'], {
                                key: query
                                // state: $state_id
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
        self.citySelected = function(city) {
            if (city != undefined) {
                self.state = $scope.warranty_job_order_request.customer_address.state;
                $scope.warranty_job_order_request.customer_address.state = city.state;
                console.log($scope.warranty_job_order_request.customer_address.state);
                // $scope.$apply();
                if (pageLoaded == 1) {
                    $scope.requestTypeChanges();
                }
            }
        }

        $scope.subAggregateChange = function() {
            if (pageLoaded == 1) {
                $scope.warranty_job_order_request.complaint = null;
            }
        }
        $scope.aggregateChange = function(aggregate, onload = null) {
            // console.log(aggregate.id);
            if (onload == null) {
                $scope.warranty_job_order_request.sub_aggregate = null;
                $scope.warranty_job_order_request.complaint = null;
            }
            $(".pace").removeClass('pace-inactive').addClass('pace-active');
            $.ajax({
                    url: base_url + '/api/aggregates/get-sub-aggregates-list',
                    method: "POST",
                    data: {
                        id: aggregate.id,
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    // console.log(res.options);
                    setTimeout(function() {
                        $scope.extras.sub_aggregates = res.options;
                        $(".pace").removeClass('inactive').addClass('pace-inactive');
                    }, 2000);

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $(".pace").removeClass('inactive').addClass('pace-inactive');
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.countryChanged = function(onload = null) {
            // console.log($scope.warranty_job_order_request.customer_address);
            // $country_id = (onload) ? $scope.warranty_job_order_request.job_order.vehicle.current_owner.customer.address.country.id : $scope.warranty_job_order_request.customer_address.country.id;
            if (onload != null) {
                $country_id = $scope.extras.default_country.id;
            } else {
                $country_id = $scope.warranty_job_order_request.customer_address.country.id;
            }
            $.ajax({
                    url: base_url + '/api/state/get-drop-down-List',
                    method: "POST",
                    data: {
                        country_id: $country_id,
                        // country_id: $scope.warranty_job_order_request.job_order.vehicle.current_owner.customer.address.country.id,
                        // country_id: $scope.warranty_job_order_request.customer_address.country.id,
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    setTimeout(function() {
                        $scope.extras.state_list = res.state_list;
                        setTimeout(function() {
                            pageLoaded = 1;
                        }, 4000);
                        if ($scope.warranty_job_order_request.customer_address == undefined) {
                            $scope.warranty_job_order_request.customer_address = [];
                        }
                        $scope.warranty_job_order_request.customer_address.country = $scope.extras.default_country;
                        if (!onload) {
                            $scope.warranty_job_order_request.customer_address.state = self.state;
                            setTimeout(function() {
                                $(".job_card_number").focus().blur();
                                console.log("blur");
                                $scope.warranty_job_order_request.photos1 = [];
                                console.log($scope.warranty_job_order_request.failure_type);
                                if ($scope.warranty_job_order_request.failure_type) {
                                    $scope.warranty_job_order_request.is_analysis_report_required = true;
                                } else {
                                    $scope.warranty_job_order_request.is_analysis_report_required = false;
                                }
                                /*$scope.warranty_job_order_request.is_analysis_report_required = false;
                                if ($scope.warranty_job_order_request.failure_type && typeof($routeParams.request_id) != 'undefined') {
                                    $scope.warranty_job_order_request.is_analysis_report_required = true;
                                }
                                console.log($scope.warranty_job_order_request.is_analysis_report_required);*/
                            }, 1000);
                        }
                        // console.log($scope.warranty_job_order_request.customer_address.state);
                        // $scope.$apply();
                    }, 2000);

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

        $scope.lastLubeChange = function() {
            if ($scope.warranty_job_order_request.last_lube_changed != '') {
                $scope.remarksForNotChangingLube = true;
                $scope.warranty_job_order_request.remarks_for_not_changing_lube = '';
            } else {
                $scope.remarksForNotChangingLube = false;
            }
        }
    }
});