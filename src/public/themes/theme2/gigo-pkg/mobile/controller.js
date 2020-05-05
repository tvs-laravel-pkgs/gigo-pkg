app.component('mobileLogin', {
    templateUrl: mobile_login_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        $scope.loading = true;
        var self = this;

        if (HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/dashboard');
            return;
        }

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

                        localStorage.setItem("user", JSON.stringify(res.user));
                        $location.path('/gigo-pkg/mobile/dashboard');
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        showServerErrorNoty();
                    });
            }
        });

        // $scope.deleteConfirm = function() {
        //     $id = $('#job_card_id').val();
        //     $http.get(
        //         laravel_routes['deleteJobCard'], {
        //             params: {
        //                 id: $id,
        //             }
        //         }
        //     ).then(function(response) {
        //         if (response.data.success) {
        //             custom_noty('success', 'Job Card Deleted Successfully');
        //             $('#job_cards_list').DataTable().ajax.reload(function(json) {});
        //             $location.path('/gigo-pkg/job-card/list');
        //         }
        //     });
        // }

        $rootScope.loading = false;
    }
});

app.component('mobileDashboard', {
    templateUrl: mobile_dashboard_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $rootScope.loading = false;
    }
});

app.component('mobileMenus', {
    templateUrl: mobile_menus_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $scope.hasPerm = HelperService.hasPerm;
        $scope.user = JSON.parse(localStorage.getItem('user'));

        $scope.logout = function() {
            localStorage.removeItem('user');
            $location.path('/gigo-pkg/mobile/login');
        }
        $rootScope.loading = false;
    }
});

app.component('mobileKanbanDashboard', {
    templateUrl: mobile_kanban_dashboard_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $scope.user = angular.fromJson($cookies.get('user'));
        $rootScope.loading = false;
    }
});

app.directive('mobileHeader', function() {
    return {
        templateUrl: mobile_header_template_url,
        controller: function() {}
    }
});