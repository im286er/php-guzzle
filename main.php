<?php


require "./vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Middleware;

$client = new Client(['base_uri' => 'https://api.suiyueyule.com/1.0.2/', 'timeout' => 20]);

$response = $client->request("GET", "feed/hot");

echo $response->getStatusCode();

$result = json_decode($response->getBody()->getContents(), true);

echo "<hr/>";

/**
 * @brief 并发发送请求
 */

$client = new Client(['base_uri' => 'http://httpbin.org/']);

$task = ['image' => $client->getAsync('/image'), 'png' => $client->getAsync('/image/png'), 'jpeg' => $client->getAsync('/image/jpeg'), 'webp' => $client->getAsync('/image/webp')];

$result = Promise\unwrap($task);

//var_dump( $result );

echo "<hr/>";

/**
 * @brief 不确定请求数量
 */

$client = new Client();

$requests = function ($total) {
    $uri = 'https://api.suiyueyule.com/1.0.2/feed/new';
    for ($i = 0; $i < $total; $i++) {
        yield new Request('GET', $uri);
    }
};

$pool = new Pool($client, $requests(10), ["concurrency" => 5, 'fulfilled' => function ($response, $index) {

    var_dump($index, $response->getBody()->getContents());

}, 'rejected' => function ($reason, $index) {

    var_dump("error: ", $reason, $index);

}]);

$promise = $pool->promise();

$promise->wait();

$tapMiddleware = Middleware::tap(function ($request) {
    var_dump($request);
});

$result = $client->request('GET', 'https://api.suiyueyule.com/1.0.2/feed/new',
    [
        'query' => ['page' => 2, 'limit' => 1],
        'headers' => [
            'X-Authorization' => 'xxx'
        ],
        'debug' => true,
        'handler' => $tapMiddleware($client->getConfig('handler'))
    ]);

echo $result->getBody()->getContents();






