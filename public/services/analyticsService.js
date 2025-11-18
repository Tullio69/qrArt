angular.module('phoneApp').service('AnalyticsService', ['$http', function($http) {

    /**
     * Traccia un evento analytics
     * @param {string} eventType - Tipo di evento (playback_start, playback_complete, language_change, etc.)
     * @param {object} data - Dati aggiuntivi dell'evento
     */
    this.trackEvent = function(eventType, data) {
        data = data || {};
        data.event_type = eventType;

        return $http.post('/api/analytics/track', data)
            .then(function(response) {
                console.log('Event tracked:', eventType, response.data);
                return response.data;
            })
            .catch(function(error) {
                console.error('Error tracking event:', eventType, error);
            });
    };

    /**
     * Traccia l'inizio della riproduzione di un contenuto
     */
    this.trackPlaybackStart = function(contentId, contentType, language) {
        return this.trackEvent('playback_start', {
            content_id: contentId,
            language: language,
            metadata: {
                content_type: contentType
            }
        });
    };

    /**
     * Traccia il completamento della riproduzione
     */
    this.trackPlaybackComplete = function(contentId, contentType, language) {
        return this.trackEvent('playback_complete', {
            content_id: contentId,
            language: language,
            metadata: {
                content_type: contentType
            }
        });
    };

    /**
     * Traccia il cambio lingua
     */
    this.trackLanguageChange = function(contentId, fromLanguage, toLanguage) {
        return this.trackEvent('language_change', {
            content_id: contentId,
            language: toLanguage,
            metadata: {
                from_language: fromLanguage,
                to_language: toLanguage
            }
        });
    };

    /**
     * Traccia la visualizzazione di un contenuto
     */
    this.trackContentView = function(contentId, contentType, language) {
        return this.trackEvent('content_view', {
            content_id: contentId,
            language: language,
            metadata: {
                content_type: contentType
            }
        });
    };

    /**
     * Traccia la pausa della riproduzione
     */
    this.trackPlaybackPause = function(contentId, contentType, language, currentTime, duration) {
        return this.trackEvent('playback_pause', {
            content_id: contentId,
            language: language,
            metadata: {
                content_type: contentType,
                current_time: currentTime,
                duration: duration,
                progress_percentage: duration > 0 ? (currentTime / duration * 100).toFixed(2) : 0
            }
        });
    };

    /**
     * Traccia un errore nella riproduzione
     */
    this.trackPlaybackError = function(contentId, contentType, language, errorMessage) {
        return this.trackEvent('playback_error', {
            content_id: contentId,
            language: language,
            metadata: {
                content_type: contentType,
                error: errorMessage
            }
        });
    };

    /**
     * Ottiene le statistiche per un contenuto
     */
    this.getContentStats = function(contentId) {
        return $http.get('/api/analytics/content/' + contentId)
            .then(function(response) {
                return response.data.data;
            })
            .catch(function(error) {
                console.error('Error getting content stats:', error);
                return null;
            });
    };

}]);
