app.component('mobileGateInVehicle', {
    templateUrl: mobile_gate_in_vehicle_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $scope.hasPerm = HelperService.hasPerm;
        $scope.user = JSON.parse(localStorage.getItem('user'));

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {},
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $('#gate_in_sucess').modal('show');
                return;
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
        $rootScope.loading = false;
    }
});