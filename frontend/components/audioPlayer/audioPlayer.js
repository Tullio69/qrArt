angular.module('app').component('audioPlayer', {
    templateUrl: 'components/audioPlayer/audioPlayer.html',
    controller: function() {
        this.$onInit = () => {
            console.log('Audio Player initialized');
        };
    },
    bindings: {
        src: '<'
    }
});
