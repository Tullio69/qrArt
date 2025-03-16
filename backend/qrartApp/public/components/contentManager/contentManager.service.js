angular.module('contentManager')
    .service('ContentService', ['$http', function ($http) {
        var baseUrl = '/api/content'; // Endpoint REST

        this.getContents = function () {
            return $http.get(baseUrl);
        };

        this.updateContent = function (content) {
            return $http.put(baseUrl + '/' + content.id, content);
        };

        this.deleteContent = function (contentId) {
            return $http.delete(baseUrl + '/' + contentId);
        };
    }]);
