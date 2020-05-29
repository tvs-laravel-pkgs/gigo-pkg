app.component('inwardVehicleCardList', {
    templateUrl: inward_vehicle_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('inward-vehicle')) {
            window.location = "#!/page-permission-denied";
            return false;
        }



        $scope.clear_search = function() {
            $('#search').val('');
        }

        //HelperService.isLoggedIn()
        self.user = $scope.user = HelperService.getLoggedUser();

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';
            $scope.searchTerm3 = '';
        };

        $scope.reset_filter = function() {
            $("#short_name").val('');
            $("#name").val('');
            $("#status").val('');
            dataTables.fnFilter();
        }

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/get',
                    method: "POST",
                    data: {

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

        //DELETE
        $scope.deleteJobOrder = function($id) {
            $('#inward_vehicle_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#inward_vehicle_id').val();
            $http.get(
                laravel_routes['deleteJobOrder'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Job Order Deleted Successfully');
                    $('#inward_vehicles_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/job-order/list');
                }
            });
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('inwardVehicleList', {
    templateUrl: inward_vehicle_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('job-orders')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-job-order');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
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
                    $('#search_inward_vehicle').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getJobOrderList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.short_name = $("#short_name").val();
                    d.name = $("#name").val();
                    d.description = $("#description").val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'short_name', name: 'inward_vehicles.short_name' },
                { data: 'name', name: 'inward_vehicles.name' },
                { data: 'description', name: 'inward_vehicles.description' },
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
            $('#search_inward_vehicle').val('');
            $('#inward_vehicles_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#inward_vehicles_list').DataTable().ajax.reload();
        });

        var dataTables = $('#inward_vehicles_list').dataTable();
        $("#search_inward_vehicle").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteJobOrder = function($id) {
            $('#inward_vehicle_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#inward_vehicle_id').val();
            $http.get(
                laravel_routes['deleteJobOrder'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Job Order Deleted Successfully');
                    $('#inward_vehicles_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/job-order/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getJobOrderFilter']
        ).then(function(response) {
            // console.log(response);
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
        $('#short_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.onSelectedStatus = function(id) {
            $('#status').val(id);
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            $("#short_name").val('');
            $("#name").val('');
            $("#status").val('');
            dataTables.fnFilter();
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//Vehicle Diagonis Details
app.component('inwardVehicleExportDiagnosisDetailForm', {
    templateUrl: inward_vehicle_export_diagnosis_details_template_url,
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

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/expert-diagnosis-report/get-form-data',
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
                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'expert_diagnosis_report': {
                    required: true,
                },
                'expert_diagnosis_report_by_id': {
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
                        url: base_url + '/api/vehicle-inward/expert-diagnosis-report/save',
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
                        $location.path('/inward-vehicle/expert-diagnosis-detail/' + $scope.job_order.id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });

        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }
    }
});



//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//Vehicle Inspection Details
app.component('inwardVehicleInspectionDetailForm', {
    templateUrl: inward_vehicle_inspection_detail_template_url,
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

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/vehicle-inspection/get-form-data',
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
                    $scope.extras = res.extras;
                    $scope.vehicle_inspection_item_groups = res.vehicle_inspection_item_groups;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'expert_diagnosis_report': {
                    required: true,
                },
                'expert_diagnosis_report_by_id': {
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
                        url: base_url + '/api/vehicle-inward/vehicle-inspection/save',
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
                        $location.path('/inward-vehicle/inspection-detail/' + $scope.job_order.id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });

        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }
    }
});



//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//DMS Check list
app.component('inwardVehicleDmsCheckListForm', {
    templateUrl: inward_vehicle_dms_checklist_template_url,
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

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/expert-diagnosis-report/get-form-data',
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
                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        self.checkbox = function() {
            if ($("#check_verify").prop('checked')) {
                $('#check_val').val(1);
            } else {
                $('#check_val').val(0);
            }

        }

        //Save Form Data 
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'warranty_expiry_date': {
                    required: true,
                },
                'ewp_expiry_date': {
                    required: true,
                },
                'warranty_expiry_attachment': {
                    required: true,
                },
                'ewp_expiry_attachment': {
                    required: true,
                },
                'membership_attachment': {
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
                        url: base_url + '/api/save-dms-checklist',
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
                        $location.path('/inward-vehicle/dms-checklist/' + $scope.job_order.id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });


        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }
    }
});


