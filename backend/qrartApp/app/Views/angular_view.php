<!DOCTYPE html>
<html ng-app="phoneApp">
<head>
	<base href="/">
	<title>Phone App</title>
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Expires" content="0" />
	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
	
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular-route.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular-sanitize.min.js"></script>
	
	
	<script src="https://cdn.tiny.cloud/1/rq6fm10cyrhlfic4umvx8byiglirglqwdd1jt37q1xpjmr5e/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
	<script src="https://cdn.tiny.cloud/1/rq6fm10cyrhlfic4umvx8byiglirglqwdd1jt37q1xpjmr5e/tinymce/5/langs/it.js" referrerpolicy="origin"></script>
	<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
	
	<style>
        [ng\:cloak], [ng-cloak], [data-ng-cloak], [x-ng-cloak], .ng-cloak, .x-ng-cloak {
            display: none !important;
        }
      	</style>
	<link rel="stylesheet" href="assets/tailwind/output.css">
	<link rel="stylesheet" href="assets/tailwind/node_modules/flowbite/dist/flowbite.css">
	<script src="assets/tailwind/node_modules/flowbite/dist/flowbite.min.js"></script>

</head>
<body>

<div ng-view></div>
<script src="app.js"></script>
<script src="components/audioPlayer/audioPlayer.js"></script>
<script src="components/videoPlayer/videoPlayer.js"></script>
<script src="components/htmlContent/htmlContent.js"></script>
<script src="components/phonePlayer/phonePlayer.js"></script>
<script src="components/wysiwygEditor/wysiwygEditor.js"></script>
<script src="components/languageSelector/languageSelector.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
    });
</script>
</body>
</html>