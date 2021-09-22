app.component('gigoSupportList', {
    templateUrl: gigo_support_list_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('inward-vehicle')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.search_key = '';
        self.user = $scope.user = HelperService.getLoggedUser();

        // var table_scroll;
        // table_scroll = $('.page-main-content.list-page-content').height() - 37;
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#gigo_support_list').DataTable({
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
                url: laravel_routes['gigoSupportList'],
                type: "GET",
                dataType: "json",
                data: function (d) {
                    d.date_range = $("#date_range").val();
                    d.registration_type = $("#registration_type").val();
                    d.reg_no = $("#reg_no").val();
                    d.customer_id = $("#customer_id").val();
                    d.model_id = $("#model_id").val();
                    d.membership = $("#membership").val();
                    d.gate_in_no = $("#gate_in_no").val();
                    d.status_id = $("#status_id").val();
                    d.service_advisor_id = self.user.id;
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
                data: 'registration_type',
                name: 'registration_type'
            },
            {
                data: 'registration_number',
                name: 'vehicles.registration_number'
            },
            {
                data: 'customer_name',
                name: 'customers.name'
            },
            {
                data: 'model_number',
                name: 'models.model_number'
            },
            {
                data: 'amc_policies',
                name: 'amc_policies.name'
            },
            {
                data: 'number',
                name: 'gate_logs.number'
            },
            {
                data: 'status',
                name: 'configs.name'
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
            $('#gigo_support_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function () {
            $('#gigo_support_list').DataTable().ajax.reload();
        });

        var dataTables = $('#gigo_support_list').dataTable();
        $scope.searchInwardVehicle = function () {
            dataTables.fnFilter(self.search_key);
        }

        // FOR FILTER
        $http.get(
            laravel_routes['gigoSupportGetVehicleInwardFilter']
        ).then(function (response) {
            self.extras = response.data.extras;
        });
        //GET CUSTOMER LIST
        self.searchCustomer = function (query) {
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['gigoSupportCustomerSearchList'], {
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
                            laravel_routes['gigoSupportGetVehicleModel'], {
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
        $scope.selectedVehicleModel = function (id) {
            $('#model_id').val(id);
        }
        $scope.onSelectedRegistrationType = function (id) {
            $('#registration_type').val(id);
        }
        $scope.onSelectedStatus = function (id) {
            $('#status_id').val(id);
        }

        /* DateRange Picker */
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
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

        $scope.applyFilter = function () {
            dataTables.fnFilter();
            $('#vehicle-inward-filter-modal').modal('hide');
        }
        $scope.reset_filter = function () {
            $("#date_range").val('');
            $("#registration_type").val('');
            $("#reg_no").val('');
            $("#customer_id").val('');
            $("#model_id").val('');
            $("#membership").val('');
            $("#gate_in_no").val('');
            $("#status_id").val('');
            dataTables.fnFilter();
            $('#vehicle-inward-filter-modal').modal('hide');
        }

        $rootScope.loading = false;
    }
});
//---------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------
app.component('gigoSupportView', {
    templateUrl: gigo_support_view_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element,CustomerSvc) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        self.angular_routes = angular_routes;

        self.csrf_token = $('meta[name="csrf-token"]').attr('content');

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();
        self.report_job_order_id = $routeParams.job_order_id;

        console.log($routeParams);
        $scope.job_order_id = $routeParams.job_order_id;
        self.is_sold = 1;

        self.customer_search_type = true;

        $scope.customerView = 1;
        $scope.customerEdit = 0;

        $scope.toEditCustomer = function () {
            $scope.customerView = 0;
            $scope.customerEdit = 1;
        }
        $scope.toCancelEditCustomer = function () {
            $scope.customerView = 1;
            $scope.customerEdit = 0;
        }

        $scope.vehicleDetailView = 1;
        $scope.vehicleDetailEdit = 0;

        $scope.toEditVehicle = function () {
            $scope.vehicleDetailView = 0;
            $scope.vehicleDetailEdit = 1;
        }

        $scope.toCancelEditVehicle = function () {
            $scope.vehicleDetailView = 1;
            $scope.vehicleDetailEdit = 0;
        }

        console.log( $scope.customerView);

        self.angular_routes = angular_routes;

    $scope.fetchData = function () {
        $http.get(
            laravel_routes['gigoSupportView'], {
                params: {
                    id: $routeParams.job_order_id,
                }
            }
        ).then(function(response) {
            self.vehicles_details = response.data.vehicles_details;
            // self.job_order = response.data.job_order;

            $scope.job_order = response.data.job_order;
            $scope.customer = $scope.job_order.vehicle.current_owner ? $scope.job_order.vehicle.current_owner.customer : [];
            $scope.ownership_type_list = response.data.ownership_type_list;
            $scope.ownership_type_id = $scope.job_order.vehicle.current_owner.ownership_type.id;
            $scope.country_list = response.data.country_list;
            $scope.state_list = response.data.state_list;
            $scope.trade_plate_number_list = response.data.trade_plate_number_list;

            if( $scope.job_order.contact_number == null){
                $("#order_detail_save").hide();
             }
            
            self.otps = response.data.otps;
            self.country = $scope.job_order.vehicle.current_owner.customer.address.country;
            self.state = $scope.customer.address.state;
            self.action = response.data.action;

            if ($scope.job_order.vehicle.is_sold) {
                console.log($scope.job_order.vehicle.is_sold);
                self.is_sold = 1;

            } else {
                self.is_sold = 0;
            }
            console.log(self.is_sold);
        });
        
    }

    $scope.fetchData();

    $scope.searchCustomer = function (query) {
        return new Promise(function (resolve, reject) {
            CustomerSvc.options({
                filter: {
                    search: query
                }
            })
                .then(function (response) {
                    resolve(response.data.options);
                });
        });
    }

    $scope.customerChanged = function (customer) {
        $scope.customer = {};

        CustomerSvc.read(customer.id)
            .then(function (response) {
                console.log(response);
                $scope.customer = response.data.customer;
                $country_id = response.data.customer.primary_address ? response.data.customer.primary_address.country_id : '1';
                if (typeof response.data.customer.primary_address != null && typeof response.data.customer.primary_address != 'string') {
                    $scope.customer.address = response.data.customer.primary_address;
                }
                $scope.countryChanged();
            });
    }


         $scope.countryChanged = function (country_id) {
            if (!$scope.customer) {
                self.state = $scope.job_order.state;
            } else {
                self.state = $scope.customer.address.state;
            }
            // $scope.$apply();
        }
        //Added for order detail save button show or hide
        $("#service_contact_no").keyup(function () { 
            if ($(this).val().length >= 10) {
               $("#order_detail_save").show();
            }
            else {
               $("#order_detail_save").hide();
            }
         });
        

        //==========================TAB CHANGE FUNCTION======================================
        var currentTab = 0;
        $(function () {
            $(".cndn-tabs").tabs({
                select: function (e, i) {
                    currentTab = i.index;
                }
            });
        });
        $(".btn-nxt").on("click", function () {
            var tabs = $('.cndn-tabs').tabs();
            var c = $('.cndn-tabs').tabs("length");
            currentTab = currentTab == (c - 1) ? currentTab : (currentTab + 1);
            tabs.tabs('select', currentTab);
            $("btn-prev").show();
            if (currentTab == (c - 1)) {
                $(".btn-nxt").hide();
            } else {
                $(".btn-nxt").show();
            }
        });
        $("btn-prev").on("click", function () {
            var tabs = $('.cndn-tabs').tabs();
            var c = $('.cndn-tabs').tabs("length");
            currentTab = currentTab == 0 ? currentTab : (currentTab - 1);
            tabs.tabs('select', currentTab);
            if (currentTab == 0) {
                $(".btn-nxt").show();
                $("btn-prev").hide();
            }
            if (currentTab < (c - 1)) {
                $(".btn-nxt").show();
            }
        });
        //===============================================================
        $scope.btnNxt = function () { }
        $scope.prev = function () { }
        /* Dropdown Arrow Function */
        arrowDropdown();

        //Added For Customer Save
        $scope.saveCustomer = function (){
            var form_id = '#customer_details_form';
            console.log(form_id);
            var form_valid = $(form_id).valid();
            console.log(form_valid);
            let formData = new FormData($(form_id)[0]);
            $('.submit').button('loading');
            $.ajax({
                url: laravel_routes['gigoSupportSave'],
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
            })
            .done(function (res) {
                if (res.success == true) {
                    custom_noty('success', res.message);
                    $location.path('/gigo-support/view/' + $scope.job_order.id);
                    $scope.customerView = 1;
                    $scope.customerEdit = 0;
                    $scope.$apply();
                    $scope.fetchData();
                } else {
                    if (!res.success == true) {
                        $('.submit').button('reset');
                        showErrorNoty(res);
                    } else {
                        $('.submit').button('reset');
                        $location.path('/gigo-support/view/' + $scope.job_order.id);
                        $scope.customerView = 1;
                        $scope.customerEdit = 0;
                        $scope.$apply();
                        $scope.fetchData();
                    }
                }
                })
                .fail(function (xhr) {
                    $scope.button_action(id, 2);
                    $('.submit').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
        }

           //GET VEHICLE MODEL LIST
           self.searchVehicleModel = function (query) {
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['gigoSupportGetVehicleModel'], {
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

        //Added For Vehicle Detail Save
        $scope.saveVehicleDetails = function (){
            var form_id = '#vehicle_details_form';
            console.log(form_id);
            var form_valid = $(form_id).valid();
            console.log(form_valid);
            let formData = new FormData($(form_id)[0]);
            $('.submit').button('loading');
            $.ajax({
                url: laravel_routes['gigoSupportSave'],
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
            })
            .done(function (res) {
                if (res.success == true) {
                    custom_noty('success', res.message);
                    $location.path('/gigo-support/view/' + $scope.job_order.id);
                    $scope.vehicleDetailView = 1;
                    $scope.vehicleDetailEdit = 0;
                    $scope.$apply();
                    $scope.fetchData();
                } else {
                    if (!res.success == true) {
                        $('.submit').button('reset');
                        showErrorNoty(res);
                    } else {
                        $('.submit').button('reset');
                        $location.path('/gigo-support/view/' + $scope.job_order.id);
                        $scope.vehicleDetailView = 1;
                        $scope.vehicleDetailEdit = 0;
                        $scope.$apply();
                        $scope.fetchData();
                    }
                }
                })
                .fail(function (xhr) {
                    $scope.button_action(id, 2);
                    $('.submit').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        //Save Order Detail
        $scope.saveOrderDetils = function (id){
            var form_id = '#order_detail_form';
            console.log(form_id);
            var form_valid = $(form_id).valid();
            console.log(form_valid);
            let formData = new FormData($(form_id)[0]);
            $('.submit').button('loading');
            $scope.button_action(id, 1);
            $.ajax({
                url: laravel_routes['gigoSupportSave'],
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
            })
            .done(function (res) {
                    if (res.success == true) {
                        custom_noty('success', res.message);
                        $scope.button_action(id, 1);
                        $location.path('/gigo-support/table-list');
                        $scope.$apply();
                        // $scope.fetchData();
                    } else {
                        if (!res.success == true) {
                            $('.submit').button('reset');
                            $scope.button_action(id, 2);
                            showErrorNoty(res);
                        } else {
                            $('.submit').button('reset');
                            $scope.button_action(id, 1);
                            $location.path('/gigo-support/table-list');
                            $scope.$apply();
                            // $scope.fetchData();
                        }
                    }
                })
                .fail(function (xhr) {
                    $scope.button_action(id, 2);
                    $('.submit').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.button_action = function (id, type) {
            if (type == 1) {
                $('.submit').button('loading');
                $('.btn-prev').unbind('click', false);

            } else {
                $('.submit').button('reset');
                $('.btn-prev').unbind('click', false);
            }
        }
    }
});
//-----------------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------------------
