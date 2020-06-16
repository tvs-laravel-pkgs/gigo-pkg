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
                    url: base_url + '/api/get-my-job-card-list',
                    method: "POST",
                    data: {
                        employee_id: self.user.id,
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
        self.user = $scope.user = HelperService.getLoggedUser();
        $scope.job_card_id = $routeParams.job_card_id;

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        
        //console.log($routeParams.job_card_id);
        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/my-job-card-view',
                    method: "POST",
                    data: {
                        job_card_id : $routeParams.job_card_id,
                        mechanic_id: self.user.id,
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
                    $scope.my_job_card_details = res.my_job_card_details;
                    $scope.user_details = res.user_details;
                    $scope.job_order_repair_orders = res.job_order_repair_orders;
                    $scope.pass_work_reasons = res.pass_work_reasons;
                    $scope.getwork_status = res.getwork_status;
                    $scope.total_labour = res.total_labour;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.StartWork = function($id,$key)
        {
         job_repair_order_id = $("#repair_repair_order_id"+$key).val();
         status_id = $id;
          $.ajax({
                    url: base_url + '/api/save-my-job-card',
                    method: "POST",
                    data: {
                        job_repair_order_id : job_repair_order_id,
                        machanic_id: self.user.id,
                        status_id : status_id,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                }).done(function(res) {
                    if(status_id == 8261)
                    {
                       custom_noty('success', 'Work has been started');
                       setTimeout(function(){ location.reload(); }, 1000);
                       
                    }
                    if(status_id == 8263)
                    {
                        $("#start_date_time").text(res.work_start_date_time.start_date_time);
                        $("#end_date_time").text(res.work_end_date_time.end_date_time);
                        $("#estimation_work_hours").text(res.estimation_work_hours[0].hours);
                        $("#actual_hours").text(res.total_working_hours);

                        $scope.confirmFinish = function()
                        {
                        custom_noty('success', 'Work has been Completed');

                        setTimeout(function(){ location.reload(); }, 1000);
                        }

                        //custom_noty('success', 'Work has been Completed');

                        //setTimeout(function(){ location.reload(); }, 1000);
                    }
                    if(status_id == 8264)
                    {
                        custom_noty('success', 'Work has been started');
                        setTimeout(function(){ location.reload(); }, 1000);
                    }
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                  
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                }); 
        }

        $scope.PauseWork = function($key)
        {
        job_repair_order_id = $("#repair_repair_order_id"+$key).val();
        pause_wrk_repair_id = $("#pause_wrk_repair_id").val(job_repair_order_id);
        }

        $scope.getReason = function($reason_id)
        {
            reason_id = $("#reason_id"+$reason_id).val();
            reason_id = $("#reason").val(reason_id);
            $("#ac"+$reason_id).addClass('active');

        }

        $scope.reasonConfirm = function() {
            reason_id = $('#reason').val();
            if(reason_id == '')
            {
                custom_noty('error', 'Select Reason to Pause Work');
                return;
            }
            pause_wrk_repair_id = $("#pause_wrk_repair_id").val();
          $.ajax({
                    url: base_url + '/api/save-my-job-card',
                    method: "POST",
                    data: {
                        job_repair_order_id : pause_wrk_repair_id,
                        machanic_id: self.user.id,
                        status_id : 8262,
                        reason_id : reason_id,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                }).done(function(res) {
                    custom_noty('success', 'Work has Paused');
                    setTimeout(function(){ location.reload(); }, 1000);
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });

                $("#pause_work").hide();
           
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


