app.directive('mobileAttendanceEmployeeCard', function() {
    return {
        templateUrl: mobile_attendance_employee_card_template_url,
        controller: function() {}
    }
});
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
function onQRCodeScanned(scannedText) {
    var scannedTextMemo = document.getElementById("encrypted_id");
    if (scannedTextMemo) {
        scannedTextMemo.value = scannedText;
    }
}

function provideVideo() {
    var n = navigator;

    if (n.mediaDevices && n.mediaDevices.getUserMedia) {
        return n.mediaDevices.getUserMedia({
            video: {
                facingMode: "environment"
            },
            audio: false
        });
    }

    return Promise.reject('Your browser does not support getUserMedia');
}

function provideVideoQQ() {
    return navigator.mediaDevices.enumerateDevices()
        .then(function(devices) {
            var exCameras = [];
            devices.forEach(function(device) {
                if (device.kind === 'videoinput') {
                    exCameras.push(device.deviceId)
                }
            });

            return Promise.resolve(exCameras);
        }).then(function(ids) {
            if (ids.length === 0) {
                return Promise.reject('Could not find a webcam');
            }

            return navigator.mediaDevices.getUserMedia({
                video: {
                    'optional': [{
                        'sourceId': ids.length === 1 ? ids[0] : ids[1] //this way QQ browser opens the rear camera
                    }]
                }
            });
        });
}

var jbScanner;
//this function will be called when JsQRScanner is ready to use
function JsQRScannerReady() {
    //create a new scanner passing to it a callback function that will be invoked when
    //the scanner succesfully scan a QR code
    if (typeof(JsQRScanner) != 'undefined') {
        jbScanner = new JsQRScanner(onQRCodeScanned);
        //var jbScanner = new JsQRScanner(onQRCodeScanned, provideVideo);
        //reduce the size of analyzed image to increase performance on mobile devices
        jbScanner.setSnapImageMaxSize(300);
        var scannerParentElement = document.getElementById("scanner");
        if (scannerParentElement) {
            //append the jbScanner to an existing DOM element
            jbScanner.appendTo(scannerParentElement);
        }

    }
}


//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------
app.component('mobileAttendanceScanQr', {
    templateUrl: mobile_attendance_scan_qr_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route) {
        $scope.loading = true;
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        self.user = $scope.user = HelperService.getLoggedUser();

        self.encrypted_id = 1;

        $rootScope.loading = false;

        if (!HelperService.isLoggedIn()) {
            $location.path('/gigo-pkg/mobile/login');
            return;
        }

        JsQRScannerReady();

        self.showQrScan = true;
        self.showCheckInSuccess = false;
        self.showCheckOutConfirmation = false;


        $scope.sendQRCode = function() {
            var form_id = '#form';
            var v = jQuery(form_id).validate({
                ignore: '',
                rules: {},
                submitHandler: function(form) {
                    let formData = new FormData($(form_id)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/employee-pkg/punch',
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
                            // jbScanner.removeFrom(document.getElementById("scanner"));

                            console.log(res);
                            self.response = res;
                            $('#qr-scan-page').hide();
                            $('.submit').button('reset');
                            if (res.status_id == 1) {
                                //Check in success
                                $('#check-in-success-page').show();

                            } else if (res.status_id == 2) {
                                //Check Out Confirmation
                                $('#check-out-confirmation-page').show();
                            }
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

        $scope.saveCheckOut = function() {
            var form_id2 = '#form2';
            var v = jQuery(form_id2).validate({
                ignore: '',
                rules: {},
                submitHandler: function(form) {
                    let formData = new FormData($(form_id2)[0]);
                    $('.submit').button('loading');
                    $.ajax({
                            url: base_url + '/api/employee-pkg/punch-out',
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

                            self.response = res;
                            $('#check-out-confirmation-page').hide();
                            $('#check-out-success-page').show();
                            $('.submit').button('reset');
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
            $route.reload();
            // $location.path('/gigo-pkg/mobile/attendance/scan-qr');
            // alert(1)
        }
    }
});
//-------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------