<?php

namespace Starsquare\Mmf;

class CalendarRenderer {
    protected $calendar;
    protected $body;

    public function __construct(Calendar $calendar) {
        $this->calendar = $calendar;
    }

    public function getOutput() {
        return array(
            'headers' => $this->getHeaders(),
            'body'    => $this->getBody(),
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
            'Content-Type'  => 'text/calendar',
            'Content-Type'  => 'text/plain',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires'       => 'Sat, 29 Sep 1984 15:00:00 GMT',
            'Last-Modified' => 'Sat, 29 Sep 1984 15:00:00 GMT',
            'ETag'          => '"' . md5($this->getBody()) . '"',
        );
    }

    public static function factory($type, array $options) {
        $type = strtolower($type);
        $types = array(
            'workout' => WorkoutCalendar::class,
        );

        if (!isset($types[$type])) {
            throw new Exception("Invalid calendar type [$type]");
        }

        $calendar = $types[$type];
        $renderer = new static(new $calendar($options));
        return $renderer;
    }
}
