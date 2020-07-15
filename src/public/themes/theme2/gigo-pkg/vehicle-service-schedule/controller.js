app.directive('serviceModalForm', function() {
    return {
        templateUrl: serviceModalForm,
        controller: function() {}
    }
});
app.directive('serviceModalView', function() {
    return {
        templateUrl: serviceModalView,
        controller: function() {}
    }
});
app.directive('scheduleForm', function() {
    return {
        templateUrl: vehicle_service_schedule_services_form,
        controller: function() {}
    }
});
app.directive('scheduleView', function() {
    return {
        templateUrl: vehicle_service_schedule_services_view,
        controller: function() {}
    }
});
app.directive('vehicleLabourDetails', function() {
    return {
        templateUrl: labour_details_list_service_form_template_url,
        controller: function() {}
    }
});
app.directive('vehiclePartDetails', function() {
    return {
        templateUrl: part_details_list_service_form_template_url,
        controller: function() {}
    }
});
app.directive('serviceModalPartModalForm', function() {
    return {
        templateUrl: vehicle_service_schedule_part_modal_form,
        controller: function() {}
    }
});
app.directive('serviceModalLabourModalForm', function() {
    return {
        templateUrl: vehicle_service_schedule_labour_modal_form,
        controller: function() {}
    }
});
app.component('vehicleServiceScheduleList', {
    templateUrl: vehicle_service_schedule_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect, $q, VehicleServiceScheduleSvc) {
        $scope.loading = true;
        $('#search_vehicle_service_schedule').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('vehicle-service-schedules')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        /*var params = {
            page: 1, // show first page
            count: 100, // count per page
            sorting: {
                created_at: 'asc' // initial sorting
            },
        };


        //FETCH DATA
        $scope.fetchData = function() {
            VehicleServiceScheduleSvc.index(params)
                .then(function(response) {
                    console.log(response.data);
                    $scope.vehicle_service_schedules = response.data.vehicle_service_schedule_collection;
                    $rootScope.loading = false;
                });
        }
        $scope.fetchData();*/

        self.add_permission = self.hasPermission('add-vehicle-service-schedule');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#vehicle_service_schedule_list').DataTable({
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
                    $('#search_vehicle_service_schedule').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: base_url + '/api/vehicle-service-schedule/list',
                // url: laravel_routes['getVehicleServiceScheduleList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.code = $("#code").val();
                    d.name = $("#name").val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'code', name: 'vehicle_service_schedules.code', searchable: true },
                { data: 'name', name: 'vehicle_service_schedules.name', searchable: true },
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
            $('#search_vehicle_service_schedule').val('');
            $('#vehicle_service_schedule_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#vehicle_service_schedule_list').DataTable().ajax.reload();
        });

        var dataTables = $('#vehicle_service_schedule_list').dataTable();
        $("#search_vehicle_service_schedule").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteVehicleServiceSchedule = function($id) {
            $('#vehicle_service_schedule_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#vehicle_service_schedule_id').val();
            var params = {
                id: $id
            };
            /*$http.get(
                // laravel_routes['deleteVehicleServiceSchedule'], {
                base_url + '/api/vehicle-service-schedule/remove', {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Vehicle Service Schedule Deleted Successfully');
                    $('#vehicle_service_schedule_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/vehicle-service-schedule/list');
                }
            });*/

            VehicleServiceScheduleSvc.remove(params)
                .then(function(response) {
                    if (!response.data.success) {
                        showErrorNoty(response.data);
                        return;
                    }
                    custom_noty('success', 'Vehicle Service Schedule Deleted Successfully');
                    $('#vehicle_service_schedule_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/vehicle-service-schedule/list');
                });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getVehicleServiceScheduleFilterData']
        ).then(function(response) {
            // console.log(response);
            self.extras = response.data.extras;
        });


        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            /*$scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';*/
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
            $('#vehicle-service-schedule-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#code").val('');
            $("#name").val('');
            $("#status").val('');
            dataTables.fnFilter();
            $('#vehicle-service-schedule-filter-modal').modal('hide');
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('vehicleServiceScheduleForm', {
    templateUrl: vehicle_service_schedule_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $q, VehicleServiceScheduleSvc, ServiceTypeSvc, ConfigSvc, PartSvc, RepairOrderSvc, SplitOrderTypeSvc) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-vehicle-service-schedule') || !self.hasPermission('edit-vehicle-service-schedule')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        $scope.page = 'form';
        self.angular_routes = angular_routes;
        /*$http.get(
            laravel_routes['getVehicleServiceScheduleFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.vehicle_service_schedule = response.data.vehicle_service_schedule;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.vehicle_service_schedule.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });*/

        $scope.init = function() {
            $rootScope.loading = true;


            let promises = {
                service_type_options: ServiceTypeSvc.options(),
                tolerance_type_options: ConfigSvc.options({ filter: { configType: 307 } }),
                split_order_type_options: SplitOrderTypeSvc.options(),
            };

            if (typeof($routeParams.id) != 'undefined') {
                $scope.updating = true;
                promises.vehicle_service_schedule_read = VehicleServiceScheduleSvc.read($routeParams.id);
            } else {
                $scope.updating = false;
            }

            $scope.options = {};
            $q.all(promises)
                .then(function(responses) {
                    $scope.options.service_types = responses.service_type_options.data.options;
                    $scope.options.tolerance_types = responses.tolerance_type_options.data.options;
                    $scope.options.split_order_types = responses.split_order_type_options.data.options;

                    if ($scope.updating) {
                        $scope.vehicle_service_schedule = responses.vehicle_service_schedule_read.data.vehicle_service_schedule;
                        if ($scope.vehicle_service_schedule.deleted_at) {
                            self.switch_value = 'Inactive';
                        } else {
                            self.switch_value = 'Active';
                        }
                    } else {
                        self.is_free = "Active";
                        self.switch_value = 'Active';
                        $scope.vehicle_service_schedule = {
                            repair_orders: [],
                            vehicle_service_schedule_service_types: [],
                            repair_order_total: 0,
                            part_total: 0,
                        }

                    }
                    /*

                    if ($scope.updating) {
                        $scope.calculateLabourTotal('update');
                        $scope.calculatePartTotal('update');
                    } else {
                    $scope.calculateLabourTotal();
                    $scope.calculatePartTotal();
                    }*/

                    $scope.updateServiceTypes('load');
                    $rootScope.loading = false;
                });
        };
        $scope.init();
        //Save Form Data 
        var form_id = '#vec_service_schedule_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                /*'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 32,
                },*/
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
                /*'code': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 32 Characters',
                },*/
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        // url: laravel_routes['saveVehicleServiceSchedule'],
                        url: base_url + '/api/vehicle-service-schedule/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/vehicle-service-schedule/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/vehicle-service-schedule/list');
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

        var form_id2 = '#service-modal-form';
        var v = jQuery(form_id2).validate({
            ignore: '',
            rules: {
                'service_type_id': {
                    required: true,
                },
                'is_free': {
                    required: true,
                },
                'km_reading': {
                    required: true,
                    number: true,
                },
                'km_tolerance': {
                    number: true,
                    required: true,
                },
                'km_tolerance_type_id': {
                    required: true,
                },
                'period': {
                    number: true,
                    required: true,
                },
                'period_tolerance': {
                    number: true,
                    required: true,
                },
                'period_tolerance_type_id': {
                    required: true,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {
                console.log($scope.modal_action);
                if ($scope.modal_action == 'Add') {
                    angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                        if (sch_serv_type.service_type.id == $scope.service_type_item.service_type.id) {
                            $id = sch_serv_type.id;
                            $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.splice(key, 1);
                            $scope.service_type_item.id = $id;
                        }
                    });
                    // sch_serv_type.is_free = (self.is_free == 'Active') ? 1 : 0;
                    $scope.service_type_item.is_free = (self.is_free == 'Active') ? 1 : 0;
                    $scope.service_type_item.repair_orders = [];
                    $scope.service_type_item.parts = [];
                    $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.push($scope.service_type_item);
                } else {
                    $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[$scope.index] = $scope.service_type_item;
                }
                // $scope.calculatePartNetAmount();
                $scope.updateServiceTypes();
                $scope.service_type_item = '';
                $('#service_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });
        $scope.isFreeChange = function() {
            if ($scope.vehicle_service_schedule.vehicle_service_schedule_service_types[$scope.index]) {
                $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[$scope.index].is_free = (self.is_free == 'Active') ? 1 : 0;
                console.log($scope.vehicle_service_schedule.vehicle_service_schedule_service_types[$scope.index].is_free);
            }
        }
        $scope.updateServiceTypes = function(load = null) {
            angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type) {
                // console.log(sch_serv_type.is_free);
                sch_serv_type.service_type_id = sch_serv_type.service_type.id;
                sch_serv_type.km_tolerance_type_id = sch_serv_type.tolerance_km.id;
                sch_serv_type.period_tolerance_type_id = sch_serv_type.tolerance_period.id;
                sch_serv_type.is_free = (sch_serv_type.is_free == "Active" || sch_serv_type.is_free == 1) ? 1 : 0;
                // console.log(sch_serv_type);
                angular.forEach(sch_serv_type.parts, function(part) {
                    // console.log(part);
                    if (load) {
                        if (!part.pivot) {
                            part.pivot = {};
                            part.pivot.quantity = part.qty;
                            part.pivot.amount = part.total_amount;
                        }
                    }
                });
                angular.forEach(sch_serv_type.repair_orders, function(repair_order) {
                    if (load) {
                        repair_order.split_order_type_id = repair_order.pivot.split_order_type_id;
                    }
                });
            });
            $scope.calculateLabourTotal();
            $scope.calculatePartTotal();

        }
        $scope.removeService = function(index) {
            $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.splice(index, 1);
            // $scope.calculatePartTotal();
        }
        $scope.showServiceForm = function(service_type_item, index) {
            // console.log(service_type_item);
            $scope.service_type_item = service_type_item;
            if (service_type_item != undefined) {
                // alert(service_type_item.is_free);
                if (service_type_item.is_free == true) {
                    self.is_free = "Active";
                } else {
                    self.is_free = "Inactive";
                }
            } else {
                self.is_free = "Active";
            }
            $scope.index = index;
            self.service_index = index;
            $scope.modal_action = !service_type_item ? 'Add' : 'Edit';
            $('#service_form_modal').modal('show');
        }

        $scope.showPartForm = function(part_index, service_type_item_part = null) {
            /*console.log(self.service_index);
            console.log(part_index);*/
            if ($scope.modal_action == 'Add') {
                if ($scope.service_type_item == undefined) {
                    custom_noty('error', 'You have errors, Kindly fill the form');
                    return false;
                }
                angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                    if (sch_serv_type.service_type.id == $scope.service_type_item.service_type.id) {
                        $id = sch_serv_type.id;
                        $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.splice(key, 1);
                        $scope.service_type_item.id = $id;
                    }
                });
                $scope.service_type_item.is_free = (self.is_free == 'Active') ? 1 : 0;
                $scope.service_type_item.repair_orders = [];
                $scope.service_type_item.parts = [];
                self.service_index = $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.length;
                $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.push($scope.service_type_item);
                $scope.modal_action = 'Edit';
            }
            var service_index = self.service_index;
            if (part_index === false) {
                $scope.parts = {};
            } else {
                $scope.service_type_part.part = service_type_item_part;
                /*$scope.service_type_part.part.qty = service_type_item_part.pivot.quantity;
                $scope.service_type_part.part.amount = service_type_item_part.pivot.amount;*/
                // $scope.parts = $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].parts[part_index];
                // $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].parts[$scope.index]
                // $scope.calculatePartAmount();
            }

            // document.querySelector('#partAutoCompleteId').focus();
            $scope.part_index = part_index;
            $scope.part_modal_action = part_index === false ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
        }
        $scope.showLabourForm = function(labour_index, service_type_item_labour = null) {
            if ($scope.modal_action == 'Add') {
                if ($scope.service_type_item == undefined) {
                    custom_noty('error', 'You have errors, Kindly fill the form');
                    return false;
                }
                angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                    if (sch_serv_type.service_type.id == $scope.service_type_item.service_type.id) {
                        $id = sch_serv_type.id;
                        $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.splice(key, 1);
                        $scope.service_type_item.id = $id;
                    }
                });
                $scope.service_type_item.is_free = (self.is_free == 'Active') ? 1 : 0;
                $scope.service_type_item.repair_orders = [];
                $scope.service_type_item.parts = [];
                self.service_index = $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.length;
                $scope.vehicle_service_schedule.vehicle_service_schedule_service_types.push($scope.service_type_item);
                console.log($scope.vehicle_service_schedule.vehicle_service_schedule_service_types);
                $scope.modal_action = 'Edit';
            }
            var service_index = self.service_index;
            if (labour_index === false) {
                $scope.repair_orders = {};
            } else {
                if (service_type_item_labour.split_order_type_id == undefined) {
                    $split_id = service_type_item_labour.pivot.split_order_type_id;
                } else {
                    $split_id = service_type_item_labour.split_order_type_id;
                }
                SplitOrderTypeSvc.read($split_id)
                    .then(function(response) {
                        $scope.service_type_ro.split_order_type = response.data.split_order_type;
                    });
                $scope.service_type_ro.repair_order = service_type_item_labour;
            }

            // document.querySelector('#partAutoCompleteId').focus();
            $scope.labour_index = labour_index;
            $scope.labour_modal_action = labour_index === false ? 'Add' : 'Edit';
            $('#labour_form_modal').modal('show');
        }

        $scope.searchSplitOrders = function(query) {
            return new Promise(function(resolve, reject) {
                SplitOrderTypeSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }
        $scope.searchParts = function(query) {
            return new Promise(function(resolve, reject) {
                PartSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }
        $scope.searchRepairOrders = function(query) {
            return new Promise(function(resolve, reject) {
                RepairOrderSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }
        $scope.partSelected = function(part) {
            if (!part) {
                return;
            }
            PartSvc.read(part.id)
                .then(function(response) {
                    $scope.service_type_part.part.qty = 1;
                    // $scope.service_type_part.part.total_amount = $scope.service_type_part.part.qty * $scope.service_type_part.part.mrp;
                    /*$scope.service_type_part.rate = part.mrp;
                    $scope.wjor_part.part.tax_code = response.data.part.tax_code;
                    $scope.wjor_part.tax_code = response.data.part.tax_code;
                    $scope.wjor_part.purchase_type = 8480;*/
                    $scope.calculatePartAmount();
                });

        }
        $scope.splitOrderSelected = function(split_order) {
            if (!split_order) {
                return;
            }
            $scope.service_type_ro.repair_order.split_order_type_id = split_order.id;
        }
        $scope.removePart = function(index) {
            var service_index = self.service_index;
            $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].parts.splice(index, 1);
            $scope.calculatePartTotal();
        }
        $scope.removeLabour = function(index) {
            var service_index = self.service_index;
            $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].repair_orders.splice(index, 1);
            $scope.calculateLabourTotal();
        }

        $scope.calculatePartAmount = function() {
            if (!$scope.service_type_part.part.pivot) {
                $scope.service_type_part.part.pivot = {};
            }
            $scope.service_type_part.part.pivot.quantity = $scope.service_type_part.part.qty;
            $scope.service_type_part.part.total_amount = $scope.service_type_part.part.qty * $scope.service_type_part.part.mrp;
            $scope.service_type_part.part.pivot.amount = $scope.service_type_part.part.total_amount;
            $scope.calculatePartTotal();
        }

        $scope.calculatePartTotal = function() {
            angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                $total_amount = 0;
                angular.forEach(sch_serv_type.parts, function(part) {
                    // $total_amount += parseFloat(part.pivot.amount);
                    $total_amount += parseInt(part.pivot.quantity) * parseFloat(part.mrp);
                });
                sch_serv_type.part_total = $total_amount;
            });
            $scope.calculateTotalLabourParts();
        }
        $scope.calculateLabourTotal = function() {
            angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                $total_amount = 0;
                angular.forEach(sch_serv_type.repair_orders, function(repair_order) {
                    $total_amount += parseFloat(repair_order.amount);
                });
                sch_serv_type.repair_order_total = $total_amount;
            });
            $scope.calculateTotalLabourParts();
        }
        $scope.calculateTotalLabourParts = function() {
            angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                sch_serv_type.total_part_labour_amount = parseFloat(sch_serv_type.repair_order_total) + parseFloat(sch_serv_type.part_total);
            });
        }

        var form_id3 = '#part-form';
        var v = jQuery(form_id3).validate({
            ignore: '',
            rules: {
                'part_id': {
                    required: true,
                },
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
                // console.log($scope.service_type_part.part.code);
                // alert($scope.part_index);
                // console.log($scope.part_modal_action);
                var service_index = self.service_index;
                if ($scope.part_modal_action == 'Add') {
                    angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].parts, function(part, key) {
                        if (part.code == $scope.service_type_part.part.code) {
                            $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].parts.splice(key, 1);
                        }
                    });
                    $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].parts.push($scope.service_type_part.part);
                } else {
                    console.log($scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].parts[$scope.part_index]);
                    $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].parts[$scope.part_index] = $scope.service_type_part.part;
                }
                // $scope.calculatePartNetAmount();
                $scope.updateServiceTypes();
                $scope.service_type_part.part = '';
                $('#part_form_modal').modal('hide');
                // $('body').removeClass('modal-open');
                // $('.modal-backdrop').remove();
                // console.log($scope.vehicle_service_schedule.vehicle_service_schedule_service_types);
            }
        });
        var form_id4 = '#labour-form';
        var v = jQuery(form_id4).validate({
            ignore: '',
            rules: {
                'repair_order_id': {
                    required: true,
                },
                'split_order_type_id': {
                    required: true,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {
                // console.log($scope.part_modal_action);
                var service_index = self.service_index;
                $scope.service_type_ro.repair_order.split_order_type_id = $scope.service_type_ro.split_order_type.id;
                console.log($scope.service_type_ro.repair_order);
                if ($scope.labour_modal_action == 'Add') {
                    /*console.log($scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index]);
                    return false;*/
                    angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].repair_orders, function(labour, key) {
                        if (labour.code == $scope.service_type_ro.repair_order.code) {
                            $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].repair_orders.splice(key, 1);
                        }
                    });
                    // console.log($scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index]);
                    $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].repair_orders.push($scope.service_type_ro.repair_order);
                } else {
                    $scope.vehicle_service_schedule.vehicle_service_schedule_service_types[service_index].repair_orders[$scope.index] = $scope.service_type_ro.repair_order;
                }
                // $scope.calculatePartNetAmount();
                $scope.updateServiceTypes();
                $scope.service_type_ro.repair_order = '';
                $scope.service_type_ro.split_order_type = '';
                $('#labour_form_modal').modal('hide');
                // $('body').removeClass('modal-open');
                // $('.modal-backdrop').remove();
            }
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('vehicleServiceScheduleView', {
    templateUrl: vehicle_service_schedule_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $q, VehicleServiceScheduleSvc, ServiceTypeSvc, ConfigSvc, PartSvc, RepairOrderSvc) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('view-vehicle-service-schedule') || !self.hasPermission('edit-vehicle-service-schedule')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        $scope.page = 'view';
        self.angular_routes = angular_routes;


        $scope.init = function() {
            $rootScope.loading = true;


            let promises = {
                service_type_options: ServiceTypeSvc.options(),
                tolerance_type_options: ConfigSvc.options({ filter: { configType: 307 } }),
            };

            if (typeof($routeParams.id) != 'undefined') {
                $scope.updating = true;
                promises.vehicle_service_schedule_read = VehicleServiceScheduleSvc.read($routeParams.id);
            } else {
                $scope.updating = false;
            }

            $scope.options = {};
            $q.all(promises)
                .then(function(responses) {
                    $scope.options.service_types = responses.service_type_options.data.options;
                    $scope.options.tolerance_types = responses.tolerance_type_options.data.options;

                    if ($scope.updating) {
                        $scope.vehicle_service_schedule = responses.vehicle_service_schedule_read.data.vehicle_service_schedule;
                        if ($scope.vehicle_service_schedule.deleted_at) {
                            self.switch_value = 'Inactive';
                        } else {
                            self.switch_value = 'Active';
                        }
                    } else {
                        self.is_free = "Active";
                        self.switch_value = 'Active';
                        $scope.vehicle_service_schedule = {
                            repair_orders: [],
                            vehicle_service_schedule_service_types: [],
                            repair_order_total: 0,
                            part_total: 0,
                            attachments: [],
                            job_order: {
                                vehicle: {},
                                customer: {},
                                outlet: {},
                            },
                            photos: [],
                        }

                    }
                    /*$scope.customer = $scope.warranty_job_order_request.job_order.customer;

                    if ($scope.updating) {
                        $scope.calculateLabourTotal('update');
                        $scope.calculatePartTotal('update');
                    } else {
                    }*/
                    $scope.calculateLabourTotal();
                    $scope.calculatePartTotal();

                    $scope.updateServiceTypes();
                    $rootScope.loading = false;
                });
        };
        $scope.init();

        $scope.updateServiceTypes = function() {
            angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type) {
                // console.log(sch_serv_type.is_free);
                sch_serv_type.service_type_id = sch_serv_type.service_type.id;
                sch_serv_type.km_tolerance_type_id = sch_serv_type.tolerance_km.id;
                sch_serv_type.period_tolerance_type_id = sch_serv_type.tolerance_period.id;
                sch_serv_type.is_free = (sch_serv_type.is_free) ? 1 : 0;
                angular.forEach(sch_serv_type.parts, function(part) {
                    if (!part.pivot) {
                        part.pivot = {};
                        part.pivot.quantity = part.qty;
                        part.pivot.amount = part.total_amount;
                    }
                });
            });
            $scope.calculateLabourTotal();

        }


        $scope.calculatePartAmount = function() {
            $scope.service_type_part.part.total_amount = $scope.service_type_part.part.qty * $scope.service_type_part.part.mrp;
            $scope.calculatePartTotal();
        }

        $scope.calculatePartTotal = function() {
            angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                $total_amount = 0;
                angular.forEach(sch_serv_type.parts, function(part) {
                    $total_amount += parseFloat(part.pivot.amount);
                });
                sch_serv_type.part_total = $total_amount;
            });
            $scope.calculateTotalLabourParts();
        }
        $scope.calculateLabourTotal = function() {
            angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                $total_amount = 0;
                angular.forEach(sch_serv_type.repair_orders, function(repair_order) {
                    $total_amount += parseFloat(repair_order.amount);
                });
                sch_serv_type.repair_order_total = $total_amount;
            });
            $scope.calculateTotalLabourParts();
        }
        $scope.calculateTotalLabourParts = function() {
            angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type, key) {
                sch_serv_type.total_part_labour_amount = parseFloat(sch_serv_type.repair_order_total) + parseFloat(sch_serv_type.part_total);
            });
        }
        $scope.showServiceForm = function(service_type_item, index) {

            /*angular.forEach($scope.vehicle_service_schedule.vehicle_service_schedule_service_types, function(sch_serv_type) {
                console.log(sch_serv_type);
            });*/
            // console.log(service_type_item);
            $scope.service_type_item = service_type_item;
            if (service_type_item != undefined) {
                if (service_type_item.is_free == true) {
                    self.is_free = "Active";
                } else {
                    self.is_free = "Inactive";
                }
            } else {
                self.is_free = "Active";
            }
            $scope.index = index;
            self.service_index = index;
            $scope.modal_action = !service_type_item ? 'Add' : 'Edit';
            $('#service_form_modal').modal('show');
        }
    }
});