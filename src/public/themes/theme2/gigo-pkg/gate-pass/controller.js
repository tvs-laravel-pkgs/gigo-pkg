app.component('inwardVehicleCardList', {
    templateUrl: inward_vehicle_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('inward-vehicle')) {
            // window.location = "#!/page-permission-denied";
            return false;
        }

        self.user = $scope.user = HelperService.getLoggedUser();
        self.gate_in_date = '';
        self.reg_no = '';
        self.membership = '';
        self.gate_in_no = '';
        self.customer_id = '';
        self.model_id = '';
        self.registration_type = '';
        self.status_id = '';
        if (!localStorage.getItem('search_key')) {
            self.search_key = '';
        } else {
            self.search_key = localStorage.getItem('search_key');
        }

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/get',
                    method: "POST",
                    data: {
                        service_advisor_id: self.user.id,
                        search_key: self.search_key,
                        gate_in_date: self.gate_in_date,
                        reg_no: self.reg_no,
                        membership: self.membership,
                        gate_in_no: self.gate_in_no,
                        customer_id: self.customer_id,
                        model_id: self.model_id,
                        registration_type: self.registration_type,
                        status_id: self.status_id,
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
                    $scope.gate_logs = res.gate_logs;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $('.refresh_table').on("click", function() {
            $scope.fetchData();
        });
        $scope.clear_search = function() {
            self.search_key = '';
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $scope.searchInwardVehicle = function() {
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $("#gate_in_date").keyup(function() {
            self.gate_in_date = this.value;
        });
        $("#reg_no").keyup(function() {
            self.reg_no = this.value;
        });
        $("#membership").keyup(function() {
            self.membership = this.value;
        });
        $("#gate_in_no").keyup(function() {
            self.gate_in_no = this.value;
        });
        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/inward-vehicle/table-list";
                return false;
            } else {
                window.location = "#!/inward-vehicle/card-list";
                return false;
            }
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
            self.customer_id = id;
        }
        $scope.selectedVehicleModel = function(id) {
            $('#model_id').val(id);
            self.model_id = id;
        }
        $scope.onSelectedRegistrationType = function(id) {
            $('#registration_type').val(id);
            self.registration_type = id;
        }
        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            self.status_id = id;
        }
        $scope.applyFilter = function() {
            $scope.fetchData();
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
            self.customer_id = '';
            self.model_id = '';
            self.registration_type = '';
            self.status_id = '';
            setTimeout(function() {
                $scope.fetchData();
            }, 1000);
            $('#vehicle-inward-filter-modal').modal('hide');
        }

        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('gatePassList', {
    templateUrl: gate_pass_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_inward_vehicle').focus();

        alert(gate_pass_list_template_url);
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('inward-vehicle')) {
            // window.location = "#!/page-permission-denied";
            return false;
        }
        self.search_key = '';
        self.user = $scope.user = HelperService.getLoggedUser();

        // var table_scroll;
        // table_scroll = $('.page-main-content.list-page-content').height() - 37;
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#inward_vehicles_list').DataTable({
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
                url: laravel_routes['getVehicleInwardList'],
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
            $('#inward_vehicles_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#inward_vehicles_list').DataTable().ajax.reload();
        });

        var dataTables = $('#inward_vehicles_list').dataTable();
        $scope.searchInwardVehicle = function() {
            dataTables.fnFilter(self.search_key);
        }

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/inward-vehicle/table-list";
                return false;
            } else {
                window.location = "#!/inward-vehicle/card-list";
                return false;
            }
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

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('inwardVehicleView', {
    templateUrl: inward_vehicle_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
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
                    url: base_url + '/api/vehicle-inward/get-view-data',
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
                    // console.log($scope.job_order);
                    $scope.schedule_maintenance = res.schedule_maintenance;
                    $scope.payable_maintenance = res.payable_maintenance;
                    $scope.total_estimate_labour_amount = res.total_estimate_labour_amount;
                    $scope.total_estimate_part_amount = res.total_estimate_part_amount;
                    $scope.total_estimate_amount = res.total_estimate_amount;
                    $scope.total_tax_amount = res.total_tax_amount;
                    $scope.extras = res.extras;
                    $scope.vehicle_inspection_item_groups = res.vehicle_inspection_item_groups;
                    $scope.inventory_list = res.inventory_list;

                    if ($scope.job_order.amc_status == 1 || $scope.job_order.amc_status == 0) {
                        self.warrany_status = 1;
                    } else {
                        self.warrany_status = 0;
                    }

                    if ($scope.job_order.amc_status == 1) {
                        self.amc_status = 1;
                    } else {
                        self.amc_status = 0;
                    }

                    if ($scope.job_order.ewp_expiry_date) {
                        self.exwarrany_status = 1;
                    } else {
                        self.exwarrany_status = 0;
                    }

                    self.inward_cancel = 0;

                    //PDF
                    $scope.total_estimate = res.job_order.total_estimate;
                    $scope.estimate_pdf = res.job_order.estimate_pdf;
                    $scope.covering_letter_pdf = res.job_order.covering_letter_pdf;
                    $scope.gate_pass_pdf = res.job_order.gate_pass_pdf;
                    $scope.inventory_pdf = res.job_order.inventory_pdf;
                    $scope.inspection_pdf = res.job_order.inspection_pdf;
                    $scope.manual_job_order_pdf = res.job_order.manual_job_order_pdf;
                    $scope.revised_estimate_url = base_url + '/gigo-pkg/pdf/job-order/revised-estimate/' + $scope.job_order.id;

                    $scope.$apply();

                    //Scrollable Tabs
                    setTimeout(function() {
                        scrollableTabs();
                    }, 1000);
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

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

        //Save Form Data 
        var form_id = '#inward_vehicle_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            // rules: {

            // },
            // messages: {

            // },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveJobOrder'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/job-order/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/job-order/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });

        //Save Form Data 
        $scope.saveInwardCancel = function(id) {
            var form_id = '#inward_cancel_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'job_order_id': {
                        required: true,
                    },
                    'inward_cancel_reason': {
                        required: true,
                    },
                },
                messages: {

                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/cancel',
                            method: "POST",
                            data: formData,
                            beforeSend: function(xhr) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                            },
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);

                            setTimeout(function() {
                                $scope.fetchData();
                            }, 1000);
                        })
                        .fail(function(xhr) {
                            $scope.button_action(id, 2);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.button_action = function(id, type) {
            if (type == 1) {
                $('.submit').button('loading');
                $('.btn-prev').bind('click', false);

            } else {
                $('.submit').button('reset');
                $('.btn-prev').unbind('click', false);
            }
        }
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('gatePassForm', {
    templateUrl: gate_pass_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element,$mdSelect,PartSvc) {
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
        if (!self.hasPermission('inward-job-card-tab-vehicle-details-edit')) {
            // window.location = "#!/inward-vehicle/table-list";
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

                    if(res.action == 'Edit')
                    {
                        if(res.gate_pass.type_id == '8282')
                        {
                            self.switch_value = 'Returnable';
                        }
                        else
                        {
                            self.switch_value = 'Non Returnable';
                        }
                    }
                    else
                    {
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

        // ADD NEW TECHNICAL LEADS
        self.addNewparts = function() {
            self.gate_pass.gate_pass_invoice_items.push({});
        }

        self.item_removal_id = [];
        self.removeparts = function(index,item_id) {
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

        $scope.partSelected = function(part,index) {
            // $('#entity_description_'+index).val(part.name);
            $('.entity_description_'+index).val(part.name);
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
            if(customer)
            {
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
            if(job_card)
            {
                $('.job_card_date').val(job_card.date);
            }
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
                messages: {
                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    // $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/gate-pass/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            // $scope.button_action(id, 2);
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            //     $location.path('/inward-vehicle/table-list');
                            
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $scope.button_action(id, 2);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }


        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }

        if ($routeParams.type_id == 1) {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }

        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.save').button('loading');
                    $('.btn-next').attr("disabled", "disabled");
                } else {
                    $('.btn-next').button('loading');
                    $('.save').attr("disabled", "disabled");
                }
                $('.btn-prev').bind('click', false);
            } else {
                $('.save').button('reset');
                $('.btn-next').button('reset');
                $('.btn-prev').unbind('click', false);
                $(".btn-next").removeAttr("disabled");
                $(".save").removeAttr("disabled");
            }
        }
    }
});

app.component('gatePassView', {
    templateUrl: gate_pass_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element,$mdSelect) {
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
        if (!self.hasPermission('inward-job-card-tab-vehicle-details-edit')) {
            // window.location = "#!/inward-vehicle/table-list";
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

        // ADD NEW TECHNICAL LEADS
        self.addNewparts = function() {
            self.gate_pass.gate_pass_invoice_items.push({});
        }

        self.item_removal_id = [];
        self.removeparts = function(index,item_id) {
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

        $scope.partSelected = function(part,index) {
            // $('#entity_description_'+index).val(part.name);
            $('.entity_description_'+index).val(part.name);
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
            if(customer)
            {
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
            if(job_card)
            {
                $('.job_card_date').val(job_card.date);
            }
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
                messages: {
                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    // $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/gate-pass/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            // $scope.button_action(id, 2);
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            //     $location.path('/inward-vehicle/table-list');
                            
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $scope.button_action(id, 2);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }


        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }

        if ($routeParams.type_id == 1) {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }

        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.save').button('loading');
                    $('.btn-next').attr("disabled", "disabled");
                } else {
                    $('.btn-next').button('loading');
                    $('.save').attr("disabled", "disabled");
                }
                $('.btn-prev').bind('click', false);
            } else {
                $('.save').button('reset');
                $('.btn-next').button('reset');
                $('.btn-prev').unbind('click', false);
                $(".btn-next").removeAttr("disabled");
                $(".save").removeAttr("disabled");
            }
        }
    }
});
