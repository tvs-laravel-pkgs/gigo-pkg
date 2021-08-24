app.component('mechanicReport', {
    templateUrl: gigo_mechanic_report_list_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_inward_vehicle').focus();
        var self = this;
        HelperService.isLoggedIn()
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');

        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('gigo-battery')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        self.user = $scope.user = HelperService.getLoggedUser();
        self.export_url = exportMechanicReport;
        // var table_scroll;
        self.csrf_token = $('meta[name="csrf-token"]').attr('content');

        /* DateRange Picker */
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            "autoApply": true,
            // startDate: 0,
            // endDate: '-30d',
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.align-left.daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            // startDate: 0,
            // endDate: '-30d',
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY",
            }
        });

        $('.daterange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            //dataTables.fnFilter();
        });

        $('.daterange').on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
        });

        $rootScope.loading = false;
    }
});

app.component('attendanceReport', {
    templateUrl: gigo_attendance_report_list_template_url,
    controller: function ($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        var self = this;
        HelperService.isLoggedIn()
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('gigo-battery')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        self.user = $scope.user = HelperService.getLoggedUser();
        self.export_url = exportAttendanceReport;
        self.csrf_token = $('meta[name="csrf-token"]').attr('content');

        /* DateRange Picker */
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            "autoApply": true,
            // startDate: 0,
            // endDate: '-30d',
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $('.align-left.daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            // startDate: 0,
            // endDate: '-30d',
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY",
            }
        });

        $('.daterange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
        });

        $('.daterange').on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
        });

        $rootScope.loading = false;
    }
});

