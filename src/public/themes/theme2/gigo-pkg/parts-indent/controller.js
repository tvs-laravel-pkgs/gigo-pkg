app.component('partsIndentList', {
    templateUrl: parts_indent_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#parts_indent_table').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('parts-indent')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('parts-indent');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#parts_indent_table').DataTable({
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
                    $('#search_parts_indent').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getPartsindentList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.vendor = $('#vendor').val();
                    d.outlet = $('#outlet').val();
                    d.date = $('#date').val();
                },
            },
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'job_card_number', name: 'job_cards.job_card_number' , searchable: true },
                { data: 'date_time', name: 'job_cards.created_at' , searchable: true },
                { data: 'qty', name: 'job_order_parts.qty' , searchable: true },
                { data: 'issued_qty', name: 'job_order_issued_parts.issued_qty' , searchable: true },
                { data: 'floor_supervisor', name: 'users.name' , searchable: true },
                { data: 'customer_name', name: '' },
                { data: 'state_name', name: 'states.name' , searchable: true},
                { data: 'region_name', name: 'regions.name', searchable: true },
                { data: 'outlet_name', name: 'outlets.code', searchable: true },
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
            $('#search_parts_indent').val('');
            $('#parts_indent_table').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#parts_indent_table').DataTable().ajax.reload();
        });

        var dataTables = $('#parts_indent_table').dataTable();
        $("#search_parts_indent").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //FOR FILTER
       /* $http.get(
            laravel_routes['getPartsIndentFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
            self.repair_order_type = response.data.repair_order_type;
            self.skill_level = response.data.skill_level;
            self.tax_code = response.data.tax_code;
            self.tax_code_selected = '';
            self.skill_level_selected = '';
            self.repair_order_type_selected = '';
        });*/
        
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
            dataTables.fnFilter();
            $('#indent_parts_filter').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#vendor").val('');
            $("#outlet").val('');
            $("#date").val('');
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('partsIndentView', {
    templateUrl: parts_indent_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('view-parts-indent')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getPartsIndentData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.job_cards = response.data.job_cards;
            self.vehicle_info = response.data.vehicle_info;
            self.customer_details = response.data.customer_details;
            self.gate_log = response.data.gate_log;
            self.labour_details = response.data.labour_details;
            self.parts_details = response.data.parts_details;
            self.part_list = response.data.part_list;
            self.mechanic_list = response.data.mechanic_list;
            self.issued_mode = response.data.issued_mode;
            self.issued_parts_details = response.data.issued_parts_details;
            self.gate_pass_details = response.data.gate_pass_details;
            self.customer_voice_details = response.data.customer_voice_details;
            $rootScope.loading = false;
        });

        $scope.onSelectedpartcode = function(part_code_selected) {
            $('#part_code').val(part_code_selected);
            if (part_code_selected) {
                return new Promise(function(resolve, reject) {
                    $http.post(
                            laravel_routes['getPartDetails'], {
                                key: part_code_selected,
                            }
                        )
                        .then(function(response) {
                            self.parts_details = response.data.parts_details;
                            $("#job_order_part_id").val(self.parts_details.id);
                            $("#req_qty").text(self.parts_details.qty+" "+"nos");
                            $("#issue_qty").text(self.parts_details.issued_qty+" "+"nos");
                            issued_qty = self.parts_details.issued_qty;
                            if(issued_qty == null)
                            {
                             issued_qty = 0;
                             $("#issue_qty").text(issued_qty+" "+"nos");
                            }
                            balance_qty = parseInt(self.parts_details.qty)-parseInt(issued_qty);
                            $("#balance_qty").text(balance_qty+" "+"nos");
                            $("#bal_qty").val(balance_qty);
                        });
                });
            } else {
                return [];
            }
        }

        $scope.onSelectedmech = function(machanic_id_selected) {
            $('#machanic_id').val(machanic_id_selected);
        }
        $scope.onSelectedmode = function(issue_modeselected) {
            $('#issued_mode').val(issue_modeselected);
        }

        //Buttons to navigate between tabs
        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });

        self.removeIssedParts = function($id) {
           $('#delete_issued_part_id').val($id);
        }

        $scope.deleteConfirm = function() {
            $id = $('#delete_issued_part_id').val();
            $http.get(
                laravel_routes['deleteIssedPart'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Issed Part  Deleted Successfully');
                    $('#pause_work_reason_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/parts-indent/view/' + $routeParams.id);
                }
            });
        }

        //Save Form Data 
        var form_id = '#part_add';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'part_code':{
                    required:true,
                },
                'issued_qty': {
                    required: true,
                },
                'issued_to_id': {
                    required: true,
                },
                'issued_mode': {
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
                        url: laravel_routes['savePartsindent'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            $('.submit').button('reset');
                            $('#issued_qty').val(" ");
                            custom_noty('success', res.message);
                           $location.path('/gigo-pkg/parts-indent/view/' + $routeParams.id);
                            $scope.$apply();
                            
                            $http.get(
                            laravel_routes['getIssedParts'], {
                                params: {
                                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                                }
                            }).then(function(response) {
                            self.issued_parts_details = response.data.issued_parts_details;
                             });

                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                $('#part_code').val(" ");
                                $('#issued_qty').val(" ");
                                $('#machanic_id').val(" ");
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $('.submit').button('reset');
                               $location.path('/gigo-pkg/parts-indent/view/' + $routeParams.id);
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
