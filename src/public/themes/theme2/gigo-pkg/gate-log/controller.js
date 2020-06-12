app.component('gateLogList', {
    templateUrl: gate_log_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_gate_log').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('gate-logs')) {
            window.location = "#!/permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-gate-log');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#gate_logs_list').DataTable({
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
                    $('#search_gate_log').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getGateLogList'],
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
                { data: 'short_name', name: 'gate_logs.short_name' },
                { data: 'name', name: 'gate_logs.name' },
                { data: 'description', name: 'gate_logs.description' },
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
            $('#search_gate_log').val('');
            $('#gate_logs_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#gate_logs_list').DataTable().ajax.reload();
        });

        var dataTables = $('#gate_logs_list').dataTable();
        $("#search_gate_log").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteGateLog = function($id) {
            $('#gate_log_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#gate_log_id').val();
            $http.get(
                laravel_routes['deleteGateLog'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Gate Log Deleted Successfully');
                    $('#gate_logs_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/gate-log/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getGateLogFilter']
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

app.component('gateLogForm', {
    templateUrl: gate_log_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $route) {
        // alert("test");
        var self = this;
        // $("input:text:visible:first").focus();
        HelperService.isLoggedIn()
        $('.image_uploadify').imageuploadify();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-gate-log') && !self.hasPermission('edit-gate-log')) {
            window.location = "#!/permission-denied";
            return false;
        }
        $scope.hasPerm = HelperService.hasPerm;
        self.user = $scope.user = HelperService.getLoggedUser();
        self.angular_routes = angular_routes;
        self.gate_log = {};
        self.is_registered = 1;

        //for md-select search
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/gate-in-entry/get-form-data',
                    method: "GET",
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
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
        var form_id = '#gate_in_vehicle_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'vehicle_photo': {
                    required: true,
                },
                'km_reading_photo': {
                    required: true,
                },
                'driver_photo': {
                    required: true,
                },
                'is_registered': {
                    required: true,
                },
                'registration_number': {
                    required: function(element) {
                        if(self.is_registered == '1'){
                            return true;
                        }
                        return false;
                    },
                    minlength: 10,
                    maxlength: 10,
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
                'driver_name': {
                    // 'nullable',
                    minlength: 3,
                    maxlength: 191,
                },
                'driver_mobile_number': {
                    number: true,
                    minlength: 10,
                    maxlength: 10,
                },
                'km_reading': {
                    required: true,
                    digits: true,
                    maxlength: 10,
                    // regex: /^-?[0-9]+(?:\.[0-9]{1,2})?$/,
                },
                'hr_reading': {
                    required: true,
                    maxlength: 10,
                },
                'vin_number': {
                    required: true,
                    minlength: 17,
                    maxlength: 17,
                },
                'gate_in_remarks': {
                    minlength: 3,
                    maxlength: 191,
                    // 'nullable',
                }
            },
            messages: {
                'registration_number': {
                    minlength: 'Minimum 10 Characters',
                    maxlength: 'Maximum 10 Characters',
                },
                'plate_number': {
                    minlength: 'Minimum 10 Characters',
                    maxlength: 'Maximum 10 Characters',
                },
                'driver_name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                },
                'contact number': {
                    minlength: 'Minimum 10 Number',
                    maxlength: 'Maximum 10 Number',
                },
                'km_reading': {
                    // minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 10 Number',
                },
                'gate_in_remarks': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                }
            },
            errorPlacement: function(error, element) {
                if (element.hasClass("vehicle_photo")) {
                    custom_noty('error', 'Vehicle Photo is Required')
                }else if (element.hasClass("km_reading_photo")) {
                    custom_noty('error', 'KM Reading Photo is Required')
                }else if (element.hasClass("driver_photo")) {
                    custom_noty('error', 'Driver Photo is Required')
                }else{
                    error.insertAfter(element)
                }
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: base_url + '/api/gate-in-entry/create',
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
                            $('#submit').button('reset');
                            showErrorNoty(res);
                        }else{
                            custom_noty('success', res.message);
                            self.gate_log = res.gate_log;
                            $('#confirm_notification').modal('show');
                            $('#number').html(res.gate_log.number);
                            $('#registration_number').html(res.gate_log.registration_number);                            
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
        $scope.reloadPage = function() {
            // $location.reload(true);
            $('#confirm_notification').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $route.reload();
        }
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------