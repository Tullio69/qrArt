angular.module('phoneApp')
    .component('wysiwygEditor', {
        template: '<textarea id="{{$ctrl.editorId}}"></textarea>',
        controller: WysiwygEditorController,
        bindings: {
            content: '='
        }
    });

function WysiwygEditorController($element, $scope, $timeout, $sce, $sanitize) {
    var $ctrl = this;
    var editor = null;

    $ctrl.$onInit = function() {
        $ctrl.editorId = 'tinyMCE_' + Math.random().toString(36).substr(2, 9);
        initTinyMCE();
    };

    $ctrl.$onDestroy = function() {
        if (editor) {
            editor.remove();
        }
    };

    // Funzione per estrarre la stringa HTML da un oggetto trusted o da una stringa
    function getHtmlString(content) {
        if (!content) return '';
        // Se è un oggetto trusted di Angular, estrai la stringa
        if (typeof content === 'object' && content.$$unwrapTrustedValue) {
            return content.$$unwrapTrustedValue();
        }
        // Se è già una stringa, restituiscila
        return content.toString();
    }

    function initTinyMCE() {
        $timeout(function() {
            tinymce.init({
                selector: '#' + $ctrl.editorId,
                height: 500,
                language: 'it',
                menubar: true,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | ' +
                    'bold italic backcolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                setup: function(ed) {
                    editor = ed;
                    editor.on('init', function() {
                        var htmlContent = getHtmlString($ctrl.content);
                        editor.setContent(htmlContent);
                    });
                    editor.on('change keyup', function() {
                        $scope.$apply(function() {
                            // Salva il contenuto come stringa HTML, non come oggetto trusted
                            $ctrl.content = editor.getContent();
                        });
                    });
                }
            });
        }, 100);
    }

    $ctrl.$onChanges = function(changes) {
        if (changes.content && !changes.content.isFirstChange()) {
            var htmlContent = getHtmlString(changes.content.currentValue);
            $ctrl.content = htmlContent;
            if (editor) {
                editor.setContent(htmlContent);
            }
        }
    };

}

WysiwygEditorController.$inject = ['$element', '$scope', '$timeout', '$sce', '$sanitize'];