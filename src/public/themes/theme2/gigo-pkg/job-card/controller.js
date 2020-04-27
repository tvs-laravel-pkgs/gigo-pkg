app.component('jobCardList', {
    templateUrl: job_card_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_job_card').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('job-cards')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-job-card');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#job_cards_list').DataTable({
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
                    $('#search_job_card').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getJobCardList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.name = $('#name').val();
                    d.short_name = $('#short_name').val();
                    d.journal_name = $("#journal_name").val();
                    d.from_account = $("#from_account").val();
                    d.to_account = $("#to_account").val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'name', name: 'job_cards.name' },
                { data: 'short_name', name: 'job_cards.short_name' },
                { data: 'journal', name: 'journals.name' },
                { data: 'from_account', name: 'from_ac.name' },
                { data: 'to_account', name: 'to_ac.name' },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_job_card').val('');
            $('#job_cards_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#job_cards_list').DataTable().ajax.reload();
        });

        var dataTables = $('#job_cards_list').dataTable();
        $("#search_job_card").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteJobCard = function($id) {
            $('#job_card_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#job_card_id').val();
            $http.get(
                laravel_routes['deleteJobCard'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Job Card Deleted Successfully');
                    $('#job_cards_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/job-card/list');
                }
            });
        }

        //FOR FILTER
        $http.get(
            laravel_routes['getJvFilterData']
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
        $('#name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#short_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.onSelectedJournal = function(id) {
            $('#journal_name').val(id);
            dataTables.fnFilter();
        }
        $scope.onSelectedFromAccount = function(id) {
            $('#from_account').val(id);
            dataTables.fnFilter();
        }
        $scope.onSelectedToAccount = function(id) {
            $('#to_account').val(id);
            dataTables.fnFilter();
        }
        $scope.onSelectedStatus = function(id) {
            $('#status').val(id);
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            $("#name").val('');
            $("#short_name").val('');
            $("#journal_name").val('');
            $("#from_account").val('');
            $("#to_account").val('');
            $("#status").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('jobCardForm', {
    templateUrl: job_card_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-job-card') || !self.hasPermission('edit-job-card')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getJobCardFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            // console.log(response);
            self.job_card = response.data.job_card;
            self.extras = response.data.extras;
            self.action = response.data.action;
            self.jv_fields = response.data.jv_field;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.job_card.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
                angular.forEach(self.jv_fields, function(value, key) {
                    // console.log(value, key);
                    // if (value.is_open == 1) {
                    //     var is_open = 'Yes';
                    //     self.jv_fields[key].is_open = 'Yes';
                    // } else {
                    //     var is_open = 'No';
                    //     self.jv_fields[key].is_open = 'No';
                    // }
                    // $scope.onChangedIsOpen(is_open, key);
                    if (value.is_editable == 1) {
                        var is_editable = 'Yes';
                        self.jv_fields[key].is_editable = 'Yes';
                    } else {
                        var is_editable = 'No';
                        self.jv_fields[key].is_editable = 'No';
                    }
                    $scope.onChangedIsEditable(is_editable, key);
                });
                // $scope.onSelectedApprovalType(self.job_card.approval_type_id);
            } else {
                self.switch_value = 'Active';
            }
        });

        $("input:text:visible:first").focus();

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';
        };



        //ON CHANGED IS EDITABLE
        $scope.onChangedIsEditable = function(value, index) {
            // console.log(value, index);
            if (value == 'No') {
                if (index == 0) {
                    self.isEditableYes0 = true;
                    $("#value0").addClass('required');
                }
                if (index == 1) {
                    self.isEditableYes1 = true;
                    $("#value1").addClass('required');
                }
                if (index == 2) {
                    self.isEditableYes2 = true;
                    $("#value2").addClass('required');
                }
            } else {
                if (index == 0) {
                    self.isEditableYes0 = false;
                    $("#value0").removeClass('required');
                }
                if (index == 1) {
                    self.isEditableYes1 = false;
                    $("#value1").removeClass('required');
                }
                if (index == 2) {
                    self.isEditableYes2 = false;
                    $("#value2").removeClass('required');
                }
            }
        }

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
                'short_name': {
                    required: true,
                    minlength: 2,
                    maxlength: 24,
                },
                'initial_status_id': {
                    required: true,
                },
                'final_approved_status_id': {
                    required: true,
                },
                'approval_type_id': {
                    required: true,
                },
            },
            messages: {
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 64 Characters',
                },
                'short_name': {
                    minlength: 'Minimum 2 Characters',
                    maxlength: 'Maximum 24 Characters',
                },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveJobCard'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/job-card/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/job-card/list');
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
app.component('jobCardView', {
    templateUrl: job_card_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (self.hasPermission('view-job-card')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getJobCardView'], {
                params: {
                    id: $routeParams.id,
                }
            }
        ).then(function(response) {
            console.log(response);
            self.job_card = response.data.job_card;
            self.jv_fields = response.data.jv_fields;
            self.action = response.data.action;
        });
    }
});