angular.module('phoneApp')
    .component('phonePlayer', {
    templateUrl: 'components/phonePlayer/phonePlayer.html',
        controller: ['FullscreenService', '$scope','$interval','$http', PhonePlayerController],
    controllerAs: 'vm'
});

function PhonePlayerController(FullscreenService,$scope,$interval,$http ) {
    var vm = this;
    // Inizializzazione delle variabili di stato
    vm.callState = 'waiting'; // Stati possibili: 'waiting', 'incoming', 'inCall', 'ended'
    vm.caller = {};
    vm.backgroundUrl = '';
    vm.callDuration = 0;
    vm.dynamicContent = '';
    vm.relatedArticles = [];
    vm.sponsorData = [];

    // Carica i contenuti dal server
    vm.loadContent = function(contentId) {
        $http.get('/api/content/' + contentId)
            .then(function(response) {
                var data = response.data;
                vm.contentType = data.content.type;
                vm.caller = {
                    name: data.content.callerTitle,
                    subtitle: data.content.callerSubtitle,
                    avatar: data.files.find(f => f.type === 'callerAvatar')?.url
                };
                vm.backgroundUrl = data.files.find(f => f.type === 'callerBackground')?.url;
                vm.dynamicContent = data.content.dynamicContent;
                vm.relatedArticles = data.content.relatedArticles;
                vm.sponsorData = data.content.sponsorData;

                // Imposta l'audio o il video in base al tipo di contenuto
                if (vm.contentType === 'audio' || vm.contentType === 'audio_call') {
                    vm.audioUrl = data.files.find(f => f.type === 'audio')?.url;
                } else if (vm.contentType === 'video' || vm.contentType === 'video_call') {
                    vm.videoUrl = data.files.find(f => f.type === 'video')?.url;
                }
            })
            .catch(function(error) {
                console.error('Error loading content:', error);
            });
    };

    // Carica il contenuto all'inizializzazione del componente
    vm.loadContent(contentId);
    // Stati iniziali


    // Variabili per il contatore della chiamata
    vm.callDuration = 0; // Secondi totali

    var callTimer = null;

    vm.availableLanguages = [
        { code: 'en', name: 'English' },
        { code: 'it', name: 'Italiano' },
        { code: 'sv', name: 'Svenska' }
    ];

    // Imposta l'Inglese come linguaggio predefinito per i test
    vm.selectedLanguage = vm.availableLanguages[0]; // Assumi che l'Inglese sia il primo elemento dell'array

    vm.selectLanguage = function(language) {
        vm.selectedLanguage = language;
        vm.goToFullscreen(); // Commenta questa riga durante i test se il fullscreen non è desiderato
        vm.receiveCall();
    };

    vm.goToFullscreen = function() {
        var element = document.documentElement;
        if (element.requestFullscreen) {
            element.requestFullscreen().catch(err => {
                console.log(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
            });
        }
    };

    vm.yourSwipeHandler = function(event) {
        if (event.type === 'swipeleft') {
            console.log('Swiped left');
        } else if (event.type === 'swiperight') {
            console.log('Swiped right');
        }
    };

    vm.goFullscreen = function() {
        var element = document.documentElement; // Prende l'elemento radice per passare a pieno schermo
        FullscreenService.enterFullscreen(element);
    };

    vm.exitFullscreen = function() {
        FullscreenService.exitFullscreen();
    };

    var vm = this;

    var ringTone = new Audio('assets/audio/marimba.mp3'); // Percorso del file audio
    var callerMedia = new Audio('media/2/it/audio.mp3'); // Percorso del file audio


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
        vm.callState = 'incoming';
        ringTone.loop = true;
        ringTone.play();
        vm.vibratePhone();
    };

    vm.answerCall = function() {
        vm.callState = 'inCall';
        vm.startCallTimer();
        // Avvia l'audio della chiamata
        callerMedia.currentTime = 0;  // Riavvia l'audio dall'inizio
        callerMedia.play();
        ringTone.pause();
        ringTone.currentTime = 0;
        vm.stopVibration();

        // Aggiungi un listener per l'evento 'ended' sul media in chiamata
        callerMedia.addEventListener('ended', function() {
            $scope.$apply(function() {
                vm.declineCall();  // Chiama la funzione per terminare la chiamata
            });
        });
    };

    vm.declineCall = function() {
        vm.callState = 'ended';
        vm.stopCallTimer();
        callerMedia.pause();
        callerMedia.currentTime = 0;
        vm.stopVibration();
        // Assicurati di fermare anche la suoneria qui.
        ringTone.pause();
        ringTone.currentTime = 0;
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




}
