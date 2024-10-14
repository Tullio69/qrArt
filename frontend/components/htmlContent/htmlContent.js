angular.module('app').component('htmlContent', {
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
