<!DOCTYPE html>
<html ng-app="phoneApp">
<head>
	<base href="/">
		<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Expires" content="0" />
	
	<!-- Aggiungi questi nella sezione head -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
	<!-- Aggiorna le dipendenze nella sezione head -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.5.6/ui-bootstrap-csp.css" rel="stylesheet">
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
	<!-- Includi Flowbite nel progetto -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
	
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
	<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
	<link rel="shortcut icon" href="/favicon.ico" />
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
	<meta name="apple-mobile-web-app-title" content="QrArt" />
	<link rel="manifest" href="/site.webmanifest" />
	<title>QrArt - Contenuti multimediali multilingua tramite QR code</title>
</head>
<body>

<div ng-view></div>
<script>
    var contentId = <?= isset($contentId) ? json_encode($contentId) : 'null'; ?>;
</script>
<script src="app.js"></script>
<script src="services/analyticsService.js"></script>
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
<script>
    window.BASE_URL = "<?= base_url() ?>";
</script>

<!-- Aggiungi questi prima della chiusura del body -->

<!-- Alla fine del body -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.5.6/ui-bootstrap-tpls.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</body>
</html>