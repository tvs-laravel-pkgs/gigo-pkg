app.directive('inwardViewTabs', function() {
    return {
        templateUrl: inward_view_tabs_template_url,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/gigo/warranty-job-order-request/list', {
        template: '<warranty-job-order-request-list></warranty-job-order-request-list>',
        title: 'Warranty Job Order Requests'
    });
}]);
app.component('warrantyJobOrderRequestList', {
    templateUrl: designInputWarrantyJobOrderRequest,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        // $scope.gate_logs = res.gate_logs;
        $rootScope.loading = false;
    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------

app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/gigo/warranty-job-order-request/ppr-form', {
        template: '<warranty-job-order-request-ppr-form></warranty-job-order-request-ppr-form>',
        title: 'Warranty Job Order Request - PPR Form'
    });
}]);
app.component('warrantyJobOrderRequestPprForm', {
    templateUrl: warrantyJobOrderRequestPPRForm,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        // $scope.gate_logs = res.gate_logs;
        $rootScope.loading = false;
    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/warranty-job-order-request/estimate-form', {
        template: '<warranty-job-order-request-estimate-form></warranty-job-order-request-estimate-form>',
        title: 'Warranty Job Order Request - Estimate Form'
    });
}]);
app.component('warrantyJobOrderRequestEstimateForm', {
    templateUrl: warrantyJobOrderRequestEstimateForm,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        // $scope.gate_logs = res.gate_logs;
        $rootScope.loading = false;
    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------