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
    .controller('ContentViewerController', ['$scope', '$routeParams','$sce', '$http','FullscreenService','ContentService', 'AnalyticsService', function($scope, $routeParams, $http,$sce,FullscreenService,ContentService, AnalyticsService) {
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

            // Track content view with language
            AnalyticsService.trackContentView(
                $scope.content.id,
                $scope.content.content_type,
                metadata.language
            ).catch(function(error) {
                console.error('Error tracking content view:', error);
            });

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
    .controller('FormController', ['$http', '$scope', '$location', FormController])
    .controller('ThemeTestController', function($scope) {
        $scope.title = "Verifica il tuo tema Tailwind";
        $scope.description = "Questo componente utilizza classi Tailwind e Flowbite per testare se il tuo tema è configurato correttamente.";
        $scope.linkText = "Scopri di più";
    })
    .controller('ContentListController', [
        '$scope', '$http', '$uibModal','$timeout', '$location',
        function ($scope, $http, $uibModal,$timeout, $location) {
            $scope.contents = [];
            $scope.loading = true;
            $scope.error = null;
            $scope.searchQuery = '';
            $scope.expandedContent = null; // ID del contenuto espanso

            // Variabili per il modal di sostituzione file
            $scope.modalVisible = false;
            $scope.selectedFileToReplace = null;
            $scope.newFile = null;
            $scope.replacingFile = false;
            $scope.replaceSuccess = false;
            $scope.replaceError = null;

            // Toggle dettagli variante completa
            $scope.toggleLanguageVariantDetails = function(language, contentId) {
                var content = $scope.contents.find(c => c.id === contentId);
                if (content && content.perLanguage && content.perLanguage[language]) {
                    content.perLanguage[language].showDetails = !content.perLanguage[language].showDetails;
                }
            };

// Toggle preview media
            $scope.toggleMediaPreview = function(language, contentId) {
                var content = $scope.contents.find(c => c.id === contentId);
                if (content && content.perLanguage && content.perLanguage[language]) {
                    content.perLanguage[language].showMediaPreview = !content.perLanguage[language].showMediaPreview;
                }
            };

// Sostituisci file multimediale
            $scope.replaceMediaFile = function(variantData, language) {
                var fileToReplace = variantData.files.audio || variantData.files.video;
                $scope.selectedFileToReplace = {
                    id: fileToReplace.id,
                    file_url: fileToReplace.file_url,
                    file_type: fileToReplace.file_type,
                    language: language
                };
                $scope.modalVisible = true;
            };

// Helper per contare le chiavi
            $scope.getObjectKeysLength = function(obj) {
                return obj ? Object.keys(obj).length : 0;
            };
            // Chiudi il modal di sostituzione file
            $scope.closeReplaceModal = function() {
                $scope.modalVisible = false;
                $scope.selectedFileToReplace = null;
                $scope.newFile = null;
                $scope.replaceSuccess = false;
                $scope.replaceError = null;

                // Reset del file input
                var fileInput = document.getElementById('file-input-replace');
                if (fileInput) {
                    fileInput.value = '';
                }
            };

// Gestisci selezione nuovo file
            $scope.handleFileSelect = function(file) {
                if (file) {
                    $scope.$apply(function() {
                        $scope.newFile = file;
                        $scope.replaceError = null;
                    });
                }
            };

// Conferma sostituzione file
            $scope.confirmReplace = function() {
                if (!$scope.newFile || !$scope.selectedFileToReplace) {
                    return;
                }

                $scope.replacingFile = true;
                $scope.replaceError = null;
                $scope.replaceSuccess = false;

                var formData = new FormData();
                formData.append('file', $scope.newFile);
                formData.append('file_id', $scope.selectedFileToReplace.id);

                $http.post('/api/content/replace-file', formData, {
                    headers: { 'Content-Type': undefined },
                    transformRequest: angular.identity
                })
                    .then(function(response) {
                        if (response.data.success) {
                            $scope.replaceSuccess = true;
                            $scope.successMessage = 'File sostituito con successo!';

                            // Chiudi il modal dopo 1.5 secondi
                            setTimeout(function() {
                                $scope.$apply(function() {
                                    $scope.closeReplaceModal();
                                    // Ricarica i dettagli del contenuto
                                    if ($scope.expandedContentId) {
                                        var content = $scope.contents.find(c => c.id === $scope.expandedContentId);
                                        if (content) {
                                            $scope.loadContentDetails(content);
                                        }
                                    }
                                });
                            }, 1500);
                        } else {
                            $scope.replaceError = response.data.error || 'Errore durante la sostituzione del file';
                        }
                    })
                    .catch(function(error) {
                        console.error('Replace file error:', error);
                        $scope.replaceError = error.data?.error || 'Errore durante la sostituzione del file';
                    })
                    .finally(function() {
                        $scope.replacingFile = false;
                    });
            };

// Ottieni il tipo di file accettato in base al tipo
            $scope.getAcceptType = function(fileType) {
                if (!fileType) return '*/*';

                if (fileType === 'audio' || fileType === 'audio_call') {
                    return 'audio/*';
                } else if (fileType === 'video' || fileType === 'video_call') {
                    return 'video/*';
                } else if (fileType === 'callerAvatar' || fileType === 'callerBackground') {
                    return 'image/*';
                }
                return '*/*';
            };

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

            // Nel controller di contentList
            $scope.showEditModal = false;
            $scope.editingContent = null;
            $scope.savingEdit = false;
            $scope.editError = null;
            $scope.editSuccessMessage = null;

            $scope.openEditModal = function(content) {
                // Fai una copia del contenuto
                $scope.editingContent = angular.copy(content);

                // Carica i dettagli se non presenti
                if (!content.perLanguage) {
                    $http.get('/api/content/' + content.short_code)
                        .then(function(response) {
                            if (response.data.status === 200) {
                                // Organizza i dati
                                var contentData = response.data.content;
                                $scope.editingContent.perLanguage = {};

                                // Raggruppa metadata per lingua
                                contentData.metadata.forEach(function(meta) {
                                    if (!$scope.editingContent.perLanguage[meta.language]) {
                                        $scope.editingContent.perLanguage[meta.language] = {
                                            metadata: meta,
                                            files: {}
                                        };
                                    }

                                    // Aggiungi file se presente
                                    if (meta.file_url) {
                                        var fileType = meta.file_type || 'audio';
                                        $scope.editingContent.perLanguage[meta.language].files[fileType] = {
                                            id: meta.metadata_id,
                                            file_url: meta.file_url,
                                            file_type: fileType
                                        };
                                    }
                                });

                                // Aggiungi file comuni
                                $scope.editingContent.globalFiles = contentData.common_files || [];
                            }
                        })
                        .catch(function(error) {
                            console.error('Error loading content:', error);
                            $scope.error = 'Errore nel caricamento dei dettagli';
                        });
                }

                // Mostra il modal
                $scope.showEditModal = true;

                // Previeni scroll del body
                document.body.style.overflow = 'hidden';
            };

            $scope.closeEditModal = function() {
                $scope.showEditModal = false;
                $scope.editingContent = null;
                $scope.editError = null;
                $scope.editSuccessMessage = null;

                // Ripristina scroll del body
                document.body.style.overflow = '';
            };

            $scope.saveEditContent = function() {
                if ($scope.savingEdit) {
                    return;
                }

                $scope.savingEdit = true;
                $scope.editError = null;
                $scope.editSuccessMessage = null;

                // Prepara i dati per il salvataggio
                var dataToSave = {
                    caller_name: $scope.editingContent.caller_name,
                    caller_title: $scope.editingContent.caller_title,
                    metadata: []
                };

                // Converti perLanguage in array metadata
                angular.forEach($scope.editingContent.perLanguage, function(variantData, language) {
                    dataToSave.metadata.push({
                        id: variantData.metadata.id || variantData.metadata.metadata_id,
                        language: language,
                        content_name: variantData.metadata.content_name,
                        description: variantData.metadata.description || '',
                        html_content: variantData.metadata.html_content || '',
                        text_only: variantData.metadata.text_only
                    });
                });

                $http.put('/api/content/update/' + $scope.editingContent.id, dataToSave)
                    .then(function(response) {
                        if (response.data.success) {
                            $scope.editSuccessMessage = 'Contenuto aggiornato con successo!';

                            // Chiudi modal dopo 1.5 secondi e ricarica
                            $timeout(function() {
                                $scope.closeEditModal();
                                $scope.loadContents();
                            }, 1500);
                        } else {
                            $scope.editError = response.data.error || 'Errore durante il salvataggio';
                        }
                    })
                    .catch(function(error) {
                        console.error('Save error:', error);
                        $scope.editError = error.data?.error || 'Errore durante il salvataggio';
                    })
                    .finally(function() {
                        $scope.savingEdit = false;
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

                const formData = new FormData();
                formData.append('file', $scope.newFile);
                formData.append('file_id', $scope.selectedFileToReplace.id);

                $http.post($scope.base_url + '/api/content/replaceFile', formData, {
                    headers: { 'Content-Type': undefined } // lascia che il browser imposti multipart/form-data
                }).then(response => {
                    alert("File sostituito con successo!");
                    $scope.modalVisible = false;
                    $scope.newFile = null;
                    // puoi ricaricare i dati del content se vuoi
                }).catch(err => {
                    alert("Errore durante la sostituzione del file.");
                    console.error(err);
                });
            };

            // Add language variant - navigate to editor with contentId
            $scope.addLanguageVariant = function(content) {
                $location.path('/editor').search('contentId', content.id);
            };

        }
    ])
    .controller('EditContentModalController', [
        '$scope',
        '$uibModalInstance', // Torniamo a usare $uibModalInstance
        '$http','$timeout',
        'content',
        function($scope, $uibModalInstance, $http, content) {
            $scope.content = angular.copy(content);
            $scope.saving = false;

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


function FormController($http, $scope, $location) {
    var vm = this;

    // Tab management
    vm.activeTab = 'base';
    vm.isSubmitting = false;
    vm.isAddingVariant = false; // Flag per indicare se stiamo aggiungendo una variante
    vm.existingContentId = null;

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

    // Check if we're adding a language variant to existing content
    var contentId = $location.search().contentId;
    if (contentId) {
        vm.isAddingVariant = true;
        vm.existingContentId = contentId;

        // Load existing content data
        $http.get(window.BASE_URL + '/api/content/details/' + contentId)
            .then(function(response) {
                var content = response.data.content;

                // Pre-fill form with existing content data
                vm.formData.callerName = content.caller_name;
                vm.formData.callerTitle = content.caller_title;
                vm.formData.contentName = content.content_name;
                vm.formData.contentType = content.content_type;

                // Add info banner
                vm.existingContentInfo = {
                    name: content.caller_name,
                    shortCode: content.short_code,
                    existingLanguages: response.data.metadata.map(function(m) { return m.language; })
                };

                // Navigate to variants tab
                vm.setActiveTab('variants');

                console.log('✅ Contenuto caricato per aggiunta variante:', content);
            })
            .catch(function(error) {
                console.error('❌ Errore caricamento contenuto:', error);
                alert('Errore nel caricamento dei dati del contenuto');
            });
    }

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

        // If we're adding a variant to existing content, include the existingContentId
        if (vm.existingContentId) {
            formData.append('existingContentId', vm.existingContentId);
        }

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

                if (response.data.isAddingVariant) {
                    alert('✅ Variante linguistica aggiunta con successo!\n\nLa nuova variante è stata aggiunta al contenuto esistente.');

                    // Redirect back to content manager
                    window.location.href = '#!/content-manager';
                } else {
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
                }
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







