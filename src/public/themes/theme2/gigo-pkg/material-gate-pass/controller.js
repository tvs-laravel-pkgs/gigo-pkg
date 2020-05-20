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
                { data: 'gate_in_date_time'},
                { data: 'gate_pass_no', name: 'gate_passes.number' },
                { data: 'job_card_number', name: 'job_cards.number', searchable: false },
                { data: 'work_order_no', name: 'gate_pass_details.work_order_no' },
                { data: 'code', name: 'vendors.code'},
                { data: 'name', name: 'vendors.name'},
                { data: 'items'},
                { data: 'status'},

            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            "aoColumnDefs": [{
                "aTargets": [0],
                "mRender": function(data, type, full) {
                    var action='';
                    if(full.gate_in_date_time){
                        action ='<td class="action width-100"><a href="'+ base_url +'/api/get-material-gate-pass-detail/'+ full.gate_pass_id +'" class=""><img class="img-responsive" src="./public/theme/img/table/cndn/view.svg" alt="View" /></a><button class="btn btn-secondary-dark btn-sm">Confirm Gate Out</button></td>';
                    }else{
                         action ='<td class="action width-100"><a href="'+base_url+'/api/get-material-gate-pass-detail/'+ full.gate_pass_id +'" class=""><img class="img-responsive" src="./public/theme/img/table/cndn/view.svg" alt="View" /></a><button class="btn btn-secondary-dark btn-sm">Confirm Gate In</button></td>';
                    }
                    console.log(data, type, full);
                    // return '<a href="#"' + 'id="' + full.gate_pass_id + '" alt="Edit">Edit</a>';

                    return action                    
                }
            }],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $("#search_box").keyup(function() {
            dataTable.fnFilter(this.value);
        });

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

    }
});