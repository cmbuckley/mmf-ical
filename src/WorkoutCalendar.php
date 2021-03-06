<?php

namespace Starsquare\Mmf;

class WorkoutCalendar extends Calendar {
    protected $name = 'Workouts';
    protected $description = 'Workout calendar for workouts logged in MapMyFitness';

    protected function getEvents() {
        $events = array();

        foreach ($this->getApi()->getWorkouts() as $workout) {
            $workout['time_offset'] = $this->options->api->time_offset;
            $events[] = new WorkoutEvent($workout, $this);
        }

        $this->getCache()->save();
        return $events;
    }
}
