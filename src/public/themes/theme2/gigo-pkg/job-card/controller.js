app.component('jobCardTableList', {
    templateUrl: job_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_job_card').focus();
        var self = this;
        //alert();
        $('li').removeClass('active');
        $('.job_cards').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('job-cards')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-job-card');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#job_cards_list').DataTable({
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
                    $('#search_job_card').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getJobCardTableList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.date = $("#date").val();
                    d.reg_no = $("#reg_no").val();
                    d.job_card_no = $("#job_card_no").val();
                    d.customer_id = $("#customer_id").val();
                    d.model_id = $("#model_id").val();
                    d.quote_type_id = $("#quote_type_id").val();
                    d.service_type_id = $("#service_type_id").val();
                    d.job_order_type_id = $("#job_order_type_id").val();
                    d.status_id = $("#status_id").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'date', },
                { data: 'job_card_number', name: 'job_cards.job_card_number' },
                { data: 'registration_number', name: 'vehicles.registration_number' },
                { data: 'customer_name', name: 'customers.name' },
                { data: 'job_order_type', name: 'service_order_types.name' },
                { data: 'quote_type', name: 'quote_types.name' },
                { data: 'service_type', name: 'service_types.name' },
                { data: 'status', searchable: false },

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
            $('#search_job_card').val('');
            $('#job_cards_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#job_cards_list').DataTable().ajax.reload();
        });

        var dataTables = $('#job_cards_list').dataTable();
        $("#search_job_card").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        // FOR FILTER
        $http.get(
            laravel_routes['getJobCardFilter']
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
        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/gigo-pkg/job-card/table-list";
                return false;
            } else {
                //alert();
                window.location = "#!/gigo-pkg/job-card/card-list";
                return false;
            }
        }

        $("#date").keyup(function() {
            self.date = this.value;
        });
        $('#reg_no').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#job_card_no').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.selectedCustomer = function(id) {
            $('#customer_id').val(id);
            self.customer_id = id;
        }
        $scope.selectedVehicleModel = function(id) {
            $('#model_id').val(id);
            self.model_id = id;
        }

        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            dataTables.fnFilter();
        }

        $scope.onSelectedQuoteType = function(id) {
            $('#quote_type_id').val(id);
            dataTables.fnFilter();
        }
        $scope.onSelectedServiceType = function(id) {
            $('#service_type_id').val(id);
            dataTables.fnFilter();
        }
        $scope.onSelectedJobOrderType = function(id) {
            $('#job_order_type_id').val(id);
            dataTables.fnFilter();
        }

        $scope.applyFilter = function() {
            //$scope.fetchData();
            $('#job-card-filter-modal').modal('hide');
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            $("#date").val('');
            $("#reg_no").val('');
            $("#customer_id").val('');
            $("#model_id").val('');
            $("#quote_type_id").val('');
            $("#service_type_id").val('');
            $("#job_order_type_id").val('');
            $("#status_id").val('');
            dataTables.fnFilter();
            //$scope.fetchData();
        }
        $rootScope.loading = false;
    }
});


