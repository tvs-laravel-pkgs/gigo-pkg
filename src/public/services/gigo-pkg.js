app.factory('ComplaintGroupSvc', function(RequestSvc) {

    var model = 'complaint-group';

    return {
        index: function(params) {
            return RequestSvc.get('/api/' + model + '/index', params);
        },
        read: function(id) {
            return RequestSvc.get('/api/' + model + '/read/' + id);
        },
        save: function(params) {
            return RequestSvc.post('/api/' + model + '/save', params);
        },
        saveFromNgData: function(params) {
            return RequestSvc.post('/api/' + model + '/save-from-ng-data', params);
        },
        remove: function(params) {
            return RequestSvc.post('api/' + model + '/delete', params);
        },
        options: function(params) {
            return RequestSvc.get('/api/' + model + '/options', params);
        },
    };

});

app.factory('WjorRepairOrderSvc', function(RequestSvc) {

    var model = 'wjor-repair-order';

    return {
        index: function(params) {
            return RequestSvc.get('/api/' + model + '/index', params);
        },
        read: function(id) {
            return RequestSvc.get('/api/' + model + '/read/' + id);
        },
        save: function(params) {
            return RequestSvc.post('/api/' + model + '/save', params);
        },
        saveFromNgData: function(params) {
            return RequestSvc.post('/api/' + model + '/save-from-ng-data', params);
        },
        remove: function(params) {
            return RequestSvc.post('api/' + model + '/delete', params);
        },
        options: function(params) {
            return RequestSvc.get('/api/' + model + '/options', params);
        },
    };

});

app.factory('WjorPartSvc', function(RequestSvc) {

    var model = 'wjor-part';

    return {
        index: function(params) {
            return RequestSvc.get('/api/' + model + '/index', params);
        },
        read: function(id) {
            return RequestSvc.get('/api/' + model + '/read/' + id);
        },
        save: function(params) {
            return RequestSvc.post('/api/' + model + '/save', params);
        },
        saveFromNgData: function(params) {
            return RequestSvc.post('/api/' + model + '/save-from-ng-data', params);
        },
        remove: function(params) {
            return RequestSvc.post('api/' + model + '/delete', params);
        },
        options: function(params) {
            return RequestSvc.get('/api/' + model + '/options', params);
        },
    };

});

app.factory('RepairOrderSvc', function(RequestSvc) {

    var model = 'repair-order';

    return {
        index: function(params) {
            return RequestSvc.get('/api/' + model + '/index', params);
        },
        read: function(id) {
            return RequestSvc.get('/api/' + model + '/read/' + id);
        },
        save: function(params) {
            return RequestSvc.post('/api/' + model + '/save', params);
        },
        saveIt: function(params) {
            return RequestSvc.post('/api/' + model + '/save-it', params);
        },
        remove: function(params) {
            return RequestSvc.post('api/' + model + '/delete', params);
        },
        options: function(params) {
            return RequestSvc.get('/api/' + model + '/options', params);
        },
    };

});

app.factory('SplitOrderTypeSvc', function(RequestSvc) {

    var model = 'split-order-type';

    return {
        index: function(params) {
            return RequestSvc.get('/api/' + model + '/index', params);
        },
        read: function(id) {
            return RequestSvc.get('/api/' + model + '/read/' + id);
        },
        save: function(params) {
            return RequestSvc.post('/api/' + model + '/save', params);
        },
        saveIt: function(params) {
            return RequestSvc.post('/api/' + model + '/save-it', params);
        },
        remove: function(params) {
            return RequestSvc.post('api/' + model + '/delete', params);
        },
        options: function(params) {
            return RequestSvc.get('/api/' + model + '/options', params);
        },
    };

});



