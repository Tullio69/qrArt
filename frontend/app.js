// Definizione del modulo principale dell'applicazione
angular.module('phoneApp', ['ngRoute'])

    // Configurazione del routing
    .config(['$routeProvider', function($routeProvider) {
        $routeProvider
            .when('/home', {
                templateUrl: 'views/home.html',
                controller: 'HomeController'
            })
            .when('/phone', {
                templateUrl: 'views/home.html',
                controller: 'PhoneController'
            })
            .when('/audio', {
                templateUrl: 'views/audio.html',
                controller: 'AudioController'
            })
            .when('/video', {
                templateUrl: 'views/video.html',
                controller: 'VideoController'
            })
            .when('/html', {
                templateUrl: 'views/html.html',
                controller: 'HtmlController'
            })
            .otherwise({
                redirectTo: '/home'
            });
    }])

    // Controller esemplificativo
    .controller('HomeController', ['$scope', function($scope) {
        $scope.message = "Welcome to the Phone App!";
    }]);

// Altri controller possono essere definiti qui o in file separati
