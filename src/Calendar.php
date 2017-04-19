<?php

namespace Starsquare\Mmf;

abstract class Calendar extends Component {
    protected $options;
    protected $name;
    protected $description;
    protected $cache;

    public function __construct(array $options) {
        $this->options = json_decode(json_encode($options));
    }

    public function getStructure() {
        return array('vcalendar' => array(
            'version'       => $this->options->calendar->version,
            'prodid'        => $this->options->calendar->productId,
            'name'          => $this->name,
            'x-wr-calname'  => $this->name,
            'description'   => $this->description,
            'x-wr-caldesc'  => $this->description,
            'x-wr-timezone' => $this->options->calendar->timezone,
            'vtimezone'     => new Timezone($this->options->calendar->timezone),
            'vevent'        => $this->getEvents(),
        ));
    }

    public function getOptions() {
        return $this->options;
    }

    public function getOption($option, $default = null) {
        return isset($this->options->$option) ? $this->options->$option : $default;
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
