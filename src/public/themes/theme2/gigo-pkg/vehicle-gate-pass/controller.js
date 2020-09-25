app.component('vehicleGatePassCardList', {
    templateUrl: vehicle_gate_pass_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search_vehicle_gate_pass').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('vehicle-gate-passes')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.user = $scope.user = HelperService.getLoggedUser();
        self.gate_pass_created_date = '';
        self.registration_number = '';
        self.driver_name = '';
        self.driver_mobile_number = '';
        self.number = '';
        self.model_id = '';
        self.status_id = '';
        self.job_card_number = '';

        if (!localStorage.getItem('search_key')) {
            self.search_key = '';
        } else {
            self.search_key = localStorage.getItem('search_key');
        }

        //CARD LIST
        $scope.fetchData = function(search_key) {
            $.ajax({
                    url: base_url + '/api/vehicle-gate-pass/get-list',
                    type: "POST",
                    dataType: "json",
                    data: {
                        search_key: self.search_key,
                        gate_pass_created_date: self.gate_pass_created_date,
                        registration_number: self.registration_number,
                        driver_name: self.driver_name,
                        driver_mobile_number: self.driver_mobile_number,
                        number: self.number,
                        model_id: self.model_id,
                        status_id: self.status_id,
                        job_card_number: self.job_card_number,
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
                    $scope.vehicle_gate_passes = res.vehicle_gate_passes;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        // FOR FILTER
        $http.get(
            laravel_routes['getVehicleGatePassFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
        });

        $('.refresh_table').on("click", function() {
            $scope.fetchData();
        });
        $scope.clear_search = function() {
            self.search_key = '';
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $scope.searchKey = function() {
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $("#gate_pass_created_date").keyup(function() {
            self.gate_pass_created_date = this.value;
        });
        $("#registration_number").keyup(function() {
            self.registration_number = this.value;
        });
        $("#driver_name").keyup(function() {
            self.driver_name = this.value;
        });
        $("#driver_mobile_number").keyup(function() {
            self.driver_mobile_number = this.value;
        });
        $("#number").keyup(function() {
            self.number = this.value;
        });
        $("#job_card_number").keyup(function() {
            self.job_card_number = this.value;
        });

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/vehicle-gate-pass/table-list";
                return false;
            } else {
                window.location = "#!/vehicle-gate-pass/card-list";
                return false;
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

        $scope.selectedVehicleModel = function(id) {
            $('#model_id').val(id);
            self.model_id = id;
        }

        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            self.status_id = id;
        }

        $scope.applyFilter = function() {
            $scope.fetchData();
            $('#vehicle-gate-pass-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#gate_pass_created_date").val('');
            $("#registration_number").val('');
            $("#driver_name").val('');
            $("#driver_mobile_number").val('');
            $("#model_id").val('');
            $("#status_id").val('');
            $("#number").val('');
            $("#job_card_number").val('');
            self.gate_pass_created_date = '';
            self.registration_number = '';
            self.driver_name = '';
            self.driver_mobile_number = '';
            self.number = '';
            self.model_id = '';
            self.status_id = '';
            self.job_card_number = '';
            setTimeout(function() {
                $scope.fetchData();
            }, 1000);
            $('#vehicle-gate-pass-filter-modal').modal('hide');
        }

        //GATE OUT VEHICLE
        $scope.vehicleGateOut = function(id) {
            $.ajax({
                url: base_url + '/api/gate-out-vehicle/save',
                type: "POST",
                data: {
                    'gate_log_id': id
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    if (!response.success) {
                        showErrorNoty(response);
                        return;
                    }
                    $("#gate_pass").text(response.gate_out_data.gate_pass_no);
                    $("#vehicle_registration_number").text(response.gate_out_data.registration_number);
                    $('#confirm_notification').modal('show');
                    $('#vehicle-gate-pass-list').DataTable().ajax.reload();
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }
        $scope.reloadPage = function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $scope.fetchData();
        }

    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('vehicleGatePassTableList', {
    templateUrl: vehicle_gate_pass_table_list_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $element, $mdSelect) {
        $rootScope.loading = true;
        var self = this;
        HelperService.isLoggedIn()
        $('#search_vehicle_gate_pass').focus();
        $('li').removeClass('active');
        $('.vehicle_gate_passes').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('vehicle-gate-passes')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.user = $scope.user = HelperService.getLoggedUser();
        self.search_key = '';
        // var table_scroll;
        // table_scroll = $('.page-main-content.list-page-content').height() - 37;

        //LIST
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#vehicle-gate-pass-list').dataTable({
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
                url: laravel_routes['getVehicleGatePassList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.gate_pass_created_date = $("#gate_pass_created_date").val();
                    d.registration_number = $("#registration_number").val();
                    d.driver_name = $("#driver_name").val();
                    d.driver_mobile_number = $("#driver_mobile_number").val();
                    d.number = $("#number").val();
                    d.model_id = $("#model_id").val();
                    d.status_id = $("#status_id").val();
                    d.job_card_number = $("#job_card_number").val();
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
            },
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'date_and_time', name: 'date_and_time', searchable: false },
                { data: 'gate_pass_no', name: 'gate_passes.number' },
                { data: 'registration_number', name: 'vehicles.registration_number' },
                { data: 'driver_name', name: 'job_orders.driver_name' },
                { data: 'driver_mobile_number', name: 'job_orders.driver_mobile_number' },
                { data: 'model_name', name: 'models.model_name' },
                { data: 'job_card_number', name: 'job_orders.number' },
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
            $('#vehicle-gate-pass-list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#vehicle-gate-pass-list').DataTable().ajax.reload();
        });

        var dataTables = $('#vehicle-gate-pass-list').dataTable();

        $scope.searchKey = function() {
            dataTables.fnFilter(self.search_key);
        }

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/vehicle-gate-pass/table-list";
                return false;
            } else {
                window.location = "#!/vehicle-gate-pass/card-list";
                return false;
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

        // FOR FILTER
        $http.get(
            laravel_routes['getVehicleGatePassFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
        });

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

        $scope.selectedVehicleModel = function(id) {
            $('#model_id').val(id);
            self.model_id = id;
        }
        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            self.status_id = id;
        }
        $scope.applyFilter = function() {
            dataTables.fnFilter();
            $('#vehicle-gate-pass-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#gate_pass_created_date").val('');
            $("#number").val('');
            $("#registration_number").val('');
            $("#driver_name").val('');
            $("#driver_mobile_number").val('');
            $("#model_id").val('');
            $("#status_id").val('');
            $("#job_card_number").val('');
            dataTables.fnFilter();
            $('#vehicle-gate-pass-filter-modal').modal('hide');
        }

        //GATE OUT VEHICLE
        $scope.vehicleGateOut = function(id) {
            $.ajax({
                url: base_url + '/api/gate-out-vehicle/save',
                type: "POST",
                data: {
                    'gate_log_id': id
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    if (!response.success) {
                        showErrorNoty(response);
                        return;
                    }
                    $("#gate_pass").text(response.gate_out_data.gate_pass_no);
                    $("#vehicle_registration_number").text(response.gate_out_data.registration_number);
                    $('#confirm_notification').modal('show');
                    $('#vehicle-gate-pass-list').DataTable().ajax.reload();
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        $scope.reloadPage = function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $('#material_gate_pass_list').DataTable().ajax.reload();
        }
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('vehicleGatePassView', {
    templateUrl: vehicle_gate_pass_view_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams) {
        $rootScope.loading = true;
        var self = this;
        HelperService.isLoggedIn()

        $scope.hasPermission = HelperService.hasPermission;
        self.user = $scope.user = HelperService.getLoggedUser();
        /*if (!self.hasPermission('view-vehicle-gate-pass')) {
            $location.path('/page-permission-denied');
            return;
        }*/

        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-gate-pass/view',
                    type: "POST",
                    dataType: "json",
                    data: {
                        gate_log_id: $routeParams.id,
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
                    self.vehicle_gate_pass = res.view_vehicle_gate_pass;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //GATE OUT
        var form_id = '#gate_pass_out';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'remarks': {
                    maxlength: 191,
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/gate-out-vehicle/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                    })
                    .done(function(res) {
                        if (!res.success) {
                            showErrorNoty(res);
                            $('.submit').button('reset');
                            return;
                        }
                        $("#gate_pass").text(res.gate_out_data.gate_pass_no);
                        $("#registration_number").text(res.gate_out_data.registration_number);
                        $('#confirm_notification').modal('show');
                        $('.submit').button('reset');
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        showServerErrorNoty();
                    });
            }
        });

        $scope.reloadPage = function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $route.reload();
        }

    }
});