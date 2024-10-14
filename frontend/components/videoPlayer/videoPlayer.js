angular.module('app').component('videoPlayer', {
    templateUrl: 'components/videoPlayer/videoPlayer.html',
    controller: function() {
        this.$onInit = () => {
            console.log('Video Player initialized');
        };
    },
    bindings: {
        src: '<'
    }
});
