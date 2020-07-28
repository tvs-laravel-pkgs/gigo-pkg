app.component('inwardPartsIndentView', {
    templateUrl: inward_parts_indent_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $q, PartSvc, SplitOrderTypeSvc, RepairOrderSvc, $mdSelect) {

        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        self.angular_routes = angular_routes;
        self.job_order_repair_order_ids = [];
        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

        $scope.job_order_id = $routeParams.job_order_id;

        $scope.init = function() {
            $rootScope.loading = true;

            let promises = {
                split_order_type_options: SplitOrderTypeSvc.options(),
                repair_order_options: RepairOrderSvc.options(),
            };

            $scope.options = {};
            $q.all(promises)
                .then(function(responses) {
                    $scope.options.split_order_types = responses.split_order_type_options.data.options;
                    $scope.options.repair_orders = responses.repair_order_options.data.options;
                    $rootScope.loading = false;
                });
        };
        $scope.init();

        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        //FETCH DATA
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/inward-part-indent/get-view-data',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id
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
                    $scope.job_order = res.job_order;
                    $scope.labour_details = res.labour_details;
                    $scope.labour_amount = res.labour_amount;
                    $scope.part_details = res.part_details;
                    // $scope.part_amount = res.part_amount;
                    $scope.job_order_parts = res.job_order_parts;
                    $scope.repair_order_mechanics = res.repair_order_mechanics;
                    $scope.indent_part_logs = res.indent_part_logs;

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.sendConfirm = function(type_id) {
            if (type_id == 4) {
                var id = $scope.job_order.job_card.id;
            } else {
                var id = $scope.job_order.id;
            }

            if (id) {
                $('.send_confirm').button('loading');
                $.ajax({
                        url: base_url + '/api/vehicle-inward/stock-incharge/request/parts',
                        method: "POST",
                        data: {
                            id: id,
                            type_id: type_id,
                        },
                    })
                    .done(function(res) {
                        $('.send_confirm').button('reset');
                        if (!res.success) {
                            showErrorNoty(res);
                            return;
                        }
                        console.log(res);
                        custom_noty('success', 'URL send to Customer Successfully!!');
                        $("#confirmation_modal").modal('hide');
                        $("#billing_confirmation_modal").modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        $scope.fetchData();
                    })
                    .fail(function(xhr) {
                        $('.send_confirm').button('reset');
                    });
            }
        }

        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        /* Dropdown Arrow Function */
        arrowDropdown();

        /* Work Tooltip */
        $(document).on('mouseover', ".work-tooltip", function() {
            var $this = $(this);
            if (this.offsetWidth <= this.scrollWidth && !$this.attr('title')) {
                var $this_content = $this.children(".work_tooltip_hide").html();
                $this.tooltip({
                    title: $this_content,
                    html: true,
                    placement: "top"
                });
                $this.tooltip('show');
            }
        });
        $scope.showReturnPartForm = function(index, part) {
            console.log(index, part);
            if (part != undefined) {
                $scope.parts_indent.return_part = {};
                $scope.parts_indent.employee = {};
                self.job_order_returned_part_id = part.job_order_part_increment_id;
                $scope.parts_indent.return_part.id = part.part_id;
                $scope.parts_indent.return_part.qty = part.qty;
                $scope.parts_indent.employee.id = part.employee_id;
                $scope.parts_indent.return_part.job_order_part_id = part.job_order_part_id;
            }
            $('#return_part_form_modal').modal('show');
        }

        $scope.showPartForm = function(part) {
            console.log(part);
            $job_order_part_id = part.job_order_part_id;
            if (part == false) {
                $scope.parts_indent = {};
            } else {
                $repair_orders = part.repair_order;
                if (part.split_order_type_id != null) {
                    $split_id = part.split_order_type_id;
                    SplitOrderTypeSvc.read($split_id)
                        .then(function(response) {
                            $scope.parts_indent.split_order_type = response.data.split_order_type;
                        });
                }
                if (part.uom == undefined) {
                    PartSvc.read(part.id)
                        .then(function(response) {
                            $scope.parts_indent.part = response.data.part;
                            $scope.parts_indent.part.qty = part.qty;
                            $scope.parts_indent.part.job_order_part_id = $job_order_part_id;
                            $scope.parts_indent.repair_order = $repair_orders;
                            // $scope.calculatePartAmount();
                        });
                }
            }
            $scope.modal_action = part === false ? 'Add' : 'Edit';
            $('#part_form_modal').modal('show');
        }
        $scope.searchParts = function(query) {
            return new Promise(function(resolve, reject) {
                PartSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }
        $scope.partSelected = function(part) {
            $qty = 1;
            if (!part) {
                return;
            } else {
                if (part.qty) {
                    $qty = part.qty;
                }
            }
            PartSvc.read(part.id)
                .then(function(response) {
                    $scope.parts_indent.part.qty = $qty;
                    $scope.calculatePartAmount();
                });

        }
        $scope.calculatePartAmount = function() {
            if (!$scope.parts_indent.part.pivot) {
                $scope.parts_indent.part.pivot = {};
            }
            $scope.parts_indent.part.pivot.quantity = $scope.parts_indent.part.qty;
            $scope.parts_indent.part.total_amount = $scope.parts_indent.part.qty * $scope.parts_indent.part.mrp;
            $scope.parts_indent.part.pivot.amount = $scope.parts_indent.part.total_amount;
            $scope.calculatePartTotal();
        }
        $scope.calculatePartTotal = function() {
            $total_amount = 0;
            angular.forEach($scope.part_details, function(part, key) {
                if (part.removal_reason_id == null || part.removal_reason_id == undefined) {
                    $total_amount += parseFloat(part.amount);
                }
            });
            $scope.part_amount = $total_amount.toFixed(2);
        }
        var part_form = '#part-form';
        var v = jQuery(part_form).validate({
            ignore: '',
            rules: {
                'part_id': {
                    required: true,
                },
                // 'split_order_id': {
                //     required: true,
                // },
                'quantity': {
                    required: true,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {

                /*if ($scope.parts_indent.split_order_type) {

                    $scope.parts_indent.part.split_order_type = $scope.parts_indent.split_order_type.name;
                    $scope.parts_indent.part.split_order_type_id = $scope.parts_indent.split_order_type.id;
                }
                $scope.parts_indent.part.type = $scope.parts_indent.part.tax_code.code;
                $scope.parts_indent.part.amount = $scope.parts_indent.part.total_amount;
                $scope.parts_indent.part.part_detail = $scope.parts_indent.part.code + ' | ' + $scope.parts_indent.part.name;
                console.log($scope.parts_indent.part);
                if ($scope.part_modal_action == 'Add') {
                    angular.forEach($scope.part_details, function(part, key) {
                        if (part.name == $scope.parts_indent.part.name) {
                            $scope.part_details.splice(key, 1);
                        }
                    });
                    $scope.part_details.push($scope.parts_indent.part);
                } else {
                    $scope.part_details[$scope.part_index] = $scope.parts_indent.part;
                }*/

                let formData = new FormData($(part_form)[0]);
                $('.submit').button('loading');

                $.ajax({
                        url: base_url + '/api/vehicle-inward/add-part/save',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            $('.submit').button('reset');
                            showErrorNoty(res);
                            return;
                        }
                        $('.submit').button('reset');
                        custom_noty('success', res.message);
                        $location.path('/inward-parts-indent/view/' + $scope.job_order_id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });

                // $scope.calculatePartTotal();
                $scope.parts_indent = {};
                $('#part_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
                $scope.fetchData();
            }
        });

        $scope.selectingRepairOrder = function(val) {
            console.log(val);
            if (val) {
                list = [];
                angular.forEach($scope.parts_indent.repair_order, function(value, key) {
                    list.push(value.id);
                });
            } else {
                list = [];
            }
            self.repair_order_ids = list;
        }
        var return_part_form = "#return-part-form";
        var v = jQuery(return_part_form).validate({
            ignore: '',
            rules: {
                'returned_to_id': {
                    required: true,
                },
                'job_order_part_id': {
                    required: true,
                },
                'returned_qty': {
                    required: true,
                },
            },
            messages: {

            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Kindly fix');
            },
            submitHandler: function(form) {
                let formData = new FormData($(return_part_form)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: base_url + '/api/inward-part-indent/save-return-part',
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            $('.submit').button('reset');
                            showErrorNoty(res);
                            return;
                        }
                        $('.submit').button('reset');
                        custom_noty('success', res.message);
                        // $location.path('/inward-parts-indent/view/' + $scope.job_order_id);
                        $scope.$apply();
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });

                $('#return_part_form_modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
                $scope.fetchData();
            }
        });
        $scope.removeLog = function(index, log) {
            console.log(log);
            $('#delete_log').modal('show');
            $('#log_id').val(log.job_order_part_increment_id);
            $('#log_type').val(log.transaction_type);

        }
        $scope.deleteConfirm = function() {
            $id = $('#log_id').val();
            $type = $('#log_type').val();

            let formData = new FormData();
            formData.append('id', $id);
            formData.append('type', $type);
            $.ajax({
                    url: base_url + '/api/vehicle-inward/part-logs/delete',
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                })
                .done(function(res) {
                    if (!res.success) {
                        $rootScope.loading = false;
                        showErrorNoty(res);
                        return;
                    }
                    $('#delete_log').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $scope.fetchData();
                    custom_noty('success', res.message);
                })
                .fail(function(xhr) {
                    $rootScope.loading = false;
                    $scope.button_action(id, 2);
                    custom_noty('error', 'Something went wrong at server');
                });

        }
        $scope.removePart = function(index, id, type) {
            console.log(id);
            if (id == undefined) {
                $scope.part_details.splice(index, 1);
                $scope.calculatePartTotal();
            } else {
                $scope.delete_reason = 10021;
                $('#removal_reason').val('');
                //HIDE REASON TEXTAREA 
                $scope.customer_delete = false;
                $scope.laboutPartsDelete(index, id, type);
            }
        }
        $scope.laboutPartsDelete = function(index, id, type) {
            $('#delete_labour_parts').modal('show');
            $('#labour_parts_id').val(id);
            $('#payable_type').val(type);

            $scope.saveLabourPartDeleteForm = function() {
                var delete_form_id = '#labour_parts_remove';
                var v = jQuery(delete_form_id).validate({
                    ignore: '',
                    rules: {
                        'removal_reason_id': {
                            required: true,
                        },
                        'removal_reason': {
                            required: true,
                        },
                    },
                    errorPlacement: function(error, element) {
                        if (element.attr("name") == "removal_reason_id") {
                            error.appendTo('#errorDeleteReasonRequired');
                            return;
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    submitHandler: function(form) {
                        let formData = new FormData($(delete_form_id)[0]);
                        $rootScope.loading = true;
                        $.ajax({
                                url: base_url + '/api/vehicle-inward/labour-parts/delete',
                                // url: base_url + '/api/vehicle-inward/labour-parts-delete/update',
                                method: "POST",
                                data: formData,
                                processData: false,
                                contentType: false,
                            })
                            .done(function(res) {
                                if (!res.success) {
                                    $rootScope.loading = false;
                                    showErrorNoty(res);
                                    return;
                                }
                                $('#delete_labour_parts').modal('hide');
                                $('body').removeClass('modal-open');
                                $('.modal-backdrop').remove();
                                $scope.fetchData();
                                custom_noty('success', res.message);
                            })
                            .fail(function(xhr) {
                                $rootScope.loading = false;
                                $scope.button_action(id, 2);
                                custom_noty('error', 'Something went wrong at server');
                            });
                    }
                });

            }
        }
        $scope.button_action = function(id, type) {
            if (type == 1) {
                if (id == 1) {
                    $('.submit').button('loading');
                    $('.btn-nxt').attr("disabled", "disabled");
                    $('.btn-prev').bind('click', false);
                } else {
                    $('.btn-nxt').button('loading');
                    $('.submit').attr("disabled", "disabled");
                    $('.btn-prev').bind('click', false);
                }
            } else {
                $('.submit').button('reset');
                $('.btn-nxt').button('reset');
                $('.btn-prev').unbind('click', false);
                $(".btn-nxt").removeAttr("disabled");
                $(".submit").removeAttr("disabled");
            }
        }
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('inwardPartsIndentIssuePartForm', {
    templateUrl: inward_parts_indent_issue_part_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $q, PartSvc, VendorSvc) {

        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();
        $scope.job_order_id = $routeParams.job_order_id;
        self.job_order_issued_part_id = $routeParams.id;
        $scope.issue_part = {};
        $scope.fetchData = function() {
            $.ajax({
                    url: base_url + '/api/inward-part-indent/get-issue-part-form-data',
                    method: "POST",
                    data: {
                        id: $routeParams.job_order_id,
                        issue_part_id: $routeParams.id
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

                    $scope.job_order_parts = res.job_order_parts;
                    $scope.repair_order_mechanics = res.repair_order_mechanics;
                    $scope.issue_modes = res.issue_modes
                    $scope.issued_part = res.issue_data;
                    PartSvc.read($scope.issued_part.part_id)
                        .then(function(response) {
                            $scope.return_part = response.data.part;
                            $scope.return_part.job_order_part_id = res.issue_data.job_order_part_id;
                        });
                    $scope.issued_to = res.issue_to_user;

                    $scope.$apply();
                })
                .fail(function(xhr) {
                    custom_noty('error', 'Something went wrong at server');
                });
        }
        $scope.fetchData();

        $scope.saveIssueForm = function() {
            var form = '#issue_part_form';
            var v = jQuery(form).validate({
                ignore: '',
                rules: {
                    'job_order_part_id': {
                        required: true,
                    },
                    'issued_qty': {
                        required: true,
                        number: true,
                    },
                    'issue_mode_id': {
                        required: true,
                    },
                    'issued_to_id': {
                        required: true
                    },
                    'remarks': {
                        required: true
                    },
                    'quantity': {
                        required: true,
                        number: true,
                    },
                    'unit_price': {
                        required: true,
                        number: true,
                    },
                    'total': {
                        required: true,
                    },
                    'tax_percentage': {
                        required: true,
                        number: true,
                    },
                    'tax_amount': {
                        required: true,
                        number: true,
                    },
                    'total_amount': {
                        required: true,
                        number: true,
                    },
                    'mrp': {
                        required: true,
                        number: true,
                    },
                    'supplier_id': {
                        required: true,
                    },
                    'po_number': {
                        required: true,
                    },
                    'po_amount': {
                        required: true,
                    },
                    'advance_amount_received_details': {
                        required: true,
                    },
                    'warranty_approved_reasons': {
                        required: true,
                    },
                },
                messages: {

                },
                invalidHandler: function(event, validator) {
                    custom_noty('error', 'You have errors, Kindly fix');
                },
                submitHandler: function(form) {
                    let formData = new FormData($(form)[0]);
                    $('.submit').button('loading');

                    $.ajax({
                            url: base_url + '/api/inward-part-indent/save-issued-part',
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                        })
                        .done(function(res) {
                            $('.submit').button('reset');
                            if (!res.success) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                                return;
                            }
                            custom_noty('success', res.message);
                            $location.path('/inward-parts-indent/view/' + $scope.job_order_id);

                            $scope.$apply();
                        })
                        .fail(function(xhr) {
                            $('.submit').button('reset');
                            custom_noty('error', 'Something went wrong at server');
                        });
                }
            });
        }

        $scope.searchVendor = function(query) {
            return new Promise(function(resolve, reject) {
                VendorSvc.options({ filter: { search: query } })
                    .then(function(response) {
                        resolve(response.data.options);
                    });
            });
        }
        $scope.calculateTotal = function() {
            if ($scope.issue_part.quantity != '' && $scope.issue_part.unit_price != '') {
                $scope.issue_part.total = parseInt($scope.issue_part.quantity) * parseFloat($scope.issue_part.unit_price);
            }
        }
        $scope.calculateTax = function() {
            $scope.issue_part.tax_amount = parseFloat($scope.issue_part.total) * (parseFloat($scope.issue_part.tax_percentage) / 100);
            $scope.issue_part.total_amount = parseFloat($scope.issue_part.total) + parseFloat($scope.issue_part.tax_amount);
            $scope.issue_part.po_amount = $scope.issue_part.total_amount;
        }
    }
});