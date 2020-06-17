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