app.component('jobCardCardList', {
    templateUrl: job_card_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search_job_card').focus();
        var self = this;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $('li').removeClass('active');
        $('.job_cards').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('job-cards')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        $scope.clear_search = function() {
            $('#search_job_card').val('');
        }

        //HelperService.isLoggedIn()
        self.user = $scope.user = HelperService.getLoggedUser();
        self.search_key = '';
        self.date = '';
        self.reg_no = '';
        self.job_card_no = '';
        self.service_type_id = '';
        self.quote_type_id = '';
        self.job_order_type_id = '';
        self.model_id = '';
        self.status_id = '';

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';
            $scope.searchTerm3 = '';
        };

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/job-card/get',
                    method: "POST",
                    data: {
                        search_key: self.search_key,
                        date: self.date,
                        reg_no: self.reg_no,
                        job_card_no: self.job_card_no,
                        gate_in_no: self.gate_in_no,
                        customer_id: self.customer_id,
                        model_id: self.model_id,
                        service_type_id: self.service_type_id,
                        quote_type_id: self.quote_type_id,
                        job_order_type_id: self.job_order_type_id,
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
                    $scope.job_cards = res.job_card_list;
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
        $("#search_job_card").keyup(function() {
            self.search_key = this.value;
            $scope.fetchData();
        });
        $("#date").keyup(function() {
            self.date = this.value;
        });
        $("#reg_no").keyup(function() {
            self.reg_no = this.value;
        });
        $("#job_card_no").keyup(function() {
            self.job_card_no = this.value;
        });

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/gigo-pkg/job-card/table-list";
                return false;
            } else {
                window.location = "#!/gigo-pkg/job-card/card-list";
                return false;
            }
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getJobCardFilter']
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
        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            self.status_id = id;
        }
        $scope.onSelectedQuoteType = function(id) {
            $('#quote_type_id').val(id);
            self.quote_type_id = id;
            //dataTables.fnFilter();
        }
        $scope.onSelectedServiceType = function(id) {
            $('#service_type_id').val(id);
            self.service_type_id = id;
            // dataTables.fnFilter();
        }
        $scope.onSelectedJobOrderType = function(id) {
            $('#job_order_type_id').val(id);
            self.job_order_type_id = id;
            // dataTables.fnFilter();
        }

        $scope.applyFilter = function() {
            $('#job-card-filter-modal').modal('hide');
            $scope.fetchData();
        }
        $scope.reset_filter = function() {
            $("#date").val('');
            $("#reg_no").val('');
            $("#customer_id").val('');
            $("#model_id").val('');
            $("#status_id").val('');
            $("#job_card_no").val('');
            $("#job_card_type_id").val('');
            $("#service_type_id").val('');
            $("#quote_type_id").val('');
            //dataTables.fnFilter();
            $scope.fetchData();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------


app.component('jobCardForm', {
    templateUrl: job_card_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-job-card') || !self.hasPermission('edit-job-card')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getJobCardFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.job_card = response.data.job_card;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.job_card.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        //Save Form Data 
        var form_id = '#job_card_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'short_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 32,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 128,
                },
                'description': {
                    minlength: 3,
                    maxlength: 255,
                }
            },
            messages: {
                'short_name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 32 Characters',
                },
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 128 Characters',
                },
                'description': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 255 Characters',
                }
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveJobCard'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/job-card/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/job-card/list');
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
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('jobCardBayForm', {
    templateUrl: job_card_bay_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        self.user = $scope.user = HelperService.getLoggedUser();
        $rootScope.loading = false;
        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }
        // console.log($routeParams.id);

        //self.angular_routes = angular_routes;
        //VIEW GATE PASS
        $.ajax({
            url: base_url + '/api/job-card/bay/get-form-data',
            type: "POST",
            data: {
                id: $routeParams.id,
            },
            dataType: "json",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
            },
            success: function(response) {
                console.log(response);
                $scope.job_card = response.job_card;
                $scope.extras = response.extras;
                $scope.bay_id;
                angular.forEach($scope.extras.bay_list, function(value, key) {
                    console.log(value.selected);
                    if (value.selected == true) {
                        $scope.bay_id = value.id;
                    }
                });
                console.log('bay_id :' + $scope.bay_id);
                // self.material_gate_pass = response.material_gate_pass_detail;
                // self.customer_detail = response.customer_detail;
                $scope.$apply();
                // Success = true; //doesn't go here
            },
            error: function(textStatus, errorThrown) {
                custom_noty('error', 'Something went wrong at server');
            }
        });

        $scope.OnselectBay = function(bay) {
            if (bay.status_id == 8240) {
                angular.forEach($scope.extras.bay_list, function(value, key) {
                    // console.log(value.id);
                    // console.log($scope.job_card.bay_id);
                    if (value.selected == true && value.id != $scope.job_card.bay_id) {
                        value.selected = false;
                    } else if (value.selected == true && value.id == $scope.job_card.bay_id) {
                        value.selected = false;
                        value.status_id = 8240;
                        value.status.name = 'Free';

                    }
                });
                bay.selected = true;
                $scope.bay_id = bay.id;

            } else {
                bay.selected = false;
            }
            console.log($scope.bay_id);

        }
        //Save Form Data 
        $scope.saveBay = function() {
            var form_id = '#bay_form';
            if (!$scope.bay_id) {
                custom_noty('error', 'Please select bay');
                return false;
            }
            var v = jQuery(form_id).validate({
                ignore: '',
                /*rules: {
                    'job_card_id': {
                        required: true,
                        minlength: 3,
                        maxlength: 32,
                    },
                    'bay_id': {
                        required: true,
                        minlength: 3,
                        maxlength: 128,
                    },
                },
                */
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/job-card/bay/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            beforeSend: function(xhr) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                            },
                        })
                        .done(function(res) {
                            console.log(res);
                            if (!res.success) {
                                showErrorNoty(res);
                                $('.submit').button('reset');
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/job-card/table-list');
                            $scope.$apply();
                            $('.submit').button('reset');
                        })
                        .fail(function(xhr) {
                            console.log(xhr);
                            $('.submit').button('reset');
                            showServerErrorNoty();
                        });
                }
            });
        }
    }
});


