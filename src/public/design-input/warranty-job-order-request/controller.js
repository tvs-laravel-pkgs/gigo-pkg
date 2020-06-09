app.directive('warrantyJobOrderRequestFormTabs', function() {
    return {
        templateUrl: warrantyJobOrderRequestFormTabs,
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
        $scope.extras = {};
        $scope.extras.service_types = [{
                id: 1,
                name: 'Pre Delivery Inspection',
            },
            {
                id: 2,
                name: '2nd Free Service',
            },
            {
                id: 3,
                name: '3rd Free Service',
            },
        ];

        $scope.extras.complaints = [{
                id: 1,
                code: '01A1',
                name: 'CYL BLOCK CASTING DEFECT - OIL LEAK',
            },
            {
                id: 2,
                code: '01A2',
                name: 'CYL BLOCK CASTING DEFECT- COOLANT LEAK',
            },
            {
                id: 3,
                code: '01A3',
                name: 'CYL HEAD CASTING DEFECT- OIL LEAK',
            },
            {
                id: 4,
                code: '01B9',
                name: 'Block Machining defect',
            },
            {
                id: 5,
                code: '01C3',
                name: 'BLOCK CRACK',
            },
            {
                id: 6,
                code: '01C4',
                name: 'NEP-OVERHEAD CAMSHAFT TRIGGER WHEEL',
            },
            {
                id: 7,
                code: '01C5',
                name: 'NEP-OVERHEAD CAMSHAFT GEAR',
            },
        ];

        $scope.searchCompaints = function(query) {
            var results = query ? $scope.extras.complaints.filter(createFilterFor(query)) : $scope.extras.complaints,
                deferred;
            return results;
        }

        $scope.extras.faults = [{
                id: 1,
                code: 'AA',
                name: 'ADRIFT/LOOSE',
            },
            {
                id: 2,
                code: 'AB',
                name: 'AIR BUBBLE',
            },
            {
                id: 3,
                code: 'AC',
                name: 'AIR LEAK',
            },
            {
                id: 4,
                code: 'AD',
                name: 'AIR LOCK',
            },
            {
                id: 5,
                code: 'AE',
                name: 'BLOCK CRACK',
            },
            {
                id: 6,
                code: 'AF',
                name: 'BLOCKED/CLOGGED',
            },
            {
                id: 7,
                code: 'AG',
                name: 'BROKEN/BURST/CRACKED',
            },
        ];

        $scope.searchFaults = function(query) {
            var results = query ? $scope.extras.faults.filter(createFilterFor(query)) : $scope.extras.faults,
                deferred;
            return results;
        }

        function createFilterFor(query) {
            var lowercaseQuery = query.toLowerCase();
            return function filterFn(item) {
                return (item.code.indexOf(lowercaseQuery) === 0);
            };

        }
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