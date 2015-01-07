<?php

CalendarRenderer::factory('workout', array(
    'api' => array(
        'scheme' => 'http',
        'host' => 'api.mapmyfitness.com',
        'version' => '3.1',
        'paths' => array(
            'workouts' => 'workouts/get_workouts',
        ),
        'format' => 'json',
        'user' => 563714,
        'timezone' => 'America/Chicago',
    ),
    'calendar' => array(
        'version' => '2.0',
        'timezone' => 'Europe/London',
        'productId' => '-//StarSquare//MapMyFITNESS//EN',
        'cache' => sys_get_temp_dir() . '/mmf-ics-cache',
    ),
))->render();

class FitnessApi {
    protected $options;

    public function __construct($options) {
        $this->options = $options;
    }


    protected function getResponse($response) {
        switch ($this->options->format) {
            case 'json': return json_decode($response, true);
            case 'php':  return unserialize($response);
        }
    }

    protected function request($path, array $query = array()) {
        if (!isset($this->options->paths->$path)) {
            throw new Exception("Invalid path type [$path]");
        }

        $query['o'] = $this->options->format;
        $query['user_id'] = $this->options->user;

        $url = http_build_url(array(
            'scheme' => $this->options->scheme,
            'host' => $this->options->host,
            'path' => '/' . $this->options->version . '/',
        ), array(
            'path' => $this->options->paths->$path,
            'query' => http_build_query($query),
        ), HTTP_URL_JOIN_PATH);

        $response = $this->getResponse(file_get_contents($url));
        if (!isset($response['result']['status']) || 1 != $response['result']['status']) {
            $errors = (isset($response['result']['errors']) ? $response['result']['errors'] : array());
            $message = 'API error';
            if (count($errors) > 0) {
                $message .= (count($errors) > 1 ? 's' : '') . ":\n" . implode("\n", $errors);
            }

            throw new Exception($message);
        }

        return $response['result']['output'];
    }

    protected function getWorkoutsByCompletedFlag($flag) {
        $workouts = array();
        $count = $start = null;
        $page = 100;

        while (null === $count || $count > count($workouts) || $start > $count) {
            $output = $this->request('workouts', array(
                'completed_flag' => $flag,
                'limit' => $page,
                'start_record' => (int) $start,
            ));

            $count = $output['count'];
            $start += ($count > $page ? $page : 0);

            foreach ($output['workouts'] as $workout) {
                $workouts[$workout['workout_id']] = $workout;
            }
        }

        return $workouts;
    }

    public function getWorkouts($completed = true, $pending = true) {
        $workouts = array();

        if ($pending) {
            $workouts += $this->getWorkoutsByCompletedFlag(0);
        }

        if ($completed) {
            $workouts += $this->getWorkoutsByCompletedFlag(1);
        }

        return $workouts;
    }
}

class EventCache {
    protected $file;
    protected $cache;

    public function __construct($file) {
        $this->file = $file;
        $this->loadCache();
    }

    protected function loadCache() {
        touch($this->file);
        $this->cache = json_decode(file_get_contents($this->file), true);

        if (null === $this->cache) {
            $this->cache = array();
        }
    }

    public function get($id) {
        return (isset($this->cache[$id]) ? $this->cache[$id] : null);
    }

    public function set($id, array $data) {
        $this->cache[$id] = $data;
    }

    public function save() {
        file_put_contents($this->file, json_encode($this->cache));
    }
}

abstract class Component {
    abstract public function getStructure();
}

abstract class Calendar extends Component {
    protected $options;
    protected $api;
    protected $name;
    protected $cache;

    public function __construct(array $options) {
        $this->options = json_decode(json_encode($options));
    }

    public function getApi() {
        return (null === $this->api ? $this->api = new FitnessApi($this->options->api) : $this->api);
    }

    public function getStructure() {
        return array('vcalendar' => array(
            'version'       => $this->options->calendar->version,
            'prodid'        => $this->options->calendar->productId,
            'x-wr-calname'  => $this->name,
            'x-wr-timezone' => $this->options->calendar->timezone,
            'vtimezone'     => new Timezone($this->options->calendar->timezone),
            'vevent'        => $this->getEvents(),
        ));
    }

    public function getCache() {
        return (null === $this->cache ? $this->cache = new EventCache($this->options->calendar->cache) : $this->cache);
    }

    abstract protected function getEvents();

    public function getOutput() {
        return $this->getOutputFromStructure($this->getStructure());
    }

