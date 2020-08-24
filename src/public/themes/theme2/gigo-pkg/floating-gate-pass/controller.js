app.component('floatingGatePassCardList', {
    templateUrl: floating_gate_pass_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route) {
        $rootScope.loading = true;
        $('#search_material_gate_pass').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('floating-gate-passes')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.user = $scope.user = HelperService.getLoggedUser();
        self.gate_pass_created_date = '';
        self.number = '';
        self.job_card_number = '';
        self.status_id = '';
        if (!localStorage.getItem('search_key')) {
            self.search_key = '';
        } else {
            self.search_key = localStorage.getItem('search_key');
        }

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/floating-gate-pass/get',
                    method: "POST",
                    data: {
                        search_key: self.search_key,
                        gate_pass_created_date: self.gate_pass_created_date,
                        number: self.number,
                        job_card_number: self.job_card_number,
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
                    $scope.floating_gate_passes = res.floating_gate_passes;
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
        $scope.searchKey = function() {
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $("#gate_pass_created_date").keyup(function() {
            self.gate_pass_created_date = this.value;
        });
        $("#number").keyup(function() {
            self.number = this.value;
        });
        $("#job_order_number").keyup(function() {
            self.job_order_number = this.value;
        });

        // FOR FILTER
        $http.get(
            laravel_routes['getFloatingGatePassFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
        });

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/floating-gate-pass/table-list";
                return false;
            } else {
                window.location = "#!/floating-gate-pass/card-list";
                return false;
            }
        }

        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            self.status_id = id;
        }

        $scope.applyFilter = function() {
            $scope.fetchData();
            $('#road-test-gate-pass-card-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#gate_pass_created_date").val('');
            $("#number").val('');
            $("#job_order_number").val('');
            $("#status_id").val('');
            self.gate_pass_created_date = '';
            self.number = '';
            self.job_order_number = '';
            self.status_id = '';
            setTimeout(function() {
                $scope.fetchData();
            }, 1000);
            $('#road-test-gate-pass-card-filter-modal').modal('hide');
        }
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('floatingGatePassTableList', {
    templateUrl: floating_gate_pass_table_list_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route) {
        $rootScope.loading = true;
        var self = this;
        HelperService.isLoggedIn()

        $('#search_material_gate_pass').focus();
        $('li').removeClass('active');
        $('.material_gate_passes').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('floating-gate-passes')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.user = $scope.user = HelperService.getLoggedUser();
        self.search_key = '';
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        // $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#floating_gate_pass_list').dataTable({
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
                url: laravel_routes['getFloatingGatePassList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.gate_pass_created_date = $("#gate_pass_created_date").val();
                    d.number = $("#number").val();
                    d.job_card_number = $("#job_card_number").val();
                    d.status_id = $("#status_id").val();
                },
            },
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'date_and_time', searchable: false },
                { data: 'floating_gate_pass_no', name: 'floating_stock_logs.number' },
                { data: 'job_card_number', name: 'job_cards.job_card_number' },
                { data: 'registration_number', name: 'vehicles.registration_number' },
                { data: 'outward_date', searchable: false  },
                { data: 'inward_date', searchable: false  },
                { data: 'status', searchable: false  },

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
            $('#floating_gate_pass_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#floating_gate_pass_list').DataTable().ajax.reload();
        });

        var dataTables = $('#floating_gate_pass_list').dataTable();
        $scope.searchKey = function() {
            dataTables.fnFilter(self.search_key);
        }

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/floating-gate-pass/table-list";
                return false;
            } else {
                window.location = "#!/floating-gate-pass/card-list";
                return false;
            }
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getFloatingGatePassFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
        });

        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            self.status_id = id;
        }

        $scope.applyFilter = function() {
            dataTables.fnFilter();
            $('#road-test-gate-pass-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#gate_pass_created_date").val('');
            $("#number").val('');
            $("#job_card_number").val('');
            $("#status_id").val('');
            dataTables.fnFilter();
            $('#material-gate-pass-filter-modal').modal('hide');
        }
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('floatingGatePassView', {
    templateUrl: floating_gate_pass_view_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams) {
        $rootScope.loading = true;
        var self = this;
        HelperService.isLoggedIn()
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('view-floating-gate-pass')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.user = $scope.user = HelperService.getLoggedUser();

        //FETCH DATA
        $scope.fetchData = function() {
            //VIEW GATE PASS
            $.ajax({
                url: base_url + '/api/floating-gate-pass/view/get-data',
                type: "POST",
                data: {
                    'gate_pass_id': $routeParams.id,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    if (!response.success) {
                        showErrorNoty(response);
                        return;
                    }
                    self.road_test_gate_pass = response.road_test_gate_pass;
                    if (self.road_test_gate_pass.status_id == 8300) { //Gate Out Pending
                        self.type = 'Out';
                    } else {
                        self.type = 'In';
                    }
                    $scope.$apply();
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }
        $scope.fetchData();

        //Save Form Data 
        $scope.saveGatePass = function(id) {
            var form_id = '#road_test_gate_pass';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {
                    'remarks': {
                        minlength: 3,
                        maxlength: 191,
                    },
                },
                messages: {
                    'gate_out_remarks': {
                        minlength: 'Minimum 3 Characters',
                        maxlength: 'Maximum 191 Characters',
                    }
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/floating-gate-pass/gate-in-out/save',
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
                                $('.submit').button('reset');
                                return;
                            }
                            if (res.type == 'Out') {
                                $('#gate_out_remarks').val('');
                                $('#gate_out_confirm_notification').modal('show');
                            } else {
                                $('#gate_in_confirm_notification').modal('show');
                                $('.submit').button('reset');
                            }
                            $('.submit').button('reset');
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            showServerErrorNoty();
                        });
                }
            });
        }


        $scope.reloadPage = function(id) {
            $('#gate_in_confirm_notification').modal('hide');
            $('#gate_out_confirm_notification').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            if (id == 1) {
                window.location = "#!/floating-gate-pass/table-list";
            } else {
                $scope.fetchData();
            }
        }
    }
});