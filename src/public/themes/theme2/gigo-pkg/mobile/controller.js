app.component('mobileLogin', {
    templateUrl: mobile_login_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $cookies) {
        $scope.loading = true;
        var self = this;

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {},
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/login',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            showErrorNoty(res);
                            return;
                        }

                        $cookies.putObject('user', res.user);
                        $location.path('/gigo-pkg/mobile/dashboard');
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        showServerErrorNoty();
                    });
            }
        });

        $scope.deleteConfirm = function() {
            $id = $('#job_card_id').val();
            $http.get(
                laravel_routes['deleteJobCard'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Job Card Deleted Successfully');
                    $('#job_cards_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/job-card/list');
                }
            });
        }

        $rootScope.loading = false;
    }
});

app.component('mobileDashboard', {
    templateUrl: mobile_dashboard_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        $rootScope.loading = false;
    }
});

app.component('mobileMenus', {
    templateUrl: mobile_menus_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        HelperService.isLoggedIn();
        $scope.user = angular.fromJson($cookies.get('user'));

        $scope.logout = function() {
            $cookies.remove('user');
            $location.path('/gigo-pkg/mobile/login');
            // $scope.$apply();
        }
        $rootScope.loading = false;
    }
});


app.component('mobileKanbanDashboard', {
    templateUrl: mobile_kanban_dashboard_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        $scope.user = angular.fromJson($cookies.get('user'));
        $rootScope.loading = false;
    }
});

app.component('mobileAttendanceScanQr', {
    templateUrl: mobile_attendance_scan_qr_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        $scope.user = angular.fromJson($cookies.get('user'));
        $rootScope.loading = false;


        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {},
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/punch',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                    })
                    .done(function(res) {
                        if (!res.success) {
                            showErrorNoty(res);
                            $('.submit').button('reset');
                            return;
                        }

                        $location.path('/gigo-pkg/mobile/dashboard');
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        showServerErrorNoty();
                    });
            }
        });
    }
});