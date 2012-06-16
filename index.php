<?php
ini_set('date.timezone', 'America/New_York');

$handle = curl_init();
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_URL, 'http://codestock.org/api/v2.0.svc/AllSessionsJson');
$rawSessionData = json_decode(curl_exec($handle));
curl_setopt($handle, CURLOPT_URL, 'http://codestock.org/api/v2.0.svc/AllSpeakersJson');
$rawSpeakerData = json_decode(curl_exec($handle));
($rawSpeakerData && $rawSessionData) or die('Could not retrieve data');

// Do we have a specific user in mind?
$email = trim($_GET['email']);
$userSessions = false;
if ($email) {
	curl_setopt($handle, CURLOPT_URL, 'http://codestock.org/api/v2.0.svc/GetUserIDJson?Email='.$email);
	$userId = json_decode(curl_exec($handle));
	if ($userId && $userId->d) {
		curl_setopt($handle, CURLOPT_URL, 'http://codestock.org/api/v2.0.svc/GetScheduleJson?ScheduleID='.$userId->d);
		$userSessions = json_decode(curl_exec($handle))->d;
	}
}
curl_close($handle);

$speakerData = array();
$timeData = array();
$sessionData = array();
foreach ($rawSpeakerData->d as $raw) {
	$raw->Sessions = array();
	$speakerData[$raw->SpeakerID] = $raw;
}
foreach ($rawSessionData->d as $raw) {
	$raw->StartTime = (preg_replace('/\D*(\d+)-.*/', '\1', $raw->StartTime)/1000);
	$raw->StartDay = strtotime('today', $raw->StartTime);
	$raw->EndTime = (preg_replace('/\D*(\d+)-.*/', '\1', $raw->EndTime)/1000);
	$raw->Speaker = $speakerData[$raw->SpeakerID];
	$sessionData[$raw->SessionID] = $raw;
	$speakerData[$raw->SpeakerID]->Sessions[] = $raw;
	$timeData[$raw->StartDay][$raw->StartTime][] = $raw;
}
unset($rawSessionData);
unset($rawSpeakerData);
ksort($timeData);
foreach ($timeData as $day => $slots) {
	ksort($slots);
	$timeData[$day] = $slots;
}
uasort($speakerData, function ($a, $b) {
	if ($a->Name == $b->Name) {
		return 0;
	}
	return ($a->Name < $b->Name) ? -1 : 1;
});
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
			height: 4.6em;
			margin: 0 10px 0 0;
			border-radius: 8px;
				-moz-border-radius: 8px;
				-webkit-border-radius: 8px;
		}
		img[src=""] {
			display: none;
		}
		div.session-abstract {
			clear: both;
		}
		div.session-info + div.session-info, div.session-abstract {
			margin-top: 10px;
		}
		div.clear {
			clear: both;
		}
		h2 > a {
			text-decoration: none;
			font-size: medium;
			vertical-align: 15%;
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
			<li><a href="#speaker-page">Speakers</a></li>
			<?php if ($userSessions) : ?>
				<li><a href="#my-page">My Schedule</a></li>
			<?php endif; ?>
		</ul>
	</div><!-- /content -->

</div><!-- /page -->

<?php foreach ($timeData as $day => $slots) : ?>
	<div data-role="page" id="day-page-<?php echo $day; ?>">

		<div data-role="header">
			<a href="#home-page" data-rel="back" data-icon="arrow-l">Back</a>
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
							<div class="session-info">
								<a href="#speaker-page-<?php echo $session->Speaker->SpeakerID; ?>">
								<?php echo $session->Speaker->Name; ?>
								</a>
							</div>
							<div class="session-info">Room <?php echo $session->Room; ?></div>
							<div class="session-info">
								<?php echo date('h:i A', $session->StartTime); ?>
								&mdash;
								<?php echo date('h:i A', $session->EndTime); ?>
							</div>
							<div class="session-abstract"><?php echo $session->Abstract; ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div><!-- /content -->

	</div><!-- /page -->
<?php endforeach; ?>

<div data-role="page" id="speaker-page">
	<div data-role="header">
		<a href="#home-page" data-rel="back" data-icon="arrow-l">Back</a>
		<h1>CodeStock 2012 - Speakers</h1>
	</div><!-- /header -->

	<div data-role="content">
		<h3>Speakers</h3>
		<ul data-role="listview">
			<?php foreach ($speakerData as $speaker) : ?>
				<li><a href="#speaker-page-<?php echo $speaker->SpeakerID; ?>">
					<?php echo $speaker->Name; ?>
			</a></li>
			<?php endforeach; ?>
		</ul>
	</div><!-- /content -->

</div><!-- /page -->

<?php foreach ($speakerData as $speaker) : ?>
	<div data-role="page" id="speaker-page-<?php echo $speaker->SpeakerID; ?>">

		<div data-role="header">
			<a href="#speaker-page" data-rel="back" data-icon="arrow-l">Back</a>
			<h1>CodeStock 2012 - <?php echo $speaker->Name; ?></h1>
		</div><!-- /header -->

		<div data-role="content">
			<h2>
				<?php echo $speaker->Name; ?>
				<?php if ($speaker->TwitterID) : ?>
					&bull;<a href="http://twitter.com/<?php echo $speaker->TwitterID; ?>">
						@<?php echo $speaker->TwitterID; ?>
					</a>
				<?php endif; ?>
				<?php if ($speaker->Website) : ?>
					&bull;<a href="<?php echo $speaker->Website; ?>">
						<?php echo $speaker->Website; ?>
					</a>
				<?php endif; ?>
			</h2>
			<img class="speaker-photo" src="<?php echo $speaker->PhotoUrl; ?>">
			<div class="speaker-bio"><?php echo $speaker->Bio; ?></div>
			<div class="clear"></div>
			<h3>Sessions</h3>
			<?php foreach ($speaker->Sessions as $session) : ?>
				<div data-role="collapsible">
					<h4><?php echo $session->Title; ?></h4>
					<div class="session-info">Room <?php echo $session->Room; ?></div>
					<div class="session-info">
						<?php echo date('D, M j', $session->StartTime); ?>:
						<?php echo date('h:i A', $session->StartTime); ?>
						&mdash;
						<?php echo date('h:i A', $session->EndTime); ?>
					</div>
					<div class="session-abstract"><?php echo $session->Abstract; ?></div>
				</div>
			<?php endforeach; ?>
		</div><!-- /content -->

	</div><!-- /page -->
<?php endforeach; ?>

<?php if ($userSessions) : ?>
	<div data-role="page" id="my-page">

		<div data-role="header">
			<a href="#home-page" data-rel="back" data-icon="arrow-l">Back</a>
			<h1>CodeStock 2012 - My Schedule</h1>
		</div><!-- /header -->

		<div data-role="content">
			<h2>Schedule for <?php echo $email; ?></h2>
			<?php foreach ($userSessions as $sessionId) :
				$session = $sessionData[$sessionId];
			?>
				<div data-role="collapsible">
					<h4>
						<?php echo date('D, M j', $session->StartTime); ?>:
						<?php echo date('h:i A', $session->StartTime); ?><br>
						<?php echo $session->Title; ?>
					</h4>
					<img class="speaker-photo" src="<?php echo $session->Speaker->PhotoUrl; ?>">
					<div class="session-info">
						<a href="#speaker-page-<?php echo $session->Speaker->SpeakerID; ?>">
						<?php echo $session->Speaker->Name; ?>
						</a>
					</div>
					<div class="session-info">Room <?php echo $session->Room; ?></div>
					<div class="session-info">
						<?php echo date('D, M j', $session->StartTime); ?>:
						<?php echo date('h:i A', $session->StartTime); ?>
						&mdash;
						<?php echo date('h:i A', $session->EndTime); ?>
					</div>
					<div class="session-abstract"><?php echo $session->Abstract; ?></div>
				</div>
			<?php endforeach; ?>
		</div><!-- /content -->

	</div><!-- /page -->
<?php endif; ?>

</body>
</html>
