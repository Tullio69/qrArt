angular.module('phoneApp')
    .component('wysiwygEditor', {
        templateUrl: 'components/wysiwygEditor/wysiwygEditor.html',
        controller: ['$element', '$scope', '$timeout', WysiwygEditorController],
        controllerAs: 'vm',
        bindings: {
            content: '=',
            options: '<'
        }
    });

function WysiwygEditorController($element, $scope, $timeout) {
    var vm = this;
    var textarea;

    vm.$onInit = function() {
        textarea = $element.find('textarea');
        initTinyMCE();
    };

    vm.$onChanges = function(changesObj) {
        if (changesObj.content && !changesObj.content.isFirstChange()) {
            updateEditorContent();
        }
    };

    vm.$onDestroy = function() {
        removeTinyMCE();
    };

    function initTinyMCE() {
        $timeout(function() {
            tinymce.init(angular.extend({
                target: textarea[0],
                language: 'it',
                plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                height: 300,
                setup: function(editor) {
                    editor.on('change', function() {
                        $scope.$apply(function() {
                            vm.content = editor.getContent();
                        });
                    });
                },
                init_instance_callback: function(editor) {
                    editor.setContent(vm.content || '');
                }
            }, vm.options));
        });
    }

    function updateEditorContent() {
        var editor = tinymce.get(textarea[0].id);
        if (editor) {
            editor.setContent(vm.content || '');
        }
    }

    function removeTinyMCE() {
        tinymce.remove(textarea);
    }
}