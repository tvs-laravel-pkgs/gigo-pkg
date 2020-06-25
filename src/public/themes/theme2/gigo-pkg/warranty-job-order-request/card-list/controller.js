app.component('warrantyJobOrderRequestCardList', {
    templateUrl: warrantyJobOrderRequestCardList,
    controller: function($http, $location, $ngBootbox, HelperService, WarrantyJobOrderRequestSvc, $scope, JobOrderSvc, $routeParams, $rootScope, $element, $mdSelect) {
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

        var params = {
            page: 1, // show first page
            count: 100, // count per page
            sorting: {
                created_at: 'asc' // initial sorting
            },
            filter: {
                // search: '',
                statusIn: [9100, 9101, 9103], //new, waiting for approval and rejected
            },
        };

        // typeIn: [2, 5], //warranty & free service orders

        //FETCH DATA
        $scope.fetchData = function() {
            WarrantyJobOrderRequestSvc.index(params)
                .then(function(response) {
                    $scope.warranty_job_order_requests = response.data.warranty_job_order_request_collection;
                    $rootScope.loading = false;
                });
        }
        $scope.fetchData();

        // $scope.sendToApproval = function(warranty_job_order_request) {

        //     console.log(warranty_job_order_request);
        //     WarrantyJobOrderRequestSvc.confirmSendToApproval(warranty_job_order_request)
        //         .then(function(status) {

        //             console.log(status);
        //             warranty_job_order_request.status = status;
        //         });
        // };

        $scope.sendToApproval = function(warranty_job_order_request) {
            $ngBootbox.confirm({
                    message: 'Are you sure you want to send to approval?',
                    title: 'Confirm',
                    size: "small",
                    className: 'text-center',
                })
                .then(function() {
                    $rootScope.loading = true;
                    WarrantyJobOrderRequestSvc.sendToApproval(warranty_job_order_request)
                        .then(function(response) {
                            $rootScope.loading = false;
                            if (!response.data.success) {
                                showErrorNoty(response.data);
                                return;
                            }
                            showNoty('success', 'Warranty job order request initiated successfully');
                            warranty_job_order_request.status = response.data.warranty_job_order_request.status;
                        });
                });
        }


        $scope.confirmDelete = function(warranty_job_order_request, key) {
            $ngBootbox.confirm({
                    message: 'Are you sure you want to delete this?',
                    title: 'Confirm',
                    size: "small",
                    className: 'text-center',
                })
                .then(function() {
                    WarrantyJobOrderRequestSvc.remove(warranty_job_order_request)
                        .then(function(response) {
                            if (!response.data.success) {
                                showErrorNoty(response.data);
                                return;
                            }
                            showNoty('success', 'Warranty job order request deleted successfully');
                            // $location.path('/warranty-job-order-request/card-list');
                            $scope.warranty_job_order_requests.splice(key, 1);
                        });
                });
        }

    }
});