app.component('manualVehicleDeliveryList', {
    templateUrl: manual_vehicle_delivery_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
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
            ajax: {
                url: laravel_routes['getManualDeliveryVehicleList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
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

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'date', searchable: false },
                { data: 'outlet_code', name: 'outlets.code' },
                { data: 'registration_type', name: 'registration_type' },
                { data: 'registration_number', name: 'vehicles.registration_number' },
                { data: 'customer_name', name: 'customers.name' },
                { data: 'model_number', name: 'models.model_number' },
                { data: 'amc_policies', name: 'amc_policies.name' },
                { data: 'number', name: 'gate_logs.number' },
                { data: 'status', name: 'configs.name' },

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
            $('#delivery_vehicles_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#delivery_vehicles_list').DataTable().ajax.reload();
        });

        var dataTables = $('#delivery_vehicles_list').dataTable();
        $scope.searchInwardVehicle = function() {
            dataTables.fnFilter(self.search_key);
        }

        $scope.listRedirect = function(type) {
                window.location = "#!/inward-vehicle/table-list";
                return false;
        }
        // FOR FILTER
        $http.get(
            laravel_routes['getVehicleInwardFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
        });
        //GET CUSTOMER LIST
        self.searchCustomer = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getManualDeliveryVehicleFilter'], {
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
        //GET VEHICLE MODEL LIST
        self.searchVehicleModel = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getVehicleModelSearchList'], {
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
        $scope.selectedVehicleModel = function(id) {
            $('#model_id').val(id);
        }
        $scope.onSelectedRegistrationType = function(id) {
            $('#registration_type').val(id);
        }
        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
        }
        $scope.applyFilter = function() {
            dataTables.fnFilter();
            $('#vehicle-inward-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
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
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        //for md-select search
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('view-manual-vehicle-delivery')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/manual-vehicle-delivery/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.id,
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

                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Scrollable Tabs
        setTimeout(function() {
            scrollableTabs();
        }, 1000);
    }
});

app.component('manualVehicleDeliveryForm', {
    templateUrl: manual_vehicle_delivery_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        //for md-select search
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-manual-vehicle-delivery')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/manual-vehicle-delivery/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.id,
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

                    $scope.extras = res.extras;

                    if ($scope.job_order.vehicle_payment_status && $scope.job_order.vehicle_payment_status == 1) {
                        self.vehicle_payment_status = 1;
                    } else {
                        self.vehicle_payment_status = 0;
                    }

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

         //Save Form Data 
         $scope.saveVehicleDelivery = function() {
            // alert(111);
            // return;
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
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/manual-vehicle-delivery/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $('.submit').button('reset');

                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/gate-pass/table-list');

                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //Scrollable Tabs
        setTimeout(function() {
            scrollableTabs();
        }, 1000);
    }
});

