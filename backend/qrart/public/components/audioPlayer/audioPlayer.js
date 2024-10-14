angular.module('phoneApp').component('audioPlayer', {
    templateUrl: 'components/audioPlayer/audioPlayer.html',
    controller: ['$scope', '$timeout', '$injector', audioPlayerController],
    bindings: {
        src: '<' // binding unidirezionale
    }
});

function audioPlayerController($scope, $timeout, $injector) {
    // Logica del controller
    $scope.isReady = false;

    $timeout(function() {
        $scope.isReady = true; // Esempio di utilizzo di $timeout
        console.log('Audio Player is ready.');
    }, 1000);

    // Puoi utilizzare $injector per risolvere altre dipendenze dinamicamente se necessario
}
