angular.module('contentManager')
    .component('contentManager', {
        templateUrl: 'components/contentManager/contentManager.html',
        controller: ['ContentService', '$scope', function (ContentService, $scope) {
            var vm = this;
            vm.contents = [];
            vm.selectedContent = null;
            vm.isEditing = false;

            // Carica i contenuti all'inizializzazione
            vm.$onInit = function () {
                vm.loadContents();
            };

            vm.loadContents = function () {
                ContentService.getContents().then(function (response) {
                    vm.contents = response.data;
                });
            };

            // Seleziona un contenuto per la modifica
            vm.editContent = function (content) {
                vm.selectedContent = angular.copy(content);
                vm.isEditing = true;
            };

            // Salva le modifiche
            vm.saveContent = function () {
                ContentService.updateContent(vm.selectedContent).then(function () {
                    vm.isEditing = false;
                    vm.loadContents();
                });
            };

            // Elimina un contenuto
            vm.deleteContent = function (contentId) {
                if (confirm("Sei sicuro di voler eliminare questo contenuto?")) {
                    ContentService.deleteContent(contentId).then(function () {
                        vm.loadContents();
                    });
                }
            };
        }],
        controllerAs: 'vm'
    });