//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//Schedule Maintenance
app.component('inwardVehicleScheduledMaintenanceForm', {
    templateUrl: inward_vehicle_schedule_maintenance_form_template_url,
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

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/schedule-maintenance/get-form-data',
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
                    $scope.part_details = res.part_details;
                    $scope.labour_details = res.labour_details;
                    $scope.total_amount = res.total_amount;
                    $scope.labour_amount = res.labour_amount;
                    $scope.parts_rate = res.parts_rate;
                    $scope.job_order_id = res.job_order_id;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        self.checkbox = function() {
            if ($("#check_verify").prop('checked')) {
                $('#check_val').val(1);
            } else {
                $('#check_val').val(0);
            }

        }

        self.removeLabourDetails = function($id) {
           $('#delete_labour_details').val($id);
        }

        $scope.deleteConfirm = function() {
            $id = $('#delete_labour_details').val();
            $('#tr_'+$id).remove();
            tot_lab_value = 0;
            $( ".lab_amount" ).each(function() {
                amt_lab = $(this).val();
                tot_lab_value = parseInt(tot_lab_value)+parseInt(amt_lab);
            });
            $("#tot_amt_lab").text(tot_lab_value);
             
            rate_part = $("#rate_part").text(); 

            tot_full_val = parseInt(tot_lab_value)+parseInt(rate_part);
            $("#tot_amt").text(tot_full_val);
        }

        self.delete_parts_details = function($id) {
           $('#delete_parts_details').val($id);
        }

        $scope.deletePartsConfirm = function() {
            $id = $('#delete_parts_details').val();
            $('#tp_'+$id).remove();

            tot_part_value = 0;
            $( ".parts_rate" ).each(function() {
                amt_part = $(this).val();
                tot_part_value = parseInt(tot_part_value)+parseInt(amt_part);
            });
            $("#rate_part").text(tot_part_value);

            rate_lab = $("#tot_amt_lab").text(); 
            
            tot_full_val = parseInt(tot_part_value)+parseInt(rate_lab);
            $("#tot_amt").text(tot_full_val);
        }

        //Save Form Data 
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                /*'warranty_expiry_date': {
                    required: true,
                },
                'ewp_expiry_date': {
                    required: true,
                },
                'warranty_expiry_attachment': {
                    required: true,
                },
                'ewp_expiry_attachment': {
                    required: true,
                },
                'membership_attachment': {
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
                        url: base_url + '/api/vehicle-inward/schedule-maintenance/save',
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
                        $location.path('/inward-vehicle/scheduled-maintenance/' + $scope.job_order.id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });


        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }
    }
});


//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//Estimate
app.component('inwardVehicleEstimateForm', {
    templateUrl: inward_vehicle_estimate_form_template_url,
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

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/estimate/get-form-data',
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
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        self.checkbox = function() {
            if ($("#check_agree").prop('checked')) {
                $('#is_customer_agreed').val(1);
            } else {
                $('#is_customer_agreed').val(0);
            }

        }

        //Save Form Data 
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'estimated_delivery_date': {
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
                        url: base_url + '/api/vehicle-inward/estimate/save',
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
                        $location.path('/inward-vehicle/estimate/' + $scope.job_order.id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });

        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }
    }
});


//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('jobOrderView', {
    templateUrl: job_order_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
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
                    url: base_url + '/api/job-order/view',
                    method: "POST",
                    data: {
                        id: $routeParams.gate_log_id
                    },
                    // beforeSend: function(xhr) {
                    //     xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    // },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.gate_log = res.gate_log;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        var form_id = '#inward_vehicle_form';
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
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
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
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('inwardVehicleVehicleDetail', {
    templateUrl: inward_vehicle_vehicle_detail_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
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
                    url: base_url + '/api/vehicle-inward/get-vehicle-detail',
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

                    if ($scope.job_order.vehicle.status_id == 8140) {
                        $scope.show_vehicle_detail = false;
                        $scope.show_vehicle_form = true;
                    } else {
                        $scope.show_vehicle_detail = true;
                        $scope.show_vehicle_form = false;
                    }
                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'is_registered': {
                    required: true,
                },
                'registration_number': {
                    required: true,
                    minlength: 3,
                    maxlength: 10,
                },
                'model_id': {
                    required: true,
                },
                'vin_number': {
                    required: true,
                    minlength: 17,
                    maxlength: 32,
                },
                'engine_number': {
                    required: true,
                    minlength: 7,
                    maxlength: 64,
                },
                'chassis_number': {
                    required: true,
                    minlength: 10,
                    maxlength: 64,
                },
            },
            messages: {
                'vin_number': {
                    minlength: 'Minimum 17 Numbers',
                    maxlength: 'Maximum 32 Numbers',
                },
                'engine_number': {
                    minlength: 'Minimum 7 Numbers',
                    maxlength: 'Maximum 64 Numbers',
                },
                'chassis_number': {
                    minlength: 'Minimum 10 Numbers',
                    maxlength: 'Maximum 64 Numbers',
                }
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/vehicle/save',
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
                        $location.path('/inward-vehicle/customer-detail/' + $scope.job_order.id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });

        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('inwardVehicleCustomerDetail', {
    templateUrl: inward_vehicle_customer_detail_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
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

                    if (!$scope.job_order.vehicle.current_owner) {
                        $scope.show_customer_detail = false;
                        $scope.show_customer_form = true;
                    } else {
                        $scope.show_customer_detail = true;
                        $scope.show_customer_form = false;
                    }
                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveCustomer = function() {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'name': {
                        required: true,
                        minlength: 3,
                        maxlength: 255,
                    },
                    'mobile_no': {
                        required: true,
                        minlength: 10,
                        maxlength: 10,
                    },
                    'email': {
                        email: true,
                    },
                    'address_line1': {
                        required: true,
                        minlength: 3,
                        maxlength: 32,
                    },
                    'address_line2': {
                        minlength: 3,
                        maxlength: 64,
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
                    'pincode': {
                        required: true,
                        minlength: 6,
                        maxlength: 6,
                    },
                    'gst_number': {
                        minlength: 6,
                        maxlength: 32,
                    },
                    'pan_number': {
                        minlength: 6,
                        maxlength: 32,
                    },
                    'ownership_id': {
                        required: true,
                    },
                },
                messages: {
                    'name': {
                        minlength: 'Minimum 3 Characters',
                        maxlength: 'Maximum 255 Characters',
                    },
                    'mobile_no': {
                        minlength: 'Minimum 10 Numbers',
                        maxlength: 'Maximum 10 Numbers',
                    },
                    'address_line1': {
                        minlength: 'Minimum 3 Characters',
                        maxlength: 'Maximum 32 Characters',
                    },
                    'address_line2': {
                        minlength: 'Minimum 3 Characters',
                        maxlength: 'Maximum 32 Characters',
                    },
                    'pincode': {
                        minlength: 'Minimum 6 Numbers',
                        maxlength: 'Maximum 6 Numbers',
                    },
                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $rootScope.loading = true;
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/save-customer-detail',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $rootScope.loading = false;
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $location.path('/inward-vehicle/order-detail/form/' + $routeParams.job_order_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.showOwnerForm = function() {
            $scope.show_customer_detail = false;
            $scope.show_customer_form = true;
        }

        $scope.addNewOwner = function() {
            $scope.show_customer_detail = false;
            $scope.show_customer_form = true;
            $scope.job_order.vehicle.current_owner = {

            };
        }

        $scope.countryChanged = function() {
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/state/get-drop-down-List',
                    method: "POST",
                    data: {
                        country_id: $scope.job_order.vehicle.current_owner.customer.address.country.id,
                    },
                })
                .done(function(res) {
                    $rootScope.loading = false;
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.extras.state_list = res.state_list;
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.stateChanged = function() {
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/city/get-drop-down-List',
                    method: "POST",
                    data: {
                        state_id: $scope.job_order.vehicle.current_owner.customer.address.state.id,
                    },
                })
                .done(function(res) {
                    $rootScope.loading = false;
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.extras.city_list = res.city_list;
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('inwardVehicleOrderDetailForm', {
    templateUrl: inward_vehicle_order_detail_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
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
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/vehicle-inward/order-detail/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id
                    },
                })
                .done(function(res) {
                    $rootScope.loading = false;
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;
                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveOrderDetailForm = function() {
            var form_id = '#order_detail_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'driver_name': {
                        required: true,
                    },
                    'driver_mobile_number': {
                        required: true,
                        minlength: 10,
                        maxlength: 10,
                    },
                    'type_id': {
                        required: true,
                    },
                    'quote_type_id': {
                        required: true,
                    },
                    'service_type_id': {
                        required: true,
                    },
                    'km_reading': {
                        required: true,
                        number: true,
                    },
                    'km_reading_type_id': {
                        required: true,
                    },
                    'contact_number': {
                        required: true,
                        minlength: 10,
                        maxlength: 10,
                    },
                    'driving_license_image': {
                        // required: true,
                    },
                    'insurance_image': {
                        //required: true,
                    },
                    'rc_book_image': {
                        //required: true,
                    },
                    'driver_license_expiry_date': {
                        required: true,

                    },
                    'insurance_expiry_date': {
                        required: true,
                    },
                },
                /*messages: {
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
                },*/
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all sections');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $rootScope.loading = true;
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/order-detail/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $rootScope.loading = false;
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.submit').button('reset');
                            custom_noty('success', res.message);
                            $location.path('/inward-vehicle/inventory-detail/form/' + $scope.job_order_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        /* Dropdown Arrow Function */
        arrowDropdown();

        /* Image Uploadify Funtion */
        $('.image_uploadify').imageuploadify();

        /* Range Slider Function */
        rangeSliderChange();

    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('inwardVehicleInventoryDetailForm', {
    templateUrl: inward_vehicle_inventory_detail_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
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
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/vehicle-inward/inventory/get-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id
                    },
                })
                .done(function(res) {
                    $rootScope.loading = false;
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;
                    console.log('job_order' + res.job_order.id);
                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveInventoryForm = function() {
            var form_id = '#inventory_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                /*rules: {
                    'driver_name': {
                        required: true,
                    },
                },
                messages: {
                    'short_name': {
                        minlength: 'Minimum 3 Characters',
                        maxlength: 'Maximum 32 Characters',
                    },
                },*/
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all sections');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $rootScope.loading = true;
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/inventory/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $rootScope.loading = false;
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.submit').button('reset');
                            custom_noty('success', res.message);
                            $location.path('/inward-vehicle/inventory-detail/form/' + $scope.job_order_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        /* Dropdown Arrow Function */
        arrowDropdown();

        /* Image Uploadify Funtion */
        $('.image_uploadify').imageuploadify();

        /* Range Slider Function */
        rangeSliderChange();

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('inwardVehicleVocDetailForm', {
    templateUrl: inward_vehicle_voc_detail_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        //for md-select search
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        var self = this;
        $('#voc_details').hide();
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();
        $scope.onSelectedVoc = function(id) {
            if (id == 6)
                $('#voc_details').show();
            else
                $('#voc_details').hide();
        }
        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/vehicle-inward/voc/get-form-data',
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
                    self.job_order = $scope.job_order = res.job_order;
                    $scope.extras = res.extras;
                    if (res.action == "Add") {
                        self.addNewCustomerVoice();
                    }
                    $scope.action = res.action;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveVocDetailForm = function() {
            var voc_form_id = '#voc_form';
            console.log('test');
            var v = jQuery(voc_form_id).validate({
                ignore: '',
                // rules: {
                // },
                // messages: {
                // },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(voc_form_id)[0]);
                    console.log('submit');
                    $rootScope.loading = true;
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/voc/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $rootScope.loading = false;
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $location.path('/inward-vehicle/road-test-detail/form/' + $scope.job_order_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        self.addNewCustomerVoice = function() {
            self.job_order.customer_voices.push({
                id: '',
            });
        }

        self.removeCustomerVoice = function(index) {
            self.job_order.customer_voices.splice(index, 1);
        }

        /* Image Uploadify Funtion */
        $('.image_uploadify').imageuploadify();

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('inwardVehicleRoadTestDetailForm', {
    templateUrl: inward_vehicle_road_test_detail_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
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
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/vehicle-inward/road-test-observation/get-form-data',
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
                    $scope.gate_log_detail = res.gate_log_detail;

                    // if (!$scope.job_order.vehicle.current_owner) {
                    //     $scope.show_customer_detail = false;
                    //     $scope.show_customer_form = true;
                    // } else {
                    //     $scope.show_customer_detail = true;
                    //     $scope.show_customer_form = false;
                    // }
                    $scope.extras = res.extras;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveRoadTestDetailForm = function() {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {

                },
                messages: {

                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $rootScope.loading = true;
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/save-road-test-observation',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $rootScope.loading = false;
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $location.path('/inward-vehicle/expert-diagnosis-detail/' + $routeParams.job_order_id);
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
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
app.directive('jobOrderHeader', function() {
    return {
        templateUrl: job_order_header_template_url,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.directive('inwardTabs', function() {
    return {
        templateUrl: inward_tabs_template_url,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------