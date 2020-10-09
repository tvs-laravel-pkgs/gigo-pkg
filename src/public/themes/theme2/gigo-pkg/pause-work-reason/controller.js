app.component('pauseWorkReasonList', {
    templateUrl: pause_work_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#pause_work_reason_list').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('pause-work-reasons')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('pause-work-reasons');
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#pause_work_reason_list').DataTable({
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
                    $('#search_pause_wrk_reason').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            ajax: {
                url: laravel_routes['getPauseWorkReasonList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.name = $('#name').val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'name', name: 'pause_work_reasons.name' ,searchable: true },
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
            $('#search_pause_wrk_reason').val('');
            $('#pause_work_reason_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#pause_work_reason_list').DataTable().ajax.reload();
        });

        var dataTables = $('#pause_work_reason_list').dataTable();
        $("#search_pause_wrk_reason").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deletePauseWorkReason = function($id) {
            $('#pause_work_reason_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#pause_work_reason_id').val();
            $http.get(
                laravel_routes['deletePauseWorkReason'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Pause Work Reason Deleted Successfully');
                    $('#pause_work_reason_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/pasuse-work-reason/list');
                }
            });
        }

        //FOR FILTER
        $http.get(
            laravel_routes['getPauseWorkReasonFilterData']
        ).then(function(response) {
            self.extras = response.data.extras;
        });
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
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
            $('#pause_work_reason-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#name").val('');
            $("#status").val('');
            dataTables.fnFilter();
            $('#pause_work_reason-filter-modal').modal('hide');
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('pauseWorkReasonForm', {
    templateUrl: pause_work_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-pause-work-reason') || !self.hasPermission('edit-pause-work-reason')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getPauseWorkReasonFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.pause_work_reason = response.data.pause_work_reason;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.pause_work_reason.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        //Save Form Data 
        var form_id = '#pause_work_reason';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
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
                        url: laravel_routes['savePauseWorkReason'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/pasuse-work-reason/list');
                            $scope.$apply();
                        } else {
                            $('.submit').button('reset');
                            showErrorNoty(res);
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
