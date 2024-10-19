<!DOCTYPE html>
<html ng-app="phoneApp">
<head>
	<title>Phone App</title>
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Expires" content="0" />
	
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular-route.min.js"></script>
	<script src="app.js"></script>
	<script src="components/audioPlayer/audioPlayer.js"></script>
	<script src="components/videoPlayer/videoPlayer.js"></script>
	<script src="components/htmlContent/htmlContent.js"></script>
	<script src="components/phonePlayer/phonePlayer.js"></script>
	
	</head>
<body>
<div ng-view></div>
<script>
    // Passiamo il contentId a AngularJS dal backend di CodeIgniter
    var contentId = <?= json_encode($contentId); ?>;  // PHP passa il contentId a AngularJS
</script>
</body>

</html>