app.factory('WarrantyJobOrderRequestSvc', function($q, RequestSvc, $rootScope, $ngBootbox) {

    var model = 'warranty-job-order-request';

    function sendToApproval(params) {
        return RequestSvc.post('/api/' + model + '/send-to-approval', params);
    }

    return {
        index: function(params) {
            return RequestSvc.get('/api/' + model + '/index', params);
        },
        read: function(id) {
            return RequestSvc.get('/api/' + model + '/read/' + id);
        },
        save: function(params) {
            return RequestSvc.post('/api/' + model + '/save', params);
        },
        saveIt: function(params) {
            return RequestSvc.post('/api/' + model + '/save-it', params);
        },
        remove: function(params) {
            return RequestSvc.post('/api/' + model + '/remove', params);
        },
        options: function(params) {
            return RequestSvc.get('/api/' + model + '/options', params);
        },
        sendToApproval: sendToApproval,
        approve: function(params) {
            return RequestSvc.post('/api/' + model + '/approve', params);
        },
        reject: function(params) {
            return RequestSvc.post('/api/' + model + '/reject', params);
        },
        confirmSendToApproval: function(warranty_job_order_request) {
            var deferred = $q.defer();

            $ngBootbox.confirm({
                    message: 'Are you sure you want to send to approval?',
                    title: 'Confirm',
                    size: "small",
                    className: 'text-center',
                })
                .then(function() {
                    $rootScope.loading = true;
                    sendToApproval(warranty_job_order_request)
                        .then(function(response) {
                            $rootScope.loading = false;
                            if (!response.data.success) {
                                showErrorNoty(response.data);
                                return;
                            }
                            showNoty('success', 'Warranty job order request initiated successfully');
                            deferred.resolve(response.data.warranty_job_order_request.status);

                            // warranty_job_order_request.status = response.data.warranty_job_order_request.status;
                        });
                });
        },
        list: function(params) { // table list
            return RequestSvc.get('/api/' + model + '/list', params);
        },
        outlets: function(params) { // outlets
            return RequestSvc.post('/api/' + model + '/outlets', params);
        },
        getFormData: function(params) { // getFormData
            return RequestSvc.post('/api/' + model + '/get-form-data', params);
        },
        getTempData: function(params) { // getTempData
            return RequestSvc.post('/api/' + model + '/get-temp-data', params);
        },
    };

});

