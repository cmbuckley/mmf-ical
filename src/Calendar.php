<?php

namespace Starsquare\Mmf;

class Calendar extends Component {
    protected $options;
    protected $name;
    protected $description;
    protected $cache;

    public function __construct(array $options) {
        $this->options = $options;
    }

    public function getStructure() {
        return array('vcalendar' => array(
            'version'       => $this->options['version'],
            'prodid'        => $this->options['productId'],
            'name'          => $this->name,
            'x-wr-calname'  => $this->name,
            'description'   => $this->description,
            'x-wr-caldesc'  => $this->description,
            'x-wr-timezone' => $this->options['timezone'],
            'vtimezone'     => new Timezone($this->options['timezone']),
            'vevent'        => $this->getEvents(),
        ));
    }

    public function setEvents(array $events) {
        $this->events = $events;
    }

    public function getEvents() {
        $events = array();

        foreach ($this->getApi()->getWorkouts() as $workout) {
            $workout['time_offset'] = $this->options->api->time_offset;
            $events[] = new WorkoutEvent($workout, $this);
        }

        $this->getCache()->save();
        return $events;
    }

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
