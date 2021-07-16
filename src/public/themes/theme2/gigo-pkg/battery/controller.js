app.component('batteryList', {
    templateUrl: battery_list_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('gigo-battery')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.search_key = '';
        self.user = $scope.user = HelperService.getLoggedUser();
        self.export_url = exportBatteryLoadTest;
        // var table_scroll;
        self.csrf_token = $('meta[name="csrf-token"]').attr('content');

        self.battery_make_id = '';
        self.load_test_status_id = '';
        self.hydro_status_id = '';
        self.overall_status_id = '';

        // table_scroll = $('.page-main-content.list-page-content').height() - 37;
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#battery_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            stateSaveCallback: function (settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function (settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    self.search_key = state_save_val.search.search;
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            ajax: {
                url: laravel_routes['getBatteryList'],
                type: "GET",
                dataType: "json",
                data: function (d) {
                    d.date_range = $("#filter_date_range").val();
                    d.reg_no = $("#reg_no").val();
                    d.customer_id = $("#customer_id").val();
                    d.battery_make_id = $("#battery_make_id").val();
                    d.load_test_status_id = $("#load_test_status_id").val();
                    d.hydro_status_id = $("#hydro_status_id").val();
                    d.overall_status_id = $("#overall_status_id").val();
                },
            },

            columns: [{
                data: 'action',
                class: 'action',
                name: 'action',
                searchable: false
            },
            {
                data: 'date',
                searchable: false
            },
            {
                data: 'outlet_code',
                name: 'outlets.code'
            },
            {
                data: 'customer_name',
                name: 'customers.name'
            },
            {
                data: 'registration_number',
                name: 'vehicles.registration_number'
            },
            {
                data: 'battery_name',
                name: 'battery_makes.name'
            },
            {
                data: 'load_test_status',
                name: 'load_test_statuses.name'
            },
            {
                data: 'hydrometer_electrolyte_status',
                name: 'hydrometer_electrolyte_statuses.name'
            },
            {
                data: 'overall_status',
                name: 'battery_load_test_statuses.name'
            },


            ],
            "infoCallback": function (settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function (row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $scope.clear_search = function () {
            self.search_key = '';
            $('#battery_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function () {
            $('#battery_list').DataTable().ajax.reload();
        });

        var dataTables = $('#battery_list').dataTable();
        $scope.searchInwardVehicle = function () {
            dataTables.fnFilter(self.search_key);
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getBatteryFilterData']
        ).then(function (response) {
            self.extras = response.data.extras;
        });
        //GET CUSTOMER LIST
        self.searchCustomer = function (query) {
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getCustomerSearchList'], {
                            key: query,
                        }
                        )
                        .then(function (response) {
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }
        //GET VEHICLE MODEL LIST
        self.searchVehicleModel = function (query) {
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getVehicleModelSearchList'], {
                            key: query,
                        }
                        )
                        .then(function (response) {
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }
        $element.find('input').on('keydown', function (ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function () {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';
            $scope.searchTerm3 = '';
        };
        /* Modal Md Select Hide */
        $('.modal').bind('click', function (event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
        $scope.selectedCustomer = function (id) {
            $('#customer_id').val(id);
        }

        $scope.applyFilter = function () {
            dataTables.fnFilter();
            $('#vehicle-inward-filter-modal').modal('hide');
        }

        /* DateRange Picker */
        $('.filter_daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.align-left.filter_daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.filter_daterange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            //dataTables.fnFilter();
        });

        $('.filter_daterange').on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
        });

        $scope.reset_filter = function () {
            $("#filter_date_range").val('');
            $("#reg_no").val('');
            $("#customer_id").val('');
            $("#battery_make_id").val('');
            $("#load_test_status_id").val('');
            $("#hydro_status_id").val('');
            $("#overall_status_id").val('');
            dataTables.fnFilter();
            $('#vehicle-inward-filter-modal').modal('hide');
        }

        /* DateRange Picker */
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            "autoApply": true,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.align-left.daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.daterange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            //dataTables.fnFilter();
        });

        $('.daterange').on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
        });

        $rootScope.loading = false;
    }
});

