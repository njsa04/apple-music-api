<?php

namespace PouleR\AppleMusicAPI;

use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\RequestFactory;
use Psr\Http\Message\StreamInterface;

/**
 * Class APIClient
  */
class APIClient
{
    const APPLEMUSIC_API_URL = 'https://api.music.apple.com/v1/';

    /**
     * Return types for json_decode
     */
    const RETURN_AS_OBJECT = 0;
    const RETURN_AS_ASSOC = 1;

    /**
     * @var PluginClient|HttpClient|null
     */
    protected $httpClient;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var string
     */
    protected $developerToken = '';

    /**
     * @var string
     */
    protected $musicUserToken = '';

    /**
     * @var int
     */
    protected $lastHttpStatusCode = 0;

    /**
     * @var
     */
    protected $responseType = self::RETURN_AS_OBJECT;

    /**
     * APIClient constructor.
     * @param HttpClient|null     $httpClient
     * @param RequestFactory|null $requestFactory
     */
    public function __construct(HttpClient $httpClient = null, RequestFactory $requestFactory = null)
    {
        if (!$httpClient) {
            $httpClient = new PluginClient(
                HttpClientDiscovery::find(),
                [new ErrorPlugin()]
            );
        }

        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * @param string $developerToken
     */
    public function setDeveloperToken(string $developerToken)
    {
        $this->developerToken = $developerToken;
    }

    /**
     * @return string
     */
    public function getDeveloperToken()
    {
        return $this->developerToken;
    }

    /**
     * @param string $musicUserToken
     */
    public function setMusicUserToken(string $musicUserToken)
    {
        $this->musicUserToken = $musicUserToken;
    }

    /**
     * @param string                               $method
     * @param string                               $service
     * @param array                                $headers
     * @param resource|string|StreamInterface|null $body
     *
     * @return array|object
     *
     * @throws AppleMusicAPIException
     */
    public function apiRequest($method, $service, array $headers = [], $body = null)
    {
        $url = sprintf(
            '%s/%s',
            self::APPLEMUSIC_API_URL,
            $service
        );

        $authorizationHeaders = $this->setAuthorizationHeaders();
        $headers = array_merge($headers, $authorizationHeaders);

        try {
            $response = $this->httpClient->sendRequest(
                $this->requestFactory->createRequest($method, $url, $headers, $body)
            );
        } catch (\Exception | \Http\Client\Exception $exception) {
            throw new AppleMusicAPIException(
                sprintf(
                    'API Request: %s, %s (%s)',
                    $service,
                    $exception->getMessage(),
                    $exception->getCode()
                ),
                $exception->getCode()
            );
        }

        $this->lastHttpStatusCode = $response->getStatusCode();

        return json_decode($response->getBody(), $this->responseType === self::RETURN_AS_ASSOC);
    }

    /**
     * @return int
     */
    public function getLastHttpStatusCode()
    {
        return $this->lastHttpStatusCode;
    }

    /**
     * @param int $responseType
     */
    public function setResponseType($responseType)
    {
        $this->responseType = $responseType;
    }
    /**
     * @return int
     */
    public function getResponseType()
    {
        return $this->responseType;
    }

    /**
     * @return array
     */
    protected function setAuthorizationHeaders(): array
    {
        $authorizationHeaders = [
            'Authorization' => 'Bearer ' . $this->developerToken,
        ];

        if (!empty($this->musicUserToken)) {
            $authorizationHeaders['Music-User-Token'] = $this->musicUserToken;
        }

        return $authorizationHeaders;
    }
}
