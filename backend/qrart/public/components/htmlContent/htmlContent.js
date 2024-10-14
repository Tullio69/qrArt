angular.module('phoneApp').component('htmlContentController', {
    templateUrl: 'components/htmlContent/htmlContent.html',
    controller: function() {
        this.$onInit = () => {
            console.log('HTML Content initialized');
        };
    },
    bindings: {
        content: '<'
    }
});
