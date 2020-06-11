app.component('repairOrderList', {
    templateUrl: repair_order_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#repair_order_table').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('repair-orders')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('repair-orders');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#repair_order_table').DataTable({
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
                    $('#search_repair_order').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getRepairOrderList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.dbm_code = $('#dbm_code').val();
                    d.dms_code = $('#dms_code').val();
                    d.name = $('#name').val();
                    d.type = $('#type').val();
                    d.skill_level = $('#skill_level').val();
                    d.hours = $('#hours').val();
                    d.amount = $('#amount').val();
                    d.tax_code = $('#tax_code').val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'code', name: 'repair_orders.code' , searchable: true },
                { data: 'alt_code', name: 'repair_orders.alt_code' , searchable: true },
                { data: 'name', name: 'repair_orders.name' , searchable: true },
                { data: 'short_name', name: 'repair_order_types.short_name' , searchable: true },
                { data: 'skill_name', name: 'skill_levels.name' , searchable: true },
                { data: 'hours', name: 'repair_orders.hours' , searchable: true},
                { data: 'amount', name: 'repair_orders.amount', searchable: true },
                { data: 'tax_code', name: 'tax_codes.code', searchable: true },
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
            $('#search_repair_order').val('');
            $('#repair_order_table').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#repair_order_table').DataTable().ajax.reload();
        });

        var dataTables = $('#repair_order_table').dataTable();
        $("#search_repair_order").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteRepairOrder = function($id) {
            $('#repair_order_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#repair_order_id').val();
            $http.get(
                laravel_routes['deleteRepairOrder'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Repair Order  Deleted Successfully');
                    $('#repair_order_table').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/repair-order/list');
                }
            });
        }

        //FOR FILTER
        $http.get(
            laravel_routes['getRepairOrderFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
            self.repair_order_type = response.data.repair_order_type;
            self.skill_level = response.data.skill_level;
            self.tax_code = response.data.tax_code;
            self.tax_code_selected = '';
            self.skill_level_selected = '';
            self.repair_order_type_selected = '';
        });

        $scope.onSelectedtaxcode = function(tax_code_selected) {
            $('#tax_code').val(tax_code_selected);
        }
        $scope.onSelectedSkil = function(skill_level_selected) {
            $('#skill_level').val(skill_level_selected);
        }
        $scope.onSelectedtype = function(repair_order_type_selected) {
            $('#type').val(repair_order_type_selected);
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
            $('#repair-order-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#name").val('');
            $("#dbm_code").val('');
            $("#dms_code").val('');
            $("#type").val('');
            $("#skill_level").val('');
            $("#amount").val('');
            $("#tax_code").val('');
            $("#status").val('');
            dataTables.fnFilter();
            $('#repair-order-filter-modal').modal('hide');
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('repairOrderForm', {
    templateUrl: repair_order_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-repair-order') || !self.hasPermission('edit-repair-order')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getRepairOrderFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.repair_order = response.data.repair_order;
            self.repair_order_type = response.data.repair_order_type;
            self.skill_level = response.data.skill_level;
            self.tax_code = response.data.tax_code;
            self.uom_code = response.data.uom_code;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.repair_order.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        //Save Form Data 
        var form_id = '#repair_order';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'type_id':{
                    required:true,
                },
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 36,
                },
                'alt_code': {
                    minlength: 3,
                    maxlength: 36,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 128,
                },
                'skill_level_id':{
                    required:true,
                },
                'hours':{
                    required:true,
                },
                'amount':{
                    required:true,
                },
                /*'tax_code_id':{
                    required:true,
                },*/
            },
            messages: {
                'code': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 36 Characters',
                },
                'alt_code': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 36 Characters',
                },
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 128 Characters',
                },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveRepairOrder'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/repair-order/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/repair-order/list');
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
