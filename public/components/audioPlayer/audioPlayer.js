angular.module('phoneApp').component('audioPlayer', {
    templateUrl: 'components/audioPlayer/audioPlayer.html',
    controller: ['$scope', '$timeout', '$element', 'AnalyticsService', audioPlayerController],
    bindings: {
        content: '<' // binding unidirezionale per il contenuto completo
    }
});

function audioPlayerController($scope, $timeout, $element, AnalyticsService) {
    var ctrl = this;
    $scope.isReady = false;
    var audioElement = null;
    var hasTrackedStart = false;
    var hasTrackedComplete = false;

    ctrl.$onInit = function() {
        $timeout(function() {
            $scope.isReady = true;
            audioElement = $element.find('audio')[0];

            if (audioElement) {
                setupAnalytics();
            }

            console.log('Audio Player is ready.');
        }, 100);
    };

    function setupAnalytics() {
        if (!audioElement || !ctrl.content) return;

        // Event: Play (inizio riproduzione)
        audioElement.addEventListener('play', function() {
            if (!hasTrackedStart) {
                AnalyticsService.trackPlaybackStart(
                    ctrl.content.id,
                    'audio',
                    ctrl.content.language
                );
                hasTrackedStart = true;
            }
        });

        // Event: Pause
        audioElement.addEventListener('pause', function() {
            if (!audioElement.ended) {
                AnalyticsService.trackPlaybackPause(
                    ctrl.content.id,
                    'audio',
                    ctrl.content.language,
                    audioElement.currentTime,
                    audioElement.duration
                );
            }
        });

        // Event: Ended (completamento)
        audioElement.addEventListener('ended', function() {
            if (!hasTrackedComplete) {
                AnalyticsService.trackPlaybackComplete(
                    ctrl.content.id,
                    'audio',
                    ctrl.content.language
                );
                hasTrackedComplete = true;
            }
        });

        // Event: Error
        audioElement.addEventListener('error', function(e) {
            var errorMsg = 'Unknown error';
            if (audioElement.error) {
                errorMsg = 'Code: ' + audioElement.error.code;
            }

            AnalyticsService.trackPlaybackError(
                ctrl.content.id,
                'audio',
                ctrl.content.language,
                errorMsg
            );
        });
    }

    $scope.$on('$destroy', function() {
        // Cleanup
        if (audioElement) {
            audioElement.removeEventListener('play', null);
            audioElement.removeEventListener('pause', null);
            audioElement.removeEventListener('ended', null);
            audioElement.removeEventListener('error', null);
        }
    });
}