//Returnable Items
app.component('jobCardReturnableItemList', {
    templateUrl: job_card_returnable_item_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_card_id = $routeParams.job_card_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/job-card/returnable-items/get',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id
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
                    $scope.job_card = res.job_card;
                    $scope.returnable_items = res.returnable_items;
                    $scope.returnable_item_attachement_path = res.attachement_path;
                    console.log(res.returnable_items);
                    console.log(res.attachement_path);
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
    }
});


//Material Gate Pass
app.component('jobCardMaterialGatepassForm', {
    templateUrl: job_card_material_gatepass_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_card_id = $routeParams.job_card_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/view-material-gate-pass',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id
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
                    $scope.job_card_id = $routeParams.job_card_id;
                    $scope.view_metrial_gate_pass = res.view_metrial_gate_pass;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//Material Outward
app.component('jobCardMaterialOutwardForm', {
    templateUrl: job_card_material_outward_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
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

        $scope.job_card_id = $routeParams.job_card_id;
        $scope.gatepass_id = $routeParams.gatepass_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/view-material-gate-pass-detail',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id,
                        gate_pass_id: $routeParams.gatepass_id
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
                    $scope.gate_pass = res.gate_pass;
                    $scope.my_job_card_details = res.my_job_card_details;
                    $scope.gate_pass_item = res.gate_pass_item;
                    $scope.make_list = res.make_list;
                    $scope.model_list = res.model_list;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.addNewItem = function() {
            $scope.gate_pass_item.push({
                item_description: '',
                item_make: '',
                item_model: '',
                item_serial_no: '',
                qty: '',
                remarks: '',
            });
        }

        //$scope.addNewItem();
        //Save Form Data 
        $scope.saveVendorDetails = function() {
            var form_id = '#vendor_save';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    /*'expert_diagnosis_report': {
                        required: true,
                    },
                    'expert_diagnosis_report_by_id': {
                        required: true,
                    },*/
                },
                messages: {

                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/save-material-gate-pass-detail',
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
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/job-card/material-outward/' + $scope.job_card_id + '/' + $scope.gatepass_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //Save Form Data 
        $scope.saveItemDetails = function() {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'item_description': {
                        required: true,
                    },
                    'item_make': {
                        required: true,
                    },
                    'item_model': {
                        required: true,
                    },
                    'item_serial_no': {
                        required: true,
                    },
                    'qty': {
                        required: true,
                    },
                    'remarks': {
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
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/save-material-gate-pass-item',
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
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/job-card/material-outward/' + $scope.job_card_id + '/' + $scope.gatepass_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }


        /* Image Uploadify Funtion */
        $('.image_uploadify').imageuploadify();

    }
});

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//Road Test Observation
app.component('jobCardRoadTestObservationForm', {
    templateUrl: job_card_material_road_test_observation_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_card_id = $routeParams.job_card_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/jobcard/road-test-observation/get',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id
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
                    $scope.job_card_id = $routeParams.job_card_id;
                    $scope.job_order = res.job_order;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
    }
});

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//Expert Diagonis
app.component('jobCardExpertDiagnosisForm', {
    templateUrl: job_card_export_diagonosis_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_card_id = $routeParams.job_card_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/jobcard/expert-diagnosis/get',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id
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
                    $scope.job_card_id = $routeParams.job_card_id;
                    $scope.job_order = res.job_order;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
    }
});

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//Vehicle Inspection
app.component('jobCardVehicleInspectionForm', {
    templateUrl: job_card_vehicle_inspection_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_card_id = $routeParams.job_card_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/jobcard/vehicle-inspection/get',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id
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
                    $scope.job_card_id = $routeParams.job_card_id;
                    $scope.job_order = res.job_order;
                    $scope.extras = res.extras;
                    $scope.vehicle_inspection_item_groups = res.vehicle_inspection_item_groups;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
    }
});

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//DMS Check list
app.component('jobCardDmsChecklistForm', {
    templateUrl: job_card_dms_checklist_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_card_id = $routeParams.job_card_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/jobcard/dms-checklist/get',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id
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
                    $scope.job_card_id = $routeParams.job_card_id;
                    $scope.job_order = res.job_order;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.directive('jobcardHeader', function() {
    return {
        templateUrl: job_card_header_template_url,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('jobcardTabs', function() {
    return {
        templateUrl: jobcard_tabs_template_url,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------