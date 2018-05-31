# CryptoMarket PHP Client

Official Client library [CryptoMarket API v1][1] to integrate CryptoMarket into your
PHP project, using HTTPlug client abstraction [HTTPlug][3].

## Installation

This library could be installed using Composer. Please read the [Composer Documentation](https://getcomposer.org/doc/01-basic-usage.md).

```json
"require": {
    "cryptomkt/cryptomkt-httplug-php": "dev-master"
}
```

## Authentication

### API Key

Use an API key and secret to access your own Crypto Market account.

```php
use Cryptomkt\Exchange\Client;
use Cryptomkt\Exchange\Configuration;

$configuration = Configuration::apiKey($apiKey, $apiSecret);
$client = Client::create($configuration);
```

### Warnings

This library will log all warnings to a
standard PSR-3 logger if one is configured.

```php
use Cryptomkt\Exchange\Client;
use Cryptomkt\Exchange\Configuration;

$configuration = Configuration::apiKey($apiKey, $apiSecret);
$configuration->setLogger($logger);
$client = Client::create($configuration);
```

### Responses

Each resource object has a `getRawData()` method which you can use to access any field that
are not mapped to the object properties.

```php
$data = $markets->getRawData();
```

Raw data from the last HTTP response is also available on the client object.

```php
$data = $client->decodeLastResponse();
```

## Usage

For more references, go to the [official documentation](https://developers.cryptomkt.com/).

### Market Data

**List markets**

```php
$markets = $client->getMarkets();
```

**Get ticker**

```php
$arguments = array('market' => 'ETHARS');
$ticker = $client->getTicker($arguments); 
```

**Get trades**

```php
$arguments = array('market' => 'ETHCLP','start' => '2017-05-20', 'end' => '2017-05-30', 'page' => 1);
$trades = $client->getTrades($arguments);
```

### Orders

**Get orders**

```php
$arguments = array('market' => 'ETHARS','type' => 'buy', 'page' => 1);
$orders = $client->getOrders($arguments); 
```

**Get order**

```php
$arguments = array('id' => 'M107435');
$order = $client->getOrder($arguments);  
```

**Get active orders**

```php
$arguments = array('market' => 'ETHCLP', 'page' => 0);
$active_orders = $client->getActiveOrders($arguments); 
```

**Get executed orders**

```php
$arguments = array('market' => 'ETHCLP', 'page' => 0);
var_dump($client->getExecutedOrders($arguments)); 
```

**Create order**

```php
$arguments = array(
        'amount' => '0.3',
        'market' => 'ethclp',
        'price' => '200000',
        'type' => 'sell'
    );
$response = $client->createOrder($arguments); 
```

**Cancel order**

```php
$arguments = array('id' => 'M107441');
$response = $client->cancelOrder($arguments); 
```

### Balance

**Get balance**

```php
$response = $client->getBalance(); 
```

**Create pay order**

```php
$arguments = array(
    'to_receive' => '3000',
    'to_receive_currency' => 'CLP',
    'payment_receiver' => 'receiver@email.com',
    'external_id' => '123456CM',
    'callback_url' => '',
    'error_url' => '',
    'success_url' => '',
    'refund_email' => 'refund@email.com'
);

$response = $client->createPayOrder($arguments);  
```

### Pay orders

**Get pay order**

```php
$arguments = array('id' => 'P13565');
$response = $client->getPayOrder($arguments);  
```

**Get pay orders**

```php
$arguments = array('start_date' => '1/05/2018','end_date' => '31/05/2018');
$response = $client->getPayOrders($arguments);  
```

## Contributing and testing

The test suite is built using PHPUnit. Run the suite of unit tests by running
the `phpunit` command.

```
phpunit
```

[1]: https://developers.cryptomkt.com
[2]: https://packagist.org/packages/cryptomkt/cryptomkt
[3]: https://github.com/php-http/httplug
