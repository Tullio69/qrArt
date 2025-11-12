// Definizione del modulo principale dell'applicazione
var app = angular.module('phoneApp', ['ngRoute','ngSanitize','ui.bootstrap'])
    // Configurazione del routing
    .config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {

        // Abilita il routing HTML5
        $locationProvider.html5Mode(true);
        $routeProvider

            .when('/', {
                templateUrl: 'views/home.html',
                controller: 'HomeController'
            })
            .when('/content/:id', {
                templateUrl: 'views/contentViewer.html',
                controller: 'ContentViewerController'
            })
            .when('/editor', {
                templateUrl: 'views/contentEditorForm.html',
                controller: 'FormController',
                controllerAs: 'vm'
            })
            .when('/content-manager', {
                templateUrl: 'views/contentList.html',
                controller: 'ContentListController'
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
    .controller('ContentViewerController', ['$scope', '$routeParams','$sce', '$http','FullscreenService','ContentService', function($scope, $routeParams, $http,$sce,FullscreenService,ContentService) {
        var contentId = $routeParams.id;
        $scope.language_selected=false;
        $scope.contentid=contentId;
        $scope.content = null;
        $scope.loading = true;
        $scope.error = null;
        $scope.callerAvatar = null;
        $scope.callerBackground = null;

        ContentService.getContent(contentId)
            .then(function(data) {
                $scope.content = data.content;
                $scope.loading = false;
                $scope.callerAvatar = $scope.getFileByType('callerAvatar');
                $scope.callerBackground = $scope.getFileByType('callerBackground');
                $scope.preloadImages();
            })
            .catch(function(error) {
                $scope.loading = false;
                if (error.error && error.htmlContent) {
                    $scope.error = 'Errore nel caricamento del contenuto';
                                } else {
                    $scope.error = 'Errore nel caricamento del contenuto';
                    console.log('Errore:', error);
                }
            });

        $scope.selectLanguage = function(metadata, filterTextOnly) {

            // Qui dobbiamo aggiungere la logica per il caricamento del contenuto corrispondente al linguaggio selezionato
            // con la variante textOnly corrispondente al valore di filterTextOnly

            var callerAvatar = $scope.getFileByType('callerAvatar');
            var callerBackground = $scope.getFileByType('callerBackground');

            $scope.selContent = {
                caller_name: $scope.content.caller_name,
                caller_subtitle: $scope.content.caller_title,
                caller_avatar: callerAvatar ? callerAvatar.file_url : null,
                caller_background: callerBackground ? callerBackground.file_url : null,
                file_type: metadata.file_type,
                text_only: filterTextOnly,
                content_name:metadata.content_name,
                content_url:metadata.file_url,
                content_type:$scope.content.content_type,
                content_language:metadata.language,
                call_state:'incoming'
                            }
            $scope.language_selected=true;
            $scope.goFullscreen();
        }

        $scope.getFileByType = function(fileType) {
            if (!$scope.content || !$scope.content.common_files) {
                console.warn('common_files non disponibile');
                return null;
            }

            return $scope.content.common_files.find(function(file) {
                return file.file_type === fileType;
            });
        };

        $scope.goFullscreen = function() {
            var element = document.documentElement; // Prende l'elemento radice per passare a pieno schermo
            FullscreenService.enterFullscreen(element);
        };

        $scope.exitFullscreen = function() {
            FullscreenService.exitFullscreen();
        };

        $scope.preloadImages = function() {
            if ($scope.callerAvatar) {
                var avatarImg = new Image();
                $scope.avatarImgSrc = 'media/'+$scope.callerAvatar.file_url;
            }
            if ($scope.callerBackground) {
                var bgImg = new Image();
                $scope.bgImgSrc = 'media/'+$scope.callerBackground.file_url;
            }
        };

    }])
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
    .controller('ContentListController', [
        '$scope', '$http', '$uibModal','$timeout',
        function ($scope, $http, $uibModal,$timeout) {
            $scope.contents = [];
            $scope.loading = true;
            $scope.error = null;
            $scope.searchQuery = '';
            $scope.expandedContent = null; // ID del contenuto espanso

            function groupByLanguage(files, metadata) {
                const grouped = {};

                files.forEach(file => {
                    if (!grouped[file.language]) grouped[file.language] = {};
                    grouped[file.language].file = file;
                });

                metadata.forEach(meta => {
                    if (!grouped[meta.language]) grouped[meta.language] = {};
                    grouped[meta.language].metadata = meta;
                });

                return grouped;
            }
            function groupByMetadata(files, metadata) {
                const perLanguage = {};
                const globalFiles = [];

                files.forEach(file => {
                    if (!file.metadata_id) {
                        globalFiles.push(file);
                    }
                });

                metadata.forEach(meta => {
                    const lang = meta.language;
                    perLanguage[lang] = {
                        metadata: meta,
                        files: {}
                    };

                    // File collegati a questa lingua
                    const relatedFiles = files.filter(f => f.metadata_id == meta.id);

                    relatedFiles.forEach(f => {
                        const type = f.file_type.toLowerCase(); // 🔽 normalizza
                        perLanguage[lang].files[type] = f;
                    });
                });

                return { perLanguage, globalFiles };
            }

            // Carica la lista dei contenuti
            $scope.loadContents = function() {
                $scope.loading = true;
                $http.get('/api/content/getlist')
                    .then(function(response) {
                        $scope.contents = response.data.data;
                        $scope.loading = false;
                    })
                    .catch(function(error) {
                        $scope.error = 'Errore nel caricamento dei contenuti';
                        $scope.loading = false;
                        console.error('Errore:', error);
                    });
            };

            $scope.expandedContentId = null;

            $scope.base_url = window.BASE_URL;

            $scope.toggleDetails = function (content) {
                if ($scope.expandedContentId == content.id) {
                    $scope.expandedContentId = null;
                } else {
                    $scope.expandedContentId = content.id;

                    $http.get($scope.base_url + '/api/content/details/' + content.id)
                        .then(function (response) {
                            const result = groupByMetadata(response.data.files, response.data.metadata);
                            content.files = response.data.files;
                            content.metadata = response.data.metadata;
                            content.perLanguage = result.perLanguage;
                            content.globalFiles = result.globalFiles;
                        });
                }
            };


            $scope.editContent = function (content) {
                alert("Funzione di modifica ancora da implementare per: " + content.caller_name);
            };

            $scope.confirmDelete = function (content) {
                if (confirm(`Sei sicuro di voler eliminare il contenuto "${content.caller_name}"?`)) {
                    $scope.deleteContent(content.id);
                }
            };

            $scope.deleteContent = function (contentId) {
                $scope.loading = true;
                $scope.error = null;

                $http.delete($scope.base_url + '/api/content/delete/' + contentId)
                    .then(function () {
                        $scope.contents = $scope.contents.filter(c => c.id !== contentId);
                    })
                    .catch(function (error) {
                        $scope.error = "Errore durante l'eliminazione del contenuto.";
                        console.error(error);
                    })
                    .finally(function () {
                        $scope.loading = false;
                    });
            };

            $scope.replaceFile = function (file) {
                console.log("🔄 Sostituisci file per ID:", file.id);
                alert("Funzione da implementare per sostituire il file: " + file.file_name);
            };



            $scope.loadContentDetails = function (contentId) {
                if ($scope.expandedContent === contentId) {
                    $scope.expandedContent = null;
                    return;
                }

                $scope.expandedContent = contentId;
                console.log("📡 Caricamento dettagli da API...");

                $http.get('/api/content/details/' + contentId)
                    .then(function (response) {
                        console.log("✅ Dati ricevuti:", response.data);

                        let content = $scope.contents.find(c => c.id === contentId);
                        if (content) {
                            content.files = response.data.files || [];
                            content.metadata = response.data.metadata || [];

                            console.log("📌 Aggiornati content.files:", content.files);
                            console.log("📌 Aggiornati content.metadata:", content.metadata);

                            // 🔹 Forza il refresh dell'UI
                            $scope.$applyAsync();
                        } else {
                            console.warn("⚠️ Contenuto non trovato nella lista!");
                        }
                    })
                    .catch(function (error) {
                        console.error("❌ Errore nel caricamento dei dettagli:", error);
                    });
            };

            // Scarica il QR Code per il contenuto
            $scope.downloadQrCode = function(content) {
                var contentUrl = window.location.origin + '/content/' + content.short_code;

                var qr = new QRCode(document.createElement("div"), {
                    text: contentUrl,
                    width: 256,
                    height: 256
                });

                var link = document.createElement('a');
                link.download = 'qrcode_' + content.short_code + '.png';
                link.href = qr._el.querySelector('canvas').toDataURL();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };

            // Apre il modal per modificare un contenuto
            $scope.openEditModal = function(content) {
                var modalInstance = $uibModal.open({
                    templateUrl: 'views/editContentModal.html',
                    controller: 'EditContentModalController',
                    size: 'lg',
                    resolve: {
                        content: function() {
                            return angular.copy(content);
                        }
                    }
                });

                modalInstance.result.then(function () {
                    $scope.loadContents();
                });
            };

            // Elimina un file associato a un contenuto
            $scope.deleteFile = function (fileId) {
                if (confirm("Sei sicuro di voler eliminare questo file?")) {
                    $http.delete('/api/content/file/' + fileId)
                        .then(function () {
            $scope.loadContents();
                        })
                        .catch(function (error) {
                            console.error("Errore nella rimozione del file:", error);
                        });
                }
            };

            // Elimina un metadato associato a un contenuto
            $scope.deleteMetadata = function (metadataId) {
                if (confirm("Sei sicuro di voler eliminare questo metadato?")) {
                    $http.delete('/api/content/metadata/' + metadataId)
                        .then(function () {
                            $scope.loadContents();
                        })
                        .catch(function (error) {
                            console.error("Errore nella rimozione del metadato:", error);
                        });
                }
            };

            $scope.selectedFile = null;

            $scope.isReplaceFileModalOpen = false;
            $scope.selectedFile = null;

// Aprire il modale con Tailwind
            $scope.openReplaceFileModal = function (file) {
                $scope.selectedFile = file;
                $scope.isReplaceFileModalOpen = true;
            };

// Chiudere il modale
            $scope.closeReplaceFileModal = function () {
                $scope.isReplaceFileModalOpen = false;
            };

// Sostituire il file
            $scope.replaceFile = function (file) {
                alert("🔄 Sostituzione file per: " + file.file_name + " (ID: " + file.id + ")");
            };


            // Inizializza la lista dei contenuti
            $scope.loadContents();

            $scope.modalVisible = false;
            $scope.selectedFileToReplace = null;
            $scope.newFile = null;

            $scope.replaceFile = function (file) {
                $scope.selectedFileToReplace = file;
                $scope.newFile = null;
                $scope.modalVisible = true;
            };

            $scope.confirmReplace = function () {
                if (!$scope.newFile) {
                    alert("Seleziona un file prima di confermare.");
                    return;
                }

                $scope.replacingFile = true;
                $scope.replaceError = null;
                $scope.replaceSuccess = false;

                const formData = new FormData();
                formData.append('file', $scope.newFile);
                formData.append('file_id', $scope.selectedFileToReplace.id);

                $http.post($scope.base_url + '/api/content/replaceFile', formData, {
                    headers: { 'Content-Type': undefined }
                }).then(response => {
                    $scope.replaceSuccess = true;
                    $timeout(function() {
                        $scope.modalVisible = false;
                        $scope.newFile = null;
                        $scope.replacingFile = false;
                        $scope.replaceSuccess = false;
                        // Ricarica i dettagli del contenuto
                        var content = $scope.contents.find(c => c.id === $scope.expandedContentId);
                        if (content) {
                            $scope.toggleDetails(content);
                            $timeout(function() {
                                $scope.toggleDetails(content);
                            }, 100);
                        }
                    }, 1500);
                }).catch(err => {
                    $scope.replaceError = "Errore durante la sostituzione del file: " + (err.data?.message || err.message);
                    $scope.replacingFile = false;
                    console.error(err);
                });
            };

            $scope.closeReplaceModal = function() {
                $scope.modalVisible = false;
                $scope.newFile = null;
                $scope.replacingFile = false;
                $scope.replaceError = null;
                $scope.replaceSuccess = false;
            };

            $scope.handleFileSelect = function(file) {
                $scope.$apply(function() {
                    $scope.newFile = file;
                });
            };

            $scope.getAcceptType = function(fileType) {
                if (!fileType) return '*/*';
                if (fileType === 'audio' || fileType === 'audio_call') return 'audio/*';
                if (fileType === 'video' || fileType === 'video_call') return 'video/*';
                if (fileType === 'callerAvatar' || fileType === 'callerBackground') return 'image/*';
                return '*/*';
            };

            $scope.toggleGlobalFilePreview = function(fileId) {
                var content = $scope.contents.find(c => c.id === $scope.expandedContentId);
                if (!content || !content.globalFiles) return;

                content.globalFiles.forEach(function(file) {
                    if (file.id === fileId) {
                        file.showPreview = !file.showPreview;
                    }
                });
            };

            $scope.toggleLanguageVariantPreview = function(lang, contentId) {
                var content = $scope.contents.find(c => c.id === contentId);
                if (!content || !content.perLanguage || !content.perLanguage[lang]) return;

                content.perLanguage[lang].showPreview = !content.perLanguage[lang].showPreview;
            };

            $scope.toggleHtmlPreview = function(metadataId) {
                var content = $scope.contents.find(c => c.id === $scope.expandedContentId);
                if (!content || !content.perLanguage) return;

                Object.keys(content.perLanguage).forEach(function(lang) {
                    if (content.perLanguage[lang].metadata.id === metadataId) {
                        content.perLanguage[lang].metadata.showHtml = !content.perLanguage[lang].metadata.showHtml;
                    }
                });
            };

            $scope.replaceLanguageVariantFile = function(data, lang) {
                if (!data.files) {
                    alert("Nessun file trovato per questa variante linguistica.");
                    return;
                }

                // Trova il primo file (audio o video)
                var file = data.files.audio || data.files.video;
                if (!file) {
                    alert("Nessun file media trovato per questa variante linguistica.");
                    return;
                }

                // Imposta il file_type in base al tipo di file
                file.file_type = data.files.audio ? 'audio' : 'video';
                $scope.replaceFile(file);
            };

            $scope.getLanguageName = function(languageCode) {
                const languageMap = {
                    'it': 'Italiano',
                    'en': 'Inglese',
                    'es': 'Spagnolo',
                    'fr': 'Francese',
                    'de': 'Tedesco',
                    'sv': 'Svedese'
                };
                return languageMap[languageCode] || languageCode.toUpperCase();
            };

            $scope.copyToClipboard = function(text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function() {
                        $scope.successMessage = 'Short code copiato negli appunti!';
                        $timeout(function() {
                            $scope.successMessage = null;
                        }, 3000);
                    }).catch(function(err) {
                        console.error('Errore nella copia:', err);
                    });
                } else {
                    // Fallback per browser più vecchi
                    var textArea = document.createElement("textarea");
                    textArea.value = text;
                    textArea.style.position = "fixed";
                    textArea.style.top = "-9999px";
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        $scope.successMessage = 'Short code copiato negli appunti!';
                        $timeout(function() {
                            $scope.successMessage = null;
                        }, 3000);
                    } catch (err) {
                        console.error('Errore nella copia:', err);
                    }
                    document.body.removeChild(textArea);
                }
            };

            // Filtri per la ricerca
            $scope.filterContents = function(content) {
                if (!$scope.searchQuery) return true;
                var query = $scope.searchQuery.toLowerCase();
                return (content.caller_name && content.caller_name.toLowerCase().includes(query)) ||
                       (content.caller_title && content.caller_title.toLowerCase().includes(query)) ||
                       (content.short_code && content.short_code.toLowerCase().includes(query));
            };

            $scope.filterByLanguage = function(content) {
                if (!$scope.filterLanguage) return true;
                return content.languages && content.languages.includes($scope.filterLanguage);
            };

            $scope.resetFilters = function() {
                $scope.searchQuery = '';
                $scope.filterType = '';
                $scope.filterLanguage = '';
            };

            // Paginazione
            $scope.currentPage = 1;
            $scope.pageSize = 10;

            $scope.totalPages = function() {
                return Math.ceil($scope.contents.length / $scope.pageSize);
            };

            $scope.nextPage = function() {
                if ($scope.currentPage < $scope.totalPages()) {
                    $scope.currentPage++;
                }
            };

            $scope.previousPage = function() {
                if ($scope.currentPage > 1) {
                    $scope.currentPage--;
                }
            };

            $scope.goToPage = function(page) {
                $scope.currentPage = page;
            };

            $scope.getPageNumbers = function() {
                var total = $scope.totalPages();
                var current = $scope.currentPage;
                var pages = [];
                var start = Math.max(1, current - 2);
                var end = Math.min(total, current + 2);

                for (var i = start; i <= end; i++) {
                    pages.push(i);
                }
                return pages;
            };

            $scope.Math = Math;

        }
    ])
    .controller('EditContentModalController', [
        '$scope',
        '$uibModalInstance',
        '$http',
        'content',
        function($scope, $uibModalInstance, $http, content) {
            $scope.content = angular.copy(content);
            $scope.saving = false;

            // Funzione per gestire il cambio dell'avatar
            $scope.handleAvatarChange = function(file) {
                $scope.$apply(function() {
                    $scope.content.new_avatar = file;
                });
            };

            // Funzione per gestire il cambio del background
            $scope.handleBackgroundChange = function(file) {
                $scope.$apply(function() {
                    $scope.content.new_background = file;
                });
            };

            // Funzione per gestire il cambio del file di una variante
            $scope.handleVariantFileChange = function(file, index) {
                $scope.$apply(function() {
                    $scope.content.metadata[index].new_file = file;
                });
            };

            // Funzione per ottenere il nome della lingua
            $scope.getLanguageName = function(languageCode) {
                const languageMap = {
                    'it': 'Italiano',
                    'en': 'Inglese',
                    'es': 'Spagnolo',
                    'fr': 'Francese',
                    'de': 'Tedesco',
                    'sv': 'Svedese'
                };
                return languageMap[languageCode] || languageCode.toUpperCase();
            };

            $scope.save = function() {
                $scope.saving = true;

                var formData = new FormData();

                formData.append('callerName', $scope.content.caller_name);
                formData.append('callerTitle', $scope.content.caller_title);
                formData.append('contentType', $scope.content.content_type);

                if ($scope.content.new_avatar) {
                    formData.append('callerAvatar', $scope.content.new_avatar);
                }
                if ($scope.content.new_background) {
                    formData.append('callerBackground', $scope.content.new_background);
                }

                var languageVariants = [];
                $scope.content.metadata.forEach(function(variant, index) {
                    var variantData = {
                        language: variant.language,
                        contentName: variant.content_name,
                        textOnly: variant.text_only,
                        htmlContent: variant.html_content
                    };
                    languageVariants.push(variantData);

                    if (variant.new_file) {
                        formData.append('languageVariants[' + index + '][file]', variant.new_file);
                    }
                });

                formData.append('languageVariants', JSON.stringify(languageVariants));

                $http.post('/api/content/update/' + $scope.content.short_code, formData, {
                    transformRequest: angular.identity,
                    headers: {'Content-Type': undefined}
                }).then(function(response) {
                    $uibModalInstance.close(response.data.content);
                }).catch(function(error) {
                    console.error('Errore nel salvataggio:', error);
                    $scope.error = 'Si è verificato un errore durante il salvataggio';
                }).finally(function() {
                    $scope.saving = false;
                });
            };

            $scope.cancel = function() {
                $uibModalInstance.dismiss('cancel');
            };
        }
    ])
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
    .directive('fileModel', ['$parse', function($parse) {
        return {
            restrict: 'A',
            link: function(scope, element, attrs) {
                var model = $parse(attrs.fileModel);
                element.bind('change', function() {
                    scope.$apply(function() {
                        model.assign(scope, element[0].files[0]);
                    });
                });
            }
        };
    }])
    .directive('hmSwipe', ['$timeout', function($timeout) {
        return {
            restrict: 'A',
            link: function(scope, element, attrs) {
                var hammer = new Hammer(element[0]);
                hammer.on('swipeleft swiperight', function(ev) {
                    $timeout(function() {
                        if (attrs.hmSwipe) {
                            scope.$eval(attrs.hmSwipe, { $event: ev });
                        }
                    });
                });
            }
        };
    }])
    .directive('hmDrag', ['$timeout', function($timeout) {
        return {
            restrict: 'A',
            scope: {
                dragDirection: '@',
                onDragEnd: '&',
            },
            link: function(scope, element) {
                var hammer = new Hammer(element[0]);
                var startX = 0;
                var deltaX = 0;
                var direction = scope.dragDirection;
                var maxDragDistance = 50;

                hammer.get('pan').set({ direction: Hammer.DIRECTION_HORIZONTAL });

                hammer.on('panstart', function(e) {
                    startX = e.center.x;
                    element.addClass('no-animation');
                });

                hammer.on('panmove', function(e) {
                    deltaX = e.center.x - startX;

                    if (Math.abs(deltaX) > maxDragDistance) {
                        deltaX = maxDragDistance * Math.sign(deltaX);
                    }

                    if (direction === 'left' && deltaX > 0) {
                        deltaX = 0;
                    } else if (direction === 'right' && deltaX < 0) {
                        deltaX = 0;
                    }

                    element.css({
                        transform: 'translateX(' + deltaX + 'px)'
                    });
                });

                hammer.on('panend', function() {
                    element.css({
                        transform: 'translateX(0px)',
                        transition: 'transform 0.3s ease'
                    });
                    element.removeClass('no-animation');

                    if (Math.abs(deltaX) >= maxDragDistance) {
                        $timeout(function() {
                            scope.onDragEnd();
                        });
                    }
                });
            }
        };
    }])
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
    .factory('ContentService', ['$http', '$q', function($http, $q) {
        return {
            getContent: function(contentId) {
                return $http.get('/api/content/' + contentId, {
                    transformResponse: function(data, headers) {
                        var contentType = headers('Content-Type');
                        if (contentType && contentType.indexOf('application/json') !== -1) {
                            // Risposta JSON normale
                            return angular.fromJson(data);
                        } else {
                            // Risposta HTML (errore 404 o 500)
                            return { error: true, htmlContent: data };
                        }
                    }
                }).then(function(response) {
                    if (response.data.error) {
                        // Se abbiamo ricevuto HTML invece di JSON, consideriamolo un errore
                        return $q.reject({
                            error: true,
                            htmlContent: response.data.htmlContent
                        });
                    }
                    return response.data;
                });
            }
        };
    }])


