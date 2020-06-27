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

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('my-jobcard')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

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

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('my-jobcard')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

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
                        location.reload();
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
                    custom_noty('success', res.message);
                    setTimeout(function() { location.reload(); }, 1000);
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
                    $('.break_confirm').button('reset');
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }

                    custom_noty('success', 'Work has Paused');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
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