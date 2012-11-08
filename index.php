<?php
require_once('config.php');

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

$sql = 'SELECT url, date, title, type, ahId FROM avherald ORDER BY id DESC LIMIT 0, 50';
$stmt = $mysqli->prepare($sql);
$stmt->execute();
$stmt->bind_result($url, $date, $title, $type, $ahId);
$events = array();
while($stmt->fetch()) {
	$ahId .= strtotime($date);
	$events[] = array('url' => $url, 'date' => strtotime($date), 'title' => $title, 'type' => $type, 'ahId' => $ahId);
	if(!isset($max_date) || strtotime($date) > $max_date) {
		$max_date = strtotime($date);
	}
}
$stmt->close();
$mysqli->close();

header('Content-Type: application/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rss version="2.0" xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
        <channel>
                <title>The Aviation Herald</title>
                <link>http://avherald.com/</link>
                <description>Incidents and News in Aviation</description>
                <language>en-us</language>
                <pubDate><?= date(DATE_RFC2822, $max_date) ?></pubDate>
		<?php foreach($events as $event): ?>
			<item>
				<title><?= htmlentities($event['type'], ENT_QUOTES, 'UTF-8') ?>: <?= htmlentities($event['title'], ENT_QUOTES, 'UTF-8'); ?></title>
				<link><?= htmlentities($event['url'], ENT_QUOTES, 'UTF-8') ?></link>
				<guid><?= htmlentities($event['ahId'], ENT_QUOTES, 'UTF-8') ?></guid>
				<description><?= htmlentities($event['title'], ENT_QUOTES, 'UTF-8'); ?></description>
				<pubDate><?= date(DATE_RFC2822, $event['date']) ?></pubDate>
				<author>The Aviation Herald</author>
			</item>
		<?php endforeach; ?>
        </channel>
</rss>


