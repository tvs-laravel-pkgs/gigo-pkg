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
        $scope.showQrScan = true;
        $scope.showCheckInSuccess = false;
        $scope.showCheckOutConfirmation = false;
        $scope.showCheckOut = false;

        $scope.onSuccess = function(data) {
            console.log(data);
            $scope.encrypted_id=data;
            //alert($scope.encrypted_id);
             //$scope.sendQRCode();
            //stopScan;

            $.ajax({
                    url: base_url + '/api/employee-pkg/punch',
                    method: "POST",
                    data: {
                        encrypted_id: $scope.encrypted_id
                        },
                    //processData: false,
                    //contentType: false,
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
                    console.log(res);
                    self.response = res.data;
                    if(res.data.action =='Out'){
                        $scope.showQrScan = false;
                        $scope.showCheckInSuccess = false;
                        $scope.showCheckOutConfirmation = false;
                        $scope.showCheckOut = true;
                         $scope.punch_out=res.data.punch_out;
                        $scope.punch_out_method_list=res.data.punch_out_method_list;
                    }else{
                        $scope.showQrScan = false;
                        $scope.showCheckInSuccess = true;
                        $scope.showCheckOutConfirmation = false;
                        $scope.showCheckOut = false;
                        $scope.punch_in=res.data.punch_in;
                    }
                    $scope.user=res.data.user;
                    $scope.date=res.data.date;
                    $scope.time=res.data.time;
                    //$scope.punch_in=res.punch_in;
                    
                    $scope.$apply();

                })
                .fail(function(xhr) {

                    console.log(xhr);
                    $('.submit').button('reset');
                    showServerErrorNoty();
                });
           
        };
        $scope.onError = function(error) {
            console.log(error);
        };
        $scope.onVideoError = function(error) {
            console.log(error);
        };

         $scope.savePunchOut = function() {
            var form_id = '#punch_out_form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {},
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    //$('.submit').button('loading');
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

                        console.log(res);
                        self.response = res.data;
                        if(res.data.action =='Out'){
                            $scope.showQrScan = false;
                            $scope.showCheckInSuccess = false;
                            $scope.showCheckOutConfirmation = true;
                            $scope.showCheckOut = false;
                            $scope.punch_out=res.data.punch_out;
                          //  $scope.punch_out_method_list=res.data.punch_out_method_list;
                        }
                        /*else{
                            $scope.showQrScan = false;
                            $scope.showCheckInSuccess = true;
                            $scope.showCheckOutConfirmation = false;
                            $scope.showCheckOut = false;
                            $scope.punch_in=res.data.punch_in;
                        }*/
                    $scope.user=res.data.user;
                    //$scope.punch_in=res.punch_in;
                    
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
            alert();
            $route.reload();
            $scope.$apply();
        }

    }
});

