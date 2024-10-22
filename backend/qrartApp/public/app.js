// Definizione del modulo principale dell'applicazione
var app = angular.module('phoneApp', ['ngRoute','ngSanitize'])
    // Configurazione del routing
    .config(['$routeProvider', function($routeProvider) {
        $routeProvider
            .when('/home', {
                templateUrl: 'views/home.html',
                controller: 'HomeController'
            })
            .when('/editor', {
                templateUrl: 'views/contentEditorForm.html',
                controller: 'FormController',
                controllerAs: 'vm'
            })
            .when('/audio', {
                templateUrl: 'views/audio.html',
                controller: 'AudioController'
            })
            .when('/phone', {
                templateUrl: 'views/phone.html',
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
    .controller('HomeController', ['$scope', function($scope , $http) {
        $scope.editorContent = '<p>Contenuto iniziale</p>';
        $scope.handleContentChange = function(newContent) {
            console.log('Il contenuto è cambiato:', newContent);
            // Fai qualcosa con il nuovo contenuto
        };
    }])
    .controller('FormController', ['$http', '$scope', FormController])
    .factory('FullscreenService', [function() {
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
})

// Definizione del modulo principale dell'applicazione
var app = angular.module('phoneApp', ['ngRoute','ngSanitize'])
    // Configurazione del routing
    .config(['$routeProvider', function($routeProvider) {
        $routeProvider
            .when('/home', {
                templateUrl: 'views/home.html',
                controller: 'HomeController'
            })
            .when('/editor', {
                templateUrl: 'views/contentEditorForm.html',
                controller: 'FormController',
                controllerAs: 'vm'
            })
            .when('/audio', {
                templateUrl: 'views/audio.html',
                controller: 'AudioController'
            })
            .when('/phone', {
                templateUrl: 'views/phone.html',
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
    .controller('HomeController', ['$scope', function($scope , $http) {
        $scope.editorContent = '<p>Contenuto iniziale</p>';
        $scope.handleContentChange = function(newContent) {
            console.log('Il contenuto è cambiato:', newContent);
            // Fai qualcosa con il nuovo contenuto
        };
    }])
    .controller('FormController', ['$http', '$scope', FormController])
    .factory('FullscreenService', [function() {
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
    })

function FormController($http, $scope) {
    var vm = this;

    vm.formData = {
        callerTitle: '',
        callerSubtitle: '',
        contentType: '',
        languageVariants: []
    };
    console.log("SUCA");
    vm.addLanguageVariant = function() {
        console.log("SUCA");
        vm.formData.languageVariants.push({
            language: '',
            textOnly: false,
            description: '',
            minimized: false
        });
    };

    vm.removeLanguageVariant = function(index) {
        vm.formData.languageVariants.splice(index, 1);
    };

    vm.handleEditorChange = function(content, index) {
        vm.formData.languageVariants[index].description = content;
    };

    vm.toggleVariant = function(index) {
        vm.formData.languageVariants[index].minimized = !vm.formData.languageVariants[index].minimized;
    };
    vm.toggleEditor = function(index) {
        vm.formData.languageVariants[index].editorMinimized = !vm.formData.languageVariants[index].editorMinimized;
    };

    vm.getLanguageName = function(languageCode) {
        const languageMap = {
            'it': 'Italiano',
            'en': 'Inglese',
            'sv': 'Svedese',
            'de': 'Tedesco'
        };
        return languageMap[languageCode] || '';
    };

    vm.submitForm = function() {
        var formData = new FormData();

        // Append main form data
        formData.append('callerTitle', vm.formData.callerTitle);
        formData.append('callerSubtitle', vm.formData.callerSubtitle);
        formData.append('contentType', vm.formData.contentType);

        // Append language variants
        vm.formData.languageVariants.forEach((variant, index) => {
            formData.append(`languageVariants[${index}][language]`, variant.language);
            formData.append(`languageVariants[${index}][textOnly]`, variant.textOnly);
            formData.append(`languageVariants[${index}][description]`, variant.description);

            // Append files if present
            var fileInput;
            if (vm.formData.contentType === 'audio' || vm.formData.contentType === 'audio_call') {
                fileInput = document.getElementById(`audioFile-${index}`);
                if (fileInput && fileInput.files[0]) {
                    formData.append(`languageVariants[${index}][audioFile]`, fileInput.files[0]);
                }
            }
            if (vm.formData.contentType === 'video' || vm.formData.contentType === 'video_call') {
                fileInput = document.getElementById(`videoFile-${index}`);
                if (fileInput && fileInput.files[0]) {
                    formData.append(`languageVariants[${index}][videoFile]`, fileInput.files[0]);
                }
            }
        });

        // Append common files (caller background and avatar)
        var backgroundInput = document.getElementById('callerBackground');
        var avatarInput = document.getElementById('callerAvatar');
        if (backgroundInput && backgroundInput.files[0]) {
            formData.append('callerBackground', backgroundInput.files[0]);
        }
        if (avatarInput && avatarInput.files[0]) {
            formData.append('callerAvatar', avatarInput.files[0]);
        }

        $http({
            method: 'POST',
            url: '/api/qrart/process', // Updated to match our new API endpoint
            data: formData,
            headers: {'Content-Type': undefined},
            transformRequest: angular.identity
        }).then(function successCallback(response) {
            if (response.data.success) {
                console.log('Dati salvati con successo:', response.data);
                alert('Dati salvati con successo!');
            } else {
                console.error('Errore nel salvataggio dei dati:', response.data.message);
                alert('Errore nel salvataggio dei dati: ' + response.data.message);
            }
        }, function errorCallback(response) {
            console.error('Errore nella richiesta:', response);
            var errorMessage = response.data && response.data.message
                ? response.data.message
                : 'Si è verificato un errore imprevisto. Per favore, riprova.';
            alert('Errore nel salvataggio dei dati: ' + errorMessage);
        });
    };
}