function FormController($http, $scope) {
    var vm = this;

    // Tab management
    vm.activeTab = 'base';
    vm.isSubmitting = false;

    vm.setActiveTab = function(tab) {
        vm.activeTab = tab;
        // Scroll to top when changing tabs
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    vm.formData = {
        callerName: '',
        callerTitle: '',
        contentName: '',
        contentType: '',
        contentDescription: '',
        callerBackground: null,
        callerAvatar: null,
        languageVariants: [],
        relatedArticles: [],
        sponsors: []
    };

    vm.addLanguageVariant = function() {
        vm.formData.languageVariants.push({
            contentName: '',
            language: '',
            textOnly: false,
            description: '',
            htmlContent: '',
            file: null,
            minimized: false,
            editorMinimized: false
        });
    };

    vm.removeLanguageVariant = function(index) {
        vm.formData.languageVariants.splice(index, 1);
    };

    vm.handleEditorChange = function(content, index) {
        vm.formData.languageVariants[index].htmlContent = content;
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

    // Related Articles management
    vm.addRelatedArticle = function() {
        vm.formData.relatedArticles.push({
            title: '',
            link: ''
        });
    };

    vm.removeRelatedArticle = function(index) {
        vm.formData.relatedArticles.splice(index, 1);
    };

    // Sponsors management
    vm.addSponsor = function() {
        vm.formData.sponsors.push({
            name: '',
            link: '',
            image: null
        });
    };

    vm.removeSponsor = function(index) {
        vm.formData.sponsors.splice(index, 1);
    };

    vm.submitForm = function() {
        // Set submitting state
        vm.isSubmitting = true;
        var formData = new FormData();

        // Append main form data
        formData.append('callerName', vm.formData.callerName);
        formData.append('callerTitle', vm.formData.callerTitle);
        formData.append('contentName', vm.formData.contentName);
        formData.append('contentType', vm.formData.contentType);

        // Append language variants
        vm.formData.languageVariants.forEach((variant, index) => {
            formData.append(`languageVariants[${index}][contentName]`, variant.contentName);
            formData.append(`languageVariants[${index}][language]`, variant.language);
            formData.append(`languageVariants[${index}][textOnly]`, variant.textOnly ? '1' : '0');
            formData.append(`languageVariants[${index}][description]`, variant.description);
            formData.append(`languageVariants[${index}][htmlContent]`, variant.htmlContent);

            // Append audio/video file if present
            var fileInput;
            if (vm.formData.contentType === 'audio' || vm.formData.contentType === 'audio_call') {
                fileInput = document.getElementById(`audioFile-${index}`);
                if (fileInput && fileInput.files[0]) {
                    formData.append(`languageVariants[${index}][audioFile]`, fileInput.files[0]);
                }
            } else if (vm.formData.contentType === 'video' || vm.formData.contentType === 'video_call') {
                fileInput = document.getElementById(`videoFile-${index}`);
                if (fileInput && fileInput.files[0]) {
                    formData.append(`languageVariants[${index}][videoFile]`, fileInput.files[0]);
                }

            }
        });

        // Append common files for audio_call and video_call
        if (vm.formData.contentType === 'audio_call' || vm.formData.contentType === 'video_call') {
            var backgroundInput = document.getElementById('callerBackground');
            var avatarInput = document.getElementById('callerAvatar');

            if (backgroundInput && backgroundInput.files[0]) {
                formData.append('callerBackground', backgroundInput.files[0]);
            }
            if (avatarInput && avatarInput.files[0]) {
                formData.append('callerAvatar', avatarInput.files[0]);
            }
        }

        // Log formData contents for debugging
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        $http({
            method: 'POST',
            url: '/api/qrart/process',
            data: formData,
            headers: {'Content-Type': undefined},
            transformRequest: angular.identity
        }).then(function successCallback(response) {
            vm.isSubmitting = false;
            if (response.data.success) {
                console.log('Dati salvati con successo:', response.data);
                alert('✅ Contenuto creato con successo!\n\nIl contenuto è stato salvato correttamente.');

                // Reset form and return to first tab
                vm.formData = {
                    callerName: '',
                    callerTitle: '',
                    contentName: '',
                    contentType: '',
                    contentDescription: '',
                    callerBackground: null,
                    callerAvatar: null,
                    languageVariants: [],
                    relatedArticles: [],
                    sponsors: []
                };
                vm.addLanguageVariant();
                vm.setActiveTab('base');
            } else {
                console.error('Errore nel salvataggio dei dati:', response.data.message);
                alert('❌ Errore nel salvataggio\n\n' + response.data.message);
            }
        }, function errorCallback(response) {
            vm.isSubmitting = false;
            console.error('Errore nella richiesta:', response);
            var errorMessage = response.data && response.data.message
                ? response.data.message
                : 'Si è verificato un errore imprevisto. Per favore, riprova.';
            alert('❌ Errore nella richiesta\n\n' + errorMessage);
        });
    };

    // Initialize with one language variant
    vm.addLanguageVariant();
}





// Inizializza le icone Lucide dopo che AngularJS ha finito di renderizzare la vista







