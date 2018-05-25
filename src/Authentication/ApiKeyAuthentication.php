<?php

namespace Cryptomkt\Exchange\Authentication;

class ApiKeyAuthentication
{
    private $apiKey;
    private $apiSecret;

    public function __construct($apiKey, $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;
    }

    public function getRequestHeaders($method, $path, $body)
    { 
        $timestamp = $this->getTimestamp();

        switch ($path){            
            case '/v1/orders/create':
                $message_to_sign = $timestamp . $path . $body['amount'].$body['market'].$body['price'].$body['type'];
                break;

            case '/v1/orders/cancel':
                $message_to_sign = $timestamp . $path . $body['id'];
                break;

            case '/v1/payment/new_order':
                $message_to_sign = $timestamp . $path . $body['callback_url'].$body['error_url'].$body['external_id'].$body['payment_receiver'].$body['refund_email'].$body['success_url'].$body['to_receive'].$body['to_receive_currency'];
                break;   
            
            default:
                if(!is_string($body)){
                    $body = http_build_query($body);
                }
                $message_to_sign = $timestamp.$path.$body;
                break;
        }

        $signature = $this->getHash('sha384', $message_to_sign, $this->apiSecret);

        return [
            'X-MKT-APIKEY' => $this->apiKey,
            'X-MKT-SIGNATURE' => $signature,
            'X-MKT-TIMESTAMP' => (string)$timestamp,
        ];
    }

    // protected

    protected function getTimestamp()
    {
        return time();
    }

    protected function getHash($algo, $data, $key)
    {
        return hash_hmac($algo, $data, $key, FALSE);
    }
}
