app.component('inwardPartsIndentView', {
    templateUrl: inward_parts_indent_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, PartSvc) {
        //for md-select search
        /*$(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });*/

        $scope.showPartForm = function() {
            $scope.part_modal_action = 'Add';
            // $scope.part_modal_action = part_index === false ? 'Add' : 'Edit';
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

        return false;
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        self.angular_routes = angular_routes;

        HelperService.isLoggedIn();
        self.user = $scope.user = HelperService.getLoggedUser();

    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------