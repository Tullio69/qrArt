// Definizione del modulo principale dell'applicazione
var app = angular.module('phoneApp', ['ngRoute','ngSanitize'])
    // Configurazione del routing
    .config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {

        // Abilita il routing HTML5
        $locationProvider.html5Mode(true);
        $routeProvider

            .when('/', {
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


    }])
    // Controller esemplificativo
    .controller('HomeController', ['$scope', '$http', '$timeout', function($scope , $http, $timeout) {
        $scope.currentYear = new Date().getFullYear();
        $scope.email = '';
        $scope.navItems = [
            { label: 'Home', url: '#' },
            { label: 'Funzionalità', url: '#features' },
            { label: 'Prezzi', url: '#pricing' },
            { label: 'Contatti', url: '#contact' }
        ];
        $scope.subscribe = function() {
            console.log('Iscrizione con email:', $scope.email);
            $scope.email = '';
        };
        $scope.activeTab = 'audio';
        $scope.tabs = [
            {
                value: 'audio',
                label: 'Audio',
                title: 'Codici QR Audio',
                description: 'Crea codici QR che riproducono audio quando scansionati.',
                icon: 'file-audio',
                content: 'Incorpora messaggi vocali, musica o qualsiasi contenuto audio direttamente nei tuoi codici QR.'
            },
            {
                value: 'video',
                label: 'Video',
                title: 'Codici QR Video',
                description: 'Incorpora video nei tuoi codici QR per un\'esperienza visiva ricca.',
                icon: 'video',
                content: 'Condividi demo di prodotti, tutorial o video promozionali attraverso i codici QR.'
            },
            {
                value: 'call',
                label: 'Simulazione Chiamata',
                title: 'Simulazione Chiamata',
                description: 'Crea simulazioni di chiamate interattive con i codici QR.',
                icon: 'phone',
                content: 'Simula chiamate telefoniche per scopi di formazione, marketing o intrattenimento.'
            },
            {
                value: 'text',
                label: 'Testo',
                title: 'Codici QR Testuali',
                description: 'Crea codici QR con ricco contenuto testuale.',
                icon: 'file-text',
                content: 'Condividi informazioni dettagliate, storie o istruzioni attraverso codici QR basati su testo.'
            }
        ];

        $scope.setActiveTab = function(tabValue) {
            $scope.activeTab = tabValue;
        };

        $timeout(function() {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }, 100);

    }])
    .controller('TabsController', function($scope) {

    })
    .controller('FormController', ['$http', '$scope', FormController])
    .controller('ThemeTestController', function($scope) {
        $scope.title = "Verifica il tuo tema Tailwind";
        $scope.description = "Questo componente utilizza classi Tailwind e Flowbite per testare se il tuo tema è configurato correttamente.";
        $scope.linkText = "Scopri di più";
    })
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
    .directive('btn', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('px-4 py-2 rounded-lg font-medium focus:outline-none focus:ring-2 focus:ring-offset-2');
            }
        };
    })
    .directive('btnPrimary', function () {
    return {
        restrict: 'C',
        link: function (scope, element, attrs) {
            element.addClass('btn bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500');
        }
    };
})
    .directive('btnGhost', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('btn bg-transparent hover:bg-primary-100 focus:ring-primary-500');
            }
        };
    })
    .directive('btnLg', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('px-6 py-3 text-lg');
            }
        };
    })
    .directive('btnOutline', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('btn border border-primary-600 text-primary-600 hover:bg-primary-50 focus:ring-primary-500');
            }
        };
    })
    .directive('input', function () {
        return {
            restrict: 'E',
            link: function (scope, element, attrs) {
                element.addClass('px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500');
            }
        };
    })
    .directive('card', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('bg-white shadow-md rounded-lg overflow-hidden');
            }
        };
    })
    .directive('cardHeader', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('px-4 py-5 border-b border-gray-200');
            }
        };
    })
    .directive('cardContent', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('px-4 py-5');
            }
        };
    })
    .directive('cardTitle', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('text-lg font-medium text-gray-900');
            }
        };
    })
    .directive('cardDescription', function () {
        return {
            restrict: 'C',
            link: function (scope, element, attrs) {
                element.addClass('mt-1 text-sm text-gray-600');
            }
        };
    })

function FormController($http, $scope) {
    var vm = this;

    vm.formData = {
        callerName: '',
        callerTitle: '',
        contentName: '',
        contentType: '',
        languageVariants: []
    };

    vm.addLanguageVariant = function() {
        vm.formData.languageVariants.push({
            language: '',
            textOnly: false,
            description: '',
            minimized: false,
            editorMinimized: false
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
        formData.append('callerName', vm.formData.callerName);
        formData.append('callerTitle', vm.formData.callerTitle);
        formData.append('contentName', vm.formData.contentName);
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
            if (vm.formData.contentType === 'audio_call' || vm.formData.contentType === 'video_call') {
                backgroundInput = document.getElementById(`callerBackground`);
                avatarInput = document.getElementById(`callerAvatar`);

                if (backgroundInput && backgroundInput.files[0]) {
                    formData.append(`callerBackground`, backgroundInput.files[0]);
                }
                if (avatarInput && avatarInput.files[0]) {
                    formData.append(`callerAvatar`, avatarInput.files[0]);
                }
            }
        });

        $http({
            method: 'POST',
            url: '/api/qrart/process',
            data: formData,
            headers: {'Content-Type': undefined},
            transformRequest: angular.identity
        }).then(function successCallback(response) {
            if (response.data.success) {
                console.log('Dati salvati con successo:', response.data);
                alert('Dati salvati con successo!');
            } else {
                console.error('Errore nel salvataggio dei dati:', response.data.message);

            }
        }, function errorCallback(response) {
            console.error('Errore nella richiesta:', response);
            var errorMessage = response.data && response.data.message
                ? response.data.message
                : 'Si è verificato un errore imprevisto. Per favore, riprova.';

        });
    };
}

// Inizializza le icone Lucide dopo che AngularJS ha finito di renderizzare la vista







