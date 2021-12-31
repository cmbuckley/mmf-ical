<?php

namespace Starsquare\Mmf;

class CalendarRenderer {
    protected $options;
    protected $calendar;
    protected $api;

    public function __construct(array $options) {
        $this->options = $options;
    }

    public function __toString() {
        try {
            $output = $this->getBody();
            $this->sendHeaders();
            return (string) $output;
        } catch (\Exception $ex) {
            return (string) $ex;
        }
    }

    public function getApi() {
        return (null === $this->api ? $this->api = new UnderArmourApi($this->options['api']) : $this->api);
    }

    public function getCalendar() {
        return (null === $this->calendar ? $this->calendar = new Calendar($this->options['calendar']) : $this->calendar);
    }

    public function getBody() {
        $api = $this->getApi();

        if ($api->isAuthenticated()) {
            $calendar = $this->getCalendar();
            $workouts = $api->getWorkouts();
            var_dump($workouts);
            exit;
            $calendar->setEvents($workouts);
            return $calendar->getOutput();
        }

		return $this->getTemplate('login.php', [
            'authorize_url' => $api->getAuthorizationUrl(),
		]);
    }

    protected function getTemplate($template, array $data) {
        try {
            ob_start();
			extract($data);
			include $this->options['templatePath'] . '/' . $template;
            $output = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

		return $output;
    }

    protected function sendHeaders() {
        if (!headers_sent()) {
            foreach ($this->getHeaders() as $name => $value) {
                header("$name: $value");
            }
        }
    }

    public function getHeaders() {
        if (!$this->getApi()->isAuthenticated()) {
            return [];
        }

        return array(
            'Content-Type'  => 'text/plain',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires'       => 'Sat, 29 Sep 1984 15:00:00 GMT',
            'Last-Modified' => 'Sat, 29 Sep 1984 15:00:00 GMT',
            'ETag'          => '"' . md5($this->getBody()) . '"',
        );
    }
}
