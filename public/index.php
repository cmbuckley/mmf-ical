<?php

require '../vendor/autoload.php';

echo new Starsquare\Mmf\CalendarRenderer(array(
    'api' => array(
        'provider' => array(
            'clientId' => getenv('API_CLIENT'),
            'clientSecret' => getenv('API_SECRET'),
            'redirectUri' => getenv('REDIRECT_URI'),
        ),
        'code' => (isset($_GET['code']) ? $_GET['code'] : null),
        'state' => (isset($_GET['state']) ? $_GET['state'] : null),
        'session' => (new \Aura\Session\SessionFactory)->newInstance($_COOKIE),
    ),
    'calendar' => array(
        'version' => '2.0',
        'timezone' => 'Europe/London',
        'productId' => sprintf('-//StarSquare//MapMyFITNESS//%s//EN', '0.1'),
    ),
    'templatePath' => realpath(__DIR__ . '/../tpl'),
));
