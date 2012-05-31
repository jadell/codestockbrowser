<?php
$handle = curl_init();
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_URL, 'http://codestock.org/api/v2.0.svc/AllSessionsJson');
$rawSessionData = json_decode(curl_exec($handle));
curl_setopt($handle, CURLOPT_URL, 'http://codestock.org/api/v2.0.svc/AllSpeakersJson');
$rawSpeakerData = json_decode(curl_exec($handle));
curl_close($handle);
($rawSpeakerData && $rawSessionData) or die('Could not retrieve data');

$speakerData = array();
$timeData = array();
foreach ($rawSpeakerData->d as $raw) {
	$speakerData[$raw->SpeakerID] = $raw;
}
foreach ($rawSessionData->d as $raw) {
	$raw->StartTime = (preg_replace('/\D*(\d+)-.*/', '\1', $raw->StartTime)/1000);
	$raw->StartDay = strtotime('today', $raw->StartTime);
	$raw->EndTime = (preg_replace('/\D*(\d+)-.*/', '\1', $raw->EndTime)/1000);
	$raw->Speaker = $speakerData[$raw->SpeakerID];
	$timeData[$raw->StartDay][$raw->StartTime][] = $raw;
}
unset($rawSessionData);
unset($rawSpeakerData);
ksort($timeData);
foreach ($timeData as &$slots) {
	ksort($slots);
}

?>
<!DOCTYPE html>
<html>
	<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CodeStock 2012 Browser</title>

	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.0/jquery.mobile-1.1.0.min.css" />
	<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.1.0/jquery.mobile-1.1.0.min.js"></script>

	<style type="text/css">
		img.speaker-photo {
			float: left;
			height: 5.4em;
			margin: 0 10px 0 0;
			border-radius: 8px;
				-moz-border-radius: 8px;
				-webkit-border-radius: 8px;
		}
		img[src=""] {
			display: none;
		}
	</style>
</head>
<body>

<div data-role="page" id="home-page">

	<div data-role="header">
		<h1>CodeStock 2012</h1>
	</div><!-- /header -->

	<div data-role="content">
		<h3>Schedule</h3>
		<ul data-role="listview">
			<?php foreach ($timeData as $day => $slots) : ?>
				<li><a href="#day-page-<?php echo $day; ?>"><?php echo date('D, M j', $day); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div><!-- /content -->

</div><!-- /page -->

<?php foreach ($timeData as $day => $slots) : ?>
	<div data-role="page" id="day-page-<?php echo $day; ?>">

		<div data-role="header">
			<a href="#home-page" data-icon="arrow-l">Back</a>
			<h1>CodeStock 2012 - <?php echo date('D, M j', $day); ?></h1>
		</div><!-- /header -->

		<div data-role="content">
			<h2><?php echo date('D, M j', $day); ?></h2>
			<?php foreach ($slots as $slot => $sessions) : ?>
				<div data-role="collapsible" data-theme="e" data-content-theme="c">
					<h3><?php echo date('h:i A', $slot); ?></h3>
					<?php foreach ($sessions as $session) : ?>
						<div data-role="collapsible">
							<h4><?php echo $session->Title; ?></h4>
							<img class="speaker-photo" src="<?php echo $session->Speaker->PhotoUrl; ?>">
							<p>
								<?php echo $session->Speaker->Name; ?>
								(Room <?php echo $session->Room; ?>)
							</p>
							<p><?php echo $session->Abstract; ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div><!-- /content -->

	</div><!-- /page -->
<?php endforeach; ?>


</body>
</html>
