app.component('vehicleGatePassList', {
    templateUrl: vehicle_gate_pass_list_template_url,
    controller: function($http, $location, HelperService, $scope, $rootScope, $route) {
        $scope.loading = true;
        var self = this;
        $('#search_gate_pass').focus();
        $scope.hasPerm = HelperService.hasPerm;
        self.user = $scope.user = HelperService.getLoggedUser();

        $rootScope.loading = false;

        if (!HelperService.isLoggedIn()) {
            $location.path('/page-permission-denied');
            return;
        }

        //LIST
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#vehicle-gate-pass-list').dataTable({
            "dom": cndn_dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows Per Page _MENU_",
                "info": "_START_ to _END_ of _TOTAL_ Listing",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            stateSave: true,
            processData: false,
            contentType: false,
            paging: true,
            "bRetrieve": true,
            "bDestroy": true,
            //scrollY: table_scroll + "px",
            //scrollCollapse: true,
            ajax: {
                url: base_url + '/api/gigo-pkg/get-vehicle-gate-pass-list',
                type: "POST",
                dataType: "json",
                data: function(d) {
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
                { data: 'gate_in_date_time', name: 'gate_in_date_time', searchable: false },
                { data: 'gate_pass_no', name: 'gate_passes.number' },
                { data: 'registration_number', name: 'vehicles.registration_number' },
                { data: 'driver_name', name: 'gate_logs.driver_name' },
                { data: 'contact_number', name: 'gate_logs.contact_number' },
                { data: 'model_name', name: 'models.model_name' },
                { data: 'job_card_number', name: 'job_cards.job_card_number' },
                { data: 'status', name: '', searchable: false },

            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            "aoColumnDefs": [{
                "aTargets": [0],
                "mRender": function(data, type, full) {
                    var action = '';
                    action += '<a href="#!/gigo-pkg/vehicle-gate-pass/view/' + full.gate_log_id + '"' + 'id="' + full.gate_log_id + '" title="View"><img src="' + view_img + '"  alt="View"></a>';
                    if (full.status_id == 8123) { //SHOW ONLY FOR GATE OUT PENDIGN STATUS
                        action += '<a href="javascript:;" onclick="angular.element(this).scope().vehicleGateOut(' + full.gate_log_id + ')" title="Gate Out"><img src="' + gate_out_img + '"  alt="Gate Out"></a>';
                    }
                    return action;
                }
            }],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $('.refresh_table').on("click", function() {
            $('#vehicle-gate-pass-list').DataTable().ajax.reload();
            $scope.fetchData();
        });

        $scope.clear_search = function() {
            $('#search_gate_pass').val('');
            $('#vehicle-gate-pass-list').DataTable().search('').draw();
            $scope.fetchData('');
        }

        var dataTables = $('#vehicle-gate-pass-list').dataTable();

        $scope.searchKey = function(event) {
            dataTables.fnFilter(event.target.value);
            $scope.fetchData(event.target.value);
        }

        $scope.fetchData = function(search_key) {
            //CARD LIST
            $.ajax({
                url: base_url + '/api/gigo-pkg/get-vehicle-gate-pass-list',
                type: "POST",
                dataType: "json",
                data: { 'search_key': search_key },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    // console.log(response);
                    self.vehicle_gate_pass_list = response.data;
                    $scope.$apply();
                    // Success = true; //doesn't go here
                },
                error: function(textStatus, errorThrown) {
                    custom_noty('error', 'Something went wrong at server');
                }
            });
        }
        $scope.fetchData();

        //GATE OUT VEHICLE
        $scope.vehicleGateOut = function(id) {
            console.log(id);
            $.ajax({
                url: base_url + '/api/gigo-pkg/gate-out-vehicle/save',
                type: "POST",
                data: { 'gate_log_id': id },
                dataType: "json",
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
                },
                success: function(response) {
                    console.log(response);
                    $("#gate_pass").text(response.gate_out_data.gate_pass_no);
                    $("#registration_number").text(response.gate_out_data.registration_number);
                    $('#confirm_notification').modal('show');
                    $('#vehicle-gate-pass-list').DataTable().ajax.reload();
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
            // $route.reload();
        }
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('vehicleGatePassView', {
    templateUrl: vehicle_gate_pass_view_template_url,
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

        $.ajax({
            url: base_url + '/api/gigo-pkg/view-vehicle-gate-pass/' + $routeParams.id,
            type: "GET",
            dataType: "json",
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + $scope.user.token);
            },
            success: function(response) {
                // console.log(response);
                self.vehicle_gate_pass = response.view_vehicle_gate_pass;
                $scope.$apply();
            },
            error: function(textStatus, errorThrown) {
                custom_noty('error', 'Something went wrong at server');
            }
        });

        //GATE OUT
        var form_id = '#gate_pass_out';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'remarks': {
                    maxlength: 191,
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/gigo-pkg/gate-out-vehicle/save',
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
                        $("#gate_pass").text(res.gate_out_data.gate_pass_no);
                        $("#registration_number").text(res.gate_out_data.registration_number);
                        // setTimeout(function() {
                        $('#confirm_notification').modal('show');
                        // }, 500);
                        $('.submit').button('reset');
                    })
                    .fail(function(xhr) {
                        console.log(xhr);
                        $('.submit').button('reset');
                        showServerErrorNoty();
                    });
            }
        });
        $scope.reloadPage = function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $route.reload();
        }
        
    }
});