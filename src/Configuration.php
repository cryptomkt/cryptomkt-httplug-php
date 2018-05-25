<?php

namespace Cryptomkt\Exchange;

use Cryptomkt\Exchange\Authentication\ApiKeyAuthentication;
use Cryptomkt\Exchange\Authentication\Authentication;

class Configuration
{
    const DEFAULT_API_URL = 'https://api.cryptomkt.com';
    const DEFAULT_API_VERSION = '1.1';

    private $authentication;
    private $apiUrl;
    private $apiVersion;
    private $logger;

    /**
     * Creates a new configuration with API key authentication.
     *
     * @param string $apiKey    An API key
     * @param string $apiSecret An API secret
     *
     * @return Configuration A new configuration instance
     */
    public static function apiKey($apiKey, $apiSecret)
    {
        return new static(
            new ApiKeyAuthentication($apiKey, $apiSecret)
        );
    }

    public function __construct(Authentication $authentication)
    {
        $this->authentication = $authentication;
        $this->apiUrl = self::DEFAULT_API_URL;
        $this->apiVersion = self::DEFAULT_API_VERSION;
    }

    /** @return HttpClient */
    public function createHttpClient()
    {
        $httpClient = new HttpClient(
            $this->apiUrl,
            $this->apiVersion,
            $this->authentication
        );

        // $httpClient->setLogger($this->logger);

        return $httpClient;
    }

    /** @return Mapper */
    public function createMapper()
    {
        return new Mapper();
    }

    public function getAuthentication()
    {
        return $this->authentication;
    }

    public function setAuthentication(Authentication $authentication)
    {
        $this->authentication = $authentication;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }
}
