app.component('gigoDashboard', {
    templateUrl: gigo_dashboard_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {

        $rootScope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('inward-vehicle')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }

        self.user = $scope.user = HelperService.getLoggedUser();
        self.customer_id = '';
        self.model_id = '';
        self.registration_type = '';
        self.status_id = '';
        if (!localStorage.getItem('search_key')) {
            self.search_key = '';
        } else {
            self.search_key = localStorage.getItem('search_key');
        }

        //FETCH DATA
        $scope.fetchDashboardData = function() {
            $.ajax({
                    url: base_url + '/api/gigo/dashboard',
                    method: "POST",
                    data: {
                        state_id: self.state_id,
                        outlet_id: self.outlet_id,
                        date_range: $('.date_range').val(),
                        user_id: self.user.id,
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
                    $scope.dashboard_data = res.dashboard_data;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }

        $scope.fetchDashboardData();

        $scope.onSelectedState = function() {
            setTimeout(function() {
                var state_id = $('.state_id').val();
                self.outlet_list = [];
                self.outlet_id = '';
                console.log(state_id);
                if (state_id) {
                    $.ajax({
                            url: base_url + '/api/state-based/outlet/' + state_id,
                            method: "GET",
                        })
                        .done(function(res) {

                            self.outlet_list = res.outlet_list;
                            console.log(self.outlet_list);
                            $scope.$apply()
                        });
                }
            }, 200);
        }

        $('.refresh_table').on("click", function() {
            $scope.fetchData();
        });
        $scope.clear_search = function() {
            self.search_key = '';
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $scope.searchInwardVehicle = function() {
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $("#gate_in_date").keyup(function() {
            self.gate_in_date = this.value;
        });
        $("#reg_no").keyup(function() {
            self.reg_no = this.value;
        });
        $("#membership").keyup(function() {
            self.membership = this.value;
        });
        $("#gate_in_no").keyup(function() {
            self.gate_in_no = this.value;
        });



        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';
            $scope.searchTerm3 = '';
        };
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });
        $scope.selectedCustomer = function(id) {
            $('#customer_id').val(id);
            self.customer_id = id;
        }
        $scope.selectedVehicleModel = function(id) {
            $('#model_id').val(id);
            self.model_id = id;
        }
        $scope.onSelectedRegistrationType = function(id) {
            $('#registration_type').val(id);
            self.registration_type = id;
        }
        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            self.status_id = id;
        }
        $scope.applyFilter = function() {
            $scope.fetchData();
            $('#vehicle-inward-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#gate_in_date").val('');
            $("#registration_type").val('');
            $("#reg_no").val('');
            $("#customer_id").val('');
            $("#model_id").val('');
            $("#membership").val('');
            $("#gate_in_no").val('');
            $("#status_id").val('');
            self.customer_id = '';
            self.model_id = '';
            self.registration_type = '';
            self.status_id = '';
            setTimeout(function() {
                $scope.fetchData();
            }, 1000);
            $('#vehicle-inward-filter-modal').modal('hide');
        }

        $rootScope.loading = false;

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        /* DateRange Picker */
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.align-left.daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            $('.date_range').val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
        });

        $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $('.date_range').val('');
        });

        $scope.applyFilter = function() {
            // $('#status').val(self.status);
            dataTables.fnFilter();
            $('#gate-log-filter-modal').modal('hide');
        }

        $scope.reset_filter = function() {
            $("#date_range").val('');
            $("#model_id").val('');
            $("#outlet_id").val('');
            dataTables.fnFilter();
            $('#gate-log-filter-modal').modal('hide');
        }
        $rootScope.loading = false;
    }
});