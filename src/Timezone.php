<?php

namespace Starsquare\Mmf;

class Timezone extends Component {
    protected $timezone;

    public function __construct($timezone) {
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function getStructure() {
        return array(
            'tzid'           => $this->timezone->getName(),
            'x-lic-location' => $this->timezone->getName(),
        );
    }
}
