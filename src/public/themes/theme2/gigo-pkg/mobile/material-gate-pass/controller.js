app.component('mobileMaterialGatePassList', {
    templateUrl: mobile_material_gate_pass_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $scope.hasPerm = HelperService.hasPerm;
        $scope.user = JSON.parse(localStorage.getItem('user'));
        $rootScope.loading = false;
    }
});