
    angular
        .module('phoneApp')
        .component('languageSelector', {
            templateUrl: 'components/languageSelector/languageSelector.template.html',
            controller: LanguageSelectorController,
            controllerAs: 'vm',
            bindings: {
                onSelectLanguage: '&'  // Aggiunge un binding per la funzione
            }
        });

    function LanguageSelectorController() {
        var vm = this;

        vm.availableLanguages = [
            { code: 'en', name: 'English' },
            { code: 'it', name: 'Italiano' },
            { code: 'fr', name: 'Français' },
            { code: 'de', name: 'Deutsch' },
            { code: 'sv', name: 'Sweden' }
            // Aggiungi altre lingue secondo necessità
        ];

        vm.selectedLanguage = vm.availableLanguages[0]; // Imposta la lingua predefinita, se desiderato

        vm.selectLanguage = function(language) {
            vm.selectedLanguage = language;
            vm.onSelectLanguage({language: language});  // Chiama la funzione esterna passando 'language' come parametro
            console.log('Language selected:', language.name);
        };

    }

