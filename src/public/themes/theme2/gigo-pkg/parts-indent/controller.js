app.component('partsIndentList', {
    templateUrl: parts_indent_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#parts_indent_table').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('parts-indent')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        self.add_permission = self.hasPermission('parts-indent');
        var table_scroll;
        self.search_key = '';
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#parts_indent_table').DataTable({
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
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    self.search_key = state_save_val.search.search;
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getPartsindentList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.job_order_no = $('#job_order_no').val();
                    d.job_order_date = $('#job_order_date').val();
                    d.job_card_no = $('#job_card_no').val();
                    d.job_card_date = $('#job_card_date').val();
                    d.customer_id = $('#customer_id').val();
                    d.outlet_id = $('#outlet_id').val();
                    d.status_id = $('#status_id').val();
                },
            },
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'job_order_number', name: 'job_orders.number', searchable: true },
                { data: 'job_order_date_time', searchable: false },
                // { data: 'job_card_number', name: 'job_cards.job_card_number' , searchable: true },
                // { data: 'job_card_date_time', searchable: false },
                { data: 'requested_qty', searchable: false },
                { data: 'issued_qty', searchable: false },
                { data: 'service_advisor', name: 'users.name', searchable: true },
                { data: 'floor_supervisor', name: 'users.name', searchable: true },
                { data: 'customer_name', name: 'customers.name' },
                { data: 'state_name', name: 'states.name', searchable: true },
                { data: 'region_name', name: 'regions.name', searchable: true },
                { data: 'outlet_name', name: 'outlets.code', searchable: true },
                { data: 'status', name: 'configs.name', searchable: true },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            self.search_key = '';
            $('#parts_indent_table').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#parts_indent_table').DataTable().ajax.reload();
        });

        var dataTables = $('#parts_indent_table').dataTable();
        $scope.searchPartIndent = function() {
            dataTables.fnFilter(self.search_key);
        }

        //FOR FILTER
        $http.get(
            laravel_routes['getPartsIndentFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
        });

        //GET CUSTOMER LIST
        self.searchCustomer = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getCustomerSearchList'], {
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

        //GET OUTLET LIST
        self.searchOutlet = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getOutletSearchList'], {
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
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';
            $scope.searchTerm3 = '';
        };
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
        $scope.selectedCustomer = function(id) {
            $('#customer_id').val(id);
        }
        $scope.selectedOutlet = function(id) {
            $('#outlet_id').val(id);
        }
        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
        }
        $scope.applyFilter = function() {
            dataTables.fnFilter();
            $('#indent_parts_filter').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#job_card_no").val('');
            $("#job_card_date").val('');
            $("#job_order_no").val('');
            $("#job_order_date").val('');
            $("#customer_id").val('');
            $("#outlet_id").val('');
            $("#status_id").val('');
            dataTables.fnFilter();
            $('#indent_parts_filter').modal('hide');
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('partsIndentVehicleView', {
    templateUrl: parts_indent_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('view-parts-indent')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/part-indent/get-vehicle-detail',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;

                    if (res.job_order.job_card) {
                        if (res.job_order.job_card.status_id == '8227') {
                            $scope.status = "Waiting for Parts Confirmation";
                        } else if (res.job_order.job_card.status_id == '8224' || res.job_order.job_card.status_id == '8225' || res.job_order.job_card.status_id == '8226') {
                            $scope.status = "JobCard Completed";
                        } else {
                            $scope.status = "JobCard Inprogress";
                        }
                    } else {
                        if (res.job_order.status_id == '8472') {
                            $scope.status = "Waiting for Parts Estimation";
                        } else {
                            $scope.status = "Vehicle Inward Inprogress";
                        }
                    }

                    if ($scope.job_order.vehicle.status_id == 8140) {
                        $scope.show_vehicle_detail = false;
                        $scope.show_vehicle_form = true;
                        self.is_sold = 1;
                    } else {
                        $scope.show_vehicle_detail = true;
                        $scope.show_vehicle_form = false;
                        if ($scope.job_order.vehicle.is_sold) {
                            self.is_sold = 1;
                        } else {
                            self.is_sold = 0;
                        }
                    }
                    if ($routeParams.type_id == 1) {
                        $scope.show_vehicle_detail = false;
                        $scope.show_vehicle_form = true;
                    }
                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

    }
});

