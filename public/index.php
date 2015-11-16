<?php

require '../vendor/autoload.php';

Starsquare\Mmf\CalendarRenderer::factory('workout', array(
    'debug' => (isset($_GET['v']) && $_GET['v'] == 'debug'),
    'api' => array(
        'scheme' => 'http',
        'host' => 'api.mapmyfitness.com',
        'version' => '3.1',
        'paths' => array(
            'workouts' => 'workouts/get_workouts',
        ),
        'format' => 'json',
        'user' => 563714,
        'time_offset' => '5 hours',
    ),
    'calendar' => array(
        'version' => '2.0',
        'timezone' => 'Europe/London',
        'productId' => '-//StarSquare//MapMyFITNESS//EN',
        'cache' => sys_get_temp_dir() . '/mmf-ics-cache',
    ),
))->render();
