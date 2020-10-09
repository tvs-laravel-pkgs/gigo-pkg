app.component('otpList', {
    templateUrl: otp_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_estimation_type').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('otp')) {
            window.location = "#!/permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('otp');
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#otp_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
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
                    $('#search_estimation_type').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            ajax: {
                url: laravel_routes['getOTPList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.event_id = $("#event_id").val();
                },
            },

            columns: [
                { data: 'type', searchable: false },
                { data: 'number', searchable: false },
                { data: 'otp_no', searchable: false },

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
            $('#search_estimation_type').val('');
            $('#otp_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#otp_list').DataTable().ajax.reload();
        });

        var dataTables = $('#otp_list').dataTable();
        $("#search_estimation_type").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        // FOR FILTER
        $http.get(
            laravel_routes['getOTPFilter']
        ).then(function(response) {
            // console.log(response);
            self.extras = response.data.extras;
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

        $scope.onSelectedEvent = function(id) {
            $('#event_id').val(id);
            self.trigger_event = id;
        }

        $scope.applyFilter = function() {
            dataTables.fnFilter();
            $('#estimation-type-filter-modal').modal('hide');
        }

        $scope.reset_filter = function() {
            $("#event_id").val('');
            self.trigger_event = '';
            dataTables.fnFilter();
            $('#estimation-type-filter-modal').modal('hide');
        }

        $rootScope.loading = false;
    }
});