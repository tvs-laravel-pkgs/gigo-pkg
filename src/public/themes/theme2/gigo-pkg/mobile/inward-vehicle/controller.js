app.component('mobileInwardVehicleList', {
    templateUrl: mobile_inward_vehicle_list_template_url,
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
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
app.component('mobileInwardVehicleDetail', {
    templateUrl: mobile_inward_vehicle_detail_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $scope.hasPerm = HelperService.hasPerm;
        $scope.user = JSON.parse(localStorage.getItem('user'));

        $scope.saveInwardVehicleDetail = function() {
            $location.path('/gigo-pkg/mobile/inward-vehicle/customer-detail');
            $scope.$apply();
        }
        $rootScope.loading = false;
    }
});
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
app.component('mobileInwardVehicleForm', {
    templateUrl: mobile_inward_vehicle_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $scope.hasPerm = HelperService.hasPerm;
        $scope.user = JSON.parse(localStorage.getItem('user'));

        $scope.saveInwardVehicleForm = function() {
            $location.path('/gigo-pkg/mobile/inward-vehicle/customer-form');
            $scope.$apply();
        }
        $rootScope.loading = false;
    }
});
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
app.component('mobileInwardCustomerDetail', {
    templateUrl: mobile_inward_customer_detail_template_url,
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
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
app.component('mobileInwardCustomerForm', {
    templateUrl: mobile_inward_customer_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $scope.hasPerm = HelperService.hasPerm;
        $scope.user = JSON.parse(localStorage.getItem('user'));

        $scope.saveInwardCustomerForm = function() {
            $location.path('/gigo-pkg/mobile/inward-vehicle/order-detail-form');
            $scope.$apply();
        }
        $rootScope.loading = false;
    }
});
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
app.component('mobileInwardOrderDetailForm', {
    templateUrl: mobile_inward_order_detail_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $cookies) {
        $scope.loading = true;
        var self = this;
        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }
        $scope.hasPerm = HelperService.hasPerm;
        $scope.user = JSON.parse(localStorage.getItem('user'));

        $scope.saveInwardVehicleDetai = function() {
            $location.path('/gigo-pkg/mobile/inward-vehicle/customer-detail');
            $scope.$apply();
        }
        $rootScope.loading = false;
    }
});
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------

app.directive('mobileInwardHeader', function() {
    return {
        templateUrl: mobile_inward_header_template_url,
        controller: function() {}
    }
});