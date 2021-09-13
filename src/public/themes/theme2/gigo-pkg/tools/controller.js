app.component('gigoToolsList', {
    templateUrl: gigo_tools_list_template_url,
    controller: function(HelperService, $rootScope, $routeParams, $scope, $http, $element, $mdSelect) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.importpermission = self.hasPermission('import-employees-shift');

        // self.vendor_type_id = $routeParams.id;       
        var dataTable1 = $('#tools-table').DataTable({
            stateSave: true,
            "dom": dom_structure_2,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows Per Page _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            stateSave: true,
            pageLength: 10,
            processing: true,
            serverSide: true,
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['getGIGOToolsList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                },
            },
            columns: [
                { data: 'code', name: 'tools.code', searchable: true },
                { data: 'name', name: 'tools.name', searchable: true },
            ],
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            },
        });

        $('.title-block').html(
            '<h1 class="title">Tools<span class="badge badge-secondary" id="table_info"></span></h1>' +
            '<p class="subtitle">List</p>'
        );
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        $('.dataTables_length select').select2();
        $('.page-header-content-left .search-block .dataTables_filter label').append('<button class="btn-clear search_clear">Clear</button>');
        $('.page-header-content-left .search-block .dataTables_filter').addClass('search_filter');

        $import_tools = '';
        // if (self.importpermission == true) {
            $import_tools = '<a type="button" class="btn btn-primary import_tools">' +
                'Import Tools' +
                '</a>';
        // }

        $('.page-header-content-right .button-block').html($import_tools);

        $('.page-header-content-left .button-block').html(
            '<button type="button" class="btn btn-refresh refresh_table"><img src="' + refresh_img_url + '" class="img-responsive btn-refresh-icon"></button>'
        );
        $('.page-header-content-left .button-block').addClass('pad-lf-rt');
        
        var dataTable = $('#tools-table').dataTable();

        $('.import_tools').on("click", function() {
            $('#import-tools').modal('show');
            $('.card-transition').hide();
        });

        $scope.popup_close = function() {
            location.reload();
        };

        /* Page Title Appended */
        $('.page-header-content .display-inline-block .data-table-title').html('EMPLOYEES');
        var templates_filter = $('.dummy').html();
        $('.page-header-content-left .button-block').html(templates_filter);

        $('.refresh_table').on("click", function() {
            $('#tools-table').DataTable().ajax.reload();
        });
        $('.search_clear').on("click", function() {
            $('#tools-table').DataTable().search('').draw();
        });

        //for md-select search
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        $rootScope.loading = false;

        $('.card-transition').hide();

        var form_id = '#import_tools_form';
        var v = jQuery(form_id).validate({
            ignore: "",
            errorPlacement: function(error, element) {
                if (element.hasClass("input_excel")) {
                    error.appendTo('.errors');
                } else {
                    error.insertAfter(element)
                }
            },
            rules: {
                input_excel: {
                    required: true,
                    extension: "xlsx,xls",
                },
            },

            submitHandler: function(form) {
                $('#import_errors').html('');
                $('.card-transition').show();
                $('#import_ctc').button('loading');
                let Upl = new FormData($('#import_tools_form')[0]);
                $.ajax({
                        url: laravel_routes['importGIGOTools'],
                        method: "POST",
                        headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
                        enctype: 'multipart/form-data',
                        data: Upl,
                        processData: false,
                        contentType: false,
                        cache: false,
                    })
                    .done(function(response) {

                        if (response.success) {

                            $('.card-transition').addClass('card-transition-active');
                            $('.card-button').addClass('card-button-active');
                            $('.card-progress').addClass('card-progress-active');
                            $('.btn-circle').addClass('btn-circle-active');
                            $('.btn-over').addClass('btn-over-active');
                            $('.card-button').hide();
                            $('#total_count').html(response.total_records);
                            // $('#import_status span').html('Importing records');
                            if (response.total_records > 0) {
                                $('#download_error_report').attr('href', response.error_report_url).hide();
                                $('#model_download_error_report').attr('href', response.error_report_url);

                                remaining_rows = response.total_records;
                                total_rows = response.total_records;
                                file = response.file;
                                outputfile = response.outputfile;
                                headings = response.headings;
                                reference = response.reference;

                                $('#import_errors').append('<div class="text-left text-primary">Import Reference ID: ' + response.reference + '</div>')
                                imports();
                            }
                        } else {
                            // alert();
                            $('#import_ctc').button('reset');
                            $('#import_errors').html(response.errors)
                            console.log(response.missing_fields);
                            if (response.error == "Invalid File, Mandatory fields are missing.") {
                                for (var i in response.missing_fields) {
                                    $('#import_errors').append('<div class="text-left">' + response.missing_fields[i] + '</div>')
                                }

                            }

                            $('#import_ctc').button('reset');

                        }
                    })
                    .fail(function(xhr, ajaxOptions, thrownError) {
                        alert(thrownError);
                        $('#import_ctc').button('reset');

                    })
            }
        });

        function getDateTime() {
            var now = new Date();
            var year = now.getFullYear();
            var month = now.getMonth() + 1;
            var day = now.getDate();
            var hour = now.getHours();
            var minute = now.getMinutes();
            var second = now.getSeconds();

            var x = Math.floor((Math.random() * 1000) + 1);

            var dateTime = year + '' + month + '' + day + '' + hour + '' + minute + '' + second + '' + x;
            return dateTime;
        }

        var total_rows = 0;
        var remaining_rows = 0;
        var imported_rows = 0;
        var file = '';
        var outputfile = '';
        var headings = '';
        var reference = '';
        var records_per_request = 100;
        var import_number = getDateTime();


        function imports() {
            $.ajax({
                    url: laravel_routes['chunkImportGIGOTools'],
                    method: "POST",
                    headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
                    data: { skip: imported_rows, file: file, records_per_request: records_per_request, outputfile: outputfile, headings: headings, reference: reference, import_number: import_number },
                })
                .done(function(response) {
                    console.log(response);
                    if (response.success) {

                        var new_count = parseInt($('#new_count').html()) + response.newCount;
                        var updated_count = parseInt($('#updated_count').html()) + response.updatedCount;
                        var error_count = parseInt($('#error_count').html()) + response.errorCount;
                        var new_ratio = parseFloat((new_count / total_rows) * 100).toFixed(2);
                        var updated_ratio = parseFloat((updated_count / total_rows) * 100).toFixed(2);
                        var error_ratio = parseFloat((error_count / total_rows) * 100).toFixed(2);
                        $('#import_progress .progress-bar-success').attr('style', 'width:' + new_ratio + '%;');
                        $('#import_progress .progress-bar-success').html(new_ratio + '%');
                        $('#import_progress .progress-bar-warning').attr('style', 'width:' + updated_ratio + '%;');
                        $('#import_progress .progress-bar-warning').html(updated_ratio + '%');
                        $('#import_progress .progress-bar-danger').attr('style', 'width:' + error_ratio + '%;');
                        $('#import_progress .progress-bar-danger').html(error_ratio + '%');
                        $('#import_errors').append(response.errors);
                        $('#new_count').html(new_count);
                        $('#updated_count').html(updated_count);
                        $('#error_count').html(error_count);

                        imported_rows += response.processed;

                        $('#import_status span').html('Inprogress (Processed: ' + imported_rows + ' Remaining: ' + (total_rows - imported_rows) + ')')
                        remaining_rows -= response.processed;

                        $('#import_progress span').html(parseInt(new_ratio) + '% Completed')
                        $('.skillbar').attr('style', 'width:' + parseInt(new_ratio) + '%;')

                        $('#import_errors').append(response.errors)
                        $('#new_count').html(new_count)
                        $('#error_count').html(error_count)

                        if (remaining_rows > 0) {
                            imports();
                        } else {
                            $('#import_progress span').html(parseInt(new_ratio) + '% Completed')
                            $('.skillbar').attr('style', 'width:' + parseInt(new_ratio) + '%;')
                            $('#import_status span').html('Completed')
                            $('#download_error_report').attr('href', response.error_report_url);
                            if (error_count > 0) {
                                $('#error_button').css('display', 'inline-block');
                                $('#download_error_report').css('display', 'inline-block');
                                $('#error_table').html(response.errors);
                            } else {
                                $('#error_button').hide();
                                $('#download_error_report').hide();
                                $('#error_table').html('No error Found');
                            }
                        }
                    } else {
                        $('#download_error_report').css('display', 'inline-block');
                        // alert('Error:'+response.error )
                        $('#import_errors').append(response.error)
                    }

                })
                .fail(function(xhr, ajaxOptions, thrownError) {
                    alert('An error occured during import')
                })
        }
    }
});

app.component('gigoImportForm', {
    templateUrl: import_gigo_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = import_cron_job_from_data_url + '/' + $routeParams.id;
        // if ($routeParams.id != 2) {
        //     $location.path('/page-not-found')
        //     // $scope.$apply()
        // }
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        self.type_id = $routeParams.id;
        self.import_cron_job_template_base_path = import_cron_job_template_base_path;
        $http.get(
            get_form_data_url
        ).then(function(response) {
            // console.log(response.data);
            self.impoty_type = response.data.impoty_type;
            // if (self.impoty_type.permission != 'import-coupon') {
            //     $location.path('/page-not-found')
            //     $scope.$apply()
            // }
            // $rootScope.loading = false;
        });

        /* Tab Funtion */
        var form_id = '#import-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'excel_file': {
                    required: true,
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#upload').button('loading');
                $.ajax({
                        url: laravel_routes['saveImportCronJob'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-import/list');
                            $scope.$apply();
                        } else {
                            if (!res.success) {
                                $('#upload').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#upload').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});