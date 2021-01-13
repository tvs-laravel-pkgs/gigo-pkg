app.component('manualVehicleDeliveryList', {
    templateUrl: manual_vehicle_delivery_list_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('gigo-manual-vehicle-delivery')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.search_key = '';
        self.user = $scope.user = HelperService.getLoggedUser();

        // var table_scroll;
        // table_scroll = $('.page-main-content.list-page-content').height() - 37;
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#delivery_vehicles_list').DataTable({
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
                url: laravel_routes['getManualDeliveryVehicleList'],
                type: "GET",
                dataType: "json",
                data: function (d) {
                    d.gate_in_date = $("#gate_in_date").val();
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
            $('#delivery_vehicles_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function () {
            $('#delivery_vehicles_list').DataTable().ajax.reload();
        });

        var dataTables = $('#delivery_vehicles_list').dataTable();
        $scope.searchInwardVehicle = function () {
            dataTables.fnFilter(self.search_key);
        }

        $scope.listRedirect = function (type) {
            window.location = "#!/inward-vehicle/table-list";
            return false;
        }
        // FOR FILTER
        $http.get(
            laravel_routes['getVehicleInwardFilter']
        ).then(function (response) {
            self.extras = response.data.extras;
        });
        //GET CUSTOMER LIST
        self.searchCustomer = function (query) {
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getManualDeliveryVehicleFilter'], {
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
        $scope.selectedVehicleModel = function (id) {
            $('#model_id').val(id);
        }
        $scope.onSelectedRegistrationType = function (id) {
            $('#registration_type').val(id);
        }
        $scope.onSelectedStatus = function (id) {
            $('#status_id').val(id);
        }
        $scope.applyFilter = function () {
            dataTables.fnFilter();
            $('#vehicle-inward-filter-modal').modal('hide');
        }
        $scope.reset_filter = function () {
            $("#gate_in_date").val('');
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

app.component('manualVehicleDeliveryView', {
    templateUrl: manual_vehicle_delivery_view_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        //for md-select search
        $element.find('input').on('keydown', function (ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $scope.hasPerm = HelperService.hasPerm;
        if (!self.hasPermission('view-manual-vehicle-delivery')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function () {
            $.ajax({
                    url: base_url + '/api/manual-vehicle-delivery/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.id,
                    },
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                })
                .done(function (res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;

                    $scope.extras = res.extras;

                    if ($scope.job_order.vehicle_payment_status && $scope.job_order.vehicle_payment_status == 1) {
                        self.vehicle_payment_status = "Yes";
                    } else {
                        self.vehicle_payment_status = "No";
                    }

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.showApprovalForm = function (job_order) {
            $('#approve_modal').modal('show');
        }

        //Save Form Data 
        $scope.approveVehicleDelivery = function () {
            // alert(111);
            // return;
            var form_id = '#vehicle-delivery-approval-form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'job_order_id': {
                        required: true,
                    },
                    'approved_remarks': {
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
                            url: base_url + '/api/manual-vehicle-delivery/save',
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
                            $('#approve_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            custom_noty('success', res.message);
                            $location.path('/manual-vehicle-delivery/table-list');

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
    }
});

app.component('manualVehicleDeliveryForm', {
    templateUrl: manual_vehicle_delivery_form_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        //for md-select search
        $element.find('input').on('keydown', function (ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-manual-vehicle-delivery') && !self.hasPermission('edit-manual-vehicle-delivery')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function () {
            $.ajax({
                    url: base_url + '/api/manual-vehicle-delivery/get-form-data',
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
                    $scope.job_order = res.job_order;
                    $scope.invoice_date = res.invoice_date;
                    $scope.extras = res.extras;

                    if ($scope.job_order.vehicle_payment_status && $scope.job_order.vehicle_payment_status == 1) {
                        self.vehicle_payment_status = 1;
                    } else {
                        self.vehicle_payment_status = 0;
                    }

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveVehicleDelivery = function () {
            var form_id = '#vehicle_delivery_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    // 'invoice_number': {
                    //     required: true,
                    // },
                    'invoice_date': {
                        required: true,
                    },
                    // 'invoice_amount': {
                    //     required: true,
                    // },
                    'labour_invoice_number': {
                        required: true,
                    },
                    'labour_amount': {
                        required: true,
                    },
                    'parts_invoice_number': {
                        required: true,
                    },
                    'parts_amount': {
                        required: true,
                    },
                    'receipt_number': {
                        required: true,
                    },
                    'receipt_date': {
                        required: true,
                    },
                    'receipt_amount': {
                        required: true,
                    },
                    'vehicle_delivery_request_remarks': {
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
                            url: base_url + '/api/manual-vehicle-delivery/save',
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
                            $location.path('/manual-vehicle-delivery/table-list');

                            $scope.$apply();
                        })
                        .fail(function (xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.vehiclePaymentStatus = function (status) {
            if(status == 1){
                $scope.invoiceAmount();
            }
        }

        $(document).on('keyup', ".amount", function() {
            $scope.invoiceAmount();
        });

        $scope.invoiceAmount = function() {
            setTimeout(function() {
                var labour_amount = $('#labour_invoice_amount').val();
                var parts_amount = $('#parts_invoice_amount').val();

                var total_amount = 0;

                if(!labour_amount || isNaN(labour_amount)){
                    labour_amount = 0;
                }

                if(!parts_amount || isNaN(parts_amount)){
                    parts_amount = 0;
                }

                total_amount = parseFloat(labour_amount) + parseFloat(parts_amount);
                total_amount = total_amount.toFixed(2);

                $('.receipt_amount').val(total_amount);
            }, 100);
        }

        //Scrollable Tabs
        setTimeout(function () {
            scrollableTabs();
        }, 1000);
    }
});