app.component('batteryView', {
    templateUrl: battery_view_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect, $window, RepairOrderSvc, SplitOrderTypeSvc, PartSvc, $q) {
        //for md-select search
        $element.find('input').on('keydown', function (ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $scope.hasPerm = HelperService.hasPerm;
        if (!self.hasPermission('view-battery-result')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        //FETCH DATA
        $scope.fetchData = function () {
            $.ajax({
                url: base_url + '/api/battery/get-form-data',
                method: "POST",
                data: {
                    id: $routeParams.id,
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
            })
                .done(function (res) {
                    console.log(res)
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }

                    $scope.battery = res.battery;
                    $scope.extras = res.extras;
                    $scope.user_info = res.user;
                    self.country = res.extras.country;
                    $scope.action = res.action;

                    $scope.customer = $scope.battery ? $scope.battery.vehicle_battery ? $scope.battery.vehicle_battery.customer : [] : [];
                    console.log($scope.customer);

                    $scope.vehicle = $scope.battery ? $scope.battery.vehicle_battery ? $scope.battery.vehicle_battery.vehicle : [] : [];
                    console.log($scope.vehicle);

                    /* Image Uploadify Funtion */
                    setTimeout(function () {
                        $('.image_uploadify').imageuploadify();
                    }, 1000);

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.showPaymentForm = function (battery) {
            $('#payment_modal').modal('show');
        }

        //Save Payment Data 
        $scope.saveBatteryPaymentData = function () {
            var form_id = '#battery-payment-form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'battery_id': {
                        required: true,
                    },
                    'invoice_number': {
                        required: true,
                    },
                    'invoice_date': {
                        required: true,
                    },
                    'invoice_amount': {
                        required: true,
                    },
                },
                messages: {},
                invalidHandler: function (event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                        url: base_url + '/api/battery/payment/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            $('.submit').button('reset');

                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            $('#payment_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            custom_noty('success', res.message);
                            // $location.path('/manual-vehicle-delivery/table-list');
                            $window.location.reload();
                            $scope.$apply();
                        })
                        .fail(function (xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //Scrollable Tabs
        setTimeout(function () {
            scrollableTabs();
        }, 1000);

        /* Modal Md Select Hide */
        $('.modal').bind('click', function (event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
    }
});

app.component('batteryForm', {
    templateUrl: battery_form_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect, CustomerSvc, VehicleSvc) {
        //for md-select search
        $element.find('input').on('keydown', function (ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-battery-result') && !self.hasPermission('edit-battery-result')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();
        self.customer_search_type = true;
        self.search_type = true;
        self.is_battery_replaced = 1;
        self.is_battery_buy_back = 1;
        //FETCH DATA
        $scope.fetchData = function () {
            $.ajax({
                url: base_url + '/api/battery/get-form-data',
                method: "POST",
                data: {
                    id: $routeParams.id,
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
            })
                .done(function (res) {
                    console.log(res)
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }

                    $scope.battery = res.battery;
                    $scope.extras = res.extras;
                    self.country = res.extras.country;
                    $scope.action = res.action;
                    $scope.user_info = res.user;

                    if ($scope.battery.is_battery_replaced == 0) {
                        self.is_battery_replaced = 0;
                    } else {
                        self.is_battery_replaced = 1;
                    }
                    if ($scope.battery.is_buy_back_opted == 0) {
                        self.is_battery_buy_back = 0;
                    } else {
                        self.is_battery_buy_back = 1;
                    }

                    if (!$scope.battery.replaced_battery_make_id) {
                        $scope.battery.replaced_battery_make_id = 4;
                    }

                    $scope.customer = $scope.battery ? $scope.battery.vehicle_battery ? $scope.battery.vehicle_battery.customer : [] : [];
                    console.log($scope.customer);

                    $scope.vehicle = $scope.battery ? $scope.battery.vehicle_battery ? $scope.battery.vehicle_battery.vehicle : [] : [];
                    console.log($scope.vehicle);

                    /* Image Uploadify Funtion */
                    setTimeout(function () {
                        $('.image_uploadify').imageuploadify();
                    }, 1000);

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $(document).on('keyup', ".registration_number", function () {
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

        $scope.searchVehicles = function (query) {
            // return new Promise(function (resolve, reject) {
            //     VehicleSvc.options({ filter: { search: query } })
            //         .then(function (response) {
            //             resolve(response.data.options);
            //         });
            // });
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getVehicleSearchList'], {
                            key: query,
                        }
                        )
                        .then(function (response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }

        $scope.vehicleSelected = function (vehicle) {
            console.log(vehicle);
            if (vehicle) {
                $scope.vehicle = vehicle;
                if (vehicle.current_owner) {
                    $scope.customer = vehicle.current_owner.customer;
                    $scope.customerChanged(vehicle.current_owner.customer);
                }

                console.log($scope.vehicle);
            }
        }

        //GET VEHICLE MODEL LIST
        self.searchVehicleModel = function (query) {
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getVehicleModelSearchList'], {
                            key: query,
                        }
                        )
                        .then(function (response) {
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }

        $scope.searchCustomer = function (query) {
            // return new Promise(function (resolve, reject) {
            //     CustomerSvc.options({
            //         filter: {
            //             search: query
            //         }
            //     })
            //         .then(function (response) {
            //             console.log(response);
            //             resolve(response.data.options);
            //         });
            // });
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            search_parts_customer_url, {
                            key: query,
                        }
                        )
                        .then(function (response) {
                            resolve(response.data);
                        });
                });
            } else {
                $scope.customer = [];
                return [];
            }
        }

        $scope.customerChanged = function (customer) {
            if (customer.id) {
                self.customer_search_type = false;
                $scope.customer = [];
                CustomerSvc.read(customer.id)
                    .then(function (response) {
                        console.log(response);
                        $scope.customer = response.data.customer;
                        $country_id = response.data.customer.primary_address ? response.data.customer.primary_address.country_id : '1';
                        if (typeof response.data.customer.primary_address != null && typeof response.data.customer.primary_address != 'string') {
                            $scope.customer.address = response.data.customer.primary_address;
                        }
                        $scope.countryChanged();
                        // $scope.$apply();
                    });
            }
        }

        $scope.countryChanged = function (country_id) {
            setTimeout(function () {
                $.ajax({
                    url: base_url + '/api/state/get-drop-down-List',
                    method: "POST",
                    data: {
                        country_id: country_id,
                    },
                })
                    .done(function (res) {
                        if (!res.success) {
                            showErrorNoty(res);
                            return;
                        }
                        $scope.extras.state_list = res.state_list;
                        console.log($scope.customer.address);

                        self.state = $scope.customer ? $scope.customer.address.state ? $scope.customer.address.state : [] : [];

                        self.customer_search_type = true;

                        $scope.$apply();
                    })
                    .fail(function (xhr) {
                        custom_noty('error', 'Something went wrong at server');
                    });
            }, 300);
        }

        //GET CITY LIST
        self.searchCity = function (query) {
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getCitySearchList'], {
                            key: query,
                        }
                        )
                        .then(function (response) {
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }

        //Save Form Data 
        $scope.saveBatteryStatus = function () {
            var form_id = '#battery_status_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'engine_number': {
                        required: true,
                    },
                    'chassis_number': {
                        required: true,
                    },
                    'registration_number': {
                        required: true,
                    },
                    'name': {
                        required: true,
                    },
                    'code': {
                        required: true,
                    },
                    'mobile_no': {
                        required: true,
                    },
                    'address_line1': {
                        required: true,
                    },
                    'country_id': {
                        required: true,
                    },
                    'state_id': {
                        required: true,
                    },
                    'city_id': {
                        required: true,
                    },
                    // 'pincode': {
                    //     required: true,
                    // },
                },
                messages: {},
                invalidHandler: function (event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                        url: base_url + '/api/battery/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            $('.submit').button('reset');

                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/battery/table-list');

                            $scope.$apply();
                        })
                        .fail(function (xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        setTimeout(function () {
            /* Image Uploadify Funtion */
            //Scrollable Tabs
            scrollableTabs();
        }, 1000);

        /* Modal Md Select Hide */
        $('.modal').bind('click', function (event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
    }
});
