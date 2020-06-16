app.directive('warrantyJobOrderRequestFormTabs', function() {
    return {
        templateUrl: warrantyJobOrderRequestFormTabs,
        controller: function() {}
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('warrantyJobOrderRequestCardList', {
    templateUrl: warrantyJobOrderRequestCardList,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $scope.user = HelperService.getLoggedUser();

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/job-order/index',
                    method: "GET",
                    data: {
                        page: 1, // show first page
                        count: 100, // count per page
                        sorting: {
                            created_at: 'asc' // initial sorting
                        },
                        filter: {
                            search: '',
                            typeIn: [2, 5], //warranty & free service orders
                        },
                        search_key: self.search_key,
                        date: self.date,
                        reg_no: self.reg_no,
                        job_card_no: self.job_card_no,
                        gate_in_no: self.gate_in_no,
                        customer_id: self.customer_id,
                        model_id: self.model_id,
                        service_type_id: self.service_type_id,
                        quote_type_id: self.quote_type_id,
                        job_order_type_id: self.job_order_type_id,
                        status_id: self.status_id,
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
                    $scope.job_orders = res.job_order_collection;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();
        $rootScope.loading = false;
    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------

app.component('warrantyJobOrderRequestPprForm', {
    templateUrl: warrantyJobOrderRequestPPRForm,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $scope.user = HelperService.getLoggedUser();

        $scope.init = function() {
            $.ajax({
                    url: base_url + '/api/service-type/options',
                    method: "GET",
                    data: {},
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.service_type_options = res.options;
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        };
        $scope.init();


        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/job-order/read/' + $routeParams.job_order_id,
                    method: "GET",
                    data: {},
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                    },
                })
                .done(function(res) {
                    if (!res.success) {
                        showErrorNoty(res);
                        return;
                    }
                    $scope.job_order = res.job_order;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.searchCompaints = function(query) {
            var results = query ? $scope.extras.complaints.filter(createFilterFor(query)) : $scope.extras.complaints,
                deferred;
            return results;
        }


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