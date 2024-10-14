// Definizione del modulo principale dell'applicazione
var app = angular.module('phoneApp', ['ngRoute'])
    // Configurazione del routing
    .config(['$routeProvider', function($routeProvider) {
        $routeProvider
            .when('/home', {
                templateUrl: 'views/home.html',
                controller: 'HomeController'
            })
            .when('/audio', {
                templateUrl: 'views/audio.html',
                controller: 'AudioController'
            })
            .when('/phone', {
                templateUrl: 'views/home.html',
                controller: 'HomeController'
            })
            .when('/video', {
                templateUrl: 'views/video.html',
                controller: 'HomeController'
            })
            .when('/html', {
                templateUrl: 'views/html.html',
                controller: 'HomeController'
            })
            .otherwise({
                redirectTo: '/home'
            });
    }])

    // Controller esemplificativo
    .controller('HomeController', ['$scope', function($scope) {
        $scope.message = "Welcome to the Phone App!";
    }])
    .controller('AudioController', ['$scope', function($scope) {
        $scope.message = "Welcome to Audio Controller";
    }])
    .controller('PhoneController', ['$scope', function($scope) {
        $scope.message = "Welcome to Phone Controller";
    }]).factory('FullscreenService', [function() {
    var service = {
        enterFullscreen: function(element) {
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        },
        exitFullscreen: function() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    };
    return service;
}])
    .directive('hmSwipe', function() {
        return {
            restrict: 'A',
            link: function(scope, element, attrs) {
                var hammer = new Hammer(element[0]);
                hammer.on('swipeleft swiperight', function(ev) {
                    scope.$apply(function() {
                        if (attrs.hmSwipe) {
                            scope.$eval(attrs.hmSwipe, { $event: ev });
                        }
                    });
                });
            }
        };
    })
    .directive('hmDrag', ['$timeout', function($timeout) {
        return {
            restrict: 'A',
            link: function(scope, element, attrs) {
                var hammer = new Hammer(element[0]);
                var startX, initialPosX = 0;

                hammer.get('pan').set({ direction: Hammer.DIRECTION_HORIZONTAL });

                element.addClass('button'); // Assicura che la classe base sia applicata

                hammer.on('panstart', function(ev) {
                    element.addClass('no-animation'); // Disabilita le animazioni durante il drag
                    startX = ev.center.x;
                    element.css('transition', 'none'); // Rimuove transizioni per migliorare la reattività del drag
                });

                hammer.on('panmove', function(ev) {
                    var deltaX = ev.center.x - startX;
                    var newPositionX = initialPosX + deltaX;
                    element.css({
                        transform: 'translateX(' + newPositionX + 'px)'
                    });
                });

                hammer.on('panend', function(ev) {
                    initialPosX += ev.deltaX;
                    element.css({
                        transform: 'translateX(' + initialPosX + 'px)',
                        transition: 'transform 0.3s ease' // Riapplica transizione
                    });
                    $timeout(function() {
                        element.removeClass('no-animation'); // Riabilita animazioni dopo un ritardo
                    }, 300); // Ritardo per prevenire sovrapposizioni
                });
            }
        };
    }]);

