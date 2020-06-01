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