    protected function getOutputFromStructure(array $structure, $overrideProperty = null) {
        $output = array();

        foreach ($structure as $property => $value) {
            if (is_array($value)) {
                $arrayStructure = $this->getOutputFromStructure($value, $property);

                if (is_int($property) || null === $overrideProperty) {
                    array_unshift($arrayStructure, 'BEGIN:' . strtoupper(is_int($property) ? $overrideProperty : $property));
                    array_push($arrayStructure, 'END:' . strtoupper(is_int($property) ? $overrideProperty : $property));
                }

                $output = array_merge($output, $arrayStructure);
            } elseif ($value instanceof Component) {
                $componentStructure = $value->getStructure();

                if (is_array($componentStructure)) {
                    $output = array_merge(
                        $output,
                        array('BEGIN:' . strtoupper(is_int($property) ? $overrideProperty : $property)),
                        $this->getOutputFromStructure($componentStructure),
                        array('END:' . strtoupper(is_int($property) ? $overrideProperty : $property))
                    );
                }
            } else {
                $output[] = strtoupper($property) . ':' . $value;
            }
        }

        return $output;
    }
}

class WorkoutCalendar extends Calendar {
    protected $name = 'Workouts';

    protected function getEvents() {
        $events = array();

        foreach ($this->getApi()->getWorkouts() as $workout) {
            $workout['timezone'] = $this->options->api->timezone;
            $events[] = new WorkoutEvent($workout, $this);
        }

        $this->getCache()->save();
        return $events;
    }
}

class Event extends Component {
    protected $structure;

    public function __construct(array $structure) {
        $this->structure = $structure;
    }

    public function getStructure() {
        return $this->structure;
    }

    protected function getDate($timezone, $date, $time = null) {
        $dateTime = new DateTime("$date $time", new DateTimeZone($timezone));
        $dateTime->setTimezone(new DateTimeZone('UTC'));
        return $dateTime->format('Ymd\THis\Z');
    }
}

class WorkoutEvent extends Event {
    protected $workout;
    protected $calendar;

    public function __construct(array $workout, WorkoutCalendar $calendar) {
        $this->workout = $workout;
        $this->calendar = $calendar;

        return parent::__construct($this->build($this->workout));
    }

    protected function cache(array $structure) {
        $id    = $this->workout['workout_id'];
        $cache = $this->calendar->getCache();
        $data  = $cache->get($id);

        if (null === $data || $structure['last-modified'] > $data['last-modified']) {
            $data = array(
                'last-modified' => $structure['last-modified'],
                'sequence'      => (null === $data ? 0 : $data['sequence'] + 1),
            );
        }

        $cache->set($id, $data);
        $structure['sequence'] = $data['sequence'];
        return $structure;
    }

    protected function build(array $workout) {
        return $this->cache(array(
            'dtstamp'       => $this->getDate($workout['timezone'], $workout['updated_date']),
            'uid'           => $workout['user_id'] . '-WORKOUT-' . $workout['workout_id'],
            'dtstart'       => $this->getDate($workout['timezone'], $workout['workout_date'], $workout['workout_start_time']),
            'dtend'         => $this->getDate($workout['timezone'], $workout['workout_date'], $workout['workout_end_time']),
            'created'       => $this->getDate($workout['timezone'], $workout['created_date']),
            'description'   => $workout['notes'],
            //'geo'         => '', // lat;lng from workout point[0]
            'last-modified' => $this->getDate($workout['timezone'], $workout['updated_date']),
            //'organizer'   => '', // user email
            'status'        => ('1' == $workout['completed_flag'] ? 'CONFIRMED' : 'TENTATIVE'),
            'summary'       => $workout['workout_description'],
            'url'           => 'http://www.mapmyfitness.com/workout/' . $workout['workout_id'],
            'sequence'      => '0',
            'transp'        => 'OPAQUE',
        ));
    }
}

class Timezone extends Component {
    protected $timezone;

    public function __construct($timezone) {
        $this->timezone = new DateTimeZone($timezone);
    }
    public function getStructure() {
        return array(
            'tzid' => $this->timezone->getName(),
            'x-lic-location' => $this->timezone->getName(),
        );
    }
}

class CalendarRenderer {
    protected $calendar;
    protected $body;

    public function __construct(Calendar $calendar) {
        $this->calendar = $calendar;
    }

    public function getOutput() {
        return array(
            'headers' => $this->getHeaders(),
            'body' => $this->getBody(),
        );
    }

    public function getBody() {
        if (null === $this->body) {
            $this->body = implode("\r\n", $this->calendar->getOutput());
        }

        return $this->body;
    }

    public function render() {
        $this->sendHeaders();
        echo $this->getBody();
    }

    protected function sendHeaders() {
        if (!headers_sent()) {
            foreach ($this->getHeaders() as $name => $value) {
                header("$name: $value");
            }
        }
    }

    public function getHeaders() {
        return array(
            'Content-Type' => 'text/calendar',
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => 'Sat, 29 Sep 1984 15:00:00 GMT',
            'Last-Modified' => 'Sat, 29 Sep 1984 15:00:00 GMT',
            'ETag' => '"' . md5($this->getBody()) . '"',
        );
    }

    public static function factory($type, array $options) {
        $type = strtolower($type);
        $types = array(
            'workout' => 'WorkoutCalendar',
        );

        if (!isset($types[$type])) {
            throw new Exception("Invalid calendar type [$type]");
        }

        $calendar = $types[$type];
        $renderer = new static(new $calendar($options));
        return $renderer;
    }
}
