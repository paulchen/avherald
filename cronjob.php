<?php

if(!defined('STDIN') && !defined($argc)) {
	die();
}

require_once('config.php');

$curl = curl_init('http://avherald.com');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Ubuntu; X11; Linux x86_64; rv:8.0) Gecko/20100101 Firefox/8.0');
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Connection: Keep-alive'));
$text = curl_exec($curl);
$info = curl_getinfo($curl);
curl_close($curl);

// print_r($info);
if($info['http_code'] != 200) {
	die();
}

/*
file_put_contents('abc', $text);
die();
echo "$text\n";

$text = file_get_contents('abc');
 */

$parts = preg_split('/bheadline_avherald/', $text);
$events = array();
$known_ids = array();
//print_r($parts);
foreach($parts as $part) {
	if(!preg_match('/^">([^<]+)<\/span>/', $part, $matches)) {
//		echo "1\n";
		continue;
	}
	$date = date('Y-m-d', strtotime($matches[1]));

	$parts2 = preg_split('/<img/', $part);
	foreach($parts2 as $part2) {
		if(!preg_match('/alt="([^"]+)"/', $part2, $matches2)) {
//			echo "2\n";
			continue;
		}
		$type = $matches2[1];

		if(!preg_match('/(h\?article=([^&"]+)[^"]+)"/', $part2, $matches2)) {
//			echo "3\n";
			continue;
		}
		$url = 'http://avherald.com/' . $matches2[1];
		$id = $matches2[2];

		if(!preg_match('/<span class="headline_avherald">([^<]+)<\/span>/', $part2, $matches2)) {
//			echo "4\n";
			continue;
		}
		$title = $matches2[1];

		if(!in_array($id, $known_ids)) {
			$events[] = array('url' => $url, 'title' => $title, 'date' => $date, 'id' => $id, 'type' => $type);
			$known_ids[] = $id;
		}
	}
}
$events = array_reverse($events);
// print_r($events);

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

$sql1 = 'SELECT id FROM avherald WHERE ahId = ? AND date = ?';
$sql2 = 'INSERT INTO avherald (ahId, url, title, date, type) VALUES (?, ?, ?, ?, ?)';

foreach($events as $event) {
	$stmt = $mysqli->prepare($sql1);
	$stmt->bind_param('ss', $event['id'], $event['date']);
	$stmt->execute();
	if(!$stmt->fetch()) {
		$stmt->close();

		$stmt = $mysqli->prepare($sql2);
		$stmt->bind_param('sssss', $event['id'], $event['url'], $event['title'], $event['date'], $event['type']);
		$stmt->execute();
	}
	$stmt->close();
}

$mysqli->close();

