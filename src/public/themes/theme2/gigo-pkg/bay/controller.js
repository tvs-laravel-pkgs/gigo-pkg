app.component('bayList', {
    templateUrl: bay_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_bay').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('bays')) {
            window.location = "#!/permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-bay');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#bays_list').DataTable({
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
                    $('#search_bay').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getBayList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.short_name = $('#short_name').val();
                    d.name = $('#name').val();
                    d.outlet = $('#outlet').val();
                    // d.bay_status = $('#bay_status').val();
                    // d.job_order = $('#job_order').val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'short_name', name: 'bays.short_name', searchable: true },
                { data: 'name', name: 'bays.name', searchable: true },
                { data: 'outlet', name: 'outlets.code', searchable: true },
                { data: 'bay_status', name: 'configs.name', searchable: true },
                // { data: 'job_order', name: 'job_orders.number', searchable: true },
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
            $('#search_bay').val('');
            $('#bays_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#bays_list').DataTable().ajax.reload();
        });

        var dataTables = $('#bays_list').dataTable();
        $("#search_bay").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteBay = function($id) {
            $('#bay_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#bay_id').val();
            $http.get(
                laravel_routes['deleteBay'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Bay Deleted Successfully');
                    $('#bays_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/bay/list');
                }
            });
        }

        //FOR FILTER
        $http.get(
            laravel_routes['getBayFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
            self.bay = response.data.bay;
            self.outlet_list = response.data.outlet_list;
            // self.bay_status_list = response.data.bay_status_list;
            // self.job_order_list = response.data.job_order_list;
            self.outlet_selected = '';
            self.bay_status_selected = '';
            // self.job_order_selected = '';
        });

        $scope.onSelectedOutlet = function(outlet_selected) {
            $('#outlet').val(outlet_selected);
        }
        // $scope.onSelectedBayStatus = function(bay_status_selected) {
        //     $('#bay_status').val(bay_status_selected);
        // }
        // $scope.onSelectedJobOrder = function(job_order_selected) {
        //     $('#job_order').val(job_order_selected);
        // }

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
            $('#bay-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#short_name").val('');
            $("#name").val('');
            $("#outlet").val('');
            // $("#bay_status").val('');
            // $("#job_order").val('');
            $("#status").val('');
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('bayForm', {
    templateUrl: bay_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-bay') && !self.hasPermission('edit-bay')) {
            window.location = "#!/permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getBayFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.bay = response.data.bay;
            self.extras = response.data.extras;
            // console.log(self.extras);
            // return;
            // self.outlet = response.data.outlet;
            // self.bay_status = response.data.bay_status;
            // self.job_order = response.data.job_order;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.bay.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        //Save Form Data 
        var form_id = '#bay_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'short_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 32,
                },
                // 'name': {
                //     required: true,
                //     minlength: 3,
                //     maxlength: 128,
                // },
                'outlet_id': {
                    required: true,
                },
            },
            messages: {
                'short_name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 32 Characters',
                },
                // 'name': {
                //     minlength: 'Minimum 3 Characters',
                //     maxlength: 'Maximum 128 Characters',
                // },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveBay'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/bay/list');
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
                                $location.path('/gigo-pkg/bay/list');
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