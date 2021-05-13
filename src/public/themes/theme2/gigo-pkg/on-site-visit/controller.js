app.component('onSiteVisitList', {
    templateUrl: on_site_visit_list_template_url,
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
        var dataTable = $('#on_site_visit_list').DataTable({
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
                url: laravel_routes['getOnSiteVisitList'],
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
                data: 'outlet_code',
                name: 'outlets.code'
            },
            {
                data: 'customer_name',
                name: 'customers.name'
            },
            {
                data: 'number',
                name: 'on_site_orders.number'
            },
            {
                data: 'se_name',
                name: 'users.name'
            },
            {
                data: 'se_mobile',
                name: 'users.contact_number'
            },
            {
                data: 'status',
                name: 'on_site_order_statuses.name'
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
            $('#on_site_visit_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function () {
            $('#on_site_visit_list').DataTable().ajax.reload();
        });

        var dataTables = $('#on_site_visit_list').dataTable();
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

app.component('onSiteVisitView', {
    templateUrl: on_site_visit_view_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect, $window, RepairOrderSvc, SplitOrderTypeSvc, PartSvc, $q) {
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
                url: base_url + '/api/on-site-visit/get-form-data',
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

                    $scope.site_visit = res.site_visit;
                    $scope.extras = res.extras;
                    $scope.site_visit.photos1 = [];

                    $scope.customer = $scope.site_visit ? $scope.site_visit.customer : [];
                    console.log($scope.customer);
                    $scope.part_details = res.part_details;
                    $scope.labour_details = res.labour_details;
                    $scope.total_amount = res.total_amount;
                    $scope.labour_amount = res.labour_amount;
                    $scope.parts_rate = res.parts_rate;
                    $scope.labours = res.labours;
                    $scope.amc_customer_status = res.amc_customer_status;
                    $scope.not_approved_labour_parts_count = res.not_approved_labour_parts_count;

                    $scope.outlet_id = $scope.site_visit ? $scope.site_visit.outlet_id : self.user.working_outlet_id;
                    self.country = res.country;

                    console.log($scope.site_visit);

                    $scope.bfiConfig = {
                        theme: 'fas',
                        overwriteInitial: true,
                        // minFileCount: 1,
                        maxFileSize: 12000, //2048,
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
                        slugCallback: function (filename) {
                            return filename.replace('(', '_').replace(']', '_');
                        }
                    };

                    $scope.$apply();

                    $scope.fetchPartsData();
                    $scope.fetchTimeLogData();

                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //FETCH PARTS DATA
        $scope.fetchPartsData = function () {
            $.ajax({
                url: base_url + '/api/on-site-visit/get-parts-data',
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

                    $scope.part_logs = res.part_logs;
                    $scope.on_site_order_parts = res.on_site_order_parts;

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        //FETCH TIME LOG DATA
        $scope.fetchTimeLogData = function () {
            $.ajax({
                url: base_url + '/api/on-site-visit/get/time-log',
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

                    $scope.travel_logs = res.travel_logs;
                    $scope.work_logs = res.work_logs;
                    $scope.total_travel_hours = res.total_travel_hours;
                    $scope.total_work_hours = res.total_work_hours;

                    $scope.travel_start_button_status = res.travel_start_button_status;
                    $scope.travel_end_button_status = res.travel_end_button_status;
                    $scope.work_start_button_status = res.work_start_button_status;
                    $scope.work_end_button_status = res.work_end_button_status;

                    $('.save_labour').button('reset');
                    $('.save_part').button('reset');
                    $('.returned_button').button('reset');
                    $('.send_confirm').button('reset');
                    $('.returned_button').button('reset');
                    $('.delete_lbr_parts').button('reset');
                    $('.work_log_action').button('reset');
                    $('.part_labour_delete').button('reset');
                    $('.send_otp_confirm').button('reset');
                    $('.submit_otp_confirm').button('reset');
                    $('.submit').button('reset');
                    $('.start_labour_part').button('reset');
                    $('.send_amc_confirm').button('reset');
                    $('.amc_customer_save').button('reset');
                    $('#confirm_start_labour_part_modal').modal('hide');
                    $('#labour_form_modal').modal('hide');
                    $('#part_form_modal').modal('hide');
                    $("#confirmation_modal").modal('hide');
                    $("#amc_confirmation_modal").modal('hide');
                    $("#amc_update_modal").modal('hide');
                    $("#billing_confirmation_modal").modal('hide');
                    $("#estimate_confirmation_modal").modal('hide');
                    $("#work_complete_confirmation_modal").modal('hide');
                    $('#send_otp_customer_modal').modal('hide');
                    $('#otp').modal('hide');
                    $('#otp_no').val('');
                    $("#send_customer_modal").modal('hide');
                    $('#part_return_modal').modal('hide');
                    $('#delete_log').modal('hide');
                    $("#confirm_work_log").modal('hide');
                    $('#delete_labour_parts').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.addPhoto = function () {
            $scope.site_visit.photos1.push($scope.site_visit.photos1.length + 1);
            // $(".addPhotoBtn").hide();
        }
        $scope.removeUploader = function (key) {
            $scope.site_visit.photos1.splice(key, 1);
            // $(".addPhotoBtn").show();
        }

        self.attachment_removal_id = [];
        $scope.remove_attachment = function (attachment_id, index) {
            if (attachment_id) {
                self.attachment_removal_id.push(attachment_id);
                $('#attachment_removal_ids').val(JSON.stringify(self.attachment_removal_id));
            }
            $scope.site_visit.photos.splice(index, 1);
        }

        $scope.searchRepairOrders = function (query) {
            if (query) {
                return new Promise(function (resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getRepairOrderSearchList'], {
                            key: query,
                            business_id: 11,
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

        $scope.showLabourForm = function (labour_index, labour = null) {
            console.log(labour);
            $scope.on_site_order_ro = [];
            $scope.on_site_repair_order_id = '';
            if (labour_index === false) {
                // $scope.labour_details = {};
            } else {
                if (labour.split_order_type_id != null) {
                    $scope.on_site_repair_order_id = labour.id;
                    if (labour.split_order_type_id == undefined) {
                        $split_id = labour.pivot.split_order_type_id;
                    } else {
                        $split_id = labour.split_order_type_id;
                    }
                    SplitOrderTypeSvc.read($split_id)
                        .then(function (response) {
                            $scope.on_site_order_ro.split_order_type = response.data.split_order_type;
                        });
                }
                if (labour.category == undefined) {
                    RepairOrderSvc.read(labour.labour_id)
                        .then(function (response) {
                            console.log(response);
                            $scope.on_site_order_ro.repair_order = response.data.repair_order;

                            // if (labour.repair_order.is_editable == 1) {
                            $scope.on_site_order_ro.repair_order.amount = labour.amount;
                            // }
                        });
                }

                $scope.on_site_order_ro.repair_order = labour;

                if (labour.amount == 0) {
                    $scope.on_site_order_ro.repair_order.amount = labour.rate;
                }
            }

            $scope.labour_index = labour_index;
            $scope.labour_modal_action = labour_index === false ? 'Add' : 'Edit';
            $('#labour_form_modal').modal('show');
            console.log($scope.on_site_order_ro);
            // console.log($scope.on_site_order_ro.repair_order);
            // console.log($scope.on_site_order_ro.repair_order.repair_order_type);
        }

        $scope.init = function () {
            $rootScope.loading = true;
            let promises = {
                split_order_type_options: SplitOrderTypeSvc.options(),
            };

            $scope.options = {};
            $q.all(promises)
                .then(function (responses) {
                    $scope.options.split_order_types = responses.split_order_type_options.data.options;
                    $rootScope.loading = false;

                });

            setTimeout(function () {
                // $scope.calculateLabourTotal();
                // $scope.calculatePartTotal();
            }, 2000);
        };
        $scope.init();

        //Save Labour
        $scope.saveLabour = function () {
            var form_id = '#labour_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'rot_id': {
                        required: true,
                    },
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_labour').button('loading');
                    $.ajax({
                        url: base_url + '/api/on-site-visit/repair-order/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            if (!res.success) {
                                $('.save_labour').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $scope.fetchData();
                        })
                        .fail(function (xhr) {
                            $('.save_labour').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.searchParts = function (query) {
            return new Promise(function (resolve, reject) {
                PartSvc.options({
                    filter: {
                        search: query
                    }
                })
                    .then(function (response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.partSelected = function (part) {
            console.log(part);
            $qty = 1;
            if (!part) {
                return;
            } else {
                if (part.qty) {
                    $qty = part.qty;
                }
            }

            $.ajax({
                url: base_url + '/api/on-site-visit/get/part/stock-details',
                method: "POST",
                data: {
                    outlet_id: $scope.outlet_id,
                    part_id: part.id,
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
            })
                .done(function (response) {
                    console.log(response)
                    $local_purchase_part = '(L)';
                    $part_code = response.data.part.code;

                    if ($part_code.indexOf($local_purchase_part) != -1) {
                        $scope.on_site_part.part.mrp = 0;
                        $scope.mrp_change = 1;
                    } else {
                        $scope.on_site_part.part.mrp = response.data.part.part_stock ? response.data.part.part_stock.mrp : '0';
                        $scope.mrp_change = 0;
                    }

                    if (part.id == $scope.part_id) {
                        $scope.on_site_part.part.mrp = $scope.part_mrp;
                    }

                    $scope.available_quantity = response.data.part.part_stock ? response.data.part.part_stock.stock : '0';

                    $scope.on_site_part.part.qty = $qty;

                    $scope.calculatePartAmount();
                    console.log($scope.on_site_part);

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.calculatePartAmount = function () {
            $scope.on_site_part.part.total_amount = $scope.on_site_part.part.qty * $scope.on_site_part.part.mrp;
        }

        $scope.showPartForm = function (part_index, part = null) {
            console.log(part);
            $scope.part_mrp = 0;
            $scope.part_id = '';
            $scope.on_site_part = [];
            $scope.on_site_part_id = '';
            $scope.mrp_change = 0;
            if (part_index === false) {
                // $scope.part_details = {};
            } else {
                // $scope.part_mrp = part.total_amount;
                $scope.part_mrp = part.rate;
                $scope.part_id = part.part_id;
                $scope.on_site_part_id = part.id;

                if (part.split_order_type_id != null) {
                    if (part.split_order_type_id == undefined) {
                        $split_id = part.pivot.split_order_type_id;
                    } else {
                        $split_id = part.split_order_type_id;
                    }
                    SplitOrderTypeSvc.read($split_id)
                        .then(function (response) {
                            $scope.on_site_part.split_order_type = response.data.split_order_type;
                        });
                }

                if (part.uom == undefined) {
                    PartSvc.getFormData({
                        outletId: $scope.outlet_id,
                        partId: part.part_id
                    })
                        .then(function (response) {
                            $scope.on_site_part.part = response.data.part;
                            $scope.on_site_part.part.qty = part.qty;
                        }).catch(function (error) {
                            console.log(error);
                        });
                }
                $scope.on_site_part.part = part;
            }

            $scope.part_index = part_index;
            $scope.part_modal_action = part_index === false ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
        }

        $scope.savePart = function () {
            var form_id = '#part_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'part_id': {
                        required: true,
                    },
                    'qty': {
                        required: true,
                        number: true,
                    },
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_part').button('loading');
                    $.ajax({
                        url: base_url + '/api/on-site-visit/parts/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            if (!res.success) {
                                $('.save_part').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $scope.fetchData();
                        })
                        .fail(function (xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.startLabour = function (index, labour, type) {
            console.log(index, labour, type);
            $scope.confirmLabourPart(index, labour, type);
        }

        $scope.issuePart = function (index, part, type) {
            console.log(index, part, type);
            $scope.confirmLabourPart(index, part, type);
        }

        $scope.confirmLabourPart = function (index, value, type) {
            $scope.labour_part_name = value.code + ' / ' + value.name;
            $scope.labour_part_type = type;
            $('#labour_parts_id').val(value.id);
            $('#confirm_start_labour_part_modal').modal('show');
            $('#labour_part_type').val(type);

            $scope.saveLabourPart = function () {
                $('.start_labour_part').button('loading');
                $.ajax({
                    url: base_url + '/api/on-site-visit/labour-part/process',
                    method: "POST",
                    data: {
                        id: value.id,
                        type: type,
                    },
                })
                    .done(function (res) {
                        if (!res.success) {
                            $('.start_labour_part').button('reset');
                            showErrorNoty(res);
                            return;
                        }
                        custom_noty('success', res.message);
                        $scope.fetchData();
                    })
                    .fail(function (xhr) {
                        $('.start_labour_part').button('reset');
                    });
            }
        }

        $scope.sendConfirm = function (type_id) {
            $('.send_confirm').button('loading');
            $.ajax({
                url: base_url + '/api/on-site-visit/request/parts',
                method: "POST",
                data: {
                    id: $scope.site_visit.id,
                    type_id: type_id,
                },
            })
                .done(function (res) {
                    if (!res.success) {
                        $('.send_confirm').button('reset');
                        showErrorNoty(res);
                        return;
                    }
                    custom_noty('success', res.message);
                    $scope.fetchData();
                })
                .fail(function (xhr) {
                    $('.send_confirm').button('reset');
                });
        }

        $scope.sendOTPConfirm = function () {
            $('.send_otp_confirm').button('loading');
            $('.resend_otp').button('loading');

            $.ajax({
                url: base_url + '/api/on-site-visit/request/otp',
                method: "POST",
                data: {
                    id: $scope.site_visit.id,
                },
            })
                .done(function (res) {
                    $('.resend_otp').button('reset');
                    if (!res.success) {
                        $('.send_otp_confirm').button('reset');
                        showErrorNoty(res);
                        return;
                    }
                    custom_noty('success', res.message);
                    $('#send_otp_customer_modal').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $('#otp').modal('show');
                    $('.send_otp_confirm').button('reset');
                    $('.resend_otp').button('reset');
                })
                .fail(function (xhr) {
                    $('.send_otp_confirm').button('reset');
                });
        }

        //SAVE OTP
        $scope.saveOTP = function (id) {
            var form_id = '#approve_behalf_customer_confirm';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'otp_no': {
                        required: true,
                        number: true,
                        minlength: 6,
                        maxlength: 6,
                    },
                },
                messages: {
                    'otp_no': {
                        required: 'OTP is required',
                        number: 'OTP Must be a number',
                        minlength: 'OTP Minimum 6 Characters',
                        maxlength: 'OTP Maximum 6 Characters',
                    },
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit_otp_confirm').button('loading');
                    $.ajax({
                        url: base_url + '/api/on-site-visit/verify/otp',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                    })
                        .done(function (res) {
                            if (!res.success) {
                                showErrorNoty(res);
                                $('.submit_otp_confirm').button('reset');
                                $('#otp_no').val('');
                                $('#otp_no').focus();
                                return;
                            }
                            console.log(res);
                            custom_noty('success', res.message);

                            $scope.fetchData();
                        })
                        .fail(function (xhr) {
                            console.log(xhr);
                            $('#otp_no').val('');
                            $('.submit_confirm').button('reset');
                            showServerErrorNoty();
                        });
                }
            });
        }

        //Save Labour
        $scope.saveReturnedForm = function () {
            var form_id = '#return-part-form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'rot_id': {
                        required: true,
                    },
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.returned_button').button('loading');
                    $.ajax({
                        url: base_url + '/api/on-site-visit/return/parts',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            if (!res.success) {
                                $('.returned_button').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $scope.fetchData();
                        })
                        .fail(function (xhr) {
                            $('.returned_button').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.removeLog = function (index, log) {
            console.log(log);
            $('#delete_log').modal('show');
            $('#log_id').val(log.job_order_part_issue_return_id);
            $('#log_type').val(log.transaction_type);
        }

        $scope.deleteConfirm = function () {
            $id = $('#log_id').val();
            $type = $('#log_type').val();

            let formData = new FormData();
            formData.append('id', $id);
            formData.append('type', $type);
            $('.delete_lbr_parts').button('loading');
            $.ajax({
                url: base_url + '/api/on-site-visit/delete/issue-return/parts',
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
            })
                .done(function (res) {
                    if (!res.success) {
                        $rootScope.loading = false;
                        $('.delete_lbr_parts').button('reset');
                        showErrorNoty(res);
                        return;
                    }
                    custom_noty('success', res.message);
                    $scope.fetchData();
                })
                .fail(function (xhr) {
                    $('.delete_lbr_parts').button('reset');
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });

        }

        $scope.saveWorkLog = function (id, work_log_type) {
            console.log(id, work_log_type);
            $('#confirm_work_log').modal('show');
            $('#work_log_id').val(id);
            $('#work_log_type').val(work_log_type);
            if (work_log_type == 'travel_log') {
                if (id == 1) {
                    $scope.log_action = 'start';
                    $scope.log_action_type = 'travel';
                    $scope.modal_type = 'success';
                } else {
                    $scope.log_action = 'stop';
                    $scope.log_action_type = 'travel';
                    $scope.modal_type = 'failure';
                }
            } else {
                if (id == 1) {
                    $scope.log_action = 'start';
                    $scope.log_action_type = 'work';
                    $scope.modal_type = 'success';
                } else {
                    $scope.log_action = 'stop';
                    $scope.log_action_type = 'work';
                    $scope.modal_type = 'failure';
                }
            }
        }

        //Save Worklog
        $scope.saveWorkLogConfirm = function () {
            // if (work_log_type == 'travel_log') {
            //     if (id == 1) {
            //         $('.start_travel').button('loading');
            //     }else{
            //         $('.end_travel').button('loading');
            //     }
            // }else{
            //     if (id == 1) {
            //         $('.start_work').button('loading');
            //     }else{
            //         $('.end_end').button('loading');
            //     }
            // }
            var id = $('#work_log_id').val();
            var work_log_type = $('#work_log_type').val();

            if (id && work_log_type) {
                $('.work_log_action').button('loading');
                $.ajax({
                    url: base_url + '/api/on-site-visit/save/time-log',
                    method: "POST",
                    data: {
                        on_site_order_id: $scope.site_visit.id,
                        type_id: id,
                        work_log_type: work_log_type,
                    },
                })
                    .done(function (res) {
                        if (!res.success) {
                            $('.work_log_action').button('reset');
                            showErrorNoty(res);
                            return;
                        }
                        custom_noty('success', res.message);
                        $scope.fetchData();
                    })
                    .fail(function (xhr) {
                        $('.work_log_action').button('reset');
                    });
            }
        }

        //Save Form Data 
        $scope.saveOnSiteVisit = function () {
            var form_id = '#on_site_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'se_remarks': {
                        required: true,
                    },
                    'parts_requirements': {
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
                        url: base_url + '/api/on-site-visit/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $scope.fetchData();
                        })
                        .fail(function (xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.removePart = function (index, id, type) {
            console.log(index, id, type);
            if (id == undefined) {
                $scope.part_details.splice(index, 1);
            } else {
                $scope.delete_reason = 10021;
                $('#removal_reason').val('');
                $scope.customer_delete = false;
                $scope.laboutPartsDelete(index, id, type);
            }
        }

        $scope.removeLabour = function (index, id, type) {
            console.log(index, id, type);
            if (id == undefined) {
                $scope.labour_details.splice(index, 1);
            } else {
                $scope.delete_reason = 10021;
                $('#removal_reason').val('');
                //HIDE REASON TEXTAREA 
                $scope.customer_delete = false;
                $scope.laboutPartsDelete(index, id, type);
            }
        }

        $scope.laboutPartsDelete = function (index, id, type) {
            $('#delete_labour_parts').modal('show');
            $('#labour_parts_id').val(id);
            $('#payable_type').val(type);

            $scope.saveLabourPartDeleteForm = function () {
                var delete_form_id = '#labour_parts_remove';
                $('.part_labour_delete').button('loading');
                var v = jQuery(delete_form_id).validate({
                    ignore: '',
                    rules: {
                        'removal_reason_id': {
                            required: true,
                        },
                        'removal_reason': {
                            required: true,
                        },
                    },
                    errorPlacement: function (error, element) {
                        if (element.attr("name") == "removal_reason_id") {
                            error.appendTo('#errorDeleteReasonRequired');
                            return;
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    submitHandler: function (form) {
                        let formData = new FormData($(delete_form_id)[0]);
                        $rootScope.loading = true;
                        $.ajax({
                            url: base_url + '/api/on-site-visit/labour-parts/delete',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                            .done(function (res) {
                                if (!res.success) {
                                    $rootScope.loading = false;
                                    $('.part_labour_delete').button('reset');
                                    showErrorNoty(res);
                                    return;
                                }
                                $scope.fetchData();
                                custom_noty('success', res.message);
                            })
                            .fail(function (xhr) {
                                $rootScope.loading = false;
                                $('.part_labour_delete').button('reset');
                                custom_noty('error', 'Something went wrong at server');
                            });
                    }
                });

            }
        }

        //AMC
        $scope.sendAMCConfirm = function (type_id) {
            $('.send_amc_confirm').button('loading');
            $.ajax({
                url: base_url + '/api/on-site-visit/amc-customer/save',
                method: "POST",
                data: {
                    id: $scope.site_visit.id,
                    type_id: type_id,
                },
            })
                .done(function (res) {
                    if (!res.success) {
                        $('.send_amc_confirm').button('reset');
                        showErrorNoty(res);
                        return;
                    }
                    custom_noty('success', res.message);
                    $scope.fetchData();
                })
                .fail(function (xhr) {
                    $('.send_amc_confirm').button('reset');
                });
        }

        //Save AMC Customer Details
        $scope.saveAMCCustomerDetails = function () {
            var form_id = '#amc_customer_save';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'customer_id': {
                        required: true,
                    },
                    'amc_customer_code': {
                        required: true,
                    },
                    'amc_starting_date': {
                        required: true,
                    },
                    'amc_ending_date': {
                        required: true,
                    },
                },
                messages: {},
                invalidHandler: function (event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.amc_customer_save').button('loading');
                    $.ajax({
                        url: base_url + '/api/on-site-visit/amc-customer/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            if (!res.success) {
                                $('.amc_customer_save').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $scope.fetchData();
                        })
                        .fail(function (xhr) {
                            $('.amc_customer_save').button('reset');
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

app.component('onSiteVisitForm', {
    templateUrl: on_site_visit_form_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect, CustomerSvc, RepairOrderSvc, SplitOrderTypeSvc, PartSvc, $q) {
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
        console.log(self.user);
        $scope.job_order_id = $routeParams.job_order_id;
        $scope.label_name = "Receipt";
        $scope.attachment_count = 1;
        self.customer_search_type = true;

        //FETCH DATA
        $scope.fetchData = function () {
            $.ajax({
                url: base_url + '/api/on-site-visit/get-form-data',
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

                    $scope.site_visit = res.site_visit;
                    $scope.extras = res.extras;

                    $scope.customer = $scope.site_visit ? $scope.site_visit.customer : [];
                    console.log($scope.customer);
                    $scope.part_details = res.part_details;
                    $scope.labour_details = res.labour_details;
                    $scope.total_amount = res.total_amount;
                    $scope.labour_amount = res.labour_amount;
                    $scope.parts_rate = res.parts_rate;
                    $scope.labours = res.labours;

                    /* Image Uploadify Funtion */
                    setTimeout(function () {
                        $('.image_uploadify').imageuploadify();
                    }, 1000);

                    $scope.outlet_id = $scope.site_visit ? $scope.site_visit.outlet_id : self.user.working_outlet_id;
                    self.country = res.country;

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
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
                        console.log(response);
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
                    console.log($scope.extras.state_list);
                    //ADD NEW OWNER TYPE
                    // if ($scope.type_id == 2) {
                    //     self.state = $scope.job_order.state;
                    // } else {
                    // if (!$scope.customer) {
                    // self.state = $scope.job_order.state;
                    // } else {
                    self.state = $scope.customer ? $scope.customer.address.state : [];
                    // }
                    // }

                    $scope.$apply();
                })
                .fail(function (xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
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

        $scope.searchRepairOrders = function (query) {
            return new Promise(function (resolve, reject) {
                RepairOrderSvc.options({
                    filter: {
                        search: query
                    }
                })
                    .then(function (response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.showLabourForm = function (labour_index, labour = null) {
            $scope.on_site_order_ro = [];
            $scope.on_site_repair_order_id = '';
            if (labour_index === false) {
                // $scope.labour_details = {};
            } else {
                if (labour.split_order_type_id != null) {
                    $scope.on_site_repair_order_id = labour.id;
                    if (labour.split_order_type_id == undefined) {
                        $split_id = labour.pivot.split_order_type_id;
                    } else {
                        $split_id = labour.split_order_type_id;
                    }
                    SplitOrderTypeSvc.read($split_id)
                        .then(function (response) {
                            $scope.on_site_order_ro.split_order_type = response.data.split_order_type;
                        });
                }
                if (labour.category == undefined) {
                    RepairOrderSvc.read(labour.labour_id)
                        .then(function (response) {
                            $scope.on_site_order_ro.repair_order = response.data.repair_order;

                            if (labour.repair_order.is_editable == 1) {
                                $scope.on_site_order_ro.repair_order.amount = labour.amount;
                            }

                        });
                }
                $scope.on_site_order_ro.repair_order = labour;
            }

            $scope.labour_index = labour_index;
            $scope.labour_modal_action = labour_index === false ? 'Add' : 'Edit';
            $('#labour_form_modal').modal('show');
        }

        $scope.init = function () {
            $rootScope.loading = true;
            let promises = {
                split_order_type_options: SplitOrderTypeSvc.options(),
            };

            $scope.options = {};
            $q.all(promises)
                .then(function (responses) {
                    $scope.options.split_order_types = responses.split_order_type_options.data.options;
                    $rootScope.loading = false;

                });

            setTimeout(function () {
                // $scope.calculateLabourTotal();
                // $scope.calculatePartTotal();
            }, 2000);
        };
        $scope.init();

        //Save Form Data 
        $scope.saveOnSiteVisit = function () {
            var form_id = '#on_site_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'customer_remarks': {
                        required: true,
                    },
                    'planned_visit_date': {
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
                        url: base_url + '/api/on-site-visit/save',
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
                            $location.path('/on-site-visit/table-list');

                            $scope.$apply();
                        })
                        .fail(function (xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //Save Labour
        $scope.saveLabour = function () {
            var form_id = '#labour_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'rot_id': {
                        required: true,
                    },
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_labour').button('loading');
                    $.ajax({
                        url: base_url + '/api/on-site-visit/repair-order/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            if (!res.success) {
                                $('.save_labour').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.save_labour').button('reset');
                            custom_noty('success', res.message);
                            $('#labour_form_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            $scope.fetchData();
                        })
                        .fail(function (xhr) {
                            $('.save_labour').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.searchParts = function (query) {
            return new Promise(function (resolve, reject) {
                PartSvc.options({
                    filter: {
                        search: query
                    }
                })
                    .then(function (response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.partSelected = function (part) {
            $qty = 1;
            if (!part) {
                return;
            } else {
                if (part.qty) {
                    $qty = part.qty;
                }
            }
            PartSvc.getFormData({
                outletId: $scope.outlet_id,
                partId: part.id
            })
                .then(function (response) {
                    console.log(response);

                    $local_purchase_part = '(L)';
                    $part_code = response.data.part.code;

                    if ($part_code.indexOf($local_purchase_part) != -1) {
                        $scope.on_site_part.part.mrp = 0;
                        $scope.mrp_change = 1;
                    } else {
                        $scope.on_site_part.part.mrp = response.data.part.part_stock ? response.data.part.part_stock.mrp : '0';
                        $scope.mrp_change = 0;
                    }

                    if (part.id == $scope.part_id) {
                        $scope.on_site_part.part.mrp = $scope.part_mrp;
                    }

                    $scope.on_site_part.part.total_amount = response.data.part.part_stock ? response.data.part.part_stock.mrp : '0';
                    $scope.available_quantity = response.data.part.part_stock ? response.data.part.part_stock.stock : '0';
                    $scope.on_site_part.part.qty = $qty;
                    $scope.calculatePartAmount();
                }).catch(function (error) {
                    console.log(error);
                });
        }

        $scope.calculatePartAmount = function () {
            $scope.on_site_part.part.total_amount = $scope.on_site_part.part.qty * $scope.on_site_part.part.mrp;
        }

        $scope.showPartForm = function (part_index, part = null) {
            // console.log(part);
            $scope.part_mrp = 0;
            $scope.part_id = '';
            self.part_customer_voice_id = '';
            self.repair_order_ids = [];
            // $scope.job_order.repair_order = [];
            $scope.on_site_part = [];
            $scope.on_site_part_id = '';
            if (part_index === false) {
                // $scope.part_details = {};
            } else {
                self.part_customer_voice_id = part.customer_voice_id;
                $scope.part_mrp = part.rate;
                $scope.part_id = part.part_id;
                $scope.on_site_part_id = part.id;

                angular.forEach(part.repair_order, function (rep_order, key) {
                    self.repair_order_ids.push(rep_order.id)
                });

                $scope.repair_orders = part.repair_order;
                if (part.split_order_type_id != null) {
                    if (part.split_order_type_id == undefined) {
                        $split_id = part.pivot.split_order_type_id;
                    } else {
                        $split_id = part.split_order_type_id;
                    }
                    SplitOrderTypeSvc.read($split_id)
                        .then(function (response) {
                            $scope.on_site_part.split_order_type = response.data.split_order_type;
                        });
                }
                if (part.uom == undefined) {
                    PartSvc.getFormData({
                        outletId: $scope.outlet_id,
                        partId: part.part_id
                    })
                        .then(function (response) {
                            $scope.on_site_part.part = response.data.part;
                            $scope.on_site_part.part.qty = part.qty;
                        }).catch(function (error) {
                            console.log(error);
                        });
                }
                $scope.on_site_part.part = part;
            }

            $scope.part_index = part_index;
            $scope.part_modal_action = part_index === false ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
        }

        $scope.savePart = function () {
            var form_id = '#part_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'part_id': {
                        required: true,
                    },
                    'qty': {
                        required: true,
                        number: true,
                    },
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_part').button('loading');
                    $.ajax({
                        url: base_url + '/api/on-site-visit/parts/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            if (!res.success) {
                                $('.save_part').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.save_part').button('reset');
                            custom_noty('success', res.message);
                            $('#part_form_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            $scope.fetchData();
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

app.component('onSiteVisitIssueBulkPart', {
    templateUrl: on_site_visit_part_bulk_issue_form_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        //for md-select search
        $element.find('input').on('keydown', function (ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-manual-vehicle-delivery') && !self.hasPermission('edit-manual-vehicle-delivery')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();
        console.log(self.user);

        //FETCH DATA
        $scope.fetchData = function () {
            $.ajax({
                url: base_url + '/api/on-site-visit/get-bulk-form-data',
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

                    $scope.site_visit = res.site_visit;

                    $scope.on_site_order_parts = res.on_site_order_parts;
                    $scope.mechanic_id = res.mechanic_id;

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

        $(document).on('click', '.select_all_parts', function () {
            if (event.target.checked == true) {
                $('.partcheckbox').prop('checked', true);
                $.each($('.partcheckbox:checked'), function () {
                    $scope.checkCheckbox($(this).val());
                    $('.parts_details_table tbody tr #in_' + $(this).val()).removeClass('ng-hide');
                    $('.parts_details_table tbody tr #checked_' + $(this).val()).val('1');
                    $('.parts_details_table tbody tr #in_' + $(this).val()).addClass('error');
                    $('.parts_details_table tbody tr #in_' + $(this).val()).addClass('required');
                });
            } else {
                $('.partcheckbox').prop('checked', false);
                $.each($('.partcheckbox'), function () {
                    $('.parts_details_table tbody tr #in_' + $(this).val()).addClass('ng-hide');
                    $('.parts_details_table tbody tr #in_' + $(this).val() + '-error').remove();
                    $('.parts_details_table tbody tr #in_' + $(this).val()).removeClass('error');
                    $('.parts_details_table tbody tr #in_' + $(this).val()).removeClass('required');
                    $('.parts_details_table tbody tr #in_' + $(this).val()).closest('.form-group').find('label.error').remove();
                    $('.parts_details_table tbody tr #in_' + $(this).val()).val('');
                    $('.parts_details_table tbody tr #checked_' + $(this).val()).val('0');
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

        $scope.saveIssueForm = function () {
            var form = '#issue_bulk_part_form';
            var v = jQuery(form).validate({
                ignore: '',
                rules: {
                    'on_site_order_id': {
                        required: true,
                    },
                },
                messages: {

                },
                invalidHandler: function (event, validator) {
                    custom_noty('error', 'You have errors, Kindly fix');
                },
                submitHandler: function (form) {
                    let formData = new FormData($(form)[0]);
                    $('.submit').button('loading');

                    $.ajax({
                        url: base_url + '/api/on-site-visit/bulk-form-data/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                        .done(function (res) {
                            $('.submit').button('reset');
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/on-site-visit/view/' + $scope.site_visit.id);

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