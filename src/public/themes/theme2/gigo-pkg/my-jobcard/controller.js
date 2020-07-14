app.component('myJobcardCardList', {
    templateUrl: myjobcard_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        /*self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('my-jobcard')) {
            window.location = "#!/page-permission-denied";
            return false;
        }*/

        $scope.clear_search = function() {
            $('#search').val('');
        }

        //HelperService.isLoggedIn()
        self.user = $scope.user = HelperService.getLoggedUser();


        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });


        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/myjobcard/list',
                    method: "POST",
                    data: {
                        user_id: $routeParams.user_id,
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
                    $scope.my_job_card_list = res.my_job_card_list;
                    $scope.user_details = res.user_details;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/my-jobcard/table-list"+"/"+$routeParams.user_id;
                return false;
            } else {
                //alert();
                window.location = "#!/my-jobcard/card-list"+"/"+$routeParams.user_id;
                return false;
            }
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------

app.component('myJobcardTableList', {
    templateUrl: myjobcard_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#searchmyJobCard').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.job_cards').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
       /* if (!self.hasPermission('job-cards')) {
            window.location = "#!/page-permission-denied";
            return false;
        }*/

        self.user = $scope.user = HelperService.getLoggedUser();
        self.search_key = '';
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#myjob_table_list').DataTable({
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
                    $('#search_my_job_card').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getMyJobCardtableList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.date = $("#date_range").val();
                    d.reg_no = $("#reg_no").val();
                    d.job_card_no = $("#job_card_no").val();
                    d.status_id = $("#status_id").val();
                    d.user_id = $routeParams.user_id;
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'created_at',  name: 'job_cards.created_at'},
                { data: 'jc_number', name: 'job_cards.job_card_number' ,searchable: true},
                { data: 'registration_number', name: 'vehicles.registration_number' ,searchable: true },
                { data: 'customer_name', name: 'customers.name' ,searchable: true },
                { data: 'no_of_ROTs' ,searchable: false},  
                { data: 'status', name: 'configs.name' ,searchable: false },

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
            $('#search_my_job_card').val('');
            $('#myjob_table_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#myjob_table_list').DataTable().ajax.reload();
        });

        var dataTables = $('#myjob_table_list').dataTable();

        $("#search_my_job_card").keyup(function() {
            dataTables.fnFilter(this.value);
        });
       
        // FOR FILTER
        $http.get(
            laravel_routes['getJobCardFilter']
        ).then(function(response) {
            self.extras = response.data.extras;
        });

        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
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
        /* DateRange Picker */
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.align-left.daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            //dataTables.fnFilter();
        });

        $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/my-jobcard/table-list"+"/"+$routeParams.user_id;
                return false;
            } else {
                //alert();
                window.location = "#!/my-jobcard/card-list"+"/"+$routeParams.user_id;
                return false;
            }
        }

        $("#date").keyup(function() {
            self.date = this.value;
        });

        $scope.applyFilter = function() {
            $('#status').val(self.status);
            $('#myjob-card-filter-modal').modal('hide');
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            $("#date_range").val('');
            $("#reg_no").val('');
            $("#job_card_no").val('');
            $("#status_id").val('');
            dataTables.fnFilter();
            $('#myjob-card-filter-modal').modal('hide');
            //$scope.fetchData();
        }
        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('myJobcardTimesheetList', {
    templateUrl: myjobcard_timesheet_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#searchmyJobCard').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.job_cards').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
       /* if (!self.hasPermission('job-cards')) {
            window.location = "#!/page-permission-denied";
            return false;
        }*/

        self.user = $scope.user = HelperService.getLoggedUser();
        self.search_key = '';
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#myjob_timesheet_list').DataTable({
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
                    $('#search_my_job_card').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getMyJobCardtimeSheetList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.date = $("#date_range").val();
                    d.job_card_no = $("#job_card_no").val();
                    d.user_id = $routeParams.user_id;
                },
            },

            columns: [
                { data: 'created_at'},
                { data: 'jc_number', name: 'job_cards.job_card_number' ,searchable: true},
                { data: 'outlet', name: 'outlets.code' ,searchable: true },
                { data: 'start_time'},
                { data: 'end_time'},
                { data: 'duration' },
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
            $('#search_my_job_card').val('');
            $('#myjob_timesheet_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#myjob_timesheet_list').DataTable().ajax.reload();
        });

        var dataTables = $('#myjob_timesheet_list').dataTable();

        $("#search_my_job_card").keyup(function() {
            dataTables.fnFilter(this.value);
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

        $('.align-left.daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            //dataTables.fnFilter();
        });

        $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        $("#date").keyup(function() {
            self.date = this.value;
        });

          // FOR FILTER
        $http.get(
            laravel_routes['getMyJobCarduserDetails']
        ).then(function(response) {
            $scope.user_details = response.data.user_details;
        });


        $scope.applyFilter = function() {
            $('#myjob-card-filter-modal').modal('hide');
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            $("#date_range").val('');
            $("#job_card_no").val('');
            dataTables.fnFilter();
            $('#myjob-card-filter-modal').modal('hide');
            //$scope.fetchData();
        }
        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('myJobcardView', {
    templateUrl: myjobcard_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        /*self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('my-jobcard')) {
            window.location = "#!/page-permission-denied";
            return false;
        }*/

        $scope.clear_search = function() {
            $('#search').val('');
        }

        //HelperService.isLoggedIn()
        $scope.user = HelperService.getLoggedUser();
        self.user_id = $routeParams.user_id;
        $scope.job_card_id = $routeParams.job_card_id;

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/my-job-card-view',
                    method: "POST",
                    data: {
                        job_card_id: $routeParams.job_card_id,
                        mechanic_id: self.user_id,
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
                    $scope.job_card = res.job_card;
                    $scope.user_details = res.user_details;
                    $scope.my_job_orders = res.my_job_orders;
                    $scope.pass_work_reasons = res.pass_work_reasons;
                    $scope.other_work_status = res.other_work_status;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.fetchData();

        $scope.StartWork = function($id, $key) {
            job_repair_order_id = $("#repair_repair_order_id" + $key).val();
            status_id = $id;
            $.ajax({
                    url: base_url + '/api/save-my-job-card',
                    method: "POST",
                    data: {
                        job_repair_order_id: job_repair_order_id,
                        machanic_id: self.user_id,
                        status_id: status_id,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                }).done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }

                    custom_noty('success', 'Work has been started');
                    setTimeout(function() {
                        // location.reload();
                        $scope.fetchData();
                    }, 1000);

                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.FinishWork = function($id, $key) {
            job_repair_order_id = $("#repair_repair_order_id" + $key).val();
            $scope.job_repair_order_id = job_repair_order_id;
            status_id = $id;
            $.ajax({
                    url: base_url + '/api/save-work-log',
                    method: "POST",
                    data: {
                        job_card_id: $routeParams.job_card_id,
                        job_repair_order_id: job_repair_order_id,
                        machanic_id: self.user_id,
                        status_id: status_id,
                        type: 1,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                }).done(function(res) {
                    $scope.work_log = res.work_logs;
                    console.log(res);
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.confirmFinish = function() {
            $('.confirm_finish').button('loading');
            $.ajax({
                    url: base_url + '/api/save-work-log',
                    method: "POST",
                    data: {
                        job_card_id: $routeParams.job_card_id,
                        job_repair_order_id: $scope.job_repair_order_id,
                        machanic_id: self.user_id,
                        status_id: 8263,
                        type: 2,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                }).done(function(res) {
                    $("#finish_work").hide();
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $('.confirm_finish').button('reset');

                    custom_noty('success', res.message);
                    setTimeout(function() {
                        // location.reload(); 
                        $scope.fetchData();
                    }, 1000);
                })
                .fail(function(xhr) {
                    $('.confirm_finish').button('reset');
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.PauseWork = function($key) {
            job_repair_order_id = $("#repair_repair_order_id" + $key).val();
            pause_wrk_repair_id = $("#pause_wrk_repair_id").val(job_repair_order_id);
        }

        $scope.OnselectWorkReason = function(index, reason_id) {
            $('.reasons').removeClass('active');
            $('#reason_id' + index).addClass('active');
            $('#selected_reason_id').val(reason_id);
        }
        $scope.reasonConfirm = function() {
            reason_id = $('#selected_reason_id').val();
            if (reason_id == '') {
                custom_noty('error', 'Select Reason to Pause Work');
                return;
            }
            $('.break_confirm').button('loading');
            pause_wrk_repair_id = $("#pause_wrk_repair_id").val();
            $.ajax({
                    url: base_url + '/api/save-my-job-card',
                    method: "POST",
                    data: {
                        job_repair_order_id: pause_wrk_repair_id,
                        machanic_id: self.user_id,
                        status_id: 8262,
                        reason_id: reason_id,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                }).done(function(res) {
                    $("#pause_work_modal").hide();
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();

                    $('.break_confirm').button('reset');
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }

                    custom_noty('success', 'Work has Paused');
                    setTimeout(function() {
                        // location.reload();
                        $scope.fetchData();
                    }, 2000);
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
        $rootScope.loading = false;
    }
});