app.component('partsIndentCustomerView', {
    templateUrl: parts_indent_customer_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('view-parts-indent')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/vehicle-inward/get-customer-detail',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id
                    },
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
                    $scope.job_order = res.job_order;
                    if (res.job_order.job_card) {
                        if (res.job_order.job_card.status_id == '8227') {
                            $scope.status = "Waiting for Parts Confirmation";
                        } else if (res.job_order.job_card.status_id == '8224' || res.job_order.job_card.status_id == '8225' || res.job_order.job_card.status_id == '8226') {
                            $scope.status = "JobCard Completed";
                        } else {
                            $scope.status = "JobCard Inprogress";
                        }
                    } else {
                        if (res.job_order.status_id == '8472') {
                            $scope.status = "Waiting for Parts Estimation";
                        } else {
                            $scope.status = "Vehicle Inward Inprogress";
                        }
                    }

                    if (!$scope.job_order.vehicle.current_owner) {
                        $scope.show_customer_detail = false;
                        $scope.show_customer_form = true;
                        self.country = $scope.job_order.country;
                    } else {
                        $scope.show_customer_detail = true;
                        $scope.show_customer_form = false;
                        self.country = $scope.job_order.vehicle.current_owner.customer.address.country;
                    }
                    $scope.extras = res.extras;

                    if ($scope.job_order.vehicle && $scope.job_order.vehicle.current_owner) {
                        $scope.ownership_type_id = $scope.job_order.vehicle.current_owner.ownership_type.id;
                    } else {
                        $scope.ownership_type_id = 8160;
                    }
                    if ($scope.type_id == 1) {
                        $scope.show_customer_detail = false;
                        $scope.show_customer_form = true;
                    }
                    if ($scope.type_id == 2) {
                        $scope.show_customer_detail = false;
                        $scope.show_customer_form = true;
                        $scope.job_order.vehicle.current_owner = {};
                        self.country = $scope.job_order.country;
                    }

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

    }
});

app.component('partsIndentRepairOrderView', {
    templateUrl: parts_indent_repair_order_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('view-parts-indent')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/part-indent/get-repair-orders',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id
                    },
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
                    $scope.job_order = res.job_order;
                    if (res.job_order.job_card) {
                        if (res.job_order.job_card.status_id == '8227') {
                            $scope.status = "Waiting for Parts Confirmation";
                        } else if (res.job_order.job_card.status_id == '8224' || res.job_order.job_card.status_id == '8225' || res.job_order.job_card.status_id == '8226') {
                            $scope.status = "JobCard Completed";
                        } else {
                            $scope.status = "JobCard Inprogress";
                        }
                    } else {
                        if (res.job_order.status_id == '8472') {
                            $scope.status = "Waiting for Parts Estimation";
                        } else {
                            $scope.status = "Vehicle Inward Inprogress";
                        }
                    }

                    console.log(res);
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

    }
});

