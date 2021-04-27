app.component('tvsOneDiscountTableList', {
    templateUrl: tvs_one_discount_list_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('gigo-tvs-one-discount-request')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.search_key = '';
        self.user = $scope.user = HelperService.getLoggedUser();
        self.export_url = exportTVSOneDiscountUrl;
        // var table_scroll;
        self.csrf_token = $('meta[name="csrf-token"]').attr('content');

        // table_scroll = $('.page-main-content.list-page-content').height() - 37;
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#tvs_one_request_vehicles_list').DataTable({
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
                url: laravel_routes['getTVSOneRequestList'],
                type: "GET",
                dataType: "json",
                data: function (d) {
                    d.date_range = $("#filter_date_range").val();
                    d.registration_type = $("#registration_type").val();
                    d.reg_no = $("#reg_no").val();
                    d.customer_id = $("#customer_id").val();
                    d.model_id = $("#model_id").val();
                    d.membership = $("#membership").val();
                    d.gate_in_no = $("#gate_in_no").val();
                    d.status_id = $("#status_id").val();
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
                data: 'vehicle_status',
                name: 'vehicle_delivery_statuses.name'
            },
            {
                data: 'outlet_code',
                name: 'outlets.code'
            },
            // {
            //     data: 'registration_type',
            //     name: 'registration_type'
            // },
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
            $('#tvs_one_request_vehicles_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function () {
            $('#tvs_one_request_vehicles_list').DataTable().ajax.reload();
        });

        var dataTables = $('#tvs_one_request_vehicles_list').dataTable();
        $scope.searchInwardVehicle = function () {
            dataTables.fnFilter(self.search_key);
        }

        $scope.listRedirect = function (type) {
            window.location = "#!/inward-vehicle/table-list";
            return false;
        }
        // FOR FILTER
        $http.get(
            laravel_routes['getManualDeliveryVehicleFilter']
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

        //Change Status
        $scope.changeStatus = function (id, vehicle_delivery_status_id) {
            setTimeout(function () {
                $scope.job_order_id = id;
                $scope.vehicle_delivery_status_id = vehicle_delivery_status_id;

                $('#vehicle_delivery_status_id').val(vehicle_delivery_status_id);
                $('#job_order_id').val(id);
            }, 100);
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

app.component('tvsOneDiscountView', {
    templateUrl: tvs_one_discount_view_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect, $window) {
        //for md-select search
        $element.find('input').on('keydown', function (ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $scope.hasPerm = HelperService.hasPerm;
        if (!self.hasPermission('view-tvs-one-discount-request')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;

        self.reject_reason_id = 2;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;
        $scope.label_name = "Receipt";

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
                        if ($scope.job_order.balance_amount > 0) {
                            self.vehicle_payment_status = "Partially Paid";
                        } else {
                            self.vehicle_payment_status = "Yes";
                        }
                    } else {
                        self.vehicle_payment_status = "No";
                    }

                    self.payment_mode_id = $scope.job_order.payment_detail[0] ? $scope.job_order.payment_detail[0].payment_mode_id : '0';

                    if (self.payment_mode_id == 1) {
                        $scope.label_name = 'Receipt';
                    } else {
                        $scope.label_name = 'Transaction';
                    }

                    if ($scope.job_order.pending_reason_id == 2 || $scope.job_order.pending_reason_id == 3 || $scope.job_order.pending_reason_id == 4 || $scope.job_order.pending_reason_id == 5) {
                        $scope.payment_mode_status = 'false';
                        $scope.label_name = 'Transaction';
                    } else {
                        $scope.payment_mode_status = 'true';
                    }

                    self.vehicle_service_status = 1;
                    if ($scope.job_order.inward_cancel_reason) {
                        self.vehicle_service_status = 0;
                    }

                    if ($scope.job_order.billing_type_id == 11523) {
                        $scope.invoice_label_name = "DSP";
                    } else {
                        $scope.invoice_label_name = "";
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
                        url: base_url + '/api/tvs-one/discount/save',
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
                            $location.path('/tvs-one/discount-request/table-list');

                            $scope.$apply();
                        })
                        .fail(function (xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.showRejectForm = function (job_order) {
            $('#reject_modal').modal('show');
        }

        //Save Form Data 
        $scope.rejectVehicleDelivery = function () {
            var form_id = '#vehicle-delivery-reject-form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'job_order_id': {
                        required: true,
                    },
                    'remarks': {
                        required: true,
                    },
                },
                messages: {},
                invalidHandler: function (event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.reject_submit').button('loading');
                    $.ajax({
                        url: base_url + '/api/tvs-one/discount/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            $('.reject_submit').button('reset');

                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            $('#reject_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            custom_noty('success', res.message);
                            $location.path('/tvs-one/discount-request/table-list');

                            $scope.$apply();
                        })
                        .fail(function (xhr) {
                            $('.reject_submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //Invoice Updates
        $(document).on('click', '.select_all_invoices', function () {
            if (event.target.checked == true) {
                $('.invoicecheckbox').prop('checked', true);
                $.each($('.invoicecheckbox:checked'), function () {
                    $scope.checkCheckbox($(this).val());
                });
            } else {
                $('.invoicecheckbox').prop('checked', false);
                $.each($('.invoicecheckbox'), function () {
                    $scope.checkCheckbox($(this).val());
                });
            }
        });

        $scope.checkCheckbox = function (id) {
            checkval = $('#check' + id).is(":checked");
            if (checkval == true) {
                $("#in_" + id).removeClass('ng-hide');
                $("#in_" + id).addClass('required');
                $("#in_" + id).addClass('error');
            } else {
                $("#in_" + id).addClass('ng-hide');
                $("#in_" + id).val(" ");
                $("#in_" + id).removeClass('required');
                $("#in_" + id).removeClass('error');
                $("#in_" + id).closest('.form-group').find('label.error').remove();
                $("#in_" + id).val('');
                $('#in_' + id + '-error').remove();
            }
        }

        /* Image Uploadify Funtion */
        $('.image_uploadify').imageuploadify();

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
