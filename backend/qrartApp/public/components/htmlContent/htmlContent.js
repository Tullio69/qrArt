angular.module('phoneApp').component('contviewer', {
    templateUrl: 'components/htmlContent/htmlContent.html',
    controller: ['$scope','$interval','$http', HtmlContentViewerController],
    controllerAs:'vm',
    bindings: {
        content: '<'
    }
});


function HtmlContentViewerController($scope,$interval,$http ) {
    var vm = this;
    // Inizializzazione delle variabili di stato

    console.log("Html Content Controller Initialized");
    console.log("Vm Content:", vm.content);


}