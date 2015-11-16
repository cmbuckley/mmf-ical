<?php

namespace Starsquare\Mmf;

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
            'dtstamp'       => $this->getDate($workout['time_offset'], $workout['updated_date']),
            'uid'           => $workout['user_id'] . '-WORKOUT-' . $workout['workout_id'],
            'dtstart'       => $this->getDate($workout['time_offset'], $workout['workout_date'], $workout['workout_start_time']),
            'dtend'         => $this->getDate($workout['time_offset'], $workout['workout_date'], $workout['workout_end_time']),
            'created'       => $this->getDate($workout['time_offset'], $workout['created_date']),
            'description'   => $workout['notes'],
            //'geo'         => '', // lat;lng from workout point[0]
            'last-modified' => $this->getDate($workout['time_offset'], $workout['updated_date']),
            //'organizer'   => '', // user email
            'status'        => ('1' == $workout['completed_flag'] ? 'CONFIRMED' : 'TENTATIVE'),
            'summary'       => $workout['workout_description'],
            'url'           => 'http://www.mapmyfitness.com/workout/' . $workout['workout_id'],
            'sequence'      => '0',
            'transp'        => 'OPAQUE',
        ));
    }
}
