
    angular
        .module('phoneApp')
        .component('languageSelector', {
            templateUrl: 'components/languageSelector/languageSelector.template.html',
            controller: LanguageSelectorController,
            controllerAs: 'vm',
            bindings: {
                availableLanguages: '<',
                onSelectLanguage: '&'  // Aggiunge un binding per la funzione
            }
        });

    function LanguageSelectorController() {
        var vm = this;

        vm.filterTextOnly = false;

        vm.filterLanguages = function(lang) {
            return lang.text_only === (vm.filterTextOnly ? '1' : '0');
        };

        vm.selectLanguage = function(metadata) {
            vm.selectedLanguage = metadata;
            vm.onSelectLanguage({metadata: metadata, filterTextOnly: vm.filterTextOnly});  // Chiama la funzione esterna passando 'language' come parametro
        };

        vm.getTextOnlyFilter= function () {
            return vm.filterTextOnly;
        }

        vm.updateFilter = function () {

        }

    }