app.component('gatePassForm', {
    templateUrl: gate_pass_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect, PartSvc) {
        //for md-select search
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-parts-tools-gate-pass') || !self.hasPermission('edit-parts-tools-gate-pass')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/gate-pass/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.id,
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
                    self.gate_pass = res.gate_pass;
                    self.action = res.action;

                    if (res.action == 'Edit') {
                        if (res.gate_pass.type_id == '8282') {
                            self.switch_value = 'Returnable';
                        } else {
                            self.switch_value = 'Non Returnable';
                        }
                    } else {
                        self.switch_value = 'Returnable';
                    }

                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Scrollable Tabs
        setTimeout(function() {
            scrollableTabs();
        }, 1000);

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        // ADD NEW ITEM
        self.addNewparts = function() {
            self.gate_pass.gate_pass_invoice_items.push({});
        }

        // ADD NEW User
        self.addNewUser = function() {
            self.gate_pass.gate_pass_users.push({});
        }

        self.item_removal_id = [];
        self.removeparts = function(index, item_id) {
            if (item_id) {
                self.item_removal_id.push(item_id);
                $('#removal_item_ids').val(JSON.stringify(self.item_removal_id));
            }
            self.gate_pass.gate_pass_invoice_items.splice(index, 1);
        }

        $scope.searchParts = function(query) {
            return new Promise(function(resolve, reject) {
                PartSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }

        $scope.partSelected = function(part, index) {
            // $('#entity_description_'+index).val(part.name);
            $('.entity_description_' + index).val(part.name);
        }

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

        $scope.customerSelected = function(customer) {
            if (customer) {
                $('.customer_name').val(customer.name);
                var full_address = customer.primary_address.address_line1 + ' , ' + customer.primary_address.address_line2 + customer.primary_address.formatted;
                $('.customer_address').val(full_address);
            }
        }

        //GET JoBCard LIST
        self.searchJobCard = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getJobCardSearchList'], {
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

        $scope.jobCardSelected = function(job_card) {
            if (job_card) {
                $('.job_card_date').val(job_card.date);
            }
        }

        //GET JoBCard LIST
        self.searchUsers = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getUserSearchList'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }

        $scope.userSelected = function(user) {
            if (user) {
                $('.user_name' + index).val(user.name);
            }
        }

        self.user_removal_id = [];
        self.removeUser = function(index, user_id) {
            if (user_id) {
                self.user_removal_id.push(user_id);
                $('#removal_user_ids').val(JSON.stringify(self.user_removal_id));
            }
            self.gate_pass.gate_pass_users.splice(index, 1);
        }

        //Save Form Data 
        $scope.saveGatePass = function() {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'type': {
                        required: true,
                    },
                    'purpose_id': {
                        required: true,
                    },
                    'hand_over_to': {
                        required: true,
                    },
                },
                messages: {},
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/gate-pass/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $('.submit').button('reset');

                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/gate-pass/table-list');

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



app.component('gatePassApproveView', {
    templateUrl: gate_pass_approve_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        //for md-select search
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/gate-pass/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.id,
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
                    self.gate_pass = res.gate_pass;

                    if (res.gate_pass.status_id == 11400) {
                        self.approve_status = 1;
                        self.gate_in_approve_status = 0;
                        self.verify_status = 0;
                    }
                    // else{
                    //     self.approve_status = 0;
                    //     self.gate_in_approve_status = 0;
                    //     self.verify_status = 0;

                    //     if(res.gate_pass.status_id == 11402)
                    //     {
                    //         self.approve_status = 0;
                    //         self.gate_in_approve_status = 1;
                    //         self.verify_status = 0;
                    //     }
                    //     else if(res.gate_pass.status_id == 11403)
                    //     {
                    //         self.approve_status = 0;
                    //         self.gate_in_approve_status = 0;
                    //         self.verify_status = 1;
                    //     }
                    // }
                    else if (res.gate_pass.status_id == 11402) {
                        $scope.approve_status = 0;
                        $scope.gate_in_approve_status = 1;
                        $scope.verify_status = 0;
                    } else if (res.gate_pass.status_id == 11403) {
                        $scope.approve_status = 0;
                        $scope.gate_in_approve_status = 0;
                        $scope.verify_status = 1;
                    } else {
                        $scope.approve_status = 0;
                        $scope.gate_in_approve_status = 0;
                        $scope.verify_status = 0;
                    }

                    if (!self.hasPermission('verify-parts-tools-gate-pass')) {
                        self.verify_status = 0;
                    }

                    if (!self.hasPermission('gate-in-out-parts-tools-gate-pass')) {
                        self.approve_status = 0;
                        self.gate_in_approve_status = 0;
                    }

                    console.log(self.approve_status);
                    console.log(self.gate_in_approve_status);
                    console.log(self.verify_status);

                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $(document).on("wheel", "input[type=number]", function(e) {
            $(this).blur();
        });

        //Scrollable Tabs
        setTimeout(function() {
            scrollableTabs();
        }, 1000);

        //Save Form Data 
        $scope.saveGatePass = function() {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'gate_pass_id': {
                        required: true,
                    },
                },
                messages: {},
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/gate-pass/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $('.submit').button('reset');

                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/gate-pass/table-list');

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

app.component('gatePassVerifyView', {
    templateUrl: gate_pass_verify_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        //for md-select search
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/gate-pass/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.id,
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
                    self.gate_pass = res.gate_pass;

                    if (res.gate_pass.status_id == 11400) {
                        self.approve_status = 1;
                        self.gate_in_approve_status = 0;
                        self.verify_status = 0;
                    }
                    // else{
                    //     self.approve_status = 0;
                    //     self.gate_in_approve_status = 0;
                    //     self.verify_status = 0;

                    //     if(res.gate_pass.status_id == 11402)
                    //     {
                    //         self.approve_status = 0;
                    //         self.gate_in_approve_status = 1;
                    //         self.verify_status = 0;
                    //     }
                    //     else if(res.gate_pass.status_id == 11403)
                    //     {
                    //         self.approve_status = 0;
                    //         self.gate_in_approve_status = 0;
                    //         self.verify_status = 1;
                    //     }
                    // }
                    else if (res.gate_pass.status_id == 11402) {
                        $scope.approve_status = 0;
                        $scope.gate_in_approve_status = 1;
                        $scope.verify_status = 0;
                    } else if (res.gate_pass.status_id == 11403) {
                        $scope.approve_status = 0;
                        $scope.gate_in_approve_status = 0;
                        $scope.verify_status = 1;
                    } else {
                        $scope.approve_status = 0;
                        $scope.gate_in_approve_status = 0;
                        $scope.verify_status = 0;
                    }

                    if (!self.hasPermission('verify-parts-tools-gate-pass')) {
                        self.verify_status = 0;
                    }

                    if (!self.hasPermission('gate-in-out-parts-tools-gate-pass')) {
                        self.approve_status = 0;
                        self.gate_in_approve_status = 0;
                    }

                    console.log(self.approve_status);
                    console.log(self.gate_in_approve_status);
                    console.log(self.verify_status);

                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $(document).on("wheel", "input[type=number]", function(e) {
            $(this).blur();
        });

        //Scrollable Tabs
        setTimeout(function() {
            scrollableTabs();
        }, 1000);

        //Save Form Data 
        $scope.saveGatePass = function() {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'gate_pass_id': {
                        required: true,
                    },
                },
                messages: {},
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/gate-pass/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $('.submit').button('reset');

                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/gate-pass/table-list');

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