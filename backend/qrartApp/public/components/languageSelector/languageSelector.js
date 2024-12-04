
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

        vm.selectLanguage = function(language) {
            vm.selectedLanguage = language;
            vm.onSelectLanguage({language: language});  // Chiama la funzione esterna passando 'language' come parametro
            console.log('Language selected:', language);
        };

        vm.getTextOnlyFilter= function () {
            return vm.filterTextOnly;
        }

    }

