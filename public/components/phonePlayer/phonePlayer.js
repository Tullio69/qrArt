angular.module('phoneApp')
    .component('phonePlayer', {
    templateUrl: 'components/phonePlayer/phonePlayer.html',
        bindings: {
            content: '<',
            callerAvatar: '<',
            callerBackground: '<',
            callState:'<'
        },
        controller: ['FullscreenService', '$scope','$interval','$http','$timeout','$window', PhonePlayerController],
    controllerAs: 'vm'
});

function PhonePlayerController(FullscreenService,$scope,$interval,$http ,$timeout,$window) {
    var vm = this;
    var callTimer;
    var ringTone = new Audio('assets/audio/marimba.mp3');


    vm.callState = 'waiting'; // Stati possibili: 'waiting', 'incoming', 'inCall', 'ended'
    vm.caller = {};
    vm.backgroundUrl = '';
    vm.callDuration = 0;
    vm.dynamicContent = '';
    vm.relatedArticles = [];
    vm.sponsorData = [];

    vm.$onInit = function() {
        // ... codice esistente ...
        vm.avatarLoaded = false;
        vm.loadAvatar();
    };

    vm.loadAvatar = function() {
        if (vm.caller && vm.caller.avatar) {
            var img = new Image();
            img.onload = function() {
                $timeout(function() {
                    vm.avatarLoaded = true;
                });
            };
            img.src = vm.caller.avatar;
        }
    };

    vm.$onChanges = function(changesObj) {
        var text_only = false;
        if (changesObj.content && changesObj.content.currentValue) {
            text_only = !!changesObj.content.currentValue.text_only;
        }
        if (changesObj.callState) {
            vm.handleCallStateChange(changesObj.callState.currentValue,text_only);
        }
    };

    vm.handleCallStateChange = function(newState,text_only) {
        if(text_only)return;
        if (newState === 'incoming') {
            vm.loadContent()
            vm.receiveCall();
        }
        // Add other state handling as needed
    };

    vm.loadContent = function() {
        /*{
            "id": "127",
            "language": "it",
            "text_only": "0",
            "content_name": "Chiesa di Sant'Orsola",
            "content_url": "10/it/10_it_audio.wav",
            "description": "",
            "created_at": "2024-11-14 10:08:37",
            "updated_at": "2024-11-14 10:08:37",
            "$$hashKey": "object:8"
        }*/
        vm.caller = {};
        vm.caller.name= vm.content.caller_name;
        vm.caller.subtitle = vm.content.caller_subtitle;
        vm.caller.avatar = 'media/'+ vm.content.caller_avatar;
        vm.text_only = vm.content.text_only;
        vm.backgroundUrl = 'media/'+ vm.content.caller_background;
        vm.dynamicContent = '';
        vm.relatedArticles = [];
        vm.sponsorData = [];
        vm.fullUrl = 'media/'+ vm.content.content_url;
        vm.callerMedia= new Audio(vm.fullUrl);
    }

    vm.loadHtmlContent = function(url) {
        $http.get(url)
            .then(function(response) {
                vm.htmlContent = response.data;
            })
            .catch(function(error) {
                console.error('Errore nel caricamento del contenuto HTML:', error);
                vm.htmlContent = '<p>Errore nel caricamento del contenuto.</p>';
            });
    };

    vm.selectLanguage = function(metadata) {
        vm.loadContent(metadata);
        vm.selectedLanguage = metadata.language;
        vm.goToFullscreen();
        if (metadata.text_only === "1") {
            vm.callState = 'text_only';
            vm.loadHtmlContent(metadata.content_url);
        } else {
            vm.receiveCall();
        }
    };

    vm.goToFullscreen = function() {
        var element = document.documentElement;
        if (element.requestFullscreen) {
            element.requestFullscreen().catch(err => {
                console.log(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
            });
        }
    };

    vm.handleSwipe = function(direction) {
        $timeout(function() {
            if (direction === 'left') {
                vm.declineCall();
            } else if (direction === 'right') {
                vm.answerCall();
            }
        });
    };

    vm.goFullscreen = function() {
        var element = document.documentElement; // Prende l'elemento radice per passare a pieno schermo
        FullscreenService.enterFullscreen(element);
    };

    vm.exitFullscreen = function() {
        FullscreenService.exitFullscreen();
    };
    // Funzione per gestire il termine della riproduzione dell'audio
    vm.onAudioEnded = function() {
        vm.callState = 'ended';  // Cambia lo stato della chiamata in "terminata"
       /* console.log("Chiamata terminata dopo la fine dell'audio.");*/
    };

    vm.vibratePhone = function() {
        if ("vibrate" in navigator) {
            // Avvia la vibrazione in un pattern ripetuto
            navigator.vibrate([500, 1000]);
            // Imposta l'intervallo per ripetere la vibrazione
            vibrateInterval = $interval(function() {
                navigator.vibrate([500, 1000]);
            }, 1500);  // La somma del tempo di vibrazione e pausa
        } else {
          /*  alert("La funzione di vibrazione non è supportata dal tuo dispositivo.");*/
        }
    };

    vm.stopVibration = function() {
        if ("vibrate" in navigator) {
            navigator.vibrate(0); // Ferma la vibrazione
            if (vibrateInterval) {
                $interval.cancel(vibrateInterval); // Cancella l'intervallo di vibrazione
            }
        }
    };

    vm.receiveCall = function() {
        vm.callDuration = 0;
        vm.callState = 'incoming';
        ringTone.loop = true;
        ringTone.play();
        vm.vibratePhone();
    };

    vm.answerCall = function() {
        vm.callDuration = 0;
        vm.callState = 'inCall';
        vm.startCallTimer();
        // Avvia l'audio della chiamata
        vm.callerMedia.currentTime = 0;  // Riavvia l'audio dall'inizio
        vm.callerMedia.play();
        ringTone.pause();
        ringTone.currentTime = 0;
        vm.stopVibration();

        // Aggiungi un listener per l'evento 'ended' sul media in chiamata
        vm.callerMedia.addEventListener('ended', function() {
            $timeout(function() {
                vm.declineCall(); // Chiama la funzione per terminare la chiamata
            })


        });
    };

    vm.declineCall = function() {
        vm.callState = 'ended';
        vm.stopCallTimer();
        vm.callerMedia.pause();
        vm.callerMedia.currentTime = 0;
        vm.stopVibration();
        // Assicurati di fermare anche la suoneria qui.
        ringTone.pause();
        ringTone.currentTime = 0;
        // Carica articoli correlati e sponsor quando la chiamata termina
        vm.loadRelatedContent();
    };

    // Funzione per caricare articoli correlati e sponsor
    vm.loadRelatedContent = function() {
        if (!vm.content || !vm.content.id) {
            return;
        }

        $http.get('api/content/related/' + vm.content.id)
            .then(function(response) {
                if (response.data.status === 200) {
                    $timeout(function() {
                        vm.relatedArticles = response.data.relatedArticles || [];
                        vm.sponsorData = response.data.sponsors || [];
                    });
                }
            })
            .catch(function(error) {
                console.error('Errore nel caricamento dei contenuti correlati:', error);
                vm.relatedArticles = [];
                vm.sponsorData = [];
            });
    };

    // Funzione per avviare il timer
    vm.startCallTimer = function() {
        if (!callTimer) {
            callTimer = $interval(function() {
                vm.callDuration++; // Incrementa la durata della chiamata ogni secondo
            }, 1000);
        }
    };

    // Funzione per fermare il timer
    vm.stopCallTimer = function() {
        if (callTimer) {
            $interval.cancel(callTimer);
            callTimer = null;
        }
    };

    // Funzione per formattare la durata in ore, minuti e secondi
    vm.formatCallDuration = function() {
        var seconds = vm.callDuration % 60;
        var minutes = Math.floor(vm.callDuration / 60) % 60;
        var hours = Math.floor(vm.callDuration / 3600);

        return (hours > 0 ? hours + ':' : '00:') +
            (minutes < 10 ? '0' : '') + minutes + ':' +
            (seconds < 10 ? '0' : '') + seconds;
    };

    $scope.$on('$destroy', function() {
        vm.declineCall(); // Termina la chiamata quando il componente viene distrutto
    });



    vm.translations = {
        it: {
            dynamicContent: 'La chiamata è terminata.',
            replayButton: 'Riascolta la Chiamata',
            relatedArticles: 'Articoli Correlati',
            sponsor: 'Sponsor'
        },
        en: {
            dynamicContent: 'The call has ended.',
            replayButton: 'Replay the Call',
            relatedArticles: 'Related Articles',
            sponsor: 'Sponsors'
        },
        sv: {
            dynamicContent: 'Samtalet har avslutats.',
            replayButton: 'Spela upp samtalet igen',
            relatedArticles: 'Relaterade artiklar',
            sponsor: 'Sponsorer'
        }
    };



}
