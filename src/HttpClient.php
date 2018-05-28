<?php

namespace Cryptomkt\Exchange;

use Cryptomkt\Exchange\Authentication\Authentication;
// use Cryptomkt\Exchange\Enum\Param;
// use Cryptomkt\Exchange\Exception\HttpException;
// use GuzzleHttp\Exception\RequestException;
// use GuzzleHttp\Psr7\Request;
use Cryptomkt\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
// use Psr\Http\RequestInterface as Request;
// use Psr\Log\LoggerInterfaceuse GuzzleHttp\Psr7\Request;
use Cryptomkt\Psr7\Request; 
use \Curl\Curl;

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
        // var_dump($apiUrl);
        // var_dump($apiVersion);
        // var_dump($auth);
        // var_dump($transport);
        // exit;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiVersion = $apiVersion;
        $this->auth = $auth;
        $this->curl = new Curl();
        // $this->transport = $transport;
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
        // var_dump($path); exit;
        // var_dump($this->curl->get($path, array(
        //      $params
        // ))); exit;
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

        $request = new Request($method, $this->prepareUrl($path));

        return $this->send($request, $params);
    }

    private function send($request, array $params = [])
    {
        $this->lastRequest = $request;

        $this->requestPath = parse_url($request->getRequestTarget(), PHP_URL_PATH);
        
        $options = $this->prepareOptions(
            $request->getMethod(),
            $this->requestPath,
            $params
        );

        try {
            // $this->lastResponse = $response = $this->transport->send($request, $options);
            
            switch ($request->getMethod()) {
                case 'GET':
                    $this->lastResponse = $response = $this->curl->get($request->getUri());    
                    break;
                case 'POST':
                    // $path = $this->prepareQueryString($path, $params);
                    break;
                case 'PUT':
                    // $path = $this->prepareQueryString($path, $params);
                    break;
                case 'DELETE':
                    // $path = $this->prepareQueryString($path, $params);
                    break;
                
                default:
                    # code...
                    break;
            }
        } catch (RequestException $e) {
            throw HttpException::wrap($e);
        }

        if ($this->logger) {
            $this->logWarnings($response);
        }

        return $response;
    }

    private function prepareQueryString($path, array &$params = [])
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

        if ($params) {
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
