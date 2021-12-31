<?php

namespace Starsquare\Mmf;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class UnderArmourResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner ID
     */
    public function getId()
    {
        return $this->getValueByKey($this->response, 'id');
    }

    /**
     * Returns the raw resource owner response.
     */
    public function toArray()
    {
        return $this->response;
    }

    public function getLink($type, $name = null)
    {
        $links = $this->getValueByKey($this->response, '_links');

        if (!isset($links[$type])) {
            return null;
        }

        $link = $links[$type];

        if (count($link) > 1 && $subtype) {
            $link = array_filter($link, function ($sublink) use ($name) {
                return $sublink['name'] == $name;
            });
        }

        if (count($link) == 1) {
            return $link[0]['href'];
        }

        return null;
    }
}
