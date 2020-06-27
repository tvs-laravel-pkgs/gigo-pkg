app.component('materialGatePassCardList', {
    templateUrl: material_gate_pass_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route) {
        $rootScope.loading = true;
        $('#search_material_gate_pass').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('material-gate-passes')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.user = $scope.user = HelperService.getLoggedUser();
        self.gate_pass_created_date = '';
        self.number = '';
        self.job_card_number = '';
        self.work_order_no = '';
        self.vendor_name = '';
        self.vendor_code = '';
        if (!localStorage.getItem('search_key')) {
            self.search_key = '';
        } else {
            self.search_key = localStorage.getItem('search_key');
        }

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/material-gate-pass/get',
                    method: "POST",
                    data: {
                        search_key: self.search_key,
                        gate_pass_created_date: self.gate_pass_created_date,
                        number: self.number,
                        job_card_number: self.job_card_number,
                        work_order_no: self.work_order_no,
                        vendor_name: self.vendor_name,
                        vendor_code: self.vendor_code,
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
                    $scope.material_gate_passes = res.material_gate_passes;
                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $('.refresh_table').on("click", function() {
            $scope.fetchData();
        });
        $scope.clear_search = function() {
            self.search_key = '';
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $scope.searchKey = function() {
            localStorage.setItem('search_key', self.search_key);
            $scope.fetchData();
        }
        $("#gate_pass_created_date").keyup(function() {
            self.gate_pass_created_date = this.value;
        });
        $("#number").keyup(function() {
            self.number = this.value;
        });
        $("#job_card_number").keyup(function() {
            self.job_card_number = this.value;
        });
        $("#work_order_no").keyup(function() {
            self.work_order_no = this.value;
        });
        $("#vendor_name").keyup(function() {
            self.vendor_name = this.value;
        });
        $("#vendor_code").keyup(function() {
            self.vendor_code = this.value;
        });

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/material-gate-pass/table-list";
                return false;
            } else {
                window.location = "#!/material-gate-pass/card-list";
                return false;
            }
        }

        $scope.applyFilter = function() {
            $scope.fetchData();
            $('#material-gate-pass-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#gate_pass_created_date").val('');
            $("#number").val('');
            $("#job_card_number").val('');
            $("#work_order_no").val('');
            $("#vendor_name").val('');
            $("#vendor_code").val('');
            self.gate_pass_created_date = '';
            self.number = '';
            self.job_card_number = '';
            self.work_order_no = '';
            self.vendor_name = '';
            self.vendor_code = '';
            setTimeout(function() {
                $scope.fetchData();
            }, 1000);
            $('#material-gate-pass-filter-modal').modal('hide');
        }

       //GATE IN / OUT 
        $scope.materialGateInOut = function(id, status_id) {
            console.log(id, status_id);
            if(status_id == 8300){
                var type = 'Out';                
                var button_class = '.confirm_gate_out_' + id;
            }else{
                var type = 'In';                
                var button_class = '.confirm_gate_in_' + id;
            }
            $(button_class).button('loading');
            $.ajax({
                url: base_url + '/api/material-gate-pass/gate-in-out/save',
                type: "POST",
                data: {
                    'gate_pass_id': id,
                    'type': type,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    if (!response.success) {
                        showErrorNoty(response);
                        return;
                    }
                    $(".gate_pass_no").text(response.gate_pass.number);
                    if (response.type == 'Out') {
                        $('#otp').modal('show');
                        $('#otp_no').val('');
                        $('#otp').on('shown.bs.modal', function() {
                            $(this).find('[autofocus]').focus();
                        });
                        $('#gate_pass_id').val(response.gate_pass.id);
                        $('.customer_mobile_no').html(response.customer_detail.mobile_no);

                    } else {
                        $('#gate_in_confirm_notification').modal('show');
                    }
                    $(button_class).button('reset');
                },
                error: function(textStatus, errorThrown) {
                    $(button_class).button('reset');
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        //GATE OUT
        var form_gate_out_confirm = '#material_gate_out_confirm';
        var v = jQuery(form_gate_out_confirm).validate({
            ignore: '',
            rules: {
                'otp_no': {
                    required: true,
                    number: true,
                    minlength: 6,
                    maxlength: 6,
                },
                'remarks': {
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
                'otp_no': {
                    required: 'OTP is required',
                    number: 'OTP Must be a number',
                    minlength: 'OTP Minimum 6 Characters',
                    maxlength: 'OTP Maximum 6 Characters',
                },
                'remarks': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_gate_out_confirm)[0]);
                $('.submit_confirm').button('loading');
                $.ajax({
                        url: base_url + '/api/material-gate-pass/gate-out/confirm',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                    })
                    .done(function(res) {
                        // console.log(res);
                        if (!res.success) {
                            showErrorNoty(res);
                            $('.submit_confirm').button('reset');
                            $('#otp_no').val('');
                            $('#otp_no').focus();
                            return;
                        }
                        $('.submit_confirm').button('reset');
                        $('#otp_no').val('');
                        $('#otp').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        $('#gate_out_confirm_notification').modal('show');
                        $scope.fetchData();
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('#otp_no').val('');
                        $('.submit_confirm').button('reset');
                        showServerErrorNoty();
                    });
            }
        });

        $scope.ResendOtp = function() {
            var id = $('#gate_pass_id').val();
            $.ajax({
                url: base_url + '/api/material-gate-pass/gate-out/otp-resend/' + id,
                type: "GET",
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    custom_noty('success', response.message);
                    $(".gate_pass_no").text(response.gate_pass.number);
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }
        $scope.reloadPage = function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $scope.fetchData();
        }

    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('materialGatePassTableList', {
    templateUrl: material_gate_pass_list_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route) {
        $rootScope.loading = true;
        var self = this;
        HelperService.isLoggedIn()
        $('#search_material_gate_pass').focus();
        $('li').removeClass('active');
        $('.material_gate_passes').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('material-gate-passes')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.user = $scope.user = HelperService.getLoggedUser();
        self.search_key = '';
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        // $('.page-main-content.list-page-content').css("overflow-y", "auto");
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
                url: laravel_routes['getMaterialGatePassList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.gate_pass_created_date = $("#gate_pass_created_date").val();
                    d.number = $("#number").val();
                    d.job_card_number = $("#job_card_number").val();
                    d.work_order_no = $("#work_order_no").val();
                    d.vendor_name = $("#vendor_name").val();
                    d.vendor_code = $("#vendor_code").val();
                },
            },
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'date_and_time', searchable: false},
                { data: 'gate_pass_no', name: 'gate_passes.number' },
                { data: 'job_card_number', name: 'job_cards.job_card_number' },
                { data: 'work_order_no', name: 'gate_pass_details.work_order_no' },
                { data: 'code', name: 'vendors.code' },
                { data: 'name', name: 'vendors.name' },
                { data: 'items', searchable: false},
                { data: 'status', name: 'configs.name' },

            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            self.search_key = '';
            $('#material_gate_pass_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#material_gate_pass_list').DataTable().ajax.reload();
        });

        var dataTables = $('#material_gate_pass_list').dataTable();
        $scope.searchKey = function() {
            dataTables.fnFilter(self.search_key);
        }

        $scope.listRedirect = function(type) {
            if (type == 'table') {
                window.location = "#!/material-gate-pass/table-list";
                return false;
            } else {
                window.location = "#!/material-gate-pass/card-list";
                return false;
            }
        }

        $scope.applyFilter = function() {
            dataTables.fnFilter();
            $('#material-gate-pass-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#gate_pass_created_date").val('');
            $("#number").val('');
            $("#job_card_number").val('');
            $("#work_order_no").val('');
            $("#vendor_name").val('');
            $("#vendor_code").val('');
            dataTables.fnFilter();
            $('#material-gate-pass-filter-modal').modal('hide');
        }

        //GATE IN / OUT 
        $scope.materialGateInOut = function(id, status_id) {
            console.log(id, status_id);
            if(status_id == 8300){
                var type = 'Out';                
                var button_class = '.confirm_gate_out_' + id;
            }else{
                var type = 'In';                
                var button_class = '.confirm_gate_in_' + id;
            }
            $(button_class).button('loading');
            $.ajax({
                url: base_url + '/api/material-gate-pass/gate-in-out/save',
                type: "POST",
                data: {
                    'gate_pass_id': id,
                    'type': type,
                },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    if (!response.success) {
                        showErrorNoty(response);
                        return;
                    }
                    $(".gate_pass_no").text(response.gate_pass.number);
                    if (response.type == 'Out') {
                        $('#otp').modal('show');
                        $('#otp_no').val('');
                        $('#otp').on('shown.bs.modal', function() {
                            $(this).find('[autofocus]').focus();
                        });
                        $('#gate_pass_id').val(response.gate_pass.id);
                        $('.customer_mobile_no').html(response.customer_detail.mobile_no);

                    } else {
                        $('#gate_in_confirm_notification').modal('show');
                    }
                    $(button_class).button('reset');
                },
                error: function(textStatus, errorThrown) {
                    $(button_class).button('reset');
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        //GATE OUT
        var form_gate_out_confirm = '#material_gate_out_confirm';
        var v = jQuery(form_gate_out_confirm).validate({
            ignore: '',
            rules: {
                'otp_no': {
                    required: true,
                    number: true,
                    minlength: 6,
                    maxlength: 6,
                },
                'remarks': {
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
                'otp_no': {
                    required: 'OTP is required',
                    number: 'OTP Must be a number',
                    minlength: 'OTP Minimum 6 Characters',
                    maxlength: 'OTP Maximum 6 Characters',
                },
                'remarks': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_gate_out_confirm)[0]);
                $('.submit_confirm').button('loading');
                $.ajax({
                        url: base_url + '/api/material-gate-pass/gate-out/confirm',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                        },
                    })
                    .done(function(res) {
                        // console.log(res);
                        if (!res.success) {
                            showErrorNoty(res);
                            $('.submit_confirm').button('reset');
                            $('#otp_no').val('');
                            $('#otp_no').focus();
                            return;
                        }
                        $('.submit_confirm').button('reset');
                        $('#otp_no').val('');
                        $('#otp').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        $('#gate_out_confirm_notification').modal('show');
                        $('#material_gate_pass_list').DataTable().ajax.reload();
                    })
                    .fail(function(xhr) {
                        $('#otp_no').val('');
                        $('.submit_confirm').button('reset');
                        showServerErrorNoty();
                    });
            }
        });

       $scope.ResendOtp = function() {
            var id = $('#gate_pass_id').val();
            $.ajax({
                url: base_url + '/api/material-gate-pass/gate-out/otp-resend/' + id,
                type: "GET",
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    custom_noty('success', response.message);
                    $(".gate_pass_no").text(response.gate_pass.number);
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }

        $scope.reloadPage = function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $('#material_gate_pass_list').DataTable().ajax.reload();
        }

    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('materialGatePassView', {
    templateUrl: material_gate_pass_view_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route, $routeParams) {
        $rootScope.loading = true;
        var self = this;
        HelperService.isLoggedIn()
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('view-material-gate-pass')) {
            window.location = "#!/page-permission-denied";
            return false;
        }

        self.user = $scope.user = HelperService.getLoggedUser();

        //VIEW GATE PASS
        $.ajax({
            url: base_url + '/api/material-gate-pass/view/get-data',
            type: "POST",
            data: {
                'gate_pass_id': $routeParams.id,
            },
            dataType: "json",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
            },
            success: function(response) {
                if (!response.success) {
                    showErrorNoty(response);
                    return;
                }
                self.material_gate_pass = response.material_gate_pass;
                self.customer_detail = response.customer_detail;
                if (self.material_gate_pass.status_id == 8300) { //Gate Out Pending
                    self.type = 'Out';
                } else {
                    self.type = 'In';
                }
                $scope.$apply();
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
                        url: base_url + '/api/material-gate-pass/gate-in-out/save',
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
                        $(".gate_pass_no").text(res.gate_pass.number);
                        if (res.type == 'Out') {
                            $('#otp_no').val('');
                            $('#otp').modal('show');
                            $('#otp').on('shown.bs.modal', function() {
                                $(this).find('[autofocus]').focus();
                            });
                            $('#gate_out_remarks').val($('#remarks').val());
                            $('#gate_pass_id').val(res.gate_pass.id);
                            $('.customer_mobile_no').html(res.customer_detail.mobile_no);
                        } else {
                            $('#gate_in_confirm_notification').modal('show');
                            $('.submit').button('reset');
                        }
                        $('.submit').button('reset');
                    })
                    .fail(function(xhr) {
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
                    minlength: 6,
                    maxlength: 6,
                },
            },
            messages: {
                'otp_no': {
                    required: 'OTP is required',
                    number: 'OTP Must be a number',
                    minlength: 'OTP Minimum 6 Characters',
                    maxlength: 'OTP Maximum 6 Characters',
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_gate_out_confirm)[0]);
                $('.submit_confirm').button('loading');
                $.ajax({
                        url: base_url + '/api/material-gate-pass/gate-out/confirm',
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
                            $('#otp_no').val('');
                            $('#otp_no').focus();
                            $('.submit_confirm').button('reset');
                            return;
                        }
                        $('.submit_confirm').button('reset');
                        $('#otp_no').val('');
                        $('#otp').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        $('.submit').button('reset');
                        $('#gate_out_confirm_notification').modal('show');
                    })
                    .fail(function(xhr) {
                        $('.submit_confirm').button('reset');
                        $('#otp_no').val('');
                        $('#otp_no').focus();
                        showServerErrorNoty();
                    });
            }
        });

        $scope.ResendOtp = function() {
            var id = $('#gate_pass_id').val();
            $.ajax({
                url: base_url + '/api/material-gate-pass/gate-out/otp-resend/' + id,
                type: "GET",
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    custom_noty('success', response.message);
                    $(".gate_pass_no").text(response.gate_pass.number);
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }
        $scope.refresh = function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $location.path('/material-gate-pass/card-list');
        }

    }
});