angular.module('phoneApp')
    .component('wysiwygEditor', {
        template: '<textarea id="{{$ctrl.editorId}}"></textarea>',
        controller: WysiwygEditorController,
        bindings: {
            content: '=',
            onChange: '&'
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
                        editor.setContent($ctrl.content || '');
                    });
                    editor.on('change', function() {
                        $scope.$apply(function() {
                            var sanitizedContent = sanitizeContent(editor.getContent());
                            $ctrl.content = sanitizedContent;
                            $ctrl.onChange({content: sanitizedContent});
                        });
                    });
                }
            });
        });
    }

    function sanitizeContent(content) {
        var sanitized = $sanitize(content);
        return $sce.trustAsHtml(sanitized);
    }

    $ctrl.$onChanges = function(changes) {
        if (changes.content && !changes.content.isFirstChange()) {
            var sanitizedContent = sanitizeContent(changes.content.currentValue);
            $ctrl.content = sanitizedContent;
            if (editor) {
                editor.setContent(sanitizedContent);
            }
        }
    };
}

WysiwygEditorController.$inject = ['$element', '$scope', '$timeout', '$sce', '$sanitize'];