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
            window.location = "#!/page-permission-denied";
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

app.component('inwardVehicleTableList', {
    templateUrl: inward_vehicle_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('inward-vehicle')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.search_key = '';
        self.user = $scope.user = HelperService.getLoggedUser();

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
//Vehicle Diagonis Details
app.component('inwardVehicleExpertDiagnosisDetailForm', {
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
        if (!self.hasPermission('inward-job-card-tab-expert-diagnosis-report-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

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

                    if ($scope.job_order.expert_diagnosis_report) {
                        self.expert_diagnosis_status = 1;
                    } else {
                        self.expert_diagnosis_status = 0;
                    }

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveExportDiagonis = function(id) {
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
                    $scope.button_action(id, 1);
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
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            if (id == 1) {
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                $location.path('/inward-vehicle/inspection-detail/form/' + $scope.job_order.id);
                                $scope.$apply();
                            }
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
        if (!self.hasPermission('inward-job-card-tab-vehicle-inspection-report-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }
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
        $scope.saveInspectionReport = function(id) {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {},
                messages: {},
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $scope.button_action(id, 1);
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
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            if (id == 1) {
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                $location.path('/inward-vehicle/dms-checklist/form/' + $scope.job_order.id);
                                $scope.$apply();
                            }
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
        if (!self.hasPermission('inward-job-card-tab-service-package-details-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

        $('.btn-nxt').attr("disabled", "disabled");
        $(".submit").attr("disabled", "disabled");

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        /* Profile Upload */
        setTimeout(function() {
            profileImgUpload();
        }, 1000);

        setTimeout(function() {
            $('.image_uploadify').imageuploadify();
        }, 1000);

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/dms-checklist/get-form-data',
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
                    console.log(res);
                    $scope.job_order = res.job_order;
                    $scope.campaigns = res.campaigns;
                    $scope.job_order_id = $routeParams.job_order_id;
                    if (!$scope.job_order.is_campaign_carried) {
                        $scope.job_order.is_campaign_carried = 0;
                    } else {
                        $scope.job_order.is_campaign_carried = 1;
                    }

                    if ($scope.job_order.ewp_expiry_date) {
                        self.exwarrany_status = 1;
                    } else {
                        self.exwarrany_status = 0;
                    }

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

                    $scope.$apply();

                    setTimeout(function() {
                        self.checkbox();
                    }, 1000);
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        self.checkbox = function() {
            if ($("#check_verify").prop('checked')) {
                $('#check_val').val(1);
                $(".btn-nxt").removeAttr("disabled");
                $(".submit").removeAttr("disabled");
            } else {
                $('#check_val').val(0);
                $('.btn-nxt').attr("disabled", "disabled");
                $(".submit").attr("disabled", "disabled");
            }
        }

        self.attachment_removal_id = [];
        $scope.remove_attachment = function(attachment_id, index) {
            console.log(attachment_id, index);
            if (attachment_id) {
                self.attachment_removal_id.push(attachment_id);
                $('#attachment_removal_ids').val(JSON.stringify(self.attachment_removal_id));
            }
            $scope.job_order.a_m_c_attachment.splice(index, 1);
        }


        $.validator.addMethod("greaterThan",
            function(value, element, param) {
                var $otherElement = $(param);
                return parseInt(value, 10) > parseInt($otherElement.val(), 10);
            });

        //Save Form Data 
        $scope.saveDms = function(id) {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'ewp_expiry_date': {
                        required: function(element) {
                            if (self.exwarrany_status == '1') {
                                return true;
                            }
                            return false;
                        },
                    },
                    'warranty_expiry_attachment': {
                        required: function(element) {
                            if (self.amc_status == '1' && !$scope.job_order.warrenty_policy_attachment) {
                                return true;
                            }
                            return false;
                        },
                    },
                    'ewp_expiry_attachment': {
                        required: function(element) {
                            if (self.exwarrany_status == '1' && !$scope.job_order.e_w_p_attachment) {
                                return true;
                            }
                            return false;
                        },
                    },
                    // 'ewp_expiry_attachment': {
                    //     required: true,
                    // },
                    // 'membership_attachment': {
                    //     required: true,
                    // },
                    'amc_starting_date': {
                        required: function(element) {
                            if (self.amc_status == '1') {
                                return true;
                            }
                            return false;
                        },
                    },
                    'amc_ending_date': {
                        required: function(element) {
                            if (self.amc_status == '1') {
                                return true;
                            }
                            return false;
                        },
                    },
                    'starting_km': {
                        required: function(element) {
                            if (self.amc_status == '1') {
                                return true;
                            }
                            return false;
                        },
                    },
                    'ending_km': {
                        required: function(element) {
                            if (self.amc_status == '1') {
                                return true;
                            }
                            return false;
                        },
                        greaterThan: "#starting_km"
                    },
                },
                messages: {

                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all fields');
                },
                errorPlacement: function(error, element) {
                    if (element.hasClass("warranty_expiry_attachment")) {
                        custom_noty('error', 'AMC Photo is Required')
                    } else if (element.hasClass("ewp_expiry_attachment")) {
                        custom_noty('error', 'Extended Warranty Photo is Required')
                    } else if (element.hasClass("membership_attachment")) {
                        custom_noty('error', 'Membership Photo is Required')
                    } else {
                        error.insertAfter(element)
                    }
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/dms-checklist/save',
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
                            if (id == 1) {
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                $location.path('/inward-vehicle/scheduled-maintenance/form/' + $scope.job_order_id);
                                $scope.$apply();
                            }
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
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
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $q, RepairOrderSvc, SplitOrderTypeSvc, PartSvc, $mdSelect) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.labour_removal_id = [];
        self.parts_removal_id = [];
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        if (!self.hasPermission('inward-job-card-tab-scheduled-maintenance-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }
        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

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
                    $scope.job_order = res.job_order;
                    $scope.part_details = res.part_details;
                    $scope.labour_details = res.labour_details;
                    $scope.total_amount = res.total_amount;
                    $scope.labour_amount = res.labour_amount;
                    $scope.parts_rate = res.parts_rate;
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

        self.removeLabourDetails = function($id, $labour_id) {
            $('.delete_labour_details').val($id);
            $('.labour_detail_id').val($labour_id);
        }

        $scope.deleteConfirm = function() {
            $id = $('.delete_labour_details').val();
            $labour_id = $('.labour_detail_id').val();

            if ($labour_id) {
                self.labour_removal_id.push(parseInt($labour_id));
                $('#labour_removal_ids').val(JSON.stringify(self.labour_removal_id));
            }

            $scope.labour_details.splice($id, 1);

            setTimeout(function() {
                var total_labour_amount = 0;
                for (var i = 0; i < $scope.labour_details.length; i++) {
                    var labour_amount = parseFloat($('#labour_amount_' + i).val());
                    if (labour_amount && !isNaN(labour_amount)) {
                        total_labour_amount += labour_amount;
                    }
                }

                $("#total_amount_labour").text(total_labour_amount.toFixed(2));

                var total_part_amount = 0;
                for (var i = 0; i < $scope.part_details.length; i++) {
                    var part_amount = parseFloat($('#part_amount_' + i).val());
                    if (part_amount && !isNaN(part_amount)) {
                        total_part_amount += part_amount;
                    }
                }

                var total_amount = parseFloat(total_labour_amount) + parseFloat(total_part_amount);
                $("#total_amount").text(total_amount.toFixed(2));

            }, 1000);
        }

        self.delete_parts_details = function($id, $part_id) {
            $('.delete_parts_details').val($id);
            $('.part_detail_id').val($part_id);
        }

        $scope.saveLabour = function() {
            var form_id = '#labour_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'rot_id': {
                        required: true,
                    },
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_labour').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/add-repair-order/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $('.save_labour').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.save_labour').button('reset');
                            custom_noty('success', res.message);
                            $('#labour_form_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            $scope.fetchData();
                        })
                        .fail(function(xhr) {
                            $('.save_labour').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.savePart = function() {
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
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_part').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/add-part/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $('.save_part').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.save_part').button('reset');
                            custom_noty('success', res.message);
                            $('#part_form_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            $scope.fetchData();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.deletePartsConfirm = function() {
            $id = $('.delete_parts_details').val();
            $part_id = $('.part_detail_id').val();

            if ($part_id) {
                self.parts_removal_id.push(parseInt($part_id));
                $('#parts_removal_ids').val(JSON.stringify(self.parts_removal_id));
            }

            $scope.part_details.splice($id, 1);

            console.log($scope.part_details);
            setTimeout(function() {
                var total_part_amount = 0;
                for (var i = 0; i < $scope.part_details.length; i++) {
                    var part_amount = parseFloat($('#part_amount_' + i).val());
                    if (part_amount && !isNaN(part_amount)) {
                        total_part_amount += part_amount;
                    }
                }
                $("#total_amount_part").text(total_part_amount.toFixed(2));

                var total_labour_amount = 0;
                for (var i = 0; i < $scope.labour_details.length; i++) {
                    var labour_amount = parseFloat($('#labour_amount_' + i).val());
                    if (labour_amount && !isNaN(labour_amount)) {
                        total_labour_amount += labour_amount;
                    }
                }

                var total_amount = parseFloat(total_labour_amount) + parseFloat(total_part_amount);
                $("#total_amount").text(total_amount.toFixed(2));

            }, 1000);

            // // alert($labour_id);
            // $('#tp_' + $id).remove();

            // tot_part_value = 0;
            // $(".parts_rate").each(function() {
            //     amt_part = $(this).val();
            //     tot_part_value = parseInt(tot_part_value) + parseInt(amt_part);
            // });
            // $("#rate_part").text(tot_part_value);

            // rate_lab = $("#tot_amt_lab").text();

            // tot_full_val = parseInt(tot_part_value) + parseInt(rate_lab);
            // $("#tot_amt").text(tot_full_val);
        }

        //Save Form Data 
        $scope.saveSchedule = function(id) {
            if (id == 1) {
                $location.path('/inward-vehicle/table-list');
                $scope.$apply();
            } else {
                $location.path('/inward-vehicle/payable-labour-part-detail/form/' + $scope.job_order.id);
                $scope.$apply();
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

        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
        }

        $scope.init = function() {
            $rootScope.loading = true;
            let promises = {
                split_order_type_options: SplitOrderTypeSvc.options(),
            };

            $scope.options = {};
            $q.all(promises)
                .then(function(responses) {
                    $scope.options.split_order_types = responses.split_order_type_options.data.options;
                    $rootScope.loading = false;

                });

            setTimeout(function() {
                $scope.calculateLabourTotal();
                $scope.calculatePartTotal();
            }, 2000);
        };
        $scope.init();
        $scope.searchRepairOrders = function(query) {
            return new Promise(function(resolve, reject) {
                RepairOrderSvc.options({ filter: { search: query } })
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
                    $scope.schedule_maintainance_part.part.qty = $qty;
                    $scope.calculatePartAmount();
                });

        }
        $scope.calculatePartAmount = function() {
            if (!$scope.schedule_maintainance_part.part.pivot) {
                $scope.schedule_maintainance_part.part.pivot = {};
            }
            $scope.schedule_maintainance_part.part.pivot.quantity = $scope.schedule_maintainance_part.part.qty;
            $scope.schedule_maintainance_part.part.total_amount = $scope.schedule_maintainance_part.part.qty * $scope.schedule_maintainance_part.part.mrp;
            $scope.schedule_maintainance_part.part.pivot.amount = $scope.schedule_maintainance_part.part.total_amount;
            $scope.calculatePartTotal();
        }
        $scope.showLabourForm = function(labour_index, labour = null) {
            $scope.schedule_maintainance_ro = [];
            $scope.repair_order_id = '';
            if (labour_index === false) {
                // $scope.labour_details = {};
            } else {
                // console.log(labour);
                // return false;
                if (labour.split_order_type_id != null) {
                    $scope.repair_order_id = labour.id;
                    if (labour.split_order_type_id == undefined) {
                        $split_id = labour.pivot.split_order_type_id;
                    } else {
                        $split_id = labour.split_order_type_id;
                    }
                    SplitOrderTypeSvc.read($split_id)
                        .then(function(response) {
                            $scope.schedule_maintainance_ro.split_order_type = response.data.split_order_type;
                        });
                }
                if (labour.category == undefined) {
                    RepairOrderSvc.read(labour.labour_id)
                        .then(function(response) {
                            $scope.schedule_maintainance_ro.repair_order = response.data.repair_order;
                        });
                }
                $scope.schedule_maintainance_ro.repair_order = labour;
            }

            $scope.labour_index = labour_index;
            $scope.labour_modal_action = labour_index === false ? 'Add' : 'Edit';
            $('#labour_form_modal').modal('show');
        }
        $scope.showPartForm = function(part_index, part = null) {
            // console.log(part.qty);
            $scope.schedule_maintainance_part = [];
            $scope.job_order_part_id = '';
            if (part_index === false) {
                // $scope.part_details = {};
            } else {
                if (part.split_order_type_id != null) {
                    $scope.job_order_part_id = part.id;
                    if (part.split_order_type_id == undefined) {
                        $split_id = part.pivot.split_order_type_id;
                    } else {
                        $split_id = part.split_order_type_id;
                    }
                    SplitOrderTypeSvc.read($split_id)
                        .then(function(response) {
                            $scope.schedule_maintainance_part.split_order_type = response.data.split_order_type;
                        });
                }
                if (part.uom == undefined) {
                    PartSvc.read(part.part_id)
                        .then(function(response) {
                            $scope.schedule_maintainance_part.part = response.data.part;
                            $scope.schedule_maintainance_part.part.qty = part.qty;
                            // $scope.calculatePartAmount();
                        });
                }
                $scope.schedule_maintainance_part.part = part;
            }

            $scope.part_index = part_index;
            $scope.part_modal_action = part_index === false ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
        }

        var part_form = '#part-form';
        var v = jQuery(part_form).validate({
            ignore: '',
            rules: {
                'part_id': {
                    required: true,
                },
                'split_order_type_id': {
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

                $scope.schedule_maintainance_part.part.split_order_type = $scope.schedule_maintainance_part.split_order_type.name;
                $scope.schedule_maintainance_part.part.type = $scope.schedule_maintainance_part.part.tax_code.code;
                $scope.schedule_maintainance_part.part.amount = $scope.schedule_maintainance_part.part.total_amount;
                $scope.schedule_maintainance_part.part.split_order_type_id = $scope.schedule_maintainance_part.split_order_type.id;
                // $scope.schedule_maintainance_part.part.name = $scope.schedule_maintainance_part.part.code + ' | ' + $scope.schedule_maintainance_part.part.name;
                if ($scope.part_modal_action == 'Add') {
                    angular.forEach($scope.part_details, function(part, key) {
                        if (part.name == $scope.schedule_maintainance_part.part.name) {
                            $scope.part_details.splice(key, 1);
                        }
                    });
                    $scope.part_details.push($scope.schedule_maintainance_part.part);
                } else {
                    $scope.part_details[$scope.part_index] = $scope.schedule_maintainance_part.part;
                }
                $scope.calculatePartTotal();
                $scope.schedule_maintainance_part = {};
                $('#part_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });
        var labour_form = '#labour-form';
        var v = jQuery(labour_form).validate({
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
                $scope.schedule_maintainance_ro.repair_order.split_order_type_id = $scope.schedule_maintainance_ro.split_order_type.id;
                $scope.schedule_maintainance_ro.repair_order.split_order_type = $scope.schedule_maintainance_ro.split_order_type.name;
                $scope.schedule_maintainance_ro.repair_order.qty = $scope.schedule_maintainance_ro.repair_order.hours;
                $scope.schedule_maintainance_ro.repair_order.type = $scope.schedule_maintainance_ro.repair_order.category.name;
                // $scope.schedule_maintainance_ro.repair_order.name = $scope.schedule_maintainance_ro.repair_order.code + ' | ' + $scope.schedule_maintainance_ro.repair_order.name;
                if ($scope.labour_modal_action == 'Add') {
                    angular.forEach($scope.labour_details, function(labour, key) {
                        if (labour.name == $scope.schedule_maintainance_ro.repair_order.name) {
                            $scope.labour_details.splice(key, 1);
                        }
                    });
                    $scope.labour_details.push($scope.schedule_maintainance_ro.repair_order);
                } else {
                    $scope.labour_details[$scope.labour_index] = $scope.schedule_maintainance_ro.repair_order;
                }

                $scope.calculateLabourTotal();
                $scope.schedule_maintainance_ro = {};
                console.log($scope.labour_details);
                $('#labour_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });
        $scope.calculateLabourTotal = function() {
            $total_amount = 0;
            angular.forEach($scope.labour_details, function(labour, key) {
                if (labour.removal_reason_id == undefined || labour.removal_reason_id == null) {
                    $total_amount += parseFloat(labour.amount);
                }
            });
            $scope.labour_amount = $total_amount.toFixed(2);
            $scope.calculateTotalLabourParts();
        }
        $scope.calculatePartTotal = function() {
            $total_amount = 0;
            angular.forEach($scope.part_details, function(part, key) {
                if (part.removal_reason_id == null || part.removal_reason_id == undefined) {
                    $total_amount += parseFloat(part.amount);
                }
            });
            $scope.parts_rate = $total_amount.toFixed(2);
            $scope.calculateTotalLabourParts();
        }
        $scope.calculateTotalLabourParts = function() {
            $scope.total_amount = parseFloat($scope.parts_rate) + parseFloat($scope.labour_amount);
            $scope.total_amount = $scope.total_amount.toFixed(2);
        }

        $scope.removeScheduledPart = function(index, id, type) {
            if (id == undefined) {
                $scope.part_details.splice(index, 1);
                $scope.calculatePartTotal();
            } else {
                // console.log(index, id, type);
                $scope.delete_reason = 10021;
                $('#removal_reason').val('');
                //HIDE REASON TEXTAREA 
                $scope.customer_delete = false;
                $scope.laboutPartsDelete(index, id, type);
            }
        }
        $scope.removeScheduledLabour = function(index, id, type) {
            // console.log(index, id, type);
            $scope.delete_reason = 10021;
            $('#removal_reason').val('');
            //HIDE REASON TEXTAREA 
            $scope.customer_delete = false;

            $scope.laboutPartsDelete(index, id, type);
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
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

//Update JOb Card
app.component('inwardVehicleUpdatejcForm', {
    templateUrl: inward_vehicle_updatejc_form_template_url,
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

        /* Profile Upload */
        setTimeout(function() {
            profileImgUpload();
        }, 1000);

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/update-jc/get-form-data',
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

        //SEND OTP TO CUSTOMER
        $scope.approveBehalfCustomer = function() {
            $(".approval_behalf").button('loading');
            $.ajax({
                url: base_url + '/api/vehicle-inward/send/customer/otp',
                type: "POST",
                data: {
                    id: $routeParams.job_order_id,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    $(".approval_behalf").button('loading');
                    console.log(response);
                    $('#otp').modal('show');
                    $('#otp_no').val('');
                    $('#otp').on('shown.bs.modal', function() {
                        $(this).find('[autofocus]').focus();
                    });
                    $('.customer_mobile_no').html(response.customer_detail.mobile_no);
                    $(".approval_behalf").button('reset');
                },
                error: function(textStatus, errorThrown) {
                    $(".approval_behalf").button('reset');
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        //RESEND OTP
        $scope.ResendOtp = function() {
            $.ajax({
                url: base_url + '/api/vehicle-inward/send/customer/otp',
                type: "POST",
                data: {
                    id: $routeParams.job_order_id,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    console.log(response);
                    custom_noty('success', response.message);
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        //Save Form Data 
        $scope.saveJobCard = function(id) {
            var form_id = '#update_jc_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'job_card_number': {
                        required: true,
                        minlength: 10,
                    },
                    'job_card_photo': {
                        required: function(element) {
                            if (!$scope.job_order.job_card) {
                                return true;
                            }
                            return false;
                        },
                    },
                    'job_card_date': {
                        required: true,
                    },
                },
                errorPlacement: function(error, element) {
                    if (element.attr('name') == 'job_card_number') {
                        error.appendTo($('.job_card_error'));
                    } else if (element.attr('name') == 'job_card_photo') {
                        error.appendTo($('.attachment_error'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_close').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/job-card/save',
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
                                $('.save_close').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            if (id == 2) {
                                $scope.approveBehalfCustomer();
                            } else if (id == 3) {
                                $scope.send_customer_approval();
                            } else {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/table-list');
                            }
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.save_close').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.send_customer_approval = function() {
            $(".send_to_customer_approval").button('loading');
            $.ajax({
                url: base_url + '/api/vehicle-inward/estimate/link/generate',
                type: "POST",
                data: {
                    job_order_id: $routeParams.job_order_id,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    custom_noty('success', response.message);
                    $(".send_to_customer_approval").button('reset');
                    $location.path('/inward-vehicle/table-list');
                    $scope.$apply();
                },
                error: function(textStatus, errorThrown) {
                    $(".send_to_customer_approval").button('reset');
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        //OTP SEND
        var approve_behalf_customer_confirm = '#approve_behalf_customer_confirm';
        var v = jQuery(approve_behalf_customer_confirm).validate({
            ignore: '',
            rules: {
                'otp_no': {
                    required: true,
                    number: true,
                    minlength: 6,
                    maxlength: 6,
                },
            },
            messages: {
                'otp_no': {
                    required: 'OTP is required',
                    number: 'OTP Must be a number',
                    minlength: 'OTP Minimum 6 Characters',
                    maxlength: 'OTP Maximum 6 Characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(approve_behalf_customer_confirm)[0]);
                $('.submit_confirm').button('loading');
                $.ajax({
                        url: base_url + '/api/vehicle-inward/verify/otp',
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
                            $('.submit_confirm').button('reset');
                            $('#otp_no').val('');
                            $('#otp_no').focus();
                            return;
                        }
                        console.log(res);
                        $('.submit_confirm').button('reset');
                        custom_noty('success', res.message);
                        $('#otp_no').val('');
                        $('#otp').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        $location.path('/inward-vehicle/table-list');
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                        $('#otp_no').val('');
                        $('.submit_confirm').button('reset');
                        showServerErrorNoty();
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
angular.module('app').requires.push('webcam');
// angular.module('app').requires.push('signature');
app.component('inwardVehicleCustomerConfirmationForm', {
    templateUrl: inward_vehicle_customer_confirmation_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        self.angular_routes = angular_routes;

        // HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();
        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/vehicle-inward/customer-confirmation/get-form-data',
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

                    console.log($scope.job_order);
                    if ($scope.job_order.is_customer_agreed != 1) {
                        $location.path('/inward-vehicle/estimation-denied/form/' + $scope.job_order.id);
                    }
                    $scope.base_url = res.extras.base_url;
                    $scope.cameraOn();
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.fetchData();

        self.video = false;
        self.screenShot = false;

        //WEBCAM TO TAKE CUSTOMER PHOTO
        // Grab elements, create settings, etc.
        var video = document.getElementById('video');
        $scope.cameraOn = function() {
            self.video = true;
            self.screenShot = true;
            // Get access to the camera!
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                // Not adding `{ audio: true }` since we only want video now
                navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream) {
                    //video.src = window.URL.createObjectURL(stream);
                    video.srcObject = stream;
                    video.play();
                });
            } else {
                custom_noty('error', 'Camera Not Working!');
            }

            // Elements for taking the snapshot
            var canvas_photo = document.getElementById('canvas');
            var context = canvas_photo.getContext('2d');
            var video = document.getElementById('video');

            $scope.snapshot = function() {
                $('#customer_pic').hide();
                // Trigger photo take
                context.drawImage(video, 0, 0, 460, 360);
                var customer_photo = canvas_photo.toDataURL('image/jpeg', 1.0);
                $("#customer_photo").val(customer_photo);
            }
        }

        //SIGN PAD
        var signaturePad;
        $(document).ready(function($) {
            $("#customer_sign").val('');
            var canvas_sign = document.getElementById("signature");
            signaturePad = new SignaturePad(canvas_sign);

            $('#clear-signature').on('click', function() {
                signaturePad.clear();
                $("#customer_sign").val('');
            });
        });

        //SIGN VALIDATION
        jQuery.validator.addMethod("customer_e_sign", function(value, element, options) {
            if (signaturePad.isEmpty()) {
                return false;
            }
            return true;
        }, "Your signature is required");

        //Save Form Data 
        $scope.saveForm = function() {
            //GET AND APPEND SIGNATURE VALUE
            var canvas_sign = document.getElementById("signature");
            var customer_sign = canvas_sign.toDataURL('image/png');
            $("#customer_sign").val(customer_sign);

            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "customer_photo") {
                        error.insertAfter("#eror_customer_photo");
                    } else if (element.attr("name") == "customer_e_sign") {
                        error.insertAfter("#eror_customer_sign");
                    } else {
                        error.insertAfter(element);
                    }
                },
                rules: {
                    'customer_photo': {
                        required: function(element) {
                            if ($scope.job_order.customer_approval_attachment.length == 0) {
                                return true;
                            }
                            return false;
                        },
                    },
                    'customer_e_sign': {
                        required: function(element) {
                            if ($scope.job_order.customer_e_sign.length == 0) {
                                return true;
                            }
                            return false;
                        },
                    },
                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $('.btn-prev').bind('click', false);
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/customer-confirmation/save',
                            method: "POST",
                            data: formData,
                            beforeSend: function(xhr) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                            },
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $('.btn-prev').unbind('click', false);
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            console.log(res);
                            self.job_order = res.repair_order_and_parts_detils.original.job_order;
                            self.message = res.message;
                            $("#inward_notification").modal('show');
                            // self.repair_order_and_parts_detils
                            // custom_noty('success', res.message);
                            // $location.path('/inward-vehicle/table-list');
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //INITIATE JOB
        $scope.initiateJob = function() {
            var form_id = '#initiateJob';
            var v = jQuery(form_id).validate({
                ignore: '',
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    // console.log(formData);
                    $('.initiate_job').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/initiate-job/save',
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
                                $('.initiate_job').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            console.log(res);
                            $("#inward_notification").modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            custom_noty('success', res.message);
                            $location.path('/inward-vehicle/table-list');
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.initiate_job').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
//Estimate
app.component('inwardVehicleEstimateForm', {
    templateUrl: inward_vehicle_estimate_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $window) {
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        if (!self.hasPermission('inward-job-card-tab-estimate-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

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

                    if ($scope.job_order.is_customer_agreed == 1) {
                        self.estimate_aggreed = 1;
                        $('#is_customer_agreed').val(1);
                    } else {
                        self.estimate_aggreed = 0;
                        $('#is_customer_agreed').val('0');
                    }

                    if ($scope.job_order && $scope.job_order.is_customer_approved == null && $scope.job_order.is_customer_agreed == 1) {
                        $('.is_customer_agreed').show();
                        $('.btn-nxt').hide();
                    } else {
                        $('.is_customer_agreed').hide();
                        $('.btn-nxt').show();
                    }

                    $scope.estimated_amount = $scope.job_order.estimated_amount;
                    $scope.est_delivery_date = $scope.job_order.est_delivery_date;
                    $scope.est_delivery_time = $scope.job_order.est_delivery_time;
                    $scope.status_id = $scope.job_order.status_id;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        self.checkbox = function(id) {
            if (id == 1) {
                $('.is_customer_agreed').show();
                $('#is_customer_agreed').val(1);
                $('.btn-nxt').hide();
            } else {
                $('.is_customer_agreed').hide();
                $('#is_customer_agreed').val(0);
                $('.btn-nxt').show();
            }
        };

        //Save Form Data 
        $scope.saveEstimate = function(id) {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'estimated_amount': {
                        required: true,
                    },
                    'est_delivery_date': {
                        required: true,
                    },
                    'est_delivery_time': {
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
                    // $scope.button_action(id, 1);
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
                            // $scope.button_action(id, 2);
                            if (!res.success) {
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            if (id == 1) {
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                if ($('#is_customer_agreed').val() == 1) {
                                    if (id == 3) {
                                        $scope.approveBehalfCustomer();
                                    } else if (id == 4) {
                                        $scope.send_customer_approval();
                                    } else if (id == 2) {
                                        var est_delivery_time = $('#est_delivery_time').val();
                                        var res = est_delivery_time.split(":");
                                        if (res[0].length == 1) {
                                            est_delivery_time = '0' + res[0] + ':' + res[1];
                                        } else {
                                            est_delivery_time = $('#est_delivery_time').val();
                                        }

                                        //Check Estimated details are same or not.If not Custoerm OTP send
                                        if (($('#estimated_amount').val() != $scope.estimated_amount) || ($('#est_delivery_date').val() != $scope.est_delivery_date) || ($scope.est_delivery_time != est_delivery_time)) {
                                            $scope.approveBehalfCustomer();
                                        } else {
                                            $location.path('/inward-vehicle/customer-confirmation/' + $scope.job_order.id);
                                        }
                                    }
                                } else {
                                    $location.path('/inward-vehicle/estimation-denied/form/' + $scope.job_order.id);
                                }
                            }
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            // $scope.button_action(id, 2);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        //URL SEND TO CUSTOMER
        $scope.send_customer_approval = function() {
            $(".send_to_customer_approval").button('loading');
            $.ajax({
                url: base_url + '/api/vehicle-inward/estimate/link/generate',
                type: "POST",
                data: {
                    job_order_id: $routeParams.job_order_id,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    $(".send_to_customer_approval").button('reset');
                    custom_noty('success', response.message);
                    // $location.path('/inward-vehicle/card-list');
                    location.href = '#!/inward-vehicle/table-list';
                },
                error: function(textStatus, errorThrown) {
                    $(".send_to_customer_approval").button('reset');
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        //SEND OTP TO CUSTOMER
        $scope.approveBehalfCustomer = function() {
            $(".approval_behalf").button('loading');
            $.ajax({
                url: base_url + '/api/vehicle-inward/send/customer/otp',
                type: "POST",
                data: {
                    id: $routeParams.job_order_id,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    $(".approval_behalf").button('loading');
                    console.log(response);
                    $('#otp').modal('show');
                    $('#otp_no').val('');
                    $('#otp').on('shown.bs.modal', function() {
                        $(this).find('[autofocus]').focus();
                    });
                    $('.customer_mobile_no').html(response.mobile_number);
                    $(".approval_behalf").button('reset');
                },
                error: function(textStatus, errorThrown) {
                    $(".approval_behalf").button('reset');
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        //RESEND OTP
        $scope.ResendOtp = function() {
            $.ajax({
                url: base_url + '/api/vehicle-inward/send/customer/otp',
                type: "POST",
                data: {
                    id: $routeParams.job_order_id,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    console.log(response);
                    custom_noty('success', response.message);
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        //SAVE OTP
        $scope.saveOTP = function(id) {
            var form_id = '#approve_behalf_customer_confirm';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'otp_no': {
                        required: true,
                        number: true,
                        minlength: 6,
                        maxlength: 6,
                    },
                },
                messages: {
                    'otp_no': {
                        required: 'OTP is required',
                        number: 'OTP Must be a number',
                        minlength: 'OTP Minimum 6 Characters',
                        maxlength: 'OTP Maximum 6 Characters',
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit_confirm').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/verify/otp',
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
                                $('.submit_confirm').button('reset');
                                $('#otp_no').val('');
                                $('#otp_no').focus();
                                return;
                            }
                            console.log(res);
                            $('.submit_confirm').button('reset');
                            custom_noty('success', res.message);
                            $('#otp_no').val('');
                            $('#otp').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();

                            $location.path('/inward-vehicle/customer-confirmation/' + $scope.job_order_id);

                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            console.log(xhr);
                            $('#otp_no').val('');
                            $('.submit_confirm').button('reset');
                            showServerErrorNoty();
                        });
                }
            });
        }


        $scope.showVehicleForm = function() {
            $scope.show_vehicle_detail = false;
            $scope.show_vehicle_form = true;
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
        if (!self.hasPermission('inward-job-card-tab-vehicle-details-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }
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
                        id: $routeParams.job_order_id,
                        service_advisor_id: self.user.id
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

        $(document).on('keyup', ".registration_number", function() {
            if ($(this).val().length == 2) {
                $('.registration_number').val($(this).val() + '-');
            }
            if ($(this).val().length == 5) {
                $('.registration_number').val($(this).val() + '-');
            }
            if ($(this).val().length == 8) {
                var regis_num = $(this).val().substr(7, 1);
                if ($.isNumeric(regis_num)) {
                    //Check Previous Character Number or String
                    var previous_char = $(this).val().substr(6, 1);
                    if (!$.isNumeric(previous_char)) {
                        var regis_number = $(this).val().slice(0, -1);
                        $('.registration_number').val(regis_number + '-' + regis_num);
                    }
                } else {
                    $('.registration_number').val($(this).val() + '-');
                }
            }
        });

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

        //Save Form Data 
        $scope.onSubmit = function(id) {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'is_registered': {
                        required: true,
                    },
                    'registration_number': {
                        required: true,
                        minlength: 8,
                        maxlength: 13,
                    },
                    'plate_number': {
                        // required: function(element) {
                        //     if(self.is_registered == '0'){
                        //         return true;
                        //     }
                        //     return false;
                        // },
                        minlength: 10,
                        maxlength: 10,
                    },
                    'sold_date': {
                        required: function(element) {
                            if (self.is_sold) {
                                return true;
                            }
                            return false;
                        },
                    },
                    'model_id': {
                        required: true,
                    },
                    // 'vin_number': {
                    //     required: true,
                    //     minlength: 17,
                    //     maxlength: 17,
                    // },
                    'engine_number': {
                        required: true,
                        minlength: 7,
                        maxlength: 64,
                    },
                    'chassis_number': {
                        required: true,
                        minlength: 10,
                        maxlength: 17,
                    },
                },
                messages: {
                    // 'vin_number': {
                    //     minlength: 'Minimum 17 Numbers',
                    //     maxlength: 'Maximum 32 Numbers',
                    // },
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
                    custom_noty('error', 'You have errors, Please check fields');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/vehicle/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            if (id == 1) {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/table-list');
                            } else {
                                $location.path('/inward-vehicle/customer-detail/' + $scope.job_order.id);
                            }
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
                    $('.btn-nxt').attr("disabled", "disabled");
                } else {
                    $('.btn-nxt').button('loading');
                    $('.save').attr("disabled", "disabled");
                }
                $('.btn-prev').bind('click', false);
            } else {
                $('.save').button('reset');
                $('.btn-nxt').button('reset');
                $('.btn-prev').unbind('click', false);
                $(".btn-nxt").removeAttr("disabled");
                $(".save").removeAttr("disabled");
            }
        }
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('inwardVehicleCustomerDetail', {
    templateUrl: inward_vehicle_customer_detail_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        if (!self.hasPermission('inward-job-card-tab-customer-details-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;
        $scope.type_id = $routeParams.type_id ? $routeParams.type_id : '';
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

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        //Save Form Data 
        $scope.saveCustomer = function(id) {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'code': {
                        required: true,
                        minlength: 3,
                        maxlength: 255,
                    },
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
                        minlength: 15,
                        maxlength: 15,
                    },
                    'pan_number': {
                        minlength: 10,
                        maxlength: 10,
                    },
                    'ownership_type_id': {
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
                    $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/save-customer-detail',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                $rootScope.loading = false;
                                showErrorNoty(res);
                                return;
                            }
                            if (id == 1) {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/order-detail/form/' + $routeParams.job_order_id);
                                $scope.$apply();
                            }
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $scope.button_action(id, 2);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        if ($routeParams.type_id == 1) {
            $scope.show_customer_detail = false;
            $scope.show_customer_form = true;
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
        //GET CITY LIST
        self.searchCity = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getCitySearchList'], {
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

        $scope.countryChanged = function() {
            $.ajax({
                    url: base_url + '/api/state/get-drop-down-List',
                    method: "POST",
                    data: {
                        country_id: self.country.id,
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.extras.state_list = res.state_list;

                    //ADD NEW OWNER TYPE
                    if ($scope.type_id == 2) {
                        self.state = $scope.job_order.state;
                    } else {
                        if (!$scope.job_order.vehicle.current_owner) {
                            self.state = $scope.job_order.state;
                        } else {
                            self.state = $scope.job_order.vehicle.current_owner.customer.address.state;
                        }
                    }

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        // $scope.stateChanged = function() {
        //     $.ajax({
        //             url: base_url + '/api/city/get-drop-down-List',
        //             method: "POST",
        //             data: {
        //                 state_id: self.state.id,
        //             },
        //         })
        //         .done(function(res) {
        //             if (!res.success) {
        //                 showErrorNoty(res);
        //                 return;
        //             }
        //             $scope.extras.city_list = res.city_list;
        //             $scope.$apply();
        //         })
        //         .fail(function(xhr) {
        //             custom_noty('error', 'Something went wrong at server');
        //         });
        // }

        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.save').button('loading');
                    $('.btn-nxt').attr("disabled", "disabled");
                } else {
                    $('.btn-nxt').button('loading');
                    $('.save').attr("disabled", "disabled");
                }
                $('.btn-prev').bind('click', false);
            } else {
                $('.save').button('reset');
                $('.btn-nxt').button('reset');
                $('.btn-prev').unbind('click', false);
                $(".btn-nxt").removeAttr("disabled");
                $(".save").removeAttr("disabled");
            }
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
        if (!self.hasPermission('inward-job-card-tab-order-details-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

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

        /* Profile Upload */
        setTimeout(function() {
            profileImgUpload();
        }, 1000);

        //Save Form Data 
        $scope.saveOrderDetailForm = function(id) {
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
                    // 'service_type_id': {
                    //     required: true,
                    // },
                    'km_reading': {
                        required: true,
                        number: true,
                    },
                    'hr_reading': {
                        required: true,
                        maxlength: 10,
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
                        required: function(element) {
                            if (!$scope.job_order.driver_license_attachment) {
                                return true;
                            }
                            return false;
                        },
                    },
                    'insurance_image': {
                        required: function(element) {
                            if (!$scope.job_order.insurance_attachment) {
                                return true;
                            }
                            return false;
                        },
                    },
                    'rc_book_image': {
                        required: function(element) {
                            if (!$scope.job_order.rc_book_attachment) {
                                return true;
                            }
                            return false;
                        },
                    },
                    'driver_license_expiry_date': {
                        required: true,
                    },
                    'insurance_expiry_date': {
                        required: true,
                    },
                },
                errorPlacement: function(error, element) {
                    if (element.attr('name') == 'driving_license_image') {
                        error.appendTo($('.attachment_error'));
                    } else if (element.attr('name') == 'insurance_image') {
                        error.appendTo($('.attachment_error'));
                    } else if (element.attr('name') == 'rc_book_image') {
                        error.appendTo($('.attachment_error'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $rootScope.loading = true;
                    $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/order-detail/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                $rootScope.loading = false;
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            if (id == 1) {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/inventory-detail/form/' + $scope.job_order_id);
                                $scope.$apply();
                            }
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $scope.button_action(id, 2);
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

        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.submit').button('loading');
                    $('.btn-nxt').attr("disabled", "disabled");
                } else {
                    $('.btn-nxt').button('loading');
                    $('.submit').attr("disabled", "disabled");
                }
                $('.btn-prev').bind('click', false);
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

        if (!self.hasPermission('inward-job-card-tab-inventory-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

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

        /*$scope.checkData = function() {
            console.log('test');
            $(".inventory_items").each(function() {
                if ($(this).is(':checked')) {
                    $(this).closest(".remarks").show();
                }
            });
        }
        $scope.checkData();
        $scope.refreshData = function(inventory) {
            if (inventory.checked) {
                var dis_id = 'chkselct_' + inventory.id;
                console.log(dis_id);
                $('.show_2').css({ 'display': 'block' });
                // $("#chkselct_" + inventory.id).show();
                //$(dis_class).display('block');
                //$scope.chkselct[inventory.id] = true;
                //$scope.chkselct[inventory.id] = true;
            }
        }*/

        $scope.showDiv = function(id) {
            if (event.target.checked == true) {
                $("#remarks_div_" + id).removeClass('ng-hide');
                $("#remarks_div_" + id).val('');
                $("#is_available_" + id).val('1');
            } else {
                $("#remarks_div_" + id).addClass('ng-hide');
                $("#remarks_div_" + id).val('');
                $("#is_available_" + id).val('0');
            }
        }

        //Save Form Data 
        $scope.saveInventoryForm = function(id) {
            $('#slide_val').val($('#range_val').val());
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
                    $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/inventory/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                $rootScope.loading = false;
                                showErrorNoty(res);
                                return;
                            }
                            if (id == 1) {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/voc-detail/form/' + $scope.job_order_id);
                                $scope.$apply();
                            }
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $scope.button_action(id, 2);
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

        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.submit').button('loading');
                    $('.btn-nxt').attr("disabled", "disabled");
                } else {
                    $('.btn-nxt').button('loading');
                    $('.submit').attr("disabled", "disabled");
                }
                $('.btn-prev').bind('click', false);
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

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('inwardVehiclePayableLabourPartForm', {
    templateUrl: inward_vehicle_payable_labour_part_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $q, RepairOrderSvc, SplitOrderTypeSvc, PartSvc, $mdSelect) {
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
        if (!self.hasPermission('inward-job-card-tab-other-labour-parts-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

        self.angular_routes = angular_routes;

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
            $rootScope.loading = true;
            $.ajax({
                    url: base_url + '/api/vehicle-inward/addtional-rot-part/get-form-data',
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
                    $scope.part_details = res.part_details;
                    $scope.labour_details = res.labour_details;
                    $scope.total_amount = res.total_amount;
                    $scope.parts_total_amount = res.parts_total_amount;
                    $scope.labour_total_amount = res.labour_total_amount;
                    $scope.total_labour_count = res.total_labour_count;
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
        $scope.savePayableForm = function(id) {
            // var form_id = '#payable_form';
            // var v = jQuery(form_id).validate({
            //     ignore: '',
            //     submitHandler: function(form) {
            //         let formData = new FormData($(form_id)[0]);
            //         $rootScope.loading = true;
            //         $scope.button_action(id, 1);
            //         $.ajax({
            //                 url: base_url + '/api/vehicle-inward/addtional-rot-part/save',
            //                 method: "POST",
            //                 data: formData,
            //                 processData: false,
            //                 contentType: false,
            //             })
            //             .done(function(res) {
            //                 $scope.button_action(id, 2);
            //                 if (!res.success) {
            //                     $rootScope.loading = false;
            //                     showErrorNoty(res);
            //                     return;
            //                 }
            //                 custom_noty('success', res.message);
            if (id == 1) {
                $location.path('/inward-vehicle/table-list');
                // $scope.$apply();
            } else {
                $location.path('/inward-vehicle/estimate/' + $scope.job_order_id);
                // $scope.$apply();
            }
            //             })
            //             .fail(function(xhr) {
            //                 $rootScope.loading = false;
            //                 $scope.button_action(id, 2);
            //                 custom_noty('error', 'Something went wrong at server');
            //             });
            //     }
            // });
        }

        $scope.saveLabour = function() {
            var form_id = '#labour_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'rot_id': {
                        required: true,
                    },
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_labour').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/add-repair-order/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $('.save_labour').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.save_labour').button('reset');
                            custom_noty('success', res.message);
                            $('#labour_form_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            $scope.fetchData();
                        })
                        .fail(function(xhr) {
                            $('.save_labour').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.savePart = function() {
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
                    'split_order_type_id': {
                        required: true,
                    },
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.save_part').button('loading');
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/add-part/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            if (!res.success) {
                                $('.save_part').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            $('.save_part').button('reset');
                            custom_noty('success', res.message);
                            $('#part_form_modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            $scope.fetchData();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.init = function() {
            $rootScope.loading = true;
            let promises = {
                split_order_type_options: SplitOrderTypeSvc.options(),
            };

            $scope.options = {};
            $q.all(promises)
                .then(function(responses) {
                    $scope.options.split_order_types = responses.split_order_type_options.data.options;
                    $rootScope.loading = false;

                });
        };
        $scope.init();
        $scope.searchRepairOrders = function(query) {
            return new Promise(function(resolve, reject) {
                RepairOrderSvc.options({ filter: { search: query } })
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
                    $scope.schedule_maintainance_part.part.qty = $qty;
                    $scope.calculatePartAmount();
                });

        }
        $scope.calculatePartAmount = function() {
            if (!$scope.schedule_maintainance_part.part.pivot) {
                $scope.schedule_maintainance_part.part.pivot = {};
            }
            $scope.schedule_maintainance_part.part.pivot.quantity = $scope.schedule_maintainance_part.part.qty;
            $scope.schedule_maintainance_part.part.total_amount = $scope.schedule_maintainance_part.part.qty * $scope.schedule_maintainance_part.part.mrp;
            $scope.schedule_maintainance_part.part.pivot.amount = $scope.schedule_maintainance_part.part.total_amount;
            $scope.calculatePartTotal();
        }
        $scope.showLabourForm = function(labour_index, labour = null) {
            $scope.schedule_maintainance_ro = [];
            $scope.repair_order_id = '';
            if (labour_index === false) {
                // $scope.labour_details = {};
            } else {
                // console.log(labour);
                // return false;
                if (labour.split_order_type_id != null) {
                    $scope.repair_order_id = labour.id;
                    if (labour.split_order_type_id == undefined) {
                        $split_id = labour.pivot.split_order_type_id;
                    } else {
                        $split_id = labour.split_order_type_id;
                    }
                    SplitOrderTypeSvc.read($split_id)
                        .then(function(response) {
                            $scope.schedule_maintainance_ro.split_order_type = response.data.split_order_type;
                        });
                }
                if (labour.category == undefined) {
                    RepairOrderSvc.read(labour.labour_id)
                        .then(function(response) {
                            $scope.schedule_maintainance_ro.repair_order = response.data.repair_order;
                        });
                }
                $scope.schedule_maintainance_ro.repair_order = labour;
            }

            $scope.labour_index = labour_index;
            $scope.labour_modal_action = labour_index === false ? 'Add' : 'Edit';
            $('#labour_form_modal').modal('show');
        }
        $scope.showPartForm = function(part_index, part = null) {
            // console.log(part.qty);
            $scope.schedule_maintainance_part = [];
            $scope.job_order_part_id = '';
            if (part_index === false) {
                // $scope.part_details = {};
            } else {
                if (part.split_order_type_id != null) {
                    $scope.job_order_part_id = part.id;
                    if (part.split_order_type_id == undefined) {
                        $split_id = part.pivot.split_order_type_id;
                    } else {
                        $split_id = part.split_order_type_id;
                    }
                    SplitOrderTypeSvc.read($split_id)
                        .then(function(response) {
                            $scope.schedule_maintainance_part.split_order_type = response.data.split_order_type;
                        });
                }
                if (part.uom == undefined) {
                    PartSvc.read(part.part_id)
                        .then(function(response) {
                            $scope.schedule_maintainance_part.part = response.data.part;
                            $scope.schedule_maintainance_part.part.qty = part.qty;
                            // $scope.calculatePartAmount();
                        });
                }
                $scope.schedule_maintainance_part.part = part;
            }

            $scope.part_index = part_index;
            $scope.part_modal_action = part_index === false ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
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
        /* Dropdown Arrow Function */
        arrowDropdown();

        // self.remove_job_order_part_ids = [];
        $scope.removePayablePart = function(index, id, type) {
            // console.log(index, id, type);
            $scope.delete_reason = 10021;
            $('#removal_reason').val('');
            //HIDE REASON TEXTAREA 
            $scope.customer_delete = false;

            $scope.laboutPartsDelete(index, id, type);
            // if (id) {
            //     self.remove_job_order_part_ids.push(id);
            //     $("#delete_job_order_part_ids").val(JSON.stringify(self.remove_job_order_part_ids));
            // }
            // $scope.job_order.job_order_parts.splice(index, 1);
            // $scope.calTotal();
        }
        // self.remove_job_order_repair_order_ids = [];
        $scope.removePayableLabour = function(index, id, type) {
            // console.log(index, id, type);
            $scope.delete_reason = 10021;
            $('#removal_reason').val('');
            //HIDE REASON TEXTAREA 
            $scope.customer_delete = false;

            $scope.laboutPartsDelete(index, id, type);

            // if (id) {
            //     self.remove_job_order_repair_order_ids.push(id);
            //     $("#delete_job_order_repair_order_ids").val(JSON.stringify(self.remove_job_order_repair_order_ids));
            // }
            // $scope.job_order.job_order_repair_orders.splice(index, 1);
            // $scope.calTotal();
        }

        $scope.laboutPartsDelete = function(index, id, type) {
            $('#delete_labour_parts').modal('show');
            $('#labour_parts_id').val(id);
            $('#payable_type').val(type);

            $scope.saveLabourPartDeleteForm = function() {
                var form_id = '#labour_parts_remove';
                var v = jQuery(form_id).validate({
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
                        let formData = new FormData($(form_id)[0]);
                        $rootScope.loading = true;
                        // $scope.button_action(id, 1);
                        $.ajax({
                                url: base_url + '/api/vehicle-inward/labour-parts/delete',
                                method: "POST",
                                data: formData,
                                processData: false,
                                contentType: false,
                            })
                            .done(function(res) {
                                // $scope.button_action(id, 2);
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
                                // if (id == 1) {
                                //     $location.path('/inward-vehicle/card-list');
                                //     $scope.$apply();
                                // } else {
                                //     $location.path('/inward-vehicle/estimate/' + $scope.job_order_id);
                                //     $scope.$apply();
                                // }
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

        $scope.calTotal = function() {
            var total_amount = 0;
            var parts_amount = 0;
            var labour_amount = 0;
            angular.forEach($scope.job_order.job_order_repair_orders, function(value, key) {
                labour_amount += parseFloat(value.amount);
            });
            $scope.labour_total_amount = parseFloat(labour_amount).toFixed(2);

            angular.forEach($scope.job_order.job_order_parts, function(value, key) {
                parts_amount += parseFloat(value.amount);
            });
            $scope.parts_total_amount = parseFloat(parts_amount).toFixed(2);

            $scope.total_amount = parseFloat($scope.parts_total_amount) + parseFloat($scope.labour_total_amount);

            $scope.labour_total_amount = parseFloat($scope.labour_total_amount).toFixed(2);
            $scope.parts_total_amount = parseFloat($scope.parts_total_amount).toFixed(2);
            $scope.total_amount = parseFloat($scope.total_amount).toFixed(2);

        }
        /* Image Uploadify Funtion */
        $('.image_uploadify').imageuploadify();

        $scope.sendConfirm = function() {
            var job_order_id = $scope.job_order.id;
            if (job_order_id) {
                $('.send_confirm').button('loading');
                $.ajax({
                        url: base_url + '/api/vehicle-inward/stock-incharge/request/parts',
                        method: "POST",
                        data: {
                            id: job_order_id,
                            type_id: 1,
                        },
                    })
                    .done(function(res) {
                        $('.send_confirm').button('reset');
                        if (!res.success) {
                            showErrorNoty(res);
                            return;
                        }
                        console.log(res);
                        custom_noty('success', 'URL send to Customer Successfully!!');
                        $("#confirmation_modal").modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        window.location = "#!/inward-vehicle/table-list";
                        // $scope.fetchData();
                    })
                    .fail(function(xhr) {
                        $('.send_confirm').button('reset');
                    });
            }
        }
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('inwardVehiclePayableAddPartForm', {
    templateUrl: inward_vehicle_payable_labour_part_add_part_form_template_url,
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
            $scope.job_order_part.amount = parseFloat($scope.job_order_part.qty * parseFloat($scope.job_order_part.mrp)).toFixed(2);
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
                    'split_order_type_id': {
                        required: true,
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
                            $location.path('/inward-vehicle/payable-labour-part-detail/form/' + $scope.job_order_id);
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


//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('inwardVehiclePayableAddLabourForm', {
    templateUrl: inward_vehicle_payable_labour_part_add_labour_form_template_url,
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
                    'split_order_type_id': {
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
                            $location.path('/inward-vehicle/payable-labour-part-detail/form/' + $scope.job_order_id);
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
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-job-order') || !self.hasPermission('edit-job-order')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }

        if (!self.hasPermission('inward-job-card-tab-capture-voc-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
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
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    console.log(res);
                    // self.job_order = $scope.job_order = res.job_order;
                    $scope.job_order = res.job_order;
                    $scope.extras = res.extras;
                    angular.forEach($scope.job_order.customer_voices, function(value, key) {
                        // console.log(value.id);
                        setTimeout(function() {
                            $scope.onSelectedVoc(value.id, key);
                        }, 500);
                    });
                    if (res.action == "add") {
                        $scope.addNewCustomerVoice();
                    }
                    $scope.action = res.action;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        // $('.voc_remark_details_0').hide();
        $scope.onSelectedVoc = function(id, index) {
            if ($scope.job_order.OTH_ID == id) {
                console.log('in');
                $('.customer_voice_remark_' + index).prop('disabled', false);
                $('.customer_voice_remark_' + index).addClass('required');
                $('.voc_remark_details_' + index).show();
                // $scope.voc_remark_details_+index = true;
            } else {
                console.log('out');
                // $scope.voc_remark_details_+index = false;
                $('.customer_voice_remark_' + index).prop('disabled', true);
                $('.customer_voice_remark_' + index).removeClass('required');
                $('.voc_remark_details_' + index).hide();
            }
        }

        //Save Form Data 
        $scope.saveVocDetailForm = function(id) {
            var voc_form_id = '#voc_form';
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
                    $rootScope.loading = true;
                    $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/voc/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                $rootScope.loading = false;
                                showErrorNoty(res);
                                return;
                            }
                            if (id == 1) {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                custom_noty('success', res.message);
                                $location.path('/inward-vehicle/road-test-detail/form/' + $scope.job_order_id);
                                $scope.$apply();
                            }
                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $scope.button_action(id, 2);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.addNewCustomerVoice = function() {
            $scope.job_order.customer_voices.push({
                id: '',
            });
        }

        self.removeCustomerVoice = function(index) {
            // if (index == 6) {
            //     $scope.job_order.customer_voices.splice(index, 1);
            //     $('#voc_remark_details').hide();
            // } else {
            $scope.job_order.customer_voices.splice(index, 1);
            // }
        }

        /* Image Uploadify Funtion */
        $('.image_uploadify').imageuploadify();

        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.submit').button('loading');
                    $('.btn-nxt').attr("disabled", "disabled");
                } else {
                    $('.btn-nxt').button('loading');
                    $('.submit').attr("disabled", "disabled");
                }
                $('.btn-prev').bind('click', false);
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

        if (!self.hasPermission('inward-job-card-tab-road-test-edit')) {
            window.location = "#!/inward-vehicle/table-list";
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;
        //FETCH DATA
        $scope.fetchData = function() {
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
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.gate_log_detail = res.gate_log_detail;
                    $scope.job_order = res.job_order;

                    if (!$scope.job_order.is_road_test_required) {
                        $scope.job_order.is_road_test_required = 0;
                    }

                    if (!$scope.job_order.road_test_done_by_id) {
                        $scope.job_order.road_test_done_by_id = 8100;
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
        $scope.saveRoadTestDetailForm = function(id) {
            var form_id = '#road_test_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'is_road_test_required': {
                        required: true,
                    },
                    'road_test_done_by_id': {
                        required: true,
                    },
                    'road_test_performed_by_id': {
                        required: true,
                    },
                    // 'road_test_report': {
                    //     required: true,
                    // },
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "is_road_test_required") {
                        error.appendTo('#errorRoadTestRequired');
                        return;
                    } else if (element.attr("name") == "road_test_done_by_id") {
                        error.appendTo('#errorRoadTestDone');
                        return;
                    }
                    // else if (element.attr("name") == "road_test_report") {
                    //     error.appendTo('#errorRoadTestObservation');
                    //     return;
                    // } 
                    else {
                        error.insertAfter(element);
                    }
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
                            url: base_url + '/api/vehicle-inward/road-test-observation/save',
                            method: "POST",
                            data: formData,
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
                            if (id == 1) {
                                $location.path('/inward-vehicle/table-list');
                                $scope.$apply();
                            } else {
                                $location.path('/inward-vehicle/expert-diagnosis-detail/form/' + $scope.job_order_id);
                                $scope.$apply();
                            }
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
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('inwardVehicleEstimationStatusDetailForm', {
    templateUrl: inward_vehicle_estimation_status_detail_form_template_url,
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
        if (!self.hasPermission('inward-job-card-tab-estimation-status')) {
            window.location = "#!/inward-vehicle/table-list";
        }

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/vehicle-inward/estimation-denied/get-form-data',
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
                    if ($scope.job_order.is_customer_agreed == 1) {
                        $location.path('/inward-vehicle/customer-confirmation/' + $scope.job_order.id);
                    }
                    $scope.estimation_type = res.estimation_type;
                    $scope.minimum_payable_amount = $scope.job_order.minimum_payable_amount;

                    $scope.getSelectedEstimationType($scope.job_order.estimation_type_id, 1);
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

        $scope.getSelectedEstimationType = function(estimation_type_id, type) {
            $.each($scope.estimation_type, function(key, val) {
                if (estimation_type_id == val['id']) {
                    if (type != 1) {
                        $scope.minimum_payable_amount = val['minimum_amount'];
                    }
                    $scope.minimum_amount = val['minimum_amount'];
                    return;
                }
            });
        }

        //Save Form Data 
        $scope.saveStatusDetaiForm = function(id) {
            var form_id = '#status_detail_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'estimation_type_id': {
                        required: true,
                    },
                    'minimum_payable_amount': {
                        required: true,
                        number: true,
                    },
                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Please check all tabs');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    // console.log('submit');
                    $rootScope.loading = true;
                    $scope.button_action(id, 1);
                    $.ajax({
                            url: base_url + '/api/vehicle-inward/estimation-denied/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $scope.button_action(id, 2);
                            if (!res.success) {
                                $rootScope.loading = false;
                                showErrorNoty(res);
                                return;
                            }
                            console.log(res.job_order);
                            $('.submit').button('reset');
                            $('#confirm_notification').modal('show');
                            $scope.estimate_ref_no = res.job_order.estimate_ref_no;

                            $scope.$apply();

                        })
                        .fail(function(xhr) {
                            $rootScope.loading = false;
                            $scope.button_action(id, 2);
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.redirectPage = function() {
            window.location = base_url + '#!/inward-vehicle/table-list';
        }
        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.submit').button('loading');
                    $('.btn-nxt').attr("disabled", "disabled");
                } else {
                    $('.btn-nxt').button('loading');
                    $('.submit').attr("disabled", "disabled");
                }
                $('.btn-prev').bind('click', false);
            } else {
                $('.submit').button('reset');
                $('.btn-nxt').button('reset');
                $('.btn-prev').unbind('click', false);
                $(".btn-nxt").removeAttr("disabled");
                $(".submit").removeAttr("disabled");
            }
        }

        /* Image Uploadify Funtion */
        $('.image_uploadify').imageuploadify();

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

                    $scope.$apply();
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
app.directive('inwardViewTabs', function() {
    return {
        templateUrl: inward_view_tabs_template_url,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------