app.component('jobCardTableList', {
    templateUrl: job_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_job_card').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.job_cards').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('job-cards')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-job-card');
        self.user = $scope.user = HelperService.getLoggedUser();
        self.search_key = '';
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
                    d.floor_supervisor_id = self.user.id;
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
            self.search_key = '';
            $('#job_cards_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#job_cards_list').DataTable().ajax.reload();
        });

        var dataTables = $('#job_cards_list').dataTable();
        $scope.searchJobCard = function() {
            dataTables.fnFilter(self.search_key);
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
        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/job-card/table-list";
                return false;
            } else {
                //alert();
                window.location = "#!/job-card/card-list";
                return false;
            }
        }

        $("#date").keyup(function() {
            self.date = this.value;
        });

        $scope.selectedCustomer = function(id) {
            $('#customer_id').val(id);
        }
        $scope.selectedVehicleModel = function(id) {
            $('#model_id').val(id);
        }

        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
        }

        $scope.onSelectedQuoteType = function(id) {
            $('#quote_type_id').val(id);
        }
        $scope.onSelectedServiceType = function(id) {
            $('#service_type_id').val(id);
        }
        $scope.onSelectedJobOrderType = function(id) {
            $('#job_order_type_id').val(id);
        }

        $scope.applyFilter = function() {
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
            $('#job-card-filter-modal').modal('hide');
            //$scope.fetchData();
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('jobCardCardList', {
    templateUrl: job_card_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search_job_card').focus();
        var self = this;

        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.job_cards').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('job-cards')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.user = $scope.user = HelperService.getLoggedUser();
        self.date = '';
        self.reg_no = '';
        self.job_card_no = '';
        self.service_type_id = '';
        self.quote_type_id = '';
        self.job_order_type_id = '';
        self.model_id = '';
        self.status_id = '';

        if (!localStorage.getItem('search_key')) {
            self.search_key = '';
        } else {
            self.search_key = localStorage.getItem('search_key');
        }

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
                        floor_supervisor_id: self.user.id,
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
        $scope.clear_search = function() {
            self.search_key = '';
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $scope.searchJobCard = function() {
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
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
                window.location = "#!/job-card/table-list";
                return false;
            } else {
                window.location = "#!/job-card/card-list";
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
        }
        $scope.onSelectedServiceType = function(id) {
            $('#service_type_id').val(id);
            self.service_type_id = id;
        }
        $scope.onSelectedJobOrderType = function(id) {
            $('#job_order_type_id').val(id);
            self.job_order_type_id = id;
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
            $('#job-card-filter-modal').modal('hide');
            self.customer_id = '';
            self.quote_type_id = '';
            self.service_type_id = '';
            self.status_id = '';
            self.job_order_type_id = '';
            setTimeout(function() {
                $scope.fetchData();
            }, 1000);
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
                            $location.path('/job-card/card-list');
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

        self.attachment_removal_id = [];
        $scope.remove_attachment = function(attachment_id, index) {
            console.log(attachment_id, index);
            if (attachment_id) {
                self.attachment_removal_id.push(attachment_id);
                $('#attachment_removal_ids').val(JSON.stringify(self.attachment_removal_id));
            }
            $scope.returnable_item.attachment.splice(index, 1);
        }

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

        setTimeout(function() {
            $('.image_uploadify').imageuploadify();
        }, 1000);
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
                    setTimeout(function() {
                        if ($scope.job_card.gate_passes.length > 0) {
                            angular.forEach($scope.job_card.gate_passes, function(gate_pass, key) {
                                $('#carousel_li_' + gate_pass.id + '0').addClass('active');
                                $('#carousel_inner_item_' + gate_pass.id + '0').addClass('active');
                            });
                        }
                    }, 1000);

                    $scope.job_order = res.job_order;
                    $scope.$apply();

                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.carouselLiChange = function(gatepass_id, index) {
            $('#carousel_parent_' + gatepass_id + " .carousel_li").removeClass('active');
            $('#carousel_parent_' + gatepass_id + " .carousel_inner_item").removeClass('active');
            $('#carousel_li_' + gatepass_id + index).addClass('active');
            $('#carousel_inner_item_' + gatepass_id + index).addClass('active');
        }
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
        var i = 0;

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
                    if (!$scope.gate_pass.gate_pass_detail.vendor_type_id) {
                        $scope.gate_pass.gate_pass_detail.vendor_type_id = 121;
                    }
                    $scope.job_card_id = $routeParams.job_card_id;
                    $scope.$apply();

                    i = $scope.gate_pass.gate_pass_items ? $scope.gate_pass.gate_pass_items.length : 0;
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
            var class_name = '.material_image_' + i;
            setTimeout(function() {
                $(class_name).imageuploadify();
            }, 100);
            $scope.gate_pass.gate_pass_items.push({
                item_description: '',
                item_make: '',
                item_model: '',
                item_serial_no: '',
                qty: '',
                remarks: '',
            });
            i++;
        }

        self.removeItem = function(index, $id) {
            i--;
            if ($id) {
                self.gate_pass_item_removal_ids.push($id);
                $('#gate_pass_item_removal_id').val(JSON.stringify(self.gate_pass_item_removal_ids));
            }
            $scope.gate_pass.gate_pass_items.splice(index, 1);
        }

        self.attachment_removal_id = [];
        $scope.remove_attachment = function(attachment_id, index) {
            console.log(attachment_id, index);
            if (attachment_id) {
                self.attachment_removal_id.push(attachment_id);
                $('#attachment_removal_ids').val(JSON.stringify(self.attachment_removal_id));
            }
            $(".attach_" + index).remove();
        }

        //Save Form Data 
        $scope.saveItemDetails = function() {
            var form_id = '#material_gatepass';
            var v = jQuery(form_id).validate({
                ignore: '',
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
                },
                messages: {

                },
                errorPlacement: function(error, element) {
                    error.insertAfter(element)
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
                            $location.path('/job-card/material-gatepass/' + $scope.job_card_id);
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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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

//PDF
app.component('jobCardPdf', {
    templateUrl: job_card_pdf_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;

        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $scope.gatepass_url = base_url + '/gigo-pkg/pdf/gatepass/' + $routeParams.job_card_id;
        $scope.covering_letter_url = base_url + '/gigo-pkg/pdf/covering-letter/' + $routeParams.job_card_id;
        $scope.estimate_url = base_url + '/gigo-pkg/pdf/estimate/' + $routeParams.job_card_id;
        $scope.insurance_estimate_url = base_url + '/gigo-pkg/pdf/insurance-estimate/' + $routeParams.job_card_id;
        $scope.revised_estimate_url = base_url + '/gigo-pkg/pdf/revised-estimate/' + $routeParams.job_card_id;
        $scope.job_card_pdf_url = base_url + '/gigo-pkg/pdf/job-card/' + $routeParams.job_card_id;
        $scope.job_card_spare_requisition_pdf_url = base_url + '/gigo-pkg/pdf/job-card-spare-requisition/' + $routeParams.job_card_id;
        $scope.work_order_outward_pdf_url = base_url + '/gigo-pkg/pdf/work-order-outward/' + $routeParams.job_card_id;
        $scope.work_order_inward_pdf_url = base_url + '/gigo-pkg/pdf/work-order-inward/' + $routeParams.job_card_id;
        $scope.warrenty_pick_list_pdf_url = base_url + '/gigo-pkg/pdf/warrenty-pick-list/' + $routeParams.job_card_id;
        $scope.vehicle_inward_pdf_url = base_url + '/gigo-pkg/pdf/vehicle-inward/' + $routeParams.job_card_id;
        $scope.vehicle_inspection_pdf_url = base_url + '/gigo-pkg/pdf/vehicle-inspection/' + $routeParams.job_card_id;
        $scope.tax_invoice_pdf_url = base_url + '/gigo-pkg/pdf/tax-invoice/' + $routeParams.job_card_id;
        $scope.service_proforma_pdf_url = base_url + '/gigo-pkg/pdf/service-proforma/' + $routeParams.job_card_id;
        $scope.service_proforma_cumulative_pdf_url = base_url + '/gigo-pkg/pdf/service-proforma-cumulative/' + $routeParams.job_card_id;

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
                    $scope.job_card = res.job_card;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Covering Letter
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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $route) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();
        $scope.add_part = [];
        $scope.job_order_part = [];

        self.add_part_indent = self.hasPermission('add-parts-indent');
        self.edit_part_indent = self.hasPermission('edit-parts-indent');
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
                    self.issued_parts = res.issued_parts;
                    self.extras = res.extras;
                    $scope.job_card = res.job_card;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

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
                        $scope.add_part.balance_qty = parseInt($scope.job_order_part.qty) - parseInt(issued_qty);
                        $scope.add_part.balance_qty_nos = $scope.add_part.balance_qty + " " + "nos";
                    });
            } else {
                $scope.add_part = [];
            }
        }

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        self.removeIssuedParts = function($id) {
            $('#delete_issued_part_id').val($id);
        }

        $scope.deleteConfirm = function() {
            $id = $('#delete_issued_part_id').val();
            $http.get(
                laravel_routes['deleteIssuedPart'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Issued Part Deleted Successfully');
                    $route.reload();
                    $scope.$apply();
                }
            });
        }

        //Save Form Data 
        $scope.submitPart = function() {
            var form_id = '#part_add';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'part_id': {
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
                    custom_noty('error', 'You have errors, Please check all');
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
                            $route.reload();
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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
//Payable Labour 
app.component('jobCardPayableLabourPartsForm', {
    templateUrl: job_card_parts_labour_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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

        $scope.removePayable = function(id, type) {
            $('.payable_type').val(type);
            $('.payable_id').val(id);
        }

        $scope.deleteConfirm = function() {
            var form_id = '#payable_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'payable_type': {
                        required: true,
                    },
                    'payable_id': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.delete_confirm').button('loading');
                    $.ajax({
                            url: base_url + '/api/jobcard/payable/delete',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $('.delete_confirm').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.delete_confirm').button('reset');
                            custom_noty('success', res.message);

                            $("#delete_payable_details").hide();
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();

                            $scope.fetchData();
                        })
                        .fail(function(xhr) {
                            $('.delete_confirm').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }
    }
});

app.component('jobcardPayableLabourForm', {
    templateUrl: job_card_payable_labour_form_template_url,
    controller: function($http, $location, HelperService, $scope, $route, $routeParams, $rootScope, $element) {
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
        $scope.job_order_repair_order_id = $routeParams.job_order_repair_order_id ? $routeParams.job_order_repair_order_id : '';

        if ($scope.job_order_repair_order_id) {
            self.action = 'Edit';
        } else {
            self.action = 'Add';
        }

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/repair-order-type-list/get',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;
                    $scope.extras = res.extras;
                    $scope.split_order_list = res.extras.split_order_list;
                    if ($scope.job_order_repair_order_id) {
                        $scope.getJobOrderRotFormData($scope.job_order_repair_order_id);
                    }
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //GET JOB ORDER ROT FORM DATA
        $scope.getJobOrderRotFormData = function(job_order_repair_order_id) {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/job-order-repair-order/get-form-data',
                    method: "POST",
                    data: {
                        id: job_order_repair_order_id
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order_labour = res.job_order_repair_order;
                    $scope.split_order_type_id = res.job_order_repair_order.split_order_type_id;
                    $scope.repair_order_type = res.job_order_repair_order.repair_order.repair_order_type;
                    $scope.fetchRotData($scope.repair_order_type.id);
                    $scope.repair_order = res.job_order_repair_order.repair_order;
                    $scope.job_order_labour.code = res.job_order_repair_order.repair_order.code;
                    $scope.job_order_labour.name = res.job_order_repair_order.repair_order.name;
                    $scope.job_order_labour.uom = res.job_order_repair_order.repair_order.uom;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        //GET ROT LIST BASE ON ROT TYPE
        $scope.fetchRotData = function(id) {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/get-repair-order-list/get',
                    method: "POST",
                    data: {
                        id: id
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.extras_rot = res.extras_list;
                    $scope.split_order_list = res.split_order_list;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        //GET ROT FORM DATA BASED ON SELECTED ROT
        $scope.getRotFormData = function(repair_order_id) {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/repair-order/get-form-data',
                    method: "POST",
                    data: {
                        id: repair_order_id
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order_labour = res.repair_order;
                    $scope.job_order_labour.qty = res.repair_order.hours;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }


        //Save Form Data 
        $scope.saveLabourForm = function() {
            var form_id = '#labour_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'rot_type_id': {
                        required: true,
                    },
                    'rot_id': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/add-repair-order/save',
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
                            //$route.reload();
                            $location.path('/job-card/payable-labour-parts/' + $scope.job_order.job_card.id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        /* Dropdown Arrow Function */
        arrowDropdown();

    }
});

app.component('jobcardPayablePartForm', {
    templateUrl: job_card_payable_part_form_template_url,
    controller: function($http, $location, HelperService, $scope, $route, $routeParams, $rootScope, $element) {
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
        $scope.job_order_part_id = $routeParams.job_order_part_id ? $routeParams.job_order_part_id : '';
        if ($scope.job_order_part_id) {
            self.action = 'Edit';
        } else {
            self.action = 'Add';
        }

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/part-list/get',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;
                    $scope.extras = res.extras;
                    $scope.split_order_list = res.extras.split_order_list;
                    if ($scope.job_order_part_id) {
                        $scope.getJobOrderPartFormData($scope.job_order_part_id);
                    }
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();


        //GET PART LIST
        self.searchPart = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getPartSearchList'], {
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

        $scope.getJobOrderPartFormData = function(job_order_part_id) {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/job_order-part/get-form-data',
                    method: "POST",
                    data: {
                        id: job_order_part_id,
                        job_order_id: $routeParams.job_order_id
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;
                    $scope.job_order_part = res.job_order_part.part;
                    $scope.job_order_part.qty = res.job_order_part.qty;
                    $scope.job_order_part.amount = res.job_order_part.amount;
                    $scope.job_order_part.uom = res.job_order_part.part.uom;
                    $scope.split_order_type_id = res.job_order_part.split_order_type_id;
                    $scope.split_order_list = res.split_order_list;
                    self.qty = parseInt(res.job_order_part.qty);
                    self.part = res.job_order_part.part;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        if ($scope.job_order_part_id) {
            $scope.getJobOrderPartFormData($scope.job_order_part_id);
        }

        $scope.getPartFormData = function(part_id) {
            if (!part_id) {
                $scope.job_order_part = [];
                return;
            }
            $.ajax({
                    url: base_url + '/api/vehicle-inward/part/get-form-data',
                    method: "POST",
                    data: {
                        id: part_id,
                        job_order_id: $routeParams.job_order_id
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }

                    $scope.job_order = res.job_order;
                    $scope.job_order_part = res.part;
                    $scope.job_order_part.amount = '0.00';
                    $scope.job_order_part.qty = 0;
                    $scope.split_order_list = res.split_order_list;
                    if (!isNaN(self.qty)) {
                        $scope.job_order_part.qty = self.qty;
                        $scope.job_order_part.amount = parseFloat($scope.job_order_part.qty * parseFloat($scope.job_order_part.rate)).toFixed(2);
                    }
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.onChangeQty = function() {
            if (!isNaN(self.qty)) {
                $scope.job_order_part.qty = self.qty;
            } else {
                $scope.job_order_part.qty = 0;
            }
            $scope.job_order_part.amount = parseFloat($scope.job_order_part.qty * parseFloat($scope.job_order_part.rate)).toFixed(2);
        }

        //Save Form Data 
        $scope.savePartForm = function() {
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
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
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
                            $location.path('/job-card/payable-labour-parts/' + $scope.job_order.jobcard.id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        /* Dropdown Arrow Function */
        arrowDropdown();
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
                    $scope.job_completed_status = res.job_completed_status;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.assignMechanic = function(repair_order_id) {
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

                    $('#selectedMachanic').val('');
                    $scope.repair_order = res.repair_order;
                    $scope.employee_details = res.employee_details;
                    $scope.repair_order_mechanics = res.repair_order_mechanics;

                    $.each($scope.repair_order_mechanics, function(key, employee_id) {
                        setTimeout(function() {
                            $scope.selectedEmployee(employee_id);
                        }, 500);
                    });

                    $('#assign_labours').modal('show');
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
            if ($('.check_uncheck_' + id).hasClass('bg-dark')) {
                $('.check_uncheck_' + id).removeClass('bg-dark');
                $('.check_uncheck_' + id).find('img').attr('src', '');
                self.selectedEmployee_ids = jQuery.grep(self.selectedEmployee_ids, function(value) {
                    return value != id;
                });
                $('#selectedMachanic').val(self.selectedEmployee_ids);
            } else {
                $('.check_uncheck_' + id).addClass('bg-dark');
                $('.check_uncheck_' + id).find('img').attr('src', './public/theme/img/content/icons/check-white.svg');
                if (self.selectedEmployee_ids.includes(id)) {
                    $('#selectedMachanic').val(self.selectedEmployee_ids);
                } else {
                    self.selectedEmployee_ids.push(id);
                    $('#selectedMachanic').val(self.selectedEmployee_ids);
                }
            }
        }
        //SAVE MECHANIC
        $scope.saveMechanic = function() {
            if (!$("#selectedMachanic").val()) {
                custom_noty('error', 'Kindly Select Employee to assign work!');
                return;
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
                            $location.path('/job-card/schedule/' + $routeParams.job_card_id);
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
                    $scope.part_details = res.part_details;
                    $scope.labour_details = res.labour_details;
                    $scope.extras = res.extras;
                    setTimeout(function() {
                        if ($scope.extras.split_order_types) {
                            angular.forEach($scope.extras.split_order_types, function(split_order, key) {
                                // split_order.total_items = 0;
                                var labour_sub_total = 0;
                                var part_sub_total = 0;
                                var grand_total = 0;
                                if (key == 0) {
                                    $('.split_order_panel_' + key).addClass('active in')
                                    $('.split_order_tab_' + key).addClass('active')
                                }
                                angular.forEach($scope.labour_details, function(labour, key1) {
                                    if (split_order.id == labour.split_order_type_id) {
                                        labour_sub_total += parseInt(labour.total_amount);
                                        // split_order.total_items += 1;
                                    }
                                });
                                $('.labour_sub_total_' + key).html(parseFloat(labour_sub_total).toFixed(2));

                                angular.forEach($scope.part_details, function(part, key2) {
                                    if (split_order.id == part.split_order_type_id) {
                                        part_sub_total += parseInt(part.total_amount);
                                        // split_order.total_items += 1;
                                    }
                                });
                                $('.part_sub_total_' + key).html(parseFloat(part_sub_total).toFixed(2));

                                grand_total = labour_sub_total + part_sub_total;
                                $('.amount_' + key).html(parseFloat(grand_total).toFixed(2));
                            });
                        }
                    }, 1000);
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.billDetailPDF = function(split_order_type_id) {
            $scope.job_card_solit_order_bill_details = base_url + '/gigo-pkg/pdf/job-card/bill-detail/' + $routeParams.job_card_id + '/' + split_order_type_id;
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
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
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
                    $scope.split_order_types = res.extras.split_order_types;
                    // $scope.unassigned_total_amount = res.unassigned_total_amount;
                    $scope.unassigned_total_count = res.unassigned_total_count;

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

                    $scope.$apply();

                    if ($scope.active_panel && $scope.active_panel != 0) {
                        if ($scope.extras.split_order_types[$scope.active_panel].total_items > 0) {
                            $('.panel').removeClass('active in');
                            $('.unassigned_tab').removeClass('active');
                            $('.split_order_tab_' + $scope.active_panel).addClass('active');
                            $('.split_order_panel_' + $scope.active_panel).addClass('active in');
                        } else {
                            $('.panel').removeClass('active in');
                            $('.split_order_tabs').removeClass('active');
                            $('.unassigned_panel').addClass('active in');
                            $('.unassigned_tab').addClass('active');
                        }
                    } else {
                        $('.panel').removeClass('active in');
                        $('.split_order_tabs').removeClass('active');
                        $('.unassigned_panel').addClass('active in');
                        $('.unassigned_tab').addClass('active');
                    }
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        var removeByAttr = function(arr, attr, value) {
            var i = arr.length;
            while (i--) {
                if (arr[i] &&
                    arr[i].hasOwnProperty(attr) &&
                    (arguments.length > 2 && arr[i][attr] === value)) {

                    arr.splice(i, 1);

                }
            }
            return arr;
        }

        $scope.splitOrderLabourChange = function(id, type, key, split_id) {
            var split_order_types;
            split_order_types = $scope.extras.split_order_types;
            if (type == 0) {} else {
                removeByAttr(split_order_types, 'id', split_id);
                split_order_types.unshift({ 'id': '-1', 'code': 'Unassigned Items', 'name': 'Unassigned Items' });
            }
            $scope.split_order_types = [];
            $scope.split_order_types = split_order_types;

            $('#labour_id').val(id);
            $('#part_id').val('');
            $scope.type = 'Labour';
            $scope.active_panel = key;

            $('#split_order_change').modal('show');
        }

        $scope.splitOrderPartChange = function(id, type, key, split_id) {
            var split_order_types;
            split_order_types = $scope.extras.split_order_types;
            if (type == 0) {} else {
                removeByAttr(split_order_types, 'id', split_id);
                split_order_types.unshift({ 'id': '-1', 'code': 'Unassigned Items', 'name': 'Unassigned Items' });
            }

            $scope.split_order_types = [];
            $scope.split_order_types = split_order_types;

            $('#part_id').val(id);
            $('#labour_id').val('');
            $scope.type = 'Part';
            $scope.active_panel = key;

            $('#split_order_change').modal('show');
        }

        //console.log($scope.user.token);
        $scope.splitOrderChange = function() {
            var split_form_id = '#split_order_form';
            var v = jQuery(split_form_id).validate({
                ignore: '',
                rules: {
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(split_form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/job-card/split-order/update',
                            method: "POST",
                            data: formData,
                            beforeSend: function(xhr) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                            },
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
                            $scope.split_order_id = '';
                            $('#split_order_change').modal('hide');
                            $('#split_order_type_id').val('');
                            $scope.fetchData();
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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
                    console.log(res);
                    if ($scope.job_card.job_order.vehicle.last_job_order == null || $scope.job_card.job_order.vehicle.last_job_order.job_card == null) {
                        console.log(' == ');
                    }
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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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
        self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

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

//---------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------

//Job Card PDF
app.component('jobCardPdfForm', {
    templateUrl: job_card_pdf_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        // self.view_only_part_indent = self.hasPermission('view-only-parts-indent');

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        var pdf_url = base_url + 'gigo-pkg/job-card/get-covering-letter-pdf/' + $scope.job_card_id;
        // $scope.job_card_id = $routeParams.job_card_id;
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