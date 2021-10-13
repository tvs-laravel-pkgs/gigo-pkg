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
        self.export_url = exportManualVehicleDeliveryUrl;
        // var table_scroll;
        self.csrf_token = $('meta[name="csrf-token"]').attr('content');

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
                data: 'cn_number',
                name: 'job_orders.cn_number'
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

        $scope.vehicleStatusSave = function () {
            var split_form_id = '#vehicle_status_form';
            var v = jQuery(split_form_id).validate({
                ignore: '',
                rules: {
                    'job_order_id': {
                        required: true,
                    },
                    'vehicle_delivery_status_id': {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    let formData = new FormData($(split_form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                        url: base_url + '/api/manual-vehicle-delivery/update/vehicle-status',
                        method: "POST",
                        data: formData,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
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
                            $scope.job_order_id = '';
                            $scope.vehicle_delivery_status_id = '';
                            $('#change_vehicle_status').modal('hide');
                            $('#job_order_id').val('');
                            $('#vehicle_delivery_status_id').val('');
                            dataTables.fnFilter();
                        })
                        .fail(function (xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                            dataTables.fnFilter();
                        });
                }
            });
        }

        $rootScope.loading = false;
    }
});

app.component('manualVehicleDeliveryView', {
    templateUrl: manual_vehicle_delivery_view_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect, $window) {
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

        $scope.showPaymentForm = function (job_order) {
            $('#payment_modal').modal('show');
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
                        url: base_url + '/api/manual-vehicle-delivery/save',
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
                            $location.path('/manual-vehicle-delivery/table-list');

                            $scope.$apply();
                        })
                        .fail(function (xhr) {
                            $('.reject_submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //Save Payment Data 
        $scope.saveVehiclePaymentData = function () {
            var form_id = '#vehicle-delivery-payment-form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'job_order_id': {
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

        $scope.getSelectedPaymentMode = function (payment_mode_id) {
            if (payment_mode_id == 1) {
                $scope.label_name = "Receipt";
            } else {
                $scope.label_name = "Transaction";
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
        $scope.label_name = "Receipt";
        $scope.attachment_count = 1;
        $scope.form_save_status = 1;
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
                    $scope.warranty_date = res.warranty_date;
                    $scope.extras = res.extras;

                    if ($scope.job_order.vehicle_payment_status && $scope.job_order.vehicle_payment_status == 1) {
                        self.vehicle_payment_status = 1;
                    } else {
                        self.vehicle_payment_status = 0;
                    }

                    if ($scope.job_order.pending_reason_id) {
                        self.remarks_status = 1;
                    } else {
                        self.remarks_status = 0;
                    }

                    self.customer_status = 0;
                    if ($scope.job_order.jv_customer_id) {
                        self.customer_status = 1;
                    }

                    if ($scope.job_order.inward_cancel_reason) {
                        self.vehicle_service_status = 0;
                    } else {
                        $scope.attachment_count = 0;
                        self.vehicle_service_status = 1;
                    }

                    if ($scope.job_order.billing_type_id) {
                        $scope.getSelectedBillingType($scope.job_order.billing_type_id);
                    }

                    self.no_of_payment = 1;
                    if ($scope.job_order.payment_detail) {
                        if ($scope.job_order.payment_detail.length > 1) {
                            self.no_of_payment = 2;
                        } else if ($scope.job_order.payment_detail.length == 0) {
                            $scope.job_order.payment_detail.push({});
                        }
                    }

                    /* Image Uploadify Funtion */
                    setTimeout(function () {
                        $('.image_uploadify').imageuploadify();
                    }, 1000);

                    $scope.$apply();

                    if ($scope.job_order.labour_discount_amount && $scope.job_order.labour_discount_amount > 0) {
                        $('.labour_discount_class').val($scope.job_order.labour_discount_amount);
                        var customer_paid_labour_amount = $scope.job_order.manual_delivery_labour_invoice.amount - $scope.job_order.labour_discount_amount;
                        $('.labour_discount_amount').show();
                        $('.labour_pay_amount').show();
                        $('.labour_discount_amount').html('Discount amount - ₹ ' + $scope.job_order.labour_discount_amount);
                        $('.labour_pay_amount').html('Customer to be paid - ₹ ' + customer_paid_labour_amount);
                    } else {
                        $('.labour_discount_amount').hide();
                        $('.labour_pay_amount').hide();
                        $('.labour_discount_class').val('');
                    }

                    if ($scope.job_order.part_discount_amount && $scope.job_order.part_discount_amount > 0) {
                        $('.part_discount_class').val($scope.job_order.part_discount_amount);
                        var customer_paid_part_amount = $scope.job_order.manual_delivery_parts_invoice.amount - $scope.job_order.part_discount_amount;
                        $('.part_discount_amount').show();
                        $('.part_pay_amount').show();

                        $('.part_discount_amount').html('Discount amount - ₹ ' + $scope.job_order.part_discount_amount);
                        $('.part_pay_amount').html('Customer to be paid - ₹ ' + customer_paid_part_amount);
                    } else {
                        $('.part_discount_amount').hide();
                        $('.part_discount_class').val('');
                        $('.part_pay_amount').hide();
                    }

                    if ($scope.job_order.is_aggregate_work == 1) {
                        self.is_aggregate_work = 1;
                    } else {
                        self.is_aggregate_work = 0;
                    }

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
                    if ($scope.form_save_status == 1) {
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
                    } else {
                        custom_noty('error', 'Kindly check Aggregate Coupon and Aggregate Works!');
                    }
                }
            });
        }

        $scope.vehiclePaymentType = function (payment_type) {
            if (payment_type == 1) {
                // $scope.label_name = "Receipt";
            } else {
                // $scope.label_name = "Transaction";
            }
        }

        // ADD NEW Payment
        self.addNewPayment = function () {
            $scope.job_order.payment_detail.push({});
        }

        self.payment_removal_id = [];
        self.removePayment = function (index, payment_id) {
            if (index != 0) {
                if (payment_id) {
                    self.payment_removal_id.push(payment_id);
                    $('#removal_payment_ids').val(JSON.stringify(self.payment_removal_id));
                }
                $scope.job_order.payment_detail.splice(index, 1);
            }
        }


        $scope.getSelectedPaymentMode = function (payment_mode_id) {
            if (payment_mode_id == 1) {
                $scope.label_name = "Receipt";
            } else {
                $scope.label_name = "Transaction";
            }
        }

        $scope.getSelectedBillingType = function (billing_typing_id) {
            if (billing_typing_id == 11523) {
                $scope.invoice_label_name = "DSP";
            } else {
                $scope.invoice_label_name = "";
            }

            if ($scope.attachment_count == 1) {
                /* Image Uploadify Funtion */
                setTimeout(function () {
                    $('.image_uploadify').imageuploadify();
                }, 1000);
            }

            $scope.attachment_count = 0;
        }

        $scope.vehiclePaymentStatus = function (status) {
            if (status == 1) {

                // /* Image Uploadify Funtion */
                // setTimeout(function () {
                //     $('.image_uploadify').imageuploadify();
                // }, 1000);

                $scope.invoiceAmount();
                $scope.billingAmount();
            }
        }

        $scope.vehicleServiceStatus = function (status) {
            if (status == 1) {
                if ($scope.attachment_count == 1) {
                    /* Image Uploadify Funtion */
                    setTimeout(function () {
                        $('.image_uploadify').imageuploadify();
                    }, 1000);
                }
            }
            $scope.attachment_count = 0;
        }

        $(document).on('keyup', ".amount", function () {
            $scope.invoiceAmount();
            $scope.billingAmount();
        });

        $scope.invoiceAmount = function () {
            setTimeout(function () {
                var labour_amount = $('#labour_invoice_amount').val();
                var parts_amount = $('#parts_invoice_amount').val();

                var total_amount = 0;

                if (!labour_amount || isNaN(labour_amount)) {
                    labour_amount = 0;
                }

                if (!parts_amount || isNaN(parts_amount)) {
                    parts_amount = 0;
                }
                total_amount = parseFloat(labour_amount) + parseFloat(parts_amount);
                total_amount = total_amount.toFixed(2);
                $('.customer_to_be_paid_amount').val(total_amount);
                $('.paid_amount_0').val(total_amount);
                var cash_back_labour_discount_value = 0;
                var cash_back_part_discount_value = 0;
                //Labour discount
                if (labour_amount && $scope.job_order.amc_member && $scope.job_order.amc_member.amc_policy.labour_discount_percentage) {
                    labour_discount_value = (labour_amount * $scope.job_order.amc_member.amc_policy.labour_discount_percentage) / 100;
                    console.log('-----------labour--------------');
                    labour_discount_value = labour_discount_value.toFixed(2);
                    console.log(labour_discount_value);
                    cash_back_labour_discount_value=labour_discount_value;

                    //labour amount after discount
                    labour_amount = labour_amount - labour_discount_value;
                    labour_amount = labour_amount.toFixed(2);
                    console.log(labour_amount);
                    $('.labour_discount_class').val(labour_discount_value);
                    $scope.job_order.labour_discount_amount = labour_discount_value;
                    $('.labour_discount_amount').show();
                    $('.labour_pay_amount').show();
                    $('.labour_discount_amount').html('Discount amount - ₹ ' + labour_discount_value);
                    $('.labour_pay_amount').html('Customer to be paid - ₹ ' + labour_amount);

                } else {
                    $('.labour_discount_class').val('');
                    $scope.job_order.labour_discount_amount = 0;
                    $('.labour_discount_amount').hide();
                    $('.labour_pay_amount').hide();
                }

                //Parts discount
                if (parts_amount && $scope.job_order.amc_member && $scope.job_order.amc_member.amc_policy.part_discount_percentage) {
                    part_discount_value = (parts_amount * $scope.job_order.amc_member.amc_policy.part_discount_percentage) / 100;
                    console.log('-----------part--------------');
                    part_discount_value = part_discount_value.toFixed(2);
                    console.log(part_discount_value);
                    cash_back_part_discount_value=part_discount_value;

                    //part amount after discount
                    parts_amount = parts_amount - part_discount_value;
                    parts_amount = parts_amount.toFixed(2);
                    console.log(parts_amount);
                    $('.part_discount_class').val(part_discount_value);
                    $scope.job_order.part_discount_amount = part_discount_value;
                    $('.part_discount_amount').show();
                    $('.part_pay_amount').show();
                    $('.part_discount_amount').html('Discount amount - ₹ ' + part_discount_value);
                    $('.part_pay_amount').html('Customer to be paid - ₹ ' + parts_amount);
                } else {
                    $('.part_discount_class').val('');
                    $scope.job_order.part_discount_amount = 0;
                    $('.part_discount_amount').hide();
                    $('.part_pay_amount').hide();
                }

                // total_amount = parseFloat(labour_amount) + parseFloat(parts_amount);
                console.log(cash_back_part_discount_value);
                total_cashback_amount = parseFloat(cash_back_labour_discount_value) + parseFloat(cash_back_part_discount_value);
                total_cashback_amount = total_cashback_amount.toFixed(2);
                console.log('-----------cashback--------------');
                console.log(total_cashback_amount);
                // total_amount = total_amount.toFixed(2);
                // total_cashback_amount = total_cashback_amount.toFixed(2);

                // $('.paid_amount_0').val(total_amount);
                // $('.customer_to_be_paid_amount').val(total_amount);

                $('.customer_cashback_amount').val(total_cashback_amount);

                $scope.$apply();
            }, 100);
        }

        $(document).on('keyup', ".receipt_amount", function () {
            $scope.billingAmount();
        });

        $scope.billingAmount = function () {
            setTimeout(function () {
                var labour_amount = $('#labour_invoice_amount').val();
                var parts_amount = $('#parts_invoice_amount').val();
                // var receipt_amount = $('.receipt_amount').val();
                var total_paid_amount = 0;

                var total_amount = 0;

                if (!labour_amount || isNaN(labour_amount)) {
                    labour_amount = 0;
                }

                if (!parts_amount || isNaN(parts_amount)) {
                    parts_amount = 0;
                }

                total_amount = parseFloat(labour_amount) + parseFloat(parts_amount);
                total_amount = total_amount.toFixed(2);
                console.log("Total Amount -- " + total_amount);
                // console.log("Bill Amount -- " + receipt_amount);

                $('.receipt_amount').each(function () {
                    var receipt_amount = parseFloat($(this).closest('tr').find('.receipt_amount').val() || 0);
                    console.log(receipt_amount);
                    if (!$.isNumeric(receipt_amount)) {
                        receipt_amount = 0;
                    }
                    total_paid_amount += receipt_amount;
                });

                var remaining_amount = 0;
                if (total_paid_amount >= total_amount) {
                    // $('.receipt_amount').val(total_amount);
                    self.remarks_status = 0;
                    remaining_amount = 0;
                } else if (total_amount > total_paid_amount) {
                    if (total_paid_amount > 0) {
                        self.remarks_status = 1;
                        remaining_amount = total_amount - total_paid_amount;
                    } else {
                        self.remarks_status = 1;
                        remaining_amount = total_amount;
                    }
                }

                remaining_amount = remaining_amount.toFixed(2);

                $('.customer_paid_amount').val(total_paid_amount);
                $('.customer_paid_remaining_amount').val(remaining_amount);

                $scope.$apply();
            }, 100);
        }

        $scope.getSelectedReason = function (pending_reason_id) {
            if (pending_reason_id == 4) {
                self.customer_status = 1;
            } else {
                self.customer_status = 0;
            }
        }

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

        self.attachment_removal_id = [];
        $scope.remove_attachment = function (attachment_id, index) {
            console.log(attachment_id, index);
            if (attachment_id) {
                self.attachment_removal_id.push(attachment_id);
                $('#attachment_removal_ids').val(JSON.stringify(self.attachment_removal_id));
            }
            $scope.job_order.transcation_attachment.splice(index, 1);
        }

        //Aggregate Coupons
        $(document).on('click', '.select_all_coupons', function () {
            if (event.target.checked == true) {
                $('.couponcheckbox').prop('checked', true);
                $.each($('.couponcheckbox:checked'), function () {
                    $scope.couponCheckbox($(this).val());
                });
            } else {
                $('.couponcheckbox').prop('checked', false);
                $scope.couponValidate();
            }
        });

        $scope.couponCheckbox = function (id) {
            checkval = $('#coupon_check' + id).is(":checked");
            $scope.couponValidate();
        }

        $scope.couponValidate = function () {
            $scope.form_save_status = 1;
            var selected_coupon = 0;
            $.each($('.couponcheckbox:checked'), function () {
                if ($('#coupon_check' + $(this).val()).is(":checked")) {
                    selected_coupon++;
                }
            });

            var selected_aggregate_works = 0;
            $.each($('.workcheckbox:checked'), function () {
                if ($('#check' + $(this).val()).is(":checked")) {
                    selected_aggregate_works++;
                    // alert($(this).val());
                }
            });
            console.log('selected aggregate work --' + selected_aggregate_works);
            console.log('selected coupon --' + selected_coupon);
            if ($scope.job_order.membership_id == 1 || $scope.job_order.membership_id == 7) {
                if (selected_coupon > 5) {
                    $scope.form_save_status = 0;
                    custom_noty('error', 'You have to choose 5 Aggregate coupons only!');
                }

                console.log('-----');

                //Check Engine Work checked or not
                if ($('.aggregate_work_10').is(":checked")) {
                    if (selected_coupon < 3) {
                        $scope.form_save_status = 0;
                        custom_noty('error', 'You have to choose 3 Aggregate coupon to for Engine Overhauling!');
                    }
                    // else {
                    //     $scope.scope.form_save_status = 1;
                    // }

                    selected_aggregate_works = selected_aggregate_works - 1;
                    selected_coupon = selected_coupon - 3;
                }

                console.log('selected aggregate work --' + selected_aggregate_works);
                console.log('selected coupon --' + selected_coupon);

                if (selected_aggregate_works > selected_coupon) {
                    $scope.form_save_status = 0;
                    custom_noty('error', 'Kindly choose one or more aggregate coupon for selected aggregate works');
                }
                //  else {
                //     $scope.form_save_status = 1;
                // }

                if (selected_aggregate_works < selected_coupon) {
                    $scope.form_save_status = 0;
                    custom_noty('error', 'Kindly choose one or more aggregate works for selected aggregate coupon!');
                }
                // else {
                //     $scope.form_save_status = 1;
                // }

            } else if ($scope.job_order.membership_id == 3 || $scope.job_order.membership_id == 8) {
                if (selected_aggregate_works > selected_coupon) {
                    $scope.form_save_status = 0;
                    custom_noty('error', 'Kindly choose one or more aggregate coupon for selected aggregate works');
                }

                if (selected_aggregate_works < selected_coupon) {
                    $scope.form_save_status = 0;
                    custom_noty('error', 'Kindly choose one or more aggregate works for selected aggregate coupon!');
                }

                if (selected_coupon > 2) {
                    $scope.form_save_status = 0;
                    custom_noty('error', 'You have to choose 2 Aggregate coupons only!');
                }
            }
        }

        //Aggregate Works
        $(document).on('click', '.select_all_works', function () {
            if (event.target.checked == true) {
                $('.workcheckbox').prop('checked', true);
                $.each($('.workcheckbox:checked'), function () {
                    $scope.checkCheckbox($(this).val());
                    $('.work_details_table tbody tr #in_' + $(this).val()).removeClass('ng-hide');
                    $('.work_details_table tbody tr #checked_' + $(this).val()).val('1');
                    $('.work_details_table tbody tr #in_' + $(this).val()).addClass('error');
                    $('.work_details_table tbody tr #in_' + $(this).val()).addClass('required');
                });
            } else {
                $('.workcheckbox').prop('checked', false);
                $.each($('.workcheckbox'), function () {
                    $('.work_details_table tbody tr #in_' + $(this).val()).addClass('ng-hide');
                    $('.work_details_table tbody tr #in_' + $(this).val() + '-error').remove();
                    $('.work_details_table tbody tr #in_' + $(this).val()).removeClass('error');
                    $('.work_details_table tbody tr #in_' + $(this).val()).removeClass('required');
                    $('.work_details_table tbody tr #in_' + $(this).val()).closest('.form-group').find('label.error').remove();
                    $('.work_details_table tbody tr #in_' + $(this).val()).val('');
                    $('.work_details_table tbody tr #checked_' + $(this).val()).val('0');
                });
                $scope.couponValidate();
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
            $scope.couponValidate();
        }

        setTimeout(function () {
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