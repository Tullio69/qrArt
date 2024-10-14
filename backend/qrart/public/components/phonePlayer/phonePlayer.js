angular.module('phoneApp').component('phonePlayer', {
    templateUrl: 'components/phonePlayer/phonePlayer.html',
        controller: ['FullscreenService', '$scope','$interval', PhonePlayerController],
    controllerAs: 'vm'
});

function PhonePlayerController(FullscreenService,$scope,$interval ) {
    var vm = this;

    // Stati iniziali
    vm.callState = 'waiting'; // Stati possibili: 'waiting', 'incoming', 'inCall', 'ended'
    vm.caller = {
        name: 'Nome del Chiamante',
        number: '123-456-7890',
        avatar: 'path/to/avatar.png' // Assicurati che il percorso dell'avatar sia corretto
    };


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
        audio.pause();
        audio.currentTime = 0;
        vm.stopVibration();
    };

    vm.declineCall = function() {
        vm.callState = 'ended';
        audio.pause();
        audio.currentTime = 0;
        vm.stopVibration();
    };
    $scope.$on('$destroy', function() {
        vm.endCall(); // Termina la chiamata quando il componente viene distrutto
    });

}
