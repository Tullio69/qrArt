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
.directive('hmDrag', function() {
    return {
        restrict: 'A',
        scope: {
            dragDirection: '@',    // Direzione ammessa del drag ('left' o 'right')
            onDragEnd: '&',        // Funzione da chiamare quando si raggiunge la massima escursione
        },
        link: function(scope, element) {
            var hammer = new Hammer(element[0]);
            var startX = 0;
            var deltaX = 0;
            var direction = scope.dragDirection;
            var maxDragDistance = 50; // Massima escursione del drag

            // Configura Hammer.js per pan orizzontale
            hammer.get('pan').set({ direction: Hammer.DIRECTION_HORIZONTAL });

            // Disabilita animazioni all'inizio del drag
            hammer.on('panstart', function(e) {
                startX = e.center.x;
                element.addClass('no-animation'); // Disattiva le animazioni durante il drag
            });

            hammer.on('panmove', function(e) {
                deltaX = e.center.x - startX;

                // Limita il movimento alla massima escursione
                if (Math.abs(deltaX) > maxDragDistance) {
                    deltaX = maxDragDistance * Math.sign(deltaX);
                }

                // Verifica se il drag è nella direzione permessa (se specificata)
                if (direction === 'left' && deltaX > 0) {
                    deltaX = 0; // Blocco del drag verso destra
                } else if (direction === 'right' && deltaX < 0) {
                    deltaX = 0; // Blocco del drag verso sinistra
                }

                element.css({
                    transform: 'translateX(' + deltaX + 'px)'
                });
            });

            hammer.on('panend', function() {
                // Ripristina la posizione e rimuovi la classe no-animation
                element.css({
                    transform: 'translateX(0px)',
                    transition: 'transform 0.3s ease'  // Ripristina la transizione
                });
                element.removeClass('no-animation'); // Riattiva le animazioni dopo il drag

                // Chiamata della funzione al termine del drag
                if (Math.abs(deltaX) >= maxDragDistance) {
                    scope.$apply(scope.onDragEnd);
                }
            });
        }
    };
});






