app.component('vehicleList', {
    templateUrl: vehicle_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_vehicle').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('vehicles')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-vehicle');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#vehicles_list').DataTable({
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
                    $('#search_vehicle').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getVehicleList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.engine_numbers = $('#engine_numbers').val();
                    d.chassis_numbers = $('#chassis_numbers').val();
                    d.model_ids = $('#model_ids').val();
                    d.registration_numbers = $('#registration_numbers').val();
                    d.vin_numbers = $('#vin_numbers').val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'engine_number', name: 'vehicles.engine_number' },
                { data: 'chassis_number', name: 'vehicles.chassis_number' },
                { data: 'model_name', name: 'models.model_name' },
                { data: 'registration_number', name: 'vehicles.registration_number' },
                { data: 'vin_number', name: 'vehicles.vin_number' },
                { data: 'sold_date', name: 'vehicles.sold_date' },
                { data: 'status', name: '' },

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
            $('#search_vehicle').val('');
            $('#vehicles_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#vehicles_list').DataTable().ajax.reload();
        });

        var dataTables = $('#vehicles_list').dataTable();
        $("#search_vehicle").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteVehicle = function($id) {
            $('#vehicle_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#vehicle_id').val();
            $http.get(
                laravel_routes['deleteVehicle'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Vehicle Deleted Successfully');
                    $('#vehicles_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/vehicle/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getVehicleFilterData']
        ).then(function(response) {
            // console.log(response);
            self.extras = response.data.extras;
            self.make_list = response.data.make_list;
            //self.model_list = response.data.model_list;
        });

        $scope.onSelectedmodel = function(model_selected) {
            $('#model_ids').val(model_selected);
        }

        $scope.SelectedMake = function(SelectedMake) {
            if (SelectedMake) {
                return new Promise(function(resolve, reject) {
                    $http.post(
                            laravel_routes['getModelList'], {
                                key: SelectedMake,
                            }
                        )
                        .then(function(response) {
                            self.model_list = response.data.model_list;
                        });
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
        $scope.applyFilter = function() {
            $('#status').val(self.status);
            dataTables.fnFilter();
            $('#vehicle-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#engine_numbers").val('');
            $("#chassis_numbers").val('');
            $("#model_ids").val('');
            $("#registration_numbers").val('');
            $("#vin_numbers").val('');
            $("#status").val('');
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('vehicleForm', {
    templateUrl: vehicle_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-vehicle') || !self.hasPermission('edit-vehicle')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getVehicleFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.vehicle = response.data.vehicle;
            self.make_id = response.data.make_id;
            self.make_list = response.data.make_list;
            self.model_list = response.data.model_list;
            self.sold_date = response.data.sold_date;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.vehicle.deleted_at) {
                    self.switch_value = 'Inactive';
                    self.register_val = 'Yes';
                } else {
                    self.switch_value = 'Active';
                    self.register_val = 'Yes';
                }
            } else {
                self.switch_value = 'Active';
                self.register_val = 'Yes';
                self.show = 1;
            }
            if (self.action == 'Edit') {
                if (self.vehicle.is_registered == 1) {
                    self.register_val = 'Yes';
                    self.show = 1;
                } else {
                    self.register_val = 'No';
                    self.show = 2;
                }
            }

        });

        $scope.RegistrationChange = function(reg_selected) {
            if (reg_selected == 'Yes')
                self.show = 1;
            else
                self.show = 2;
        }

        $scope.SelectedMake = function(SelectedMake) {
            if (SelectedMake) {
                return new Promise(function(resolve, reject) {
                    $http.post(
                            laravel_routes['getModelList'], {
                                key: SelectedMake,
                            }
                        )
                        .then(function(response) {
                            self.model_list = response.data.model_list;
                        });
                });
            } else {
                return [];
            }
        }

        //Save Form Data 
        var form_id = '#vehicle_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'engine_number': {
                    required: true,
                    minlength: 10,
                    maxlength: 64,
                },
                'chassis_number': {
                    required: true,
                    minlength: 10,
                    maxlength: 64,
                },
                /*'model_id': {
                    required:true,
                },*/
                'registration_number': {
                    minlength: 10,
                    maxlength: 10,
                },
                'vin_number': {
                    minlength: 10,
                    maxlength: 32,
                },
                /*'sold_date':{
                    required:true,
                },*/
            },
            messages: {
                'engine_number': {
                    minlength: 'Minimum 10 Characters',
                    maxlength: 'Maximum 64 Characters',
                },
                'chassis_number': {
                    minlength: 'Minimum 10 Characters',
                    maxlength: 'Maximum 64 Characters',
                },
                'registration_number': {
                    minlength: 'Minimum 10 Characters',
                    maxlength: 'Maximum 32 Characters',
                },
                'vin_number': {
                    minlength: 'Minimum 10 Characters',
                    maxlength: 'Maximum 10 Characters',
                }
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveVehicle'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/vehicle/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/vehicle/list');
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
app.component('vehicleDataView', {
    templateUrl: vehicle_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {

        var self = this;
        self.hasPermission = HelperService.hasPermission;
        /* if (self.hasPermission('view-vehicle')) {
             window.location = "#!/page-permission-denied";
             return false;
         }*/
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getVehicles'], {
                params: {
                    id: $routeParams.id,
                }
            }
        ).then(function(response) {
            self.vehicles_details = response.data.vehicles_details;
            self.job_order = response.data.job_order;
            self.action = response.data.action;
        });


        //Buttons to navigate between tabs
        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
    }
});