app.factory('VehiclePrimaryApplicationSvc', function(RequestSvc) {

    var model = 'vehicle-primary-application';

    function index(params) {
        return RequestSvc.get('/api/' + model + '/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/' + model + '/read/' + id);
    }

    function save(params) {
        return RequestSvc.post('/api/' + model + '/save', params);
    }

    function remove(params) {
        return RequestSvc.post('api/' + model + '/delete', params);
    }

    function options(params) {
        return RequestSvc.get('/api/' + model + '/options', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        remove: remove,
        options: options,
    };

});

app.factory('VehicleSecondaryApplicationSvc', function(RequestSvc) {

    var model = 'vehicle-secondary-application';

    function index(params) {
        return RequestSvc.get('/api/' + model + '/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/' + model + '/read/' + id);
    }

    function save(params) {
        return RequestSvc.post('/api/' + model + '/save', params);
    }

    function remove(params) {
        return RequestSvc.post('api/' + model + '/delete', params);
    }

    function options(params) {
        return RequestSvc.get('/api/' + model + '/options', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        remove: remove,
        options: options,
    };

});

app.factory('PartSupplierSvc', function(RequestSvc) {

    var model = 'part-supplier';

    function index(params) {
        return RequestSvc.get('/api/' + model + '/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/' + model + '/read/' + id);
    }

    function save(params) {
        return RequestSvc.post('/api/' + model + '/save', params);
    }

    function remove(params) {
        return RequestSvc.post('api/' + model + '/delete', params);
    }

    function options(params) {
        return RequestSvc.get('/api/' + model + '/options', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        remove: remove,
        options: options,
    };

});

app.factory('ComplaintSvc', function(RequestSvc) {

    var model = 'complaint';

    function index(params) {
        return RequestSvc.get('/api/' + model + '/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/' + model + '/read/' + id);
    }

    function save(params) {
        return RequestSvc.post('/api/' + model + '/save', params);
    }

    function remove(params) {
        return RequestSvc.post('api/' + model + '/delete', params);
    }

    function options(params) {
        return RequestSvc.get('/api/' + model + '/options', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        remove: remove,
        options: options,
    };

});

app.factory('FaultSvc', function(RequestSvc) {

    var model = 'fault';

    function index(params) {
        return RequestSvc.get('/api/' + model + '/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/' + model + '/read/' + id);
    }

    function save(params) {
        return RequestSvc.post('/api/' + model + '/save', params);
    }

    function remove(params) {
        return RequestSvc.post('api/' + model + '/delete', params);
    }

    function options(params) {
        return RequestSvc.get('/api/' + model + '/options', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        remove: remove,
        options: options,
    };

});

app.factory('ServiceTypeSvc', function(RequestSvc) {

    function index(params) {
        return RequestSvc.get('/api/service-type/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/service-type/read/' + id);
    }

    function save(params) {
        return RequestSvc.post('/api/service-type/save', params);
    }

    function remove(params) {
        return RequestSvc.post('api/service-type/delete', params);
    }

    function options(params) {
        return RequestSvc.get('/api/service-type/options', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        remove: remove,
        options: options,
    };

});

app.factory('JobOrderSvc', function(RequestSvc) {

    function index(params) {
        return RequestSvc.get('/api/job-order/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/job-order/read/' + id);
    }

    function save(params) {
        return RequestSvc.post('/api/job-order/save', params);
    }

    function options(params) {
        return RequestSvc.get('/api/job-order/options', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        options: options,
    };

});

app.factory('PartSvc', function(RequestSvc) {

    var model = 'part';

    return {
        index: function(params) {
            return RequestSvc.get('/api/' + model + '/index', params);
        },
        read: function(id) {
            return RequestSvc.get('/api/' + model + '/read/' + id);
        },
        save: function(params) {
            return RequestSvc.post('/api/' + model + '/save', params);
        },
        saveFromNgData: function(params) {
            return RequestSvc.post('/api/' + model + '/save-from-ng-data', params);
        },
        remove: function(params) {
            return RequestSvc.post('api/' + model + '/delete', params);
        },
        options: function(params) {
            return RequestSvc.get('/api/' + model + '/options', params);
        },
    };

});
app.factory('VehicleServiceScheduleSvc', function(RequestSvc) {

    var model = 'vehicle-service-schedule';

    function index(params) {
        return RequestSvc.get('/api/' + model + '/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/' + model + '/read/' + id + '/withtrashed');
    }

    function save(params) {
        return RequestSvc.post('/api/' + model + '/save', params);
    }

    function remove(params) {
        return RequestSvc.get('/api/' + model + '/remove', params);
    }

    function options(params) {
        return RequestSvc.get('/api/' + model + '/options', params);
    }

    function list(params) {
        return RequestSvc.get('/api/' + model + '/list', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        remove: remove,
        options: options,
        list: list,
    };

});
app.factory('VendorSvc', function(RequestSvc) {

    var model = 'vendor';

    function index(params) {
        return RequestSvc.get('/api/' + model + '/index', params);
    }

    function read(id) {
        return RequestSvc.get('/api/' + model + '/read/' + id + '/withtrashed');
    }

    function save(params) {
        return RequestSvc.post('/api/' + model + '/save', params);
    }

    function remove(params) {
        return RequestSvc.get('/api/' + model + '/remove', params);
    }

    function options(params) {
        return RequestSvc.get('/api/' + model + '/options', params);
    }

    function list(params) {
        return RequestSvc.get('/api/' + model + '/list', params);
    }

    return {
        index: index,
        read: read,
        save: save,
        remove: remove,
        options: options,
        list: list,
    };

});
/*

app.factory('VehicleServiceScheduleServiceTypeSvc', function(RequestSvc) {

    var model = 'vehicle-service-schedule-service-type';

    function read(id) {
        return RequestSvc.get('/api/' + model + '/read/' + id);
    }

    return {
        read: read,
    };

});*/