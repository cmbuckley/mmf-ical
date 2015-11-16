<?php

namespace Starsquare\Mmf;

class Event extends Component {
    protected $structure;

    public function __construct(array $structure) {
        $this->structure = $structure;
    }

    public function getStructure() {
        return $this->structure;
    }

    protected function getDate($offset, $date, $time = null) {
        $dateTime = new \DateTime("$date $time", new \DateTimeZone('UTC'));
        $dateTime->modify($offset);
        return $dateTime->format('Ymd\THis\Z');
    }
}
