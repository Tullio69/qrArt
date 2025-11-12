angular.module('phoneApp').component('htmlContentViewer', {
    templateUrl: 'components/htmlContent/htmlContent.html',
    controller: ['$scope', '$interval', '$http', '$sce', HtmlContentViewerController],
    controllerAs: 'vm',
    bindings: {
        content: '<',
        contentId:'@'
    }
});

function HtmlContentViewerController($scope, $interval, $http, $sce) {
    var vm = this;
    vm.htmlContent = '';
    vm.contentName = '';
    vm.isLoading = true;
    vm.error = null;

    vm.$onInit = function() {
        console.log("Html Content Controller Initialized");
        console.log("Vm Content:", vm.content);
        vm.loadHtmlContent();
    };

    vm.loadHtmlContent = function() {
        vm.isLoading = true;
        vm.error = null;

        $http.get('/content/html/' + vm.contentId + '/' + vm.content.content_language)
            .then(function(response) {
                if (response.data && response.data.status === 200) {
                    vm.htmlContent = $sce.trustAsHtml(response.data.html_content);
                    vm.contentName = response.data.content_name;
                } else {
                    vm.error = 'Errore nel caricamento del contenuto HTML';
                }
            })
            .catch(function(error) {
                console.error('Errore nel caricamento del contenuto HTML:', error);
                vm.error = 'Errore nel caricamento del contenuto HTML';
            })
            .finally(function() {
                vm.isLoading = false;
            });
    };
}

