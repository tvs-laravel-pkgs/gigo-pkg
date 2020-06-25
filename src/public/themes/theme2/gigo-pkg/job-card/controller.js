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
                { data: 'created_at', },
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
                        //console.log('add');
                        value.selected = false;
                        value.status_id = 8240;
                        value.status.name = 'Free';
                    } else if (value.selected == true && value.id == $scope.job_card.bay_id) {
                        //console.log('edit');
                        value.selected = false;
                        value.status_id = 8240;
                        value.status.name = 'Free';

                    }
                });
                bay.selected = true;
                bay.status.name = 'Selected';
                $scope.bay_id = bay.id;

            } else {
                bay.selected = false;
            }
            console.log($scope.bay_id);
            console.log(bay);

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
                    console.log($scope.returnable_item_attachement_path);
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
    }
});

app.component('jobCardReturnableItemForm', {
    templateUrl: job_card_returnable_item_form_template_url,
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
        $scope.returnable_item_id = $routeParams.id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/job-card/returnable-items/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id,
                        returnable_item_id: $routeParams.id
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
                    $scope.returnable_item = res.returnable_item;
                    console.log($scope.returnable_item);
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
        //Save Form Data 
        $scope.saveReturnableItem = function() {
            var form_id = '#returnable_item';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'job_card_returnable_items[0][item_name]': {
                        required: true,
                    },
                    'job_card_returnable_items[0][item_description]': {
                        required: true,
                    },
                    'job_card_returnable_items[0][item_make]': {
                        maxlength: 191,
                    },
                    'job_card_returnable_items[0][item_model]': {
                        maxlength: 191,
                    },
                    'job_card_returnable_items[0][item_serial_no]': {
                        maxlength: 191,
                    },
                    'job_card_returnable_items[0][qty]': {
                        required: true,
                        number: true,
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
                            url: base_url + '/api/job-card/returnable-item/save',
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
                            $location.path('/gigo-pkg/job-card/returnable-item/' + $scope.job_card.id);
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
                    url: base_url + '/api/material-gatepass/view',
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
                    $scope.job_card = res.view_metrial_gate_pass;
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
//Material Outward
app.component('jobCardMaterialOutwardForm', {
    templateUrl: job_card_material_outward_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $timeout) {
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
        self.gate_pass_item_removal_ids = [];

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/material-gatepass/get-form-data',
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
                    $scope.job_card = res.job_card;
                    self.vendor = $scope.gate_pass.gate_pass_detail.vendor;
                    if(!$scope.gate_pass.gate_pass_detail.vendor_type_id){
                        $scope.gate_pass.gate_pass_detail.vendor_type_id = 121;
                    }
                    $scope.job_card_id = $routeParams.job_card_id;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //GET VEHICLE MODEL LIST
        self.searchVendorCode = function(query, type_id) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getVendorCodeSearchList'], {
                                key: query,
                                type_id: type_id,
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

        //GET VENDOR INFO
        $scope.selectedVendorCode = function(id) {
            self.isFire = true;
            if (id) {
                $.ajax({
                        url: laravel_routes['getVendorDetails'],
                        method: "POST",
                        data: {
                            id: id,
                        },
                    })
                    .done(function(res) {
                        if (!res.success) {
                            showErrorNoty(res);
                            return;
                        }
                        $scope.gate_pass.gate_pass_detail.vendor = res.vendor_details;
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        }

        $scope.vendorTypeOnchange = function() {
            $scope.gate_pass.gate_pass_detail.vendor = [];
            self.modelSearchText = [];
            $scope.vendor = [];
        }

        $scope.vendorTextChange = function() {
            $scope.gate_pass.gate_pass_detail.vendor = [];
        }

        $scope.addNewItem = function() {
            $scope.gate_pass.gate_pass_items.push({
                item_description: '',
                item_make: '',
                item_model: '',
                item_serial_no: '',
                qty: '',
                remarks: '',
            });
        }

        self.removeItem = function(index, $id) {
            if ($id) {
                self.gate_pass_item_removal_ids.push($id);
                $('#gate_pass_item_removal_id').val(JSON.stringify(self.gate_pass_item_removal_ids));
            }
            $scope.gate_pass.gate_pass_items.splice(index, 1);
        }

        //Save Form Data 
        $scope.saveItemDetails = function() {
            var form_id = '#material_gatepass';
            var v = jQuery(form_id).validate({
                errorPlacement: function(error, element) {
                    show_alert = true;
                    error.insertAfter(element)
                },
                invalidHandler: function(form, validator) {
                    console.log('Errors!!');
                },
                ignore: [],
                rules: {
                    'vendor_id': {
                        required: true,
                    },
                    'vendor_contact_no': {
                        required: true,
                    },
                    'work_order_no': {
                        required: true,
                    },
                    'work_order_description': {
                        required: true,
                    },
                    'item_description[]': {
                        required: true,
                    },
                    'item_make[]': {
                        required: true,
                    },
                    'item_model[]': {
                        required: true,
                    },
                    'item_serial_no[]': {
                        required: true,
                    },
                    'qty[]': {
                        required: true,
                    },
                    'remarks[]': {
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
                            url: base_url + '/api/material-gatepass/save',
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
                            $location.path('/gigo-pkg/job-card/material-gatepass/' + $scope.job_card_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //Buttons to navigate between tabs
        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });

        setTimeout(function() {
            $('.image_uploadify').imageuploadify();
        }, 1000);

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
                    $scope.job_card = res.job_card;
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
                    $scope.job_card = res.job_card;
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
                    $scope.job_card = res.job_card;
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
                    $scope.job_card = res.job_card;
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
//Part Indent
app.component('jobCardPartIndentForm', {
    templateUrl: job_card_part_indent_template_url,
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
                    url: base_url + '/api/jobcard/part-indent/get',
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
                    self.job_card_id = $routeParams.job_card_id;
                    self.issued_parts_details = res.issued_parts_details;
                    self.part_list = res.part_list;
                    self.mechanic_list = res.mechanic_list;
                    self.issued_mode = res.issued_mode;
                    $scope.job_order = res.job_order;
                    $scope.job_card = res.job_card;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.onSelectedpartcode = function(part_code_selected) {
            $('#part_code').val(part_code_selected);
            if (part_code_selected) {
                return new Promise(function(resolve, reject) {
                    $http.post(
                            laravel_routes['getPartDetails'], {
                                key: part_code_selected,
                                job_order_id: $scope.job_card.job_order_id,
                            }
                        )
                        .then(function(response) {
                            if (response.data.parts_details.id != null) {
                                self.parts_details = response.data.parts_details;
                                $("#job_order_part_id").val(self.parts_details.id);
                                $("#req_qty").text(self.parts_details.qty + " " + "nos");
                                $("#issue_qty").text(self.parts_details.issued_qty + " " + "nos");
                                issued_qty = self.parts_details.issued_qty;
                                if (issued_qty == null) {
                                    issued_qty = 0;
                                    $("#issue_qty").text(issued_qty + " " + "nos");
                                }
                                balance_qty = parseInt(self.parts_details.qty) - parseInt(issued_qty);
                                $("#balance_qty").text(balance_qty + " " + "nos");
                                $("#bal_qty").val(balance_qty);
                            } else {
                                $("#req_qty").text("0 nos");
                                $("#issue_qty").text("0 nos");
                                $("#balance_qty").text("0 nos");
                                $("#bal_qty").val(0);
                            }
                        });
                });
            } else {
                return [];
            }
        }

        $scope.onSelectedmech = function(machanic_id_selected) {
            $('#machanic_id').val(machanic_id_selected);
        }
        $scope.onSelectedmode = function(issue_modeselected) {
            $('#issued_mode').val(issue_modeselected);
        }

        self.removeIssedParts = function($id) {
            $('#delete_issued_part_id').val($id);
        }

        $scope.deleteConfirm = function() {
            $id = $('#delete_issued_part_id').val();
            $http.get(
                laravel_routes['deleteIssedPart'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Issed Part  Deleted Successfully');
                    $('#pause_work_reason_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/job-card/part-indent/' + $routeParams.job_card_id);
                }
            });
        }

        //Save Form Data 
        var form_id = '#part_add';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'part_code': {
                    required: true,
                },
                'issued_qty': {
                    required: true,
                },
                'issued_to_id': {
                    required: true,
                },
                'issued_mode': {
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
                        url: laravel_routes['savePartsindent'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            $('.submit').button('reset');
                            $('#issued_qty').val(" ");
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/job-card/part-indent/' + $routeParams.job_card_id);
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                $('#part_code').val(" ");
                                $('#issued_qty').val(" ");
                                $('#machanic_id').val(" ");
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/job-card/part-indent/' + $routeParams.job_card_id);
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

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//Schedule Maintendance
app.component('jobCardScheduleMaintenanceForm', {
    templateUrl: job_card_schedule_maintendance_template_url,
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
                    url: base_url + '/api/jobcard/schedule-maintenance/get',
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
                    $scope.schedule_maintenance = res.schedule_maintenance;
                    $scope.job_card = res.job_card;
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
//Schedule Maintendance
app.component('jobCardPayableLabourPartsForm', {
    templateUrl: job_card_parts_labour_template_url,
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
                    url: base_url + '/api/jobcard/payable-labour-part/get',
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
                    $scope.part_details = res.part_details;
                    $scope.labour_details = res.labour_details;
                    $scope.total_amount = res.total_amount;
                    $scope.parts_total_amount = res.parts_total_amount;
                    $scope.labour_total_amount = res.labour_total_amount;
                    $scope.job_card = res.job_card;
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
//SCHEDULES
app.component('jobCardScheduleForm', {
    templateUrl: job_card_schedule_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $route) {
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
                    url: base_url + '/api/job-card/labour-assignment/get-form-data',
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
                    console.log(res);
                    $scope.job_card = res.job_card_view;
                    // $scope.employee_details = res.employee_details;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //ASSIGN MECHANIC
        // $scope.assign_mechanic = function(code, name) {
        //     console.log(code, name);
        //     $scope.job_card.repair_order_code = code;
        //     $scope.job_card.repair_order_name = name;
        // }
        $scope.assignMechanic = function(repair_order_id) {
            // console.log(repair_order_id);
            $('.assign_mechanic_' + repair_order_id).button('loading');
            $.ajax({
                    url: base_url + '/api/job-card/get-mechanic',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id,
                        repair_order_id: repair_order_id
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
                    console.log(res);
                    $scope.job_card = res.job_card;
                    $scope.repair_order = res.repair_order;
                    $scope.employee_details = res.employee_details;
                    angular.forEach($scope.job_card.job_order.job_order_repair_orders, function(value, key) {
                        if (value.repair_order_mechanics && value.repair_order_id == repair_order_id) {
                            angular.forEach(value.repair_order_mechanics, function(value, key) {
                                setTimeout(function() {
                                    $scope.selectedEmployee(value.mechanic_id);
                                }, 500);
                            });
                        } else {
                            $('#selectedMachanic').val('');
                        }
                    });
                    $('#assign_labours').modal('show');
                    // $("#selectedMachanic").;
                    $('.assign_mechanic_' + repair_order_id).button('reset');
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $('.assign_mechanic_' + repair_order_id).button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        self.selectedEmployee_ids = [];
        $scope.selectedEmployee = function(id) {
            // console.log(id);
            if ($('.check_uncheck_' + id).hasClass('bg-dark')) {
                console.log("1");
                $('.check_uncheck_' + id).removeClass('bg-dark');
                $('.check_uncheck_' + id).find('img').attr('src', '');
                self.selectedEmployee_ids = jQuery.grep(self.selectedEmployee_ids, function(value) {
                    return value != id;
                });
                // console.log(self.selectedEmployee_ids);
                $('#selectedMachanic').val(self.selectedEmployee_ids);
            } else {
                // console.log("2");
                $('.check_uncheck_' + id).addClass('bg-dark');
                $('.check_uncheck_' + id).find('img').attr('src', './public/theme/img/content/icons/check-white.svg');
                if (self.selectedEmployee_ids.includes(id)) {
                    $('#selectedMachanic').val(self.selectedEmployee_ids);
                } else {
                    // console.log("2");
                    self.selectedEmployee_ids.push(id);
                    $('#selectedMachanic').val(self.selectedEmployee_ids);
                }
                // console.log(self.selectedEmployee_ids);
            }
        }
        //SAVE MECHANIC
        $scope.saveMechanic = function() {
            if (!$("#selectedMachanic").val()) {
                custom_noty('error', 'Kindly Select Employee to assign work!');
            }
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'selected_mechanic_ids': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/job-card/save-mechanic',
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
                            $("#assign_labours").modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            $route.reload();
                            // $location.path('/gigo-pkg/job-card/schedule/' + $routeParams.job_card_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //GET SINGLE MECHANIC TIME LOG
        $scope.getMechanicTimeLog = function(repair_order_mechanic_id, repair_order_id) {
            $.ajax({
                    url: base_url + '/api/job-card/mechanic-time-log',
                    method: "POST",
                    data: {
                        // id: $routeParams.job_card_id,
                        repair_order_mechanic_id: repair_order_mechanic_id,
                        repair_order_id: repair_order_id
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
                    console.log(res);
                    $scope.repair_order_mechanic_time_logs = res.data.repair_order_mechanic_time_logs;
                    $scope.repair_order_detail = res.data.repair_order;
                    $scope.total_duration = res.data.total_duration;
                    // $scope.employee_details = res.employee_details;
                    // console.log($scope.job_card);
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.viewTimeLog = function(job_order_repair_order_id) {
            // console.log(repair_order_id);
            // return;
            $.ajax({
                    url: base_url + '/api/get-job-card-time-log',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id,
                        job_order_repair_order_id: job_order_repair_order_id
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
                    console.log(res);
                    $scope.job_order_repair_order_time_log = res.job_order_repair_order_time_log;
                    // $("#selectedMachanic").;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.saveJobStatus = function() {
            $('.job_completed').button('loading');
            $.ajax({
                    url: base_url + '/api/job-card/update-status',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id,
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

                    $('.job_completed').button('reset');
                    custom_noty('success', res.message);
                    $route.reload();
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $('.job_completed').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
        }
    }
});

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//Schedule Review
app.component('jobCardLabourReview', {
    templateUrl: job_card_labour_review_template_url,
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
        $scope.job_order_repair_order_id = $routeParams.job_order_repair_order_id;

        //FETCH DATA
        $scope.fetchLabourReviewData = function() {
            // console.log(1);
            $.ajax({
                    url: base_url + '/api/get-labour-review',
                    method: "POST",
                    data: {
                        id: $routeParams.job_card_id,
                        job_order_repair_order_id: $routeParams.job_order_repair_order_id
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
                    console.log(res);
                    $scope.labour_review_data = res.labour_review_data;
                    $scope.job_order_repair_order = res.job_order_repair_order;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchLabourReviewData();

        //Save Form Data 
        $scope.saveLabourReview = function() {
            var form_id = '#labour_review_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'status_id': {
                        required: true,
                        maxlength: 4,
                    },
                    'observation': {
                        required: true,
                    },
                    'action_taken': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/labour-review-save',
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
                            $location.path('/gigo-pkg/job-card/schedule/' + $routeParams.job_card_id);
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

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//Bill Details
app.component('jobCardBillDetailView', {
    templateUrl: job_card_bil_detail_template_url,
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
                    url: base_url + '/api/job-card/bill-detail/view',
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
                    console.log(res);
                    $scope.job_card = res.job_card;
                    console.log($scope.job_card);
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
//Bay Details
app.component('jobCardBayView', {
    templateUrl: job_card_bay_view_template_url,
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
        console.log('job_card ' + $scope.job_card_id);
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/job-card/bay-view/get',
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
                    $scope.job_card = res.job_card;
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
//Split Order Details
app.component('jobCardSplitOrder', {
    templateUrl: job_card_split_order_template_url,
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
                    url: base_url + '/api/job-card/split-order/view',
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
                    //console.log(res);
                    $scope.job_card = res.job_card;
                    $scope.labour_details = res.labour_details;
                    $scope.part_details = res.part_details;
                    $scope.extras = res.extras;
                    $scope.unassigned_total_amount = res.unassigned_total_amount;
                    $scope.unassigned_total_count = res.unassigned_total_count;
                    //console.log($scope.job_card);
                    //console.log($scope.job_card);
                    //console.log($scope.extras);
                    //console.log($scope.labour_details);
                    //console.log($scope.part_details);
                    // var unassigned_total_amount=0;
                    // var unassigned_total_items=0;
                    // var labour_ids=[];
                    // var part_ids =[];
                    angular.forEach($scope.extras.split_order_types, function(split_order, key) {
                        split_order.total_amount = 0;
                        split_order.total_items = 0;
                        angular.forEach($scope.labour_details, function(labour, key1) {
                            if (split_order.id == labour.split_order_type_id) {
                                split_order.total_amount += parseInt(labour.total_amount);
                                split_order.total_items += 1;
                            }
                        });

                        angular.forEach($scope.part_details, function(part, key2) {
                            if (split_order.id == part.split_order_type_id) {
                                split_order.total_amount += parseInt(part.total_amount);
                                split_order.total_items += 1;
                            }
                        });

                    });
                    // console.log(part_ids);
                    // console.log(labour_ids);
                    //$scope.unassigned_total_amount = parseFloat(unassigned_total_amount).toFixed(2);
                    //$scope.unassigned_total_items = unassigned_total_items;*/

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();


        $scope.splitOrderLabourChange = function(id) {
            // alert(id);
            console.log(id);
            $('#labour_id').val(id);
            $('#part_id').val('');
            $scope.type = 'Labour';
        }

        $scope.splitOrderPartChange = function(id) {
            // alert(id);
            $('#part_id').val(id);
            $('#labour_id').val('');
            $scope.type = 'Part';
        }

        console.log($scope.user.token);
        $scope.splitOrderChange = function() {
            //console.log('in');
            var split_form_id = '#split_order_form';
            var v = jQuery(split_form_id).validate({
                ignore: '',
                rules: {
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    //alert('submit');
                    let formData = new FormData($(split_form_id)[0]);
                    //$('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/job-card/split-order-update',
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

                            $scope.fetchData();

                            // $location.path('/gigo-pkg/job-card/bill-detail/' + $routeParams.job_card_id);
                            //$scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        /* var c = {};
         $("#contact-list tr").draggable({
                 helper: "clone",
                 start: function(event, ui) {
                     c.tr = this;
                     c.helper = ui.helper;
                 }
         });
         $("#guest-list tr").droppable({
             drop: function(event, ui) {
                 var guest = ui.draggable.text();
                 var copy = $(this);
                var copy = $(this);
                 copy.clone(true).find(":input").val("").end().insertAfter(copy);
                 $(c.tr).remove();
                 $(c.helper).remove();
             }
            });*/

        $tabs = $(".tabbable");

        $("tbody.connectedSortable")
            .sortable({
                connectWith: ".connectedSortable",
                items: "> tr",
                appendTo: $tabs,
                helper: "clone",
                zIndex: 999990,
                start: function(event, ui) {
                    console.log(ui);

                    $tabs.addClass("dragging")
                },
                stop: function(event, ui) {
                    console.log(event);
                    $tabs.removeClass("dragging")
                }
            })
            .disableSelection();

        var $tab_items = $(".panel-group > tbody", $tabs).droppable({
            accept: ".connectedSortable tr",
            hoverClass: "ui-state-hover",
            over: function(event, ui) {
                var $item = $(this);
                // $item.find("a").tab("show");

            },
            drop: function(event, ui) {
                console.log('drop');
                return false;
            }
        });



        /*$tabs = $(".tabbable");

    $('.nav-tabs a').click(function(e) {
        e.preventDefault();
        $(this).tab('show');
    })
    
    $( "tbody.connectedSortable" )
        .sortable({
            connectWith: ".connectedSortable",
            items: "> tr:not(:first)",
            appendTo: $tabs,
            helper:"clone",
            zIndex: 999990,
            start: function(){ $tabs.addClass("dragging") },
            stop: function(){ $tabs.removeClass("dragging") }
        })
        .disableSelection()
    ;


    $("#table1 .childgrid tr, #table2 .childgrid tr").draggable({
      helper: function(){
          var selected = $('.childgrid tr.selectedRow');
        if (selected.length === 0) {
          selected = $(this).addClass('selectedRow');
        }
        var container = $('<div/>').attr('id', 'draggingContainer');
    container.append(selected.clone().removeClass("selectedRow"));
    return container;
      }
 });

$("#table1 .childgrid, #table2 .childgrid").droppable({
    drop: function (event, ui) {
    $(this).append(ui.helper.children());
    $('.selectedRow').remove();
    }
});

$(document).on("click", ".childgrid tr", function () {
    $(this).toggleClass("selectedRow");
});*/


        /*var $tab_items = $( ".nav-tabs > li", $tabs ).droppable({
          accept: ".connectedSortable tr",
          hoverClass: "ui-state-hover",
          over: function( event, ui ) {
            var $item = $( this );
            $item.find("a").tab("show");
            
          },
          drop: function( event, ui ) {
            return false;
          }
        });*/

        //$(".listitems" ).draggable();

        /*   var index_value = ui.item.index();
                    var count_value = ui.item.closest("tbody").find(".tr_scheme_priorities").length;
                    var inc_index_value = index_value + 1;
                    // console.log(' == total ===' + count_value);
    //DOWN
                    for (var i = inc_index_value; i < count_value; i++) {
                        var scheme_type_id = ui.item.closest("tbody").find(".tr_scheme_priorities").eq(i).attr('data-scheme_type_id');
                        var down_sorting_data = 'scheme_type_id=' + scheme_type_id + '&priority=' + i;
                        // console.log(' == down === scheme_type_id ==' + scheme_type_id + ' == priority=' + i);
                        $.ajax({
                                url: update_scheme_type_priority_ajax_url,
                                type: "POST",
                                async: false,
                                data: down_sorting_data,
                                processData: false,
                            })
                            .done(function(data) {

                            }).fail(function(xhr) {
                                custom_noty('error', 'Something went wrong at server');
                            });
                    }
    //UP
                    for (var i = 0; i < index_value; i++) {
                        var scheme_type_id = ui.item.closest("tbody").find(".tr_scheme_priorities").eq(i).attr('data-scheme_type_id');
                        var up_sorting_data = 'scheme_type_id=' + scheme_type_id + '&priority=' + i;
                        // console.log(' == up === scheme_type_id ==' + scheme_type_id + '===priority=' + i);
                        $.ajax({
                                url: update_scheme_type_priority_ajax_url,
                                type: "POST",
                                async: false,
                                data: up_sorting_data,
                                processData: false,
                            })
                            .done(function(data) {

                            }).fail(function(xhr) {
                                custom_noty('error', 'Something went wrong at server');
                            });
                    }
//CURRENT
                    var current_scheme_type_id = ui.item.attr('data-scheme_type_id');
                    var current_data = 'scheme_type_id=' + current_scheme_type_id + '&priority=' + index_value;
                    // console.log(' == current === scheme_type_id ==' + current_scheme_type_id + ' == priority=' + index_value);
                    $.ajax({
                            url: update_scheme_type_priority_ajax_url,
                            type: "POST",
                            async: false,
                            data: current_data,
                            processData: false,
                        })
                        .done(function(data) {
                            if (data.success) {
                                get_list();
                                $('#sortable tbody').sortable('option', 'disabled', false);
                                custom_noty('success', 'Scheme Priorities updated successfully');
                            } else {
                                custom_noty('error', 'Something went wrong at server');
                            }

                        }).fail(function(xhr) {
                            custom_noty('error', 'Something went wrong at server');
                        });*/

        /*function dragstart_handler(ev) {
            // Add the target element's id to the data transfer object
            ev.dataTransfer.setData("application/my-app", ev.target.id);
            ev.dataTransfer.dropEffect = "move";
        }

        function dragover_handler(ev) {
            ev.preventDefault();
            ev.dataTransfer.dropEffect = "move"
        }

        function drop_handler(ev) {
            ev.preventDefault();
            // Get the id of the target and add the moved element to the target's DOM
            const data = ev.dataTransfer.getData("application/my-app");
            ev.target.appendChild(document.getElementById(data));
        }*/
    }
});
//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//Update Bill Details
app.component('jobCardUpdateBillDetail', {
    templateUrl: job_card_bil_detail_update_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $('.image_uploadify').imageuploadify();
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_card_id = $routeParams.job_card_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/job-card/bill-update/get-form-data',
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
                    console.log(res);
                    $scope.job_card = res.job_card;
                    console.log($scope.job_card);
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveForm = function() {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'bill_date': {
                        required: true,
                    },
                    'bill_number': {
                        required: true,
                    },
                    'bill_copy': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/job-card/bill-update',
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
                            $location.path('/gigo-pkg/job-card/bill-detail/' + $routeParams.job_card_id);
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

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------
//Estimate
app.component('jobCardEstimateForm', {
    templateUrl: job_card_estimate_template_url,
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
                    url: base_url + '/api/jobcard/estimate/get',
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
                    $scope.job_card = res.job_card;
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
//Estimate Status
app.component('jobCardEstimateStatusForm', {
    templateUrl: job_card_estimate_status_template_url,
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
                    url: base_url + '/api/jobcard/estimate-status/get',
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
                    $scope.attachement_path = res.attachement_path;
                    $scope.job_card = res.job_card;
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
//Gate In Details
app.component('jobCardGateinDetailForm', {
    templateUrl: job_card_gatein_template_url,
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
                    url: base_url + '/api/jobcard/gate-in-detial/get',
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
                    $scope.job_card = res.job_card;
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
//Vehicle  Details
app.component('jobCardVehicleDetailView', {
    templateUrl: job_card_vehicle_detail_template_url,
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
                    url: base_url + '/api/jobcard/vehicle-detial/get',
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
                    $scope.job_card = res.job_card;
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
//Customer  Details
app.component('jobCardCustomerDetailView', {
    templateUrl: job_card_customer_detail_template_url,
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
                    url: base_url + '/api/jobcard/customer-detial/get',
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
                    $scope.job_card = res.job_card;
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
//Order  Details
app.component('jobCardOrderDetailView', {
    templateUrl: job_card_order_detail_template_url,
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
                    url: base_url + '/api/jobcard/order-detial/get',
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
                    $scope.job_card = res.job_card;
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
//Inventory
app.component('jobCardInventoryView', {
    templateUrl: job_card_inventory_template_url,
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
                    url: base_url + '/api/jobcard/inventory/get',
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
                    $scope.job_card = res.job_card;
                    $scope.inventory_list = res.inventory_list;
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
//Inventory
app.component('jobCardCaptureVocView', {
    templateUrl: job_card_capture_voc_template_url,
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
                    url: base_url + '/api/jobcard/capture-voc/get',
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
                    //$scope.job_order = res.job_order;
                    $scope.job_card = res.job_card;
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