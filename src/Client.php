<?php

namespace Cryptomkt\Exchange;

// use Cryptomkt\Exchange\ActiveRecord\ActiveRecordContext;
// use Cryptomkt\Exchange\Enum\Param;
// use Cryptomkt\Exchange\Resource\Order;
// use Cryptomkt\Exchange\Resource\Resource;
// use Cryptomkt\Exchange\Resource\ResourceCollection;

class Client
{
    const VERSION = '1.1.0';

    private $http;
    private $mapper;

    /**
     * Creates a new Cryptomkt client.
     *
     * @return Client A new Cryptomkt client
     */
    public static function create(Configuration $configuration)
    {
        return new static(
            $configuration->createHttpClient(),
            $configuration->createMapper()
        );
    }

    public function __construct(HttpClient $http, Mapper $mapper)
    {
        $this->http = $http;
        $this->mapper = $mapper;
    }

    public function getHttpClient()
    {
        return $this->http;
    }

    public function getMapper()
    {
        return $this->mapper;
    }

    /** @return array|null */
    public function decodeLastResponse()
    {
        if ($response = $this->http->getLastResponse()) {
            return $this->mapper->decode($response);
        }
    }

    /**
     * Enables active record methods on resource objects.
     */
    public function enableActiveRecord()
    {
        ActiveRecordContext::setClient($this);
    }

    // Public endpoints
    public function getMarkets(){
        return $this->getAndMapData('/v1/market');
    }
    public function getTicker(array $params = []){
        return $this->getAndMapData('/v1/ticker',$params);
    }

    /**
     * Lists orders for the current user.
     * 
     * @return ResourceCollection|Order[]
     */
    public function getOrders(array $params = [])
    {
        return $this->getAndMapData('/v1/book', $params, 'toData');
    }

    public function getActiveOrders(array $params = [])
    {
        return $this->getAndMapData('/v1/orders/active', $params, 'toData');
    }

    public function getExecutedOrders(array $params = [])
    {
        return $this->getAndMapData('/v1/orders/executed', $params, 'toData');
    }

    /** @return Order */
    public function getOrder(array $params = [])
    {
        return $this->getAndMapData('/v1/orders/status', $params, 'toData');
    }
    
    public function cancelOrder(array $params = [])
    {
        return $this->postAndMap('/v1/orders/cancel', $params, 'toData');
    }

    public function createOrder(array $params = [])
    {
        return $this->postAndMap('/v1/orders/create', $params, 'toData');
    }

    public function createPayOrder(array $params = [])
    {
        return $this->postAndMap('/v1/payment/new_order', $params, 'toData');
    }

    public function getPayOrder(array $params = [])
    {
        return $this->getAndMapData('v1/payment/status', $params, 'toData');
    }

    /**
     * [getBalance description]
     * @return [array] [description]
     */
    public function getBalance()
    {
        return $this->getAndMapData('/v1/balance');
    }

    /**
     * Lists trades.
     *
     * @return ResourceCollection|Order[]
     */
    public function getTrades(array $params = [])
    {
        return $this->getAndMapData('/v1/trades', $params, 'toTrades');
    }

    // private

    private function getAndMapData($path, array $params = [])
    {
        $response = $this->http->get($path, $params);
        return $this->mapper->toData($response);
    }

    private function getAndMapMoney($path, array $params = [])
    {
        $response = $this->http->get($path, $params);

        return $this->mapper->toMoney($response);
    }

    /** @return ResourceCollection|Resource[] */
    private function getAndMapCollection($path, array $params, $mapperMethod)
    {
        $fetchAll = isset($params[Param::FETCH_ALL]) ? $params[Param::FETCH_ALL] : false;
        unset($params[Param::FETCH_ALL]);

        $response = $this->http->get($path, $params);

        /** @var ResourceCollection $collection */
        $collection = $this->mapper->$mapperMethod($response);

        if ($fetchAll) {
            while ($collection->hasNextPage()) {
                $this->loadNext($collection, $params, $mapperMethod);
            }
        }

        return $collection;
    }

    /** @return Resource */
    private function getAndMap($path, array $params, $mapperMethod, Resource $resource = null)
    {
        $response = $this->http->get($path, $params);

        return $this->mapper->$mapperMethod($response, $resource);
    }

    private function postAndMap($path, array $params, $mapperMethod, Resource $resource = null)
    {
        $response = $this->http->post($path, $params);

        return $this->mapper->$mapperMethod($response, $resource);
    }
}
