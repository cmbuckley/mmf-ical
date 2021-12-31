<?php

namespace Starsquare\Mmf;

use League\OAuth2\Client\Token\AccessToken;

class UnderArmourApi {
    protected $options;
    protected $provider;
    protected $session;
    protected $resourceOwner;

    public function __construct($options) {
        $this->options = $options;
        $this->provider = new UnderArmourProvider($this->options['provider']);
        $this->session = $this->options['session']->getSegment(self::class);
    }

    protected function getResourceOwner() {
        if (!$this->resourceOwner) {
            $this->resourceOwner = $this->provider->getResourceOwner($this->getAccessToken());
        }

        return $this->resourceOwner;
    }

    protected function request($type, $method = 'GET') {
        $url = $this->provider->getApiUrl($this->getResourceOwner()->getLink($type));

        $request = $this->provider->getAuthenticatedRequest(
            $method,
            $url,
            $this->getAccessToken()
        );

        return $this->provider->getParsedResponse($request);
    }

    public function getWorkouts() {
        $workouts = array();
        $response = $this->request('workouts');

        foreach ($response['_embedded']['workouts'] as $workout) {
            $workouts[] = array(
                'id' => $workout['reference_key'],
                'created' => $workout['created_datetime'],
                'updated' => $workout['updated_datetime'],
                'start' => $workout['start_datetime'],
                'tz' => $workout['start_locale_timezone'],
                'duration' => $workout['aggregates']['elapsed_time_total'],
                'summary' => $workout['name'],
                'description' => $workout['notes'],
            );
        }

        return $workouts;
    }

    public function getAccessToken() {
        if (!$this->session->get('oauth_token')) {
            if (isset($this->options['code'])) {
                if ($this->session->get('oauth_state') &&
                    isset($this->options['state']) &&
                    $this->session->get('oauth_state') === $this->options['state']
                ) {
                    $accessToken = $this->provider->getAccessToken('authorization_code', [
                        'code' => $this->options['code'],
                    ]);

                    $this->session->set('oauth_token', json_encode($accessToken));
                    return $accessToken;
                }
            }

            return null;
        }

        $tokenData = json_decode($this->session->get('oauth_token'), true);
        $accessToken = new AccessToken($tokenData);

        if ($accessToken->hasExpired()) {
            try {
                $accessToken = $provider->getAccessToken('refresh_token', [
                    'refresh_token' => $accessToken->getRefreshToken()
                ]);
            } catch (Exception $ex) {
                return null;
            }
        }

        return $accessToken;
    }

    public function isAuthenticated() {
        return !!$this->getAccessToken();
    }

    public function getAuthorizationUrl() {
        $url = $this->provider->getAuthorizationUrl();
        $this->session->set('oauth_state', $this->provider->getState());
        return $url;
    }
}
