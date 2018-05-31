<?php

namespace Cryptomkt\Exchange;

use Cryptomkt\Exchange\Authentication\Authentication;
use Cryptomkt\Exchange\Enum\Param;
use Cryptomkt\RequestOptions;
use Http\Discovery\MessageFactoryDiscovery;

class HttpClient
{
    private $apiUrl;
    private $apiVersion;
    private $auth;
    private $requestPath;
    private $curl;

    /** @var LoggerInterface */
    private $logger;

    /** @var RequestInterface */
    private $lastRequest;

    /** @var ResponseInterface */
    private $lastResponse;

    public function __construct($apiUrl, $apiVersion, $auth, $transport)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiVersion = $apiVersion;
        $this->auth = $auth;
        $this->transport = $transport;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /** @return ResponseInterface */
    public function get($path, array $params = [])
    {
        return $this->request('GET', $path, $params);
    }

    /** @return ResponseInterface */
    public function put($path, array $params = [])
    {
        return $this->request('PUT', $path, $params);
    }

    /** @return ResponseInterface */
    public function post($path, array $params = [])
    {
        return $this->request('POST', $path, $params);
    }

    /** @return ResponseInterface */
    public function delete($path, array $params = [])
    {
        return $this->request('DELETE', $path, $params);
    }

    public function refreshAuthentication(array $params = [])
    {
        if ($request = $this->auth->createRefreshRequest($this->apiUrl)) {
            $response = $this->send($request, $params);
            $this->auth->handleRefreshResponse($request, $response);
        }
    }

    public function revokeAuthentication(array $params = [])
    {
        if ($request = $this->auth->createRevokeRequest($this->apiUrl)) {
            $response = $this->send($request, $params);
            $this->auth->handleRevokeResponse($request, $response);
        }
    }

    // private

    private function request($method, $path, array $params = [])
    {
        if ('GET' === $method) {
            $path = $this->prepareQueryString($path, $params);
        }
        return $this->send($method, $path, $params);
    }

    private function send($method, $path, $params = [])
    {        
        $options = $this->prepareOptions(
            $method,
            parse_url($path, PHP_URL_PATH),
            $params
        );
        
        $messageFactory = MessageFactoryDiscovery::find(); 

        switch ($method) {
            case 'GET':
                $this->lastRequest = $request = $messageFactory->createRequest( $method, $this->prepareUrl($path), $options['headers']);  
                break;
            
            default:
                $this->lastRequest = $request = $messageFactory->createRequest( $method, $this->prepareUrl($path), $options['headers'], http_build_query($options['form_params']));  
                break;
        }

        $this->requestPath = parse_url($request->getRequestTarget(), PHP_URL_PATH);

        try {
            $this->lastResponse = $response = $this->transport->sendRequest($request);            
        } catch (RequestException $e) {
            throw HttpException::wrap($e);
        }

        if ($this->logger) {
            $this->logWarnings($response);
        }

        return $response;
    }

    private function prepareQueryString($path, array $params = [])
    {
        if (!$params) {
            return $path;
        }

        // omit two_factor_token
        $query = array_diff_key($params, [Param::TWO_FACTOR_TOKEN => true]);
        $params = array_intersect_key($params, [Param::TWO_FACTOR_TOKEN => true]);

        $path .= false === strpos($path, '?') ? '?' : '&';
        $path .= http_build_query($query, '', '&');

        return $path;
    }

    private function prepareUrl($path)
    {
        return $this->apiUrl.'/'.ltrim($path, '/');
    }

    private function prepareOptions($method, $path, array $params = [])
    {
        $options = [];

        if ($params && $method !== 'GET') {
            $options[RequestOptions::FORM_PARAMS] = $params;
            $body = $params;
        } else {
            $body = '';
        }

        $defaultHeaders = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $options[RequestOptions::HEADERS] = $defaultHeaders + $this->auth->getRequestHeaders(
            $method,
            $path,
            $body
        );

        return $options;
    }

    private function logWarnings(ResponseInterface $response)
    {
        $body = (string) $response->getBody();
        if (false === strpos($body, '"warnings"')) {
            return;
        }

        $data = json_decode($body, true);
        if (!isset($data['warnings'])) {
            return;
        }

        foreach ($data['warnings'] as $warning) {
            $this->logger->warning(isset($warning['url'])
                ? sprintf('%s (%s)', $warning['message'], $warning['url'])
                : $warning['message']);
        }
    }
}
