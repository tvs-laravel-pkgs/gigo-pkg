app.component('warrantyJobOrderRequestTableList', {
    templateUrl: warrantyJobOrderRequestTableList,
    controller: function($http, $location, $ngBootbox, HelperService, WarrantyJobOrderRequestSvc, $scope, JobOrderSvc, $routeParams, $rootScope, $element, $mdSelect, ConfigSvc) {
        $rootScope.loading = true;
        $('#search').focus();
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        if (!HelperService.isLoggedIn()) {
            $location.path('/login');
            return;
        }

        $scope.user = HelperService.getLoggedUser();
        $scope.hasPerm = HelperService.hasPerm;

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        var defaultParams = function() {
            var defaultParams = {
                filters: {
                    requestDate: $("#request_date").val(),
                    registrationNumber: $("#reg_no").val(),
                    // customer_id : $("#customer_id").val();
                    // model_id : $("#model_id").val();
                    jobCardNo: $("#job_card_no").val(),
                    statusId: $("#status_id").val(),
                }
            };
            return defaultParams;
        };

        $scope.options = [];
        ConfigSvc.options({ filter: { configType: 305 } })
            .then(function(response) {
                $scope.options.status_options = response.data.options;
            });

        var initialParams = function() {
            // if (angular.isDefined($localStorage.wjorIndexParams)) {
            //     return angular.extend({}, defaultParams(), $localStorage.wjorIndexParams);
            // } else {
            return angular.extend({}, defaultParams());
            // }
        };

        var params = initialParams();
        $scope.filters = angular.copy(params.filters);


        // $scope.$watch('filters', function(value) {
        //     init();
        //     $scope.getActiveFilters();
        // }, true);

        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#warranty_job_order_request_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    self.search_key = state_save_val.search.search;
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: base_url + '/api/warranty-job-order-request/list',
                type: "GET",
                dataType: "json",
                data: function(d) {
                    // d = $scope.filters;
                    d.request_date = $("#request_date").val();
                    d.reg_no = $("#reg_no").val();
                    // d.customer_id = $("#customer_id").val();
                    // d.model_id = $("#model_id").val();
                    d.job_card_no = $("#job_card_no").val();
                    d.statusIds = $("#status_id").val();
                    // console.log($scope.filters);
                },
            },
            // data : response,
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'request_date', searchable: false },
                { data: 'number', name: 'warranty_job_order_requests.number', searchable: true },
                { data: 'job_card_number', name: 'job_orders.number' },
                { data: 'outlet_name', name: 'outlets.code' },
                { data: 'status', name: 'configs.name' },
                { data: 'requested_by', name: 'users.name' },
                { data: 'customer_name', name: 'customers.name' },
                { data: 'model_number', name: 'models.model_number' },
                { data: 'registration_number', name: 'vehicles.registration_number' },
                { data: 'chassis_number', name: 'vehicles.chassis_number' },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        var dataTables = $('#warranty_job_order_request_list').dataTable();

        $scope.searchWarrantyJobOrderRequest = function() {
            dataTables.fnFilter(self.search_key);
        }
        $(".refresh_table").click(function() {
            dataTables.fnFilter();
        });
        $scope.applyFilter = function() {
            dataTables.fnFilter();
            $('#warranty-job-order-request-filter-modal').modal('hide');
        }
        $scope.clear_search = function() {
            self.search_key = '';
            $('#warranty_job_order_request_list').DataTable().search('').draw();
        }


        $scope.sendToApproval = function(id) {
            $scope.warranty_job_order_request = {};
            $scope.warranty_job_order_request.id = id;
            $ngBootbox.confirm({
                    message: 'Are you sure you want to send to approval?',
                    title: 'Confirm',
                    size: "small",
                    className: 'text-center',
                })
                .then(function() {
                    $rootScope.loading = true;
                    WarrantyJobOrderRequestSvc.sendToApproval($scope.warranty_job_order_request)
                        .then(function(response) {
                            $rootScope.loading = false;
                            if (!response.data.success) {
                                showErrorNoty(response.data);
                                return;
                            }
                            showNoty('success', 'Warranty job order request initiated successfully');
                            // warranty_job_order_request.status = response.data.warranty_job_order_request.status;
                            dataTables.fnFilter();
                        }).catch(function(error) {
                            console.log(error);
                            // showErrorNoty(error.data.error);
                        });
                });
        }


        $scope.confirmDelete = function(id) {
            $scope.warranty_job_order_request = {};
            $scope.warranty_job_order_request.id = id;
            $ngBootbox.confirm({
                    message: 'Are you sure you want to delete this?',
                    title: 'Confirm',
                    size: "small",
                    className: 'text-center',
                })
                .then(function() {
                    // alert();
                    WarrantyJobOrderRequestSvc.remove($scope.warranty_job_order_request)
                        .then(function(response) {
                            if (!response.data.success) {
                                showErrorNoty(response.data);
                                return;
                            }
                            showNoty('success', 'Warranty job order request deleted successfully');
                            dataTables.fnFilter();
                            // $location.path('/warranty-job-order-request/card-list');
                            // $scope.warranty_job_order_requests.splice(key, 1);
                        });
                });
        }
        self.searchCustomer = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getCustomerSearchList'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                    //reject(response);
                });
            } else {
                return [];
            }
        }
        self.searchVehicleModel = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getVehicleModelSearchList'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }
        // $scope.selectedVehicleModel = function(id) {
        //     $('#model_id').val(id);
        // }
        // $scope.selectedCustomer = function(id) {
        //     $('#customer_id').val(id);
        // }
    }
});

//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------