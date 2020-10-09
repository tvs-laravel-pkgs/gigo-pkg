app.component('serviceOrderTypeList', {
    templateUrl: service_order_type_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#service_order_types_list').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('service-order-types')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-service-order-type');
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#service_order_types_list').DataTable({
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
                    $('#search_service_order_type').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            ajax: {
                url: laravel_routes['getServiceOrderTypeList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.short_name = $("#short_name").val();
                    d.name = $("#name").val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'code', name: 'service_order_types.code' ,searchable: true },
                { data: 'name', name: 'service_order_types.name' ,searchable: true },
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
            $('#search_service_order_type').val('');
            $('#service_order_types_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#service_order_types_list').DataTable().ajax.reload();
        });

        var dataTables = $('#service_order_types_list').dataTable();
        $("#search_service_order_type").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteServiceOrderType = function($id) {
            $('#service_order_type_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#service_order_type_id').val();
            $http.get(
                laravel_routes['deleteServiceOrderType'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Service Order Type Deleted Successfully');
                    $('#service_order_types_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/service-order-type/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getServiceOrderTypeFilterData']
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
       
        $scope.applyFilter = function() {
            $('#status').val(self.status);
            dataTables.fnFilter();
            $('#service-order-type-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#short_name").val('');
            $("#name").val('');
            $("#status").val('');
            dataTables.fnFilter();
            $('#service-order-type-filter-modal').modal('hide');
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('serviceOrderTypeForm', {
    templateUrl: service_order_type_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-service-order-type') || !self.hasPermission('edit-service-order-type')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getServiceOrderTypeFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.service_order_type = response.data.service_order_type;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.service_order_type.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
                if (self.service_order_type.is_expert_diagnosis_required) {
                    self.is_expert_diagnosis_required = 'Yes';
                } else {
                    self.is_expert_diagnosis_required = 'No';
                }
            } else {
                self.switch_value = 'Active';
                self.is_expert_diagnosis_required = 'No';
            }
        });

        //Save Form Data 
        var form_id = '#service_order_type_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
            },
            messages: {
                'code': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 64 Characters',
                },
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 64 Characters',
                },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveServiceOrderType'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/service-order-type/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/service-order-type/list');
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