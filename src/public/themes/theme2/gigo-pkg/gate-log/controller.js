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
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        // self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('add-gate-log') && !self.hasPermission('edit-gate-log')) {
        //     window.location = "#!/permission-denied";
        //     return false;
        // }
        $scope.hasPerm = HelperService.hasPerm;
        self.user = $scope.user = HelperService.getLoggedUser();

        if (!HelperService.isLoggedIn()) {
            $location.path('/');
            return;
        }
        self.angular_routes = angular_routes;

        $http.get(
            laravel_routes['getGateLogFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.gate_log = response.data.gate_log;
            self.extras = response.data.extras;
            self.action = response.data.action;
            $rootScope.loading = false;
            // if (self.action == 'Edit') {
            //     if (self.gate_log.deleted_at) {
            //         self.switch_value = 'Inactive';
            //     } else {
            //         self.switch_value = 'Active';
            //     }
            // } else {
            //     self.switch_value = 'Active';
            // }
        });

        //Save Form Data             
        var form_id = '#gate_log_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'number': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'gate_in_date': {
                    required: true,
                },
                'driver_name': {
                    // 'nullable',
                    minlength: 3,
                    maxlength: 191,
                },
                'contact_number': {
                    number: true,
                    minlength: 10,
                    maxlength: 10,
                },
                'vehicle_id': {
                    // 'nullable',
                    required: true,
                },
                'km_reading': {
                    required: true,
                    max: 10,
                    // regex: /^-?[0-9]+(?:\.[0-9]{1,2})?$/,
                },
                'reading_type_id': {
                    // 'nullable',
                },
                'gate_in_remarks': {
                    minlength: 3,
                    maxlength: 191,
                    // 'nullable',
                }
            },
            messages: {
                'number': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
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
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },

            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/gigo-pkg/save-vehicle-gate-in-entry',
                        // laravel_routes['saveGateLog'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            // $location.path('/gigo-pkg/gate-log/list');
                            $location.reload(true);
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
                                // $location.path('/gigo-pkg/gate-log/list');
                                $location.reload(true);
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