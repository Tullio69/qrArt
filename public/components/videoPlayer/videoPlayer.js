angular.module('phoneApp').component('videoPlayerController', {
    templateUrl: 'components/videoPlayer/videoPlayer.html',
    controller: ['$scope', '$timeout', '$element', 'AnalyticsService', videoPlayerController],
    bindings: {
        content: '<'
    }
});

function videoPlayerController($scope, $timeout, $element, AnalyticsService) {
    var ctrl = this;
    var videoElement = null;
    var hasTrackedStart = false;
    var hasTrackedComplete = false;

    ctrl.$onInit = function() {
        console.log('Video Player initialized');

        $timeout(function() {
            videoElement = $element.find('video')[0];

            if (videoElement) {
                setupAnalytics();
            }
        }, 100);
    };

    function setupAnalytics() {
        if (!videoElement || !ctrl.content) return;

        // Event: Play (inizio riproduzione)
        videoElement.addEventListener('play', function() {
            if (!hasTrackedStart) {
                AnalyticsService.trackPlaybackStart(
                    ctrl.content.id,
                    'video',
                    ctrl.content.language
                );
                hasTrackedStart = true;
            }
        });

        // Event: Pause
        videoElement.addEventListener('pause', function() {
            if (!videoElement.ended) {
                AnalyticsService.trackPlaybackPause(
                    ctrl.content.id,
                    'video',
                    ctrl.content.language,
                    videoElement.currentTime,
                    videoElement.duration
                );
            }
        });

        // Event: Ended (completamento)
        videoElement.addEventListener('ended', function() {
            if (!hasTrackedComplete) {
                AnalyticsService.trackPlaybackComplete(
                    ctrl.content.id,
                    'video',
                    ctrl.content.language
                );
                hasTrackedComplete = true;
            }
        });

        // Event: Error
        videoElement.addEventListener('error', function(e) {
            var errorMsg = 'Unknown error';
            if (videoElement.error) {
                errorMsg = 'Code: ' + videoElement.error.code;
            }

            AnalyticsService.trackPlaybackError(
                ctrl.content.id,
                'video',
                ctrl.content.language,
                errorMsg
            );
        });
    }

    $scope.$on('$destroy', function() {
        // Cleanup
        if (videoElement) {
            videoElement.removeEventListener('play', null);
            videoElement.removeEventListener('pause', null);
            videoElement.removeEventListener('ended', null);
            videoElement.removeEventListener('error', null);
        }
    });
}
