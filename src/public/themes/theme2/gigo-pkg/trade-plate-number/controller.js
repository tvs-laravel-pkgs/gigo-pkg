app.component('tradePlateNumberList', {
    templateUrl: trade_plate_number_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_estimation_type').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('trade-plate-numbers')) {
            window.location = "#!/permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('trade-plate-numbers');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#trade_plate_list').DataTable({
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
                    $('#search_estimation_type').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getTradePlateNumberList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.outlet_id = $("#outlet_id").val();
                    d.status = $("#status").val();
                    d.date_range = $("#date_range").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'trade_plate_number', name: 'trade_plate_numbers.trade_plate_number' },
                { data: 'code', name: 'outlets.code' },
                { data: 'insurance_validity_from', searchable: false },
                { data: 'insurance_validity_to', searchable: false },
                { data: 'insurance_validity_status', searchable: false },
                { data: 'status', searchable: false },

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
            $('#search_estimation_type').val('');
            $('#trade_plate_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#trade_plate_list').DataTable().ajax.reload();
        });

        var dataTables = $('#trade_plate_list').dataTable();
        $("#search_estimation_type").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteTradePlateNumber = function($id) {
            $('#trade_plate_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#trade_plate_id').val();
            $http.get(
                laravel_routes['deleteTradePlateNumber'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Trade Plate Number Deleted Successfully');
                    $('#trade_plate_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/trade-plate-number/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getTradePlateNumberFilter']
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

        /* DateRange Picker */
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            // dataTables.fnFilter();
        });

        $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        $scope.applyFilter = function() {
            $('#status').val(self.status);
            dataTables.fnFilter();
            $('#estimation-type-filter-modal').modal('hide');
        }

        $scope.reset_filter = function() {
            $("#outlet_id").val('');
            $("#status").val('');
            self.outlet_id = '';
            dataTables.fnFilter();
            $('#estimation-type-filter-modal').modal('hide');
        }

        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('tradePlateNumberForm', {
    templateUrl: trade_plate_number_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        var self = this;

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-trade-plate-number') && !self.hasPermission('edit-trade-plate-number')) {
            window.location = "#!/permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getTradePlateNumberFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.trade_plate_number_data = response.data.trade_plate_number_data;
            self.outlet_list = response.data.outlet_list;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.trade_plate_number_data.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
                var insurance_periods = response.data.trade_plate_number_data.insurance_validity_from + ' to ' + response.data.trade_plate_number_data.insurance_validity_to;
                self.insurance_periods = insurance_periods;
            } else {
                self.switch_value = 'Active';
                self.insurance_periods = '';
            }
        });

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        /* DateRange Picker */
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        // $('.align-left.daterange').daterangepicker({
        //     autoUpdateInput: false,
        //     "opens": "left",
        //     locale: {
        //         cancelLabel: 'Clear',
        //         format: "DD-MM-YYYY"
        //     }
        // });

        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            //dataTables.fnFilter();
        });

        $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        //Save Form Data 
        var form_id = '#trade_plate_number_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'trade_plate_number': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
                'outlet_id': {
                    required: true,
                },
                'insurance_periods': {
                    required: true,
                },
            },
            messages: {
                'trade_plate_number': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 64 Characters',
                },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check the tab');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveTradePlateNumber'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/trade-plate-number/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/trade-plate-number/list');
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