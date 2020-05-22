app.component('kanbanApp', {
    templateUrl: kanban_app_dashboard_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams) {
        $scope.loading = true;
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        //self.user = $scope.user = HelperService.getLoggedUser();
        $scope.user = JSON.parse(localStorage.getItem('user'));
        // console.log(self.user);
        console.log($scope.user);
        $rootScope.loading = false;
        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }
    }
});

app.component('kanbanAppAttendanceScanQr', {
    templateUrl: kanban_app_attendance_sacn_qr_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams) {
        $scope.loading = true;
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        //self.user = $scope.user = HelperService.getLoggedUser();
        $scope.user = JSON.parse(localStorage.getItem('user'));
        $scope.date = new Date();
        // console.log(self.user);
        console.log($scope.user);
        $rootScope.loading = false;
        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }
    }
});