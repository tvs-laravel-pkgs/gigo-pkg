angular.module('app').requires.push('qrScanner');
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
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams, $interval) {
        $scope.loading = true;
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        //self.user = $scope.user = HelperService.getLoggedUser();
        $scope.user = JSON.parse(localStorage.getItem('user'));
        $scope.date = new Date();
        $scope.time = new Date();
        $interval(function() {
            $scope.time = new Date();
        }, 1000);
        $rootScope.loading = false;
        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }

        setTimeout(function() {
            $scope.showQrScan = true;
            $scope.showCheckInSuccess = false;
            $scope.showCheckOutConfirmation = false;
            $scope.showCheckOut = false;
        }, 1000);


        $scope.onSuccess = function(data) {
            console.log(data);
            $scope.Punch(data);
        };
        $scope.onError = function(error) {
            console.log(error);
        };
        $scope.onVideoError = function(error) {
            console.log(error);
        };

        $scope.Punch = function(data) {
            if (data) {
                $.ajax({
                        url: base_url + '/api/employee-pkg/punch',
                        method: "POST",
                        data: {
                            encrypted_id: data,
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
                        console.log(res);
                        self.response = res.data;
                        if (res.data.action == 'Out') {
                            $scope.showQrScan = false;
                            $scope.showCheckInSuccess = false;
                            $scope.showCheckOutConfirmation = false;
                            $scope.showCheckOut = true;
                            $scope.punch_out = res.data.punch_out;
                            $scope.punch_out_method_list = res.data.punch_out_method_list;
                        } else {
                            $scope.showQrScan = false;
                            $scope.showCheckInSuccess = true;
                            $scope.showCheckOutConfirmation = false;
                            $scope.showCheckOut = false;
                            $scope.punch_in = res.data.punch_in;
                        }
                        $scope.punch_user = res.data.punch_user;
                        //$scope.punch_in=res.punch_in;
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        custom_noty('error', 'Something went wrong at server');
                    });
            } else {
                custom_noty('error', 'Wrong QR Code!');
            }
        }

        $scope.savePunchOut = function() {
            //console.log($scope.user);
            var form_id = '#punch_out_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {},
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/employee-pkg/punch-out/save',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            beforeSend: function(xhr) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                            },
                        })
                        .done(function(res) {
                            if (!res.success) {
                                showErrorNoty(res);
                                $('.submit').button('reset');
                                return;
                            }
                            self.response = res.data;
                            $scope.showQrScan = false;
                            $scope.showCheckInSuccess = false;
                            $scope.showCheckOutConfirmation = true;
                            $scope.showCheckOut = false;
                            $scope.punch_user = res.data.punch_user;
                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            console.log(xhr);
                            $('.submit').button('reset');
                            showServerErrorNoty();
                        });
                }
            });
        }

        $scope.reloadPage = function() {
            window.location = base_url + '#!/kanban-app';
        }

    }
});


app.component('kanbanAppMyJobCardScanQr', {
    templateUrl: kanban_app_my_job_card_sacn_qr_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams, $interval) {
        $scope.loading = true;
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        //self.user = $scope.user = HelperService.getLoggedUser();
        $scope.user = JSON.parse(localStorage.getItem('user'));
        $scope.date = new Date();
        $scope.time = new Date();
        $interval(function() {
            $scope.time = new Date();
        }, 1000);
        $rootScope.loading = false;
        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }

        $scope.onSuccess = function(data) {
            console.log(data);
            // $scope.user_id = data;
            $scope.scanQR(data);
        };
        $scope.onError = function(error) {
            console.log(error);
        };
        $scope.onVideoError = function(error) {
            console.log(error);
        };

        $scope.scanQR = function(data) {
            if (data) {
                $.ajax({
                        url: base_url + '/api/myjobcard/list',
                        method: "POST",
                        data: {
                            user_id: data,
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
                        window.location = base_url + '#!/my-jobcard/table-list/' + data;
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        custom_noty('error', 'Something went wrong at server');
                    });
            } else {
                custom_noty('error', 'Wrong QR Code!');
            }
        }
        $scope.reloadPage = function() {
            window.location = base_url + '#!/kanban-app';
        }
    }
});

app.component('kanbanAppMyTimeSheetScanQr', {
    templateUrl: kanban_app_my_time_sheet_sacn_qr_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams, $interval) {
        $scope.loading = true;
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        //self.user = $scope.user = HelperService.getLoggedUser();
        $scope.user = JSON.parse(localStorage.getItem('user'));
        $scope.date = new Date();
        $scope.time = new Date();
        $interval(function() {
            $scope.time = new Date();
        }, 1000);
        $rootScope.loading = false;
        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }

        $scope.onSuccess = function(data) {
            console.log(data);
            // $scope.user_id = data;
            $scope.scanQR(data);
        };
        $scope.onError = function(error) {
            console.log(error);
        };
        $scope.onVideoError = function(error) {
            console.log(error);
        };

        $scope.scanQR = function(data) {
            if (data) {
                $.ajax({
                        url: base_url + '/api/mytimesheet/list',
                        method: "POST",
                        data: {
                            user_id: data,
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
                        console.log(base_url + '#!/my-jobcard/timesheet-list/' + data);
                        window.location = base_url + '#!/my-jobcard/timesheet-list/' + data;
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        custom_noty('error', 'Something went wrong at server');
                    });
            } else {
                custom_noty('error', 'Wrong QR Code!');
            }
        }
        $scope.reloadPage = function() {
            window.location = base_url + '#!/kanban-app';
        }
    }
});