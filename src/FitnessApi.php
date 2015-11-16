<?php

namespace Starsquare\Mmf;

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
            'host'   => $this->options->host,
            'path'   => '/' . $this->options->version . '/',
        ), array(
            'path'  => $this->options->paths->$path,
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
                'limit'          => $page,
                'start_record'   => (int) $start,
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
