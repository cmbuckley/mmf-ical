<?php

namespace Starsquare\Mmf;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class UnderArmourProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected $apiVersion = 'v7.1';
    protected $baseUrl = 'https://api.ua.com';

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return "https://www.mapmyfitness.com/$this->apiVersion/oauth2/uacf/authorize/";
    }

    /**
     * Get access token url to retrieve token
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getApiUrl('/oauth2/uacf/access_token/', true);
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getApiUrl('/user/self/', true);
    }

    public function getDefaultScopes()
    {
        return null;
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new IdentityProviderException(
                isset($data['message']) ? $data['message'] : $response->getReasonPhrase(),
                $statusCode,
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param object $response
     * @param AccessToken $token
     * @return HerokuResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new UnderArmourResourceOwner($response);
    }

    public function getApiUrl($path, $withVersion = false)
    {
        $url = [$this->baseUrl];

        if ($withVersion) {
            $url[] = $this->apiVersion;
        }

        $url[] = ltrim($path, '/');
        return implode('/', $url);
    }
}
