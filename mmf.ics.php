<?php

define('PRODUCT_ID', '-//StarSquare//MapMyFITNESS//EN');
define('VERSION', '2.0');
define('API_TIMEZONE', 'America/Chicago');

$url = array(
    'scheme' => 'http',
    'host'   => 'api.mapmyfitness.com',
    'path'   => '/3.1/workouts/get_workouts',
    'query'  => array(
        'o'              => 'json',
        'user_id'        => 563714,
        'completed_flag' => 1,
        'start_record'   => 0,
        'limit'          => 100,
    ),
);

$url['query'] = http_build_str($url['query']);
$url = http_build_url($url);

$response = json_decode(file_get_contents($url), true);
$workouts = $response['result']['output']['workouts'];
//var_dump($workouts);

function getEventDate($date, $time = null) {
    $dateTime = new DateTime(rtrim("$date $time"), new DateTimeZone(API_TIMEZONE));
    $dateTime->setTimezone(new DateTimeZone('UTC'));
    return $dateTime->format('Ymd\THis\Z');
}

function getEvent(array $workout) {
    return array(
        'dtstamp'     => getEventDate($workout['updated_date']),
        'uid'         => $workout['user_id'] . ':WORKOUT:' . $workout['workout_id'],
        'dtstart'     => getEventDate($workout['workout_date'], $workout['workout_start_time']),
        'dtend'       => getEventDate($workout['workout_date'], $workout['workout_end_time']),
        'created'     => getEventDate($workout['created_date']),
        'description' => $workout['notes'],
        'geo'         => '', // lat;lng from workout point[0]
        'last-mod'    => getEventDate($workout['updated_date']),
        'organizer'   => '', // user email
        'status'      => ('1' == $workout['completed_flag'] ? 'CONFIRMED' : 'TENTATIVE'),
        'summary'     => $workout['workout_description'],
        'url'         => 'http://www.mapmyfitness.com/workout/' . $workout['workout_id'],
    );
}

function output(array $events) {
    $calendar = array(
        'version'       => VERSION,
        'prodid'        => PRODUCT_ID,
        'x-wr-calname'  => 'Workouts',
        'x-wr-timezone' => 'Europe/London',
    );

    $output = array('BEGIN:VCALENDAR');

    foreach ($calendar as $property => $value) {
        $output[] = strtoupper($property) . ':' . $value;
    }

    foreach ($events as $event) {
        $output[] = 'BEGIN:VEVENT';

        foreach ($event as $property => $value) {
            if (!empty($value)) {
                $output[] = strtoupper($property) . ':' . $value;
            }
        }

        $output[] = 'END:VEVENT';
    }

    $output[] = 'END:VCALENDAR';
    return $output;
}

$events = array_map('getEvent', $workouts);
$output = implode("\r\n", output($events));

header('Content-Type: text/calendar');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 29 Sep 1984 15:00:00 GMT');
header('Last-Modified: Sat, 29 Sep 1984 15:00:00 GMT');
header('ETag: "' . md5($output) . '"');
echo $output;