app.component('partsIndentPartsView', {
    templateUrl: parts_indent_parts_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $q, PartSvc, SplitOrderTypeSvc, RepairOrderSvc, $mdSelect) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        self.angular_routes = angular_routes;
        self.job_order_repair_order_ids = [];
        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        $scope.init = function() {
            $rootScope.loading = true;

            let promises = {
                split_order_type_options: SplitOrderTypeSvc.options(),
                // repair_order_options: RepairOrderSvc.options($scope.job_order_id),
            };

            $scope.options = {};
            $q.all(promises)
                .then(function(responses) {
                    $scope.options.split_order_types = responses.split_order_type_options.data.options;
                    // $scope.options.repair_orders = responses.repair_order_options.data.options;
                    $rootScope.loading = false;
                });
        };
        $scope.init();

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/inward-part-indent/get-view-data',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;
                    $scope.labour_details = res.labour_details;
                    $scope.labour_amount = res.labour_amount;
                    $scope.part_details = res.part_details;
                    // $scope.part_amount = res.part_amount;
                    $scope.job_order_parts = res.job_order_parts;
                    $scope.repair_order_mechanics = res.repair_order_mechanics;
                    $scope.indent_part_logs = res.indent_part_logs;

                    if (res.job_order.job_card) {
                        if (res.job_order.job_card.status_id == '8227') {
                            $scope.status = "Waiting for Parts Confirmation";
                        } else if (res.job_order.job_card.status_id == '8224' || res.job_order.job_card.status_id == '8225' || res.job_order.job_card.status_id == '8226') {
                            $scope.status = "JobCard Completed";
                        } else {
                            $scope.status = "JobCard Inprogress";
                        }
                    } else {
                        if (res.job_order.status_id == '8472') {
                            $scope.status = "Waiting for Parts Estimation";
                        } else {
                            $scope.status = "Vehicle Inward Inprogress";
                        }
                    }

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.sendConfirm = function(type_id) {
            if (type_id == 4) {
                var id = $scope.job_order.job_card.id;
            } else {
                var id = $scope.job_order.id;
            }

            if (id) {
                $('.send_confirm').button('loading');
                $.ajax({
                        url: base_url + '/api/vehicle-inward/stock-incharge/request/parts',
                        method: "POST",
                        data: {
                            id: id,
                            type_id: type_id,
                        },
                    })
                    .done(function(res) {
                        $('.send_confirm').button('reset');
                        if (!res.success) {
                            showErrorNoty(res);
                            return;
                        }
                        custom_noty('success', res.message);
                        $("#confirmation_modal").modal('hide');
                        $("#billing_confirmation_modal").modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        $scope.fetchData();
                    })
                    .fail(function(xhr) {
                        $('.send_confirm').button('reset');
                    });
            }
        }

        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        /* Dropdown Arrow Function */
        arrowDropdown();

        /* Work Tooltip */
        $(document).on('mouseover', ".work-tooltip", function() {
            var $this = $(this);
            if (this.offsetWidth <= this.scrollWidth && !$this.attr('title')) {
                var $this_content = $this.children(".work_tooltip_hide").html();
                $this.tooltip({
                    title: $this_content,
                    html: true,
                    placement: "top"
                });
                $this.tooltip('show');
            }
        });
        $scope.showReturnPartForm = function(index, part) {
            console.log(index, part);
            if (part != undefined) {
                $scope.parts_indent.return_part = {};
                $scope.parts_indent.employee = {};
                self.job_order_returned_part_id = part.job_order_part_increment_id;
                $scope.parts_indent.return_part.id = part.part_id;
                $scope.parts_indent.return_part.qty = parseInt(part.qty);
                $scope.parts_indent.employee.id = part.employee_id;
                $scope.parts_indent.return_part.job_order_part_id = part.job_order_part_id;
            }
            $('#return_part_form_modal').modal('show');
        }

        $scope.showPartForm = function(part) {
            console.log(part);
            $job_order_part_id = part.job_order_part_id;
            if (part == false) {
                $scope.parts_indent = {};
            } else {
                $repair_orders = part.repair_order;
                if (part.split_order_type_id != null) {
                    $split_id = part.split_order_type_id;
                    SplitOrderTypeSvc.read($split_id)
                        .then(function(response) {
                            $scope.parts_indent.split_order_type = response.data.split_order_type;
                        });
                }
                if (part.uom == undefined) {
                    PartSvc.read(part.id)
                        .then(function(response) {
                            $scope.parts_indent.part = response.data.part;
                            $scope.parts_indent.part.qty = part.qty;
                            $scope.parts_indent.part.job_order_part_id = $job_order_part_id;
                            $scope.parts_indent.repair_order = $repair_orders;
                            // $scope.calculatePartAmount();
                        });
                }
            }
            $scope.modal_action = part === false ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
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
            $qty = 1;
            if (!part) {
                return;
            } else {
                if (part.qty) {
                    $qty = part.qty;
                }
            }
            PartSvc.read(part.id)
                .then(function(response) {
                    $scope.parts_indent.part.qty = $qty;
                    $scope.calculatePartAmount();
                });

        }
        $scope.calculatePartAmount = function() {
            if (!$scope.parts_indent.part.pivot) {
                $scope.parts_indent.part.pivot = {};
            }
            $scope.parts_indent.part.pivot.quantity = $scope.parts_indent.part.qty;
            $scope.parts_indent.part.total_amount = $scope.parts_indent.part.qty * $scope.parts_indent.part.mrp;
            $scope.parts_indent.part.pivot.amount = $scope.parts_indent.part.total_amount;
            $scope.calculatePartTotal();
        }
        $scope.calculatePartTotal = function() {
            $total_amount = 0;
            angular.forEach($scope.part_details, function(part, key) {
                if (part.removal_reason_id == null || part.removal_reason_id == undefined) {
                    $total_amount += parseFloat(part.amount);
                }
            });
            $scope.part_amount = $total_amount.toFixed(2);
        }
        var part_form = '#part-form';
        var v = jQuery(part_form).validate({
            ignore: '',
            rules: {
                'part_id': {
                    required: true,
                },
                // 'split_order_id': {
                //     required: true,
                // },
                'quantity': {
                    required: true,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {

                /*if ($scope.parts_indent.split_order_type) {

                    $scope.parts_indent.part.split_order_type = $scope.parts_indent.split_order_type.name;
                    $scope.parts_indent.part.split_order_type_id = $scope.parts_indent.split_order_type.id;
                }
                $scope.parts_indent.part.type = $scope.parts_indent.part.tax_code.code;
                $scope.parts_indent.part.amount = $scope.parts_indent.part.total_amount;
                $scope.parts_indent.part.part_detail = $scope.parts_indent.part.code + ' | ' + $scope.parts_indent.part.name;
                console.log($scope.parts_indent.part);
                if ($scope.part_modal_action == 'Add') {
                    angular.forEach($scope.part_details, function(part, key) {
                        if (part.name == $scope.parts_indent.part.name) {
                            $scope.part_details.splice(key, 1);
                        }
                    });
                    $scope.part_details.push($scope.parts_indent.part);
                } else {
                    $scope.part_details[$scope.part_index] = $scope.parts_indent.part;
                }*/

                let formData = new FormData($(part_form)[0]);
                $('.submit').button('loading');

                $.ajax({
                        url: base_url + '/api/vehicle-inward/add-part/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            $('.submit').button('reset');
                            showErrorNoty(res);
                            return;
                        }
                        $('.submit').button('reset');
                        custom_noty('success', res.message);
                        $scope.fetchData();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });

                // $scope.calculatePartTotal();
                $scope.parts_indent = {};
                $('#part_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
                $scope.fetchData();
            }
        });

        $scope.selectingRepairOrder = function(val) {
            console.log(val);
            if (val) {
                list = [];
                angular.forEach($scope.parts_indent.part.repair_order_parts, function(value, key) {
                    // angular.forEach($scope.parts_indent.repair_order, function(value, key) {
                    list.push(value.id);
                });
            } else {
                list = [];
            }
            self.repair_order_ids = list;
        }
        var return_part_form = "#return-part-form";
        var v = jQuery(return_part_form).validate({
            ignore: '',
            rules: {
                'returned_to_id': {
                    required: true,
                },
                'job_order_part_id': {
                    required: true,
                },
                'returned_qty': {
                    required: true,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {
                let formData = new FormData($(return_part_form)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/inward-part-indent/save-return-part',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            $('.submit').button('reset');
                            showErrorNoty(res);
                            return;
                        }
                        $('.submit').button('reset');
                        custom_noty('success', res.message);
                        $('#return_part_form_modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        $scope.fetchData();
                        // $location.path('/inward-parts-indent/view/' + $scope.job_order_id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
        $scope.removeLog = function(index, log) {
            console.log(log);
            $('#delete_log').modal('show');
            $('#log_id').val(log.job_order_part_increment_id);
            $('#log_type').val(log.transaction_type);

        }
        $scope.deleteConfirm = function() {
            $id = $('#log_id').val();
            $type = $('#log_type').val();

            let formData = new FormData();
            formData.append('id', $id);
            formData.append('type', $type);
            $.ajax({
                    url: base_url + '/api/vehicle-inward/part-logs/delete',
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                })
                .done(function(res) {
                    if (!res.success) {
                        $rootScope.loading = false;
                        showErrorNoty(res);
                        return;
                    }
                    $('#delete_log').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $scope.fetchData();
                    custom_noty('success', res.message);
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    $scope.button_action(id, 2);
                    custom_noty('error', 'Something went wrong at server');
                });

        }
        $scope.removePart = function(index, id, type) {
            console.log(id);
            if (id == undefined) {
                $scope.part_details.splice(index, 1);
                $scope.calculatePartTotal();
            } else {
                $scope.delete_reason = 10021;
                $('#removal_reason').val('');
                //HIDE REASON TEXTAREA 
                $scope.customer_delete = false;
                $scope.laboutPartsDelete(index, id, type);
            }
        }
        $scope.laboutPartsDelete = function(index, id, type) {
            $('#delete_labour_parts').modal('show');
            $('#labour_parts_id').val(id);
            $('#payable_type').val(type);

            $scope.saveLabourPartDeleteForm = function() {
                var delete_form_id = '#labour_parts_remove';
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
                    errorPlacement: function(error, element) {
                        if (element.attr("name") == "removal_reason_id") {
                            error.appendTo('#errorDeleteReasonRequired');
                            return;
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    submitHandler: function(form) {
                        let formData = new FormData($(delete_form_id)[0]);
                        $rootScope.loading = true;
                        $.ajax({
                                url: base_url + '/api/vehicle-inward/labour-parts/delete',
                                // url: base_url + '/api/vehicle-inward/labour-parts-delete/update',
                                method: "POST",
                                data: formData,
                                processData: false,
                                contentType: false,
                            })
                            .done(function(res) {
                                if (!res.success) {
                                    $rootScope.loading = false;
                                    showErrorNoty(res);
                                    return;
                                }
                                $('#delete_labour_parts').modal('hide');
                                $('body').removeClass('modal-open');
                                $('.modal-backdrop').remove();
                                $scope.fetchData();
                                custom_noty('success', res.message);
                            })
                            .fail(function(xhr) {
                                $rootScope.loading = false;
                                $scope.button_action(id, 2);
                                custom_noty('error', 'Something went wrong at server');
                            });
                    }
                });

            }
        }
        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.submit').button('loading');
                    $('.btn-nxt').attr("disabled", "disabled");
                    $('.btn-prev').bind('click', false);
                } else {
                    $('.btn-nxt').button('loading');
                    $('.submit').attr("disabled", "disabled");
                    $('.btn-prev').bind('click', false);
                }
            } else {
                $('.submit').button('reset');
                $('.btn-nxt').button('reset');
                $('.btn-prev').unbind('click', false);
                $(".btn-nxt").removeAttr("disabled");
                $(".submit").removeAttr("disabled");
            }
        }
    }
});

app.component('partsIndentIssuePartForm', {
    templateUrl: parts_indent_issue_part_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $q, PartSvc, VendorSvc) {

        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();
        $scope.job_order_id = $routeParams.job_order_id;
        self.job_order_issued_part_id = $routeParams.id;
        $scope.issue_part = {};
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/inward-part-indent/get-issue-part-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id,
                        issue_part_id: $routeParams.id
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }

                    $scope.job_order_parts = res.job_order_parts;
                    $scope.repair_order_mechanics = res.repair_order_mechanics;
                    $scope.issue_modes = res.issue_modes
                    $scope.issued_part = res.issue_data;
                    if ($scope.issued_part) {
                        PartSvc.read($scope.issued_part.part_id)
                            .then(function(response) {
                                $scope.return_part = response.data.part;
                                $scope.issuedPartSelected($scope.return_part);
                                $scope.return_part.job_order_part_id = res.issue_data.job_order_part_id;
                            });
                        $scope.issued_to = res.issue_to_user;
                        $scope.issued_part.issued_qty = parseFloat($scope.issued_part.issued_qty);
                    }
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.saveIssueForm = function() {
            var form = '#issue_part_form';
            var v = jQuery(form).validate({
                ignore: '',
                rules: {
                    'job_order_part_id': {
                        required: true,
                    },
                    'issued_qty': {
                        required: true,
                        number: true,
                    },
                    'issue_mode_id': {
                        required: true,
                    },
                    'issued_to_id': {
                        required: true
                    },
                    'remarks': {
                        required: true
                    },
                    'quantity': {
                        required: true,
                        number: true,
                    },
                    'unit_price': {
                        required: true,
                        number: true,
                    },
                    'total': {
                        required: true,
                    },
                    'tax_percentage': {
                        required: true,
                        number: true,
                    },
                    'tax_amount': {
                        required: true,
                        number: true,
                    },
                    'total_amount': {
                        required: true,
                        number: true,
                    },
                    'mrp': {
                        required: true,
                        number: true,
                    },
                    'supplier_id': {
                        required: true,
                    },
                    'po_number': {
                        required: true,
                    },
                    'po_amount': {
                        required: true,
                    },
                    'advance_amount_received_details': {
                        required: true,
                    },
                    'warranty_approved_reasons': {
                        required: true,
                    },
                },
                messages: {

                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Kindly fix');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form)[0]);
                    $('.submit').button('loading');

                    $.ajax({
                            url: base_url + '/api/inward-part-indent/save-issued-part',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $('.submit').button('reset');
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/part-indent/parts/view/' + $scope.job_order_id);

                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.searchVendor = function(query) {
            return new Promise(function(resolve, reject) {
                VendorSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }
        $scope.calculateTotal = function() {
            if ($scope.issue_part.quantity != '' && $scope.issue_part.unit_price != '') {
                $scope.issue_part.total = parseInt($scope.issue_part.quantity) * parseFloat($scope.issue_part.unit_price);
            }
        }
        $scope.calculateTax = function() {
            $scope.issue_part.tax_amount = parseFloat($scope.issue_part.total) * (parseFloat($scope.issue_part.tax_percentage) / 100);
            $scope.issue_part.total_amount = parseFloat($scope.issue_part.total) + parseFloat($scope.issue_part.tax_amount);
            $scope.issue_part.po_amount = $scope.issue_part.total_amount;
        }
        $scope.checkAvailability = function() {
            // console.log($scope.available_quantity, $scope.issued_part.issued_qty);
            if ($scope.available_quantity == undefined) {
                custom_noty('error', 'Please Select Part');
                return false;
            } else {
                if (parseFloat($scope.available_quantity) < parseFloat($scope.issued_part.issued_qty)) {
                    custom_noty('error', 'Issued quantity should not exceed available quantity');
                    return false;
                }
            }
        }
        $scope.issuedPartSelected = function(part) {
            if (part) {
                $.ajax({
                        url: base_url + '/api/inward-part-indent/get-part-detail-pias',
                        method: "POST",
                        data: {
                            code: part.code,
                            job_order_id: $routeParams.job_order_id,
                        },
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                    })
                    .done(function(res) {
                        if (!res.success) {
                            showErrorNoty(res);
                            return;
                        }
                        $scope.available_quantity = res.available_quantity;
                        $scope.total_request_qty = res.total_request_qty;
                        $scope.total_issued_qty = res.total_issued_qty;
                        $scope.total_balance_qty = res.total_balance_qty;
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        }
    }
});

// app.component('partsIndentView', {
//     templateUrl: parts_indent_view_template_url,
//     controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
//         var self = this;
//         $("input:text:visible:first").focus();
//         self.hasPermission = HelperService.hasPermission;
//         if (!self.hasPermission('view-parts-indent')) {
//             window.location = "#!/page-permission-denied";
//             return false;
//         }
//         self.angular_routes = angular_routes;
//         $http.get(
//             laravel_routes['getPartsIndentData'], {
//                 params: {
//                     id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
//                 }
//             }
//         ).then(function(response) {
//             self.job_cards = response.data.job_cards;
//             self.vehicle_info = response.data.vehicle_info;
//             self.customer_details = response.data.customer_details;
//             self.gate_log = response.data.gate_log;
//             self.labour_details = response.data.labour_details;
//             self.parts_details = response.data.parts_details;
//             self.part_list = response.data.part_list;
//             self.mechanic_list = response.data.mechanic_list;
//             self.issued_mode = response.data.issued_mode;
//             self.issued_parts_details = response.data.issued_parts_details;
//             self.gate_pass_details = response.data.gate_pass_details;
//             self.customer_voice_details = response.data.customer_voice_details;
//             $rootScope.loading = false;
//         });

//         $scope.onSelectedpartcode = function(part_code_selected) {
//             $('#part_code').val(part_code_selected);
//             if (part_code_selected) {
//                 return new Promise(function(resolve, reject) {
//                     $http.post(
//                             laravel_routes['getPartDetails'], {
//                                 key: part_code_selected,
//                                 job_order_id : self.job_cards.job_order_id,
//                             }
//                         )
//                         .then(function(response) {
//                             self.parts_details = response.data.parts_details;
//                             $("#job_order_part_id").val(self.parts_details.id);
//                             $("#req_qty").text(self.parts_details.qty+" "+"nos");
//                             $("#issue_qty").text(self.parts_details.issued_qty+" "+"nos");
//                             issued_qty = self.parts_details.issued_qty;
//                             if(issued_qty == null)
//                             {
//                              issued_qty = 0;
//                              $("#issue_qty").text(issued_qty+" "+"nos");
//                             }
//                             balance_qty = parseInt(self.parts_details.qty)-parseInt(issued_qty);
//                             $("#balance_qty").text(balance_qty+" "+"nos");
//                             $("#bal_qty").val(balance_qty);
//                         });
//                 });
//             } else {
//                 return [];
//             }
//         }

//         $scope.onSelectedmech = function(machanic_id_selected) {
//             $('#machanic_id').val(machanic_id_selected);
//         }
//         $scope.onSelectedmode = function(issue_modeselected) {
//             $('#issued_mode').val(issue_modeselected);
//         }

//         //Buttons to navigate between tabs
//         $('.btn-nxt').on("click", function() {
//             $('.cndn-tabs li.active').next().children('a').trigger("click");
//             tabPaneFooter();
//         });
//         $('.btn-prev').on("click", function() {
//             $('.cndn-tabs li.active').prev().children('a').trigger("click");
//             tabPaneFooter();
//         });

//         self.removeIssedParts = function($id) {
//            $('#delete_issued_part_id').val($id);
//         }

//         $scope.deleteConfirm = function() {
//             $id = $('#delete_issued_part_id').val();
//             $http.get(
//                 laravel_routes['deleteIssedPart'], {
//                     params: {
//                         id: $id,
//                     }
//                 }
//             ).then(function(response) {
//                 if (response.data.success) {
//                     custom_noty('success', 'Issed Part  Deleted Successfully');
//                     $('#pause_work_reason_list').DataTable().ajax.reload(function(json) {});
//                     $location.path('/gigo-pkg/parts-indent/view/' + $routeParams.id);
//                 }
//             });
//         }

//         //Save Form Data 
//         var form_id = '#part_add';
//         var v = jQuery(form_id).validate({
//             ignore: '',
//             rules: {
//                 'part_code':{
//                     required:true,
//                 },
//                 'issued_qty': {
//                     required: true,
//                 },
//                 'issued_to_id': {
//                     required: true,
//                 },
//                 'issued_mode': {
//                     required: true,
//                 },

//             },
//             messages: {

//             },
//             invalidHandler: function(event, validator) {
//                 custom_noty('error', 'You have errors, Please check all tabs');
//             },
//             submitHandler: function(form) {
//                 let formData = new FormData($(form_id)[0]);
//                 $('.submit').button('loading');
//                 $.ajax({
//                         url: laravel_routes['savePartsindent'],
//                         method: "POST",
//                         data: formData,
//                         processData: false,
//                         contentType: false,
//                     })
//                     .done(function(res) {
//                         if (res.success == true) {
//                             $('.submit').button('reset');
//                             $('#issued_qty').val(" ");
//                             custom_noty('success', res.message);
//                            $location.path('/gigo-pkg/parts-indent/view/' + $routeParams.id);
//                             $scope.$apply();

//                             $http.get(
//                             laravel_routes['getIssedParts'], {
//                                 params: {
//                                     id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
//                                 }
//                             }).then(function(response) {
//                             self.issued_parts_details = response.data.issued_parts_details;
//                              });

//                         } else {
//                             if (!res.success == true) {
//                                 $('.submit').button('reset');
//                                 $('#part_code').val(" ");
//                                 $('#issued_qty').val(" ");
//                                 $('#machanic_id').val(" ");
//                                 showErrorNoty(res);
//                             } else {
//                                 $('.submit').button('reset');
//                                $location.path('/gigo-pkg/parts-indent/view/' + $routeParams.id);
//                                 $scope.$apply();
//                             }
//                         }
//                     })
//                     .fail(function(xhr) {
//                         $('.submit').button('reset');
//                         custom_noty('error', 'Something went wrong at server');
//                     });
//             }
//         });

//     }
// });

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('partsIndentEditParts', {
    templateUrl: parts_indent_edit_parts_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('edit-parts-indent')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;
        $scope.add_part = [];
        $scope.job_order_part = [];

        $http.get(
            laravel_routes['getPartsIndentPartsData'], {
                params: {
                    job_card_id: $routeParams.job_card_id,
                    job_order_issued_part_id: $routeParams.job_order_issued_part_id,

                }
            }
        ).then(function(response) {
            // console.log(response);
            self.job_card = response.data.job_card;
            self.extras = response.data.extras;
            self.job_order_issued_part = response.data.job_order_issued_part;
            self.job_order_issued_part.issued_part_edit_qty = self.job_order_issued_part.issued_qty;
            self.job_order_issued_part.tot_qty = self.job_order_issued_part.job_order_part.qty;
            self.job_order_part_id = self.job_order_issued_part.job_order_part.id;
            $scope.onSelectedpartcode(self.job_order_issued_part.job_order_part.id);
        });

        $scope.onSelectedpartcode = function(job_order_part_id) {
            if (job_order_part_id) {
                $http.post(
                        laravel_routes['getPartDetails'], {
                            job_order_part_id: job_order_part_id,
                        }
                    )
                    .then(function(response) {
                        if (!response.data.success) {
                            $scope.add_part = [];
                            showErrorNoty(response.data);
                            return;
                        }
                        $scope.job_order_part = response.data.job_order_parts;
                        $scope.add_part.name = $scope.job_order_part.name;
                        $scope.add_part.req_qty = $scope.job_order_part.qty + " " + "nos";
                        if (!$scope.job_order_part.issued_qty) {
                            $scope.add_part.issue_qty = "0 nos";
                            var issued_qty = 0;
                        } else {
                            $scope.add_part.issue_qty = $scope.job_order_part.issued_qty + " " + "nos";
                            var issued_qty = $scope.job_order_part.issued_qty;
                        }
                        $scope.add_part.all_issued_qty = issued_qty;
                        $scope.add_part.balance_qty = parseInt($scope.job_order_part.qty) - parseInt(issued_qty);
                        $scope.add_part.balance_qty_nos = $scope.add_part.balance_qty + " " + "nos";
                        if (self.job_order_part_id != job_order_part_id) {
                            self.job_order_issued_part.issued_part_edit_qty = 0;
                        }
                        self.job_order_issued_part.tot_qty = $scope.job_order_part.qty;
                    });
            } else {
                $scope.add_part = [];
            }
        }

        //Save Form Data 
        $scope.submitPart = function() {
            var form_id = '#part_add';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'job_order_part_id': {
                        required: true,
                    },
                    'issued_qty': {
                        required: true,
                    },
                    'issued_to_id': {
                        required: true,
                    },
                    'issued_mode_id': {
                        required: true,
                    },

                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: laravel_routes['savePartsindent'],
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.submit').button('reset');
                            custom_noty('success', res.message);
                            $location.path('/job-card/part-indent/' + $routeParams.job_card_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.directive('partIndentHeader', function() {
    return {
        templateUrl: part_indent_header_template_url,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('partIndentTabs', function() {
    return {
        templateUrl: part_indent_tabs_template_url,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------