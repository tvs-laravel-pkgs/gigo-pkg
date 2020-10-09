app.component('surveyTypeList', {
    templateUrl: survey_type_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_estimation_type').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('survey-types')) {
            window.location = "#!/permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('survey-types');
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#survey_type_list').DataTable({
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
                url: laravel_routes['getSurveyTypeList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.status_id = $("#status_id").val();
                    d.attendee_type_id = $("#attendee_type").val();
                    d.trigger_event_id = $("#trigger_event").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'number', name: 'survey_types.name' },
                { data: 'name', name: 'survey_types.name' },
                { data: 'attendee_name', name: 'configs.name' },
                { data: 'trigger_event_name', name: 'configs.name' },
                { data: 'status', searchable: false },

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
            $('#survey_type_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#survey_type_list').DataTable().ajax.reload();
        });

        var dataTables = $('#survey_type_list').dataTable();
        $("#search_estimation_type").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteTradePlateNumber = function($id) {
            $('#trade_plate_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#trade_plate_id').val();
            $http.get(
                laravel_routes['deleteSurveyType'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Survey Type Deleted Successfully');
                    $('#survey_type_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/survey-type/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getSurveyTypeFilter']
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

        $scope.onSelectedStatus = function(id) {
            $('#status_id').val(id);
            self.status_id = id;
        }

        $scope.onSelectedEvent = function(id) {
            $('#trigger_event').val(id);
            self.trigger_event = id;
        }

        $scope.onSelectedAttendeeType = function(id) {
            $('#attendee_type').val(id);
            self.attendee_type = id;
        }

        $scope.applyFilter = function() {
            dataTables.fnFilter();
            $('#estimation-type-filter-modal').modal('hide');
        }

        $scope.reset_filter = function() {
            $("#outlet_id").val('');
            $("#status_id").val('');
            $("#trigger_event").val('');
            $("#attendee_type").val('');
            self.attendee_type = '';
            self.trigger_event = '';
            self.status_id = '';
            dataTables.fnFilter();
            $('#estimation-type-filter-modal').modal('hide');
        }

        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('surveyTypeForm', {
    templateUrl: survey_type_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        var self = this;

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-survey-type') && !self.hasPermission('edit-survey-type')) {
            window.location = "#!/permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getSurveyTypeFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.survey_type = response.data.survey_type;
            self.extras = response.data.extras;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.survey_type.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        // ADD NEW TECHNICAL LEADS
        self.addNewQuestion = function() {
            self.survey_type.survey_field.push({});
        }

        self.removeQuestion = function(index) {
            self.survey_type.survey_field.splice(index, 1);
        }

        //Save Form Data 
        var form_id = '#survey_type_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
                'purpose': {
                    required: true,
                },
                'attendee_type_id': {
                    required: true,
                },
                'survey_trigger_event_id': {
                    required: true,
                },
            },
            messages: {
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 64 Characters',
                },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveSurveyType'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/survey-type/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/survey-type/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------