angular.module('phoneApp').component('phonePlayer', {
    templateUrl: 'components/phonePlayer/phonePlayer.html',
        controller: ['FullscreenService', '$scope','$interval', PhonePlayerController],
    controllerAs: 'vm'
});

function PhonePlayerController(FullscreenService,$scope,$interval ) {
    var vm = this;

    // Stati iniziali
    vm.caller = {}; // Dati del chiamante caricati dinamicamente
    vm.content = {}; // Contenuto dinamico
    // Lista delle lingue disponibili
    vm.availableLanguages = [];

    // Variabili per il contatore della chiamata
    vm.callDuration = 0; // Secondi totali

    var callTimer = null;

    vm.availableLanguages = [
        { code: 'en', name: 'English', flagUrl: 'path/to/flag/en.png' },
        { code: 'it', name: 'Italiano', flagUrl: 'path/to/flag/it.png' },
        { code: 'fr', name: 'Français', flagUrl: 'path/to/flag/fr.png' }
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
    var audio = new Audio('assets/audio/marimba.mp3'); // Percorso del file audio

    vm.vibratePhone = function() {
        if ("vibrate" in navigator) {
            // Avvia la vibrazione in un pattern ripetuto
            navigator.vibrate([500, 1000]);
            // Imposta l'intervallo per ripetere la vibrazione
            vibrateInterval = $interval(function() {
                navigator.vibrate([500, 1000]);
            }, 1500);  // La somma del tempo di vibrazione e pausa
        } else {
            alert("La funzione di vibrazione non è supportata dal tuo dispositivo.");
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
        audio.loop = true;
        audio.play();
        vm.vibratePhone();
    };

    vm.answerCall = function() {
        vm.callState = 'inCall';
        vm.startCallTimer();
        audio.pause();
        audio.currentTime = 0;
        vm.stopVibration();
    };

    vm.declineCall = function() {
        vm.callState = 'ended';
        vm.stopCallTimer();
        audio.pause();
        audio.currentTime = 0;
        vm.stopVibration();
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
