app.component('materialGatePassList', {
    templateUrl: material_gate_pass_list_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route) {
        $scope.loading = true;
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        self.user = $scope.user = HelperService.getLoggedUser();
        $rootScope.loading = false;
        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }

        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#material_gate_pass_list').dataTable({
            "dom": cndn_dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows Per Page MENU",
                "info": "START to END of TOTAL Listing",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            stateSave: true,
            processData: false,
            contentType: false,
            paging: true,
            //scrollY: table_scroll + "px",
            //scrollCollapse: true,
            ajax: {
                url: base_url + '/api/gigo-pkg/get-material-gate-pass-list',
                type: "POST",
                dataType: "json",
                data: function(d) {
                    console.log(d);
                    // d.short_name = $("#short_name").val();
                    // d.name = $("#name").val();
                    // d.description = $("#description").val();
                    // d.status = $("#status").val();
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
            },
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'gate_in_date_time' },
                { data: 'gate_pass_no', name: 'gate_passes.number' },
                { data: 'job_card_number', name: 'job_cards.number', searchable: false },
                { data: 'work_order_no', name: 'gate_pass_details.work_order_no' },
                { data: 'code', name: 'vendors.code' },
                { data: 'name', name: 'vendors.name' },
                { data: 'items' },
                { data: 'status' },

            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            "aoColumnDefs": [{
                "aTargets": [0],
                "mRender": function(data, type, full) {
                    var action = '';
                    action += '<a href="' + base_url + '/#!/gigo-pkg/material-gate-pass/view/' + full.gate_pass_id + '" class=""><img class="img-responsive" src="./public/theme/img/table/cndn/view.svg" alt="View" /></a>';
                    if (full.status_id == 8300) { //Gate Out Pending
                        action += '<button class="btn btn-secondary-dark btn-sm"><a href="javascript:;" onclick="angular.element(this).scope().materialGateOut(' + full.gate_pass_id + ')" title="Gate Out">Confirm Gate Out</a></button>';
                    } else {
                        action += '<button class="btn btn-secondary-dark btn-sm">Confirm Gate In</button>';
                    }
                    console.log(data, type, full);
                    return action
                }
            }],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $("#search_material_gate_pass").keyup(function() {
            dataTable.fnFilter(this.value);
        });

        $('.refresh_table').on("click", function() {
            $('#material_gate_pass_list').DataTable().ajax.reload();
        });

        $scope.clear_search = function() {
            $('#search_material_gate_pass').val('');
            $('#material_gate_pass_list').DataTable().search('').draw();
        }

        //CARD LIST
        $.ajax({
            url: base_url + '/api/gigo-pkg/get-material-gate-pass-list',
            type: "POST",
            dataType: "json",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
            },
            success: function(response) {
                // console.log(response);
                self.material_gate_pass_list = response.data;
                $scope.$apply();
                // Success = true; //doesn't go here
            },
            error: function(textStatus, errorThrown) {
                custom_noty('error', 'Something went wrong at server');
            }
        });

        //GATE OUT 
        $scope.materialGateOut = function(id) {
            console.log(id);
            $.ajax({
                url: base_url + '/api/gigo-pkg/save-gate-in-out-material-gate-pass',
                type: "POST",
                data: {
                    'gate_pass_id': id,
                    'type': 'Out',
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    console.log(response);
                    $("#gate_pass_no").text(response.gate_pass.number);
                    //$("#registration_number").text(response.gate_out_data.registration_number);
                    $('#otp').modal('show');
                    // $('#vehicle-gate-pass-list').DataTable().ajax.reload();
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }
        //GATE OUT 
        $scope.materialGateIn = function(id) {
            console.log(id);
            $.ajax({
                url: base_url + '/api/gigo-pkg/save-gate-in-out-material-gate-pass',
                type: "POST",
                data: {
                    'gate_pass_id': id,
                    'type': 'In',
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    console.log(response);
                    $("#gate_pass_no").text(response.gate_pass.number);
                    //$("#registration_number").text(response.gate_out_data.registration_number);
                    $('#gate_in_confirm_notification').modal('show');
                    // $('#vehicle-gate-pass-list').DataTable().ajax.reload();
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }


        $scope.ResendOtp = function() {
            var id = $('#gate_pass_id').val();
            console.log(id);
            $.ajax({
                url: base_url + '/api/gigo-pkg/material-gate-out-otp-resend/' + id,
                type: "GET",
                data: {},
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    console.log(response);
                    custom_noty('success', response.message);
                    $("#gate_pass").text(response.material_gate_pass.number);
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

    }
});

app.component('materialGatePassView', {
    templateUrl: material_gate_pass_view_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams) {
        $scope.loading = true;
        var self = this;
        $scope.hasPerm = HelperService.hasPerm;
        self.user = $scope.user = HelperService.getLoggedUser();
        $rootScope.loading = false;
        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }

        console.log($routeParams.id);
        //VIEW GATE PASS
        $.ajax({
            url: base_url + '/api/gigo-pkg/get-material-gate-pass-detail/' + $routeParams.id,
            type: "GET",
            dataType: "json",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
            },
            success: function(response) {
                // console.log(response);
                self.material_gate_pass = response.material_gate_pass_detail;
                self.customer_detail = response.customer_detail;

                console.log(self.material_gate_pass);
                console.log(self.material_gate_pass.status_id);
                console.log(self.customer_detail);
                if (self.material_gate_pass.status_id == 8300) { //Gate Out Pending
                    self.type = 'Out';
                } else {
                    self.type = 'In';
                }
                $scope.$apply();
                // Success = true; //doesn't go here
            },
            error: function(textStatus, errorThrown) {
                custom_noty('error', 'Something went wrong at server');
            }
        });

        //GATE OUT
        var form_id = '#material_gate_pass';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'remarks': {
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
                'remarks': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                }
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/gigo-pkg/save-gate-in-out-material-gate-pass',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                    })
                    .done(function(res) {
                        console.log(res);
                        if (!res.success) {
                            showErrorNoty(res);
                            $('.submit').button('reset');
                            return;
                        }
                        console.log(res);
                        if (res.type == 'Out') {
                            $('#otp').modal('show');
                            $('#gate_pass_id').val(res.gate_pass.id);

                        } else {
                            $('#gate_in_confirm_notification').modal('show');
                        }
                        $('.submit').button('reset');
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                        $('.submit').button('reset');
                        showServerErrorNoty();
                    });
            }
        });

        //GATE OUT
        var form_gate_out_confirm = '#material_gate_out_confirm';
        var v = jQuery(form_gate_out_confirm).validate({
            ignore: '',
            rules: {
                'otp_no': {
                    required: true,
                    number: true,
                },
                'remarks': {
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
                'otp': {
                    required: 'OTP is required',
                    number: 'OTP Must be a number',
                },
                'remarks': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_gate_out_confirm)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/gigo-pkg/save-gate-out-confirm-material-gate-pass',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                    })
                    .done(function(res) {
                        console.log(res);
                        if (!res.success) {
                            showErrorNoty(res);
                            $('.submit').button('reset');
                            return;
                        }
                        console.log(res);
                        $('.submit').button('reset');
                        reloadPage();
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                        $('.submit').button('reset');
                        showServerErrorNoty();
                    });
            }
        });


        $scope.ResendOtp = function() {
            var id = $('#gate_pass_id').val();
            console.log(id);
            $.ajax({
                url: base_url + '/api/gigo-pkg/material-gate-out-otp-resend/' + id,
                type: "GET",
                data: {},
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    console.log(response);
                    custom_noty('success', response.message);
                    $("#gate_pass").text(response.material_gate_pass.number);
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }
        $scope.reloadPage = function() {
            // $location.reload(true);
            //$('#confirm_notification').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $route.reload();
        }
    }
});