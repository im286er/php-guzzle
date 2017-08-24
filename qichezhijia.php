<?php

require './vendor/autoload.php';

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Pool;

$brands = []; //品牌

$series = []; //车系

$model = []; //车型

$timeout = 10;
$concurrency = 100;

$handleStack = HandlerStack::create();

$handleStack->push(Middleware::retry(function ($retries) {
    return $retries < 3;
}, function ($retries) {
    return pow(2, $retries - 1);
}));

$client = new Client([
//    'debug'    => true,
    'timeout'  => $timeout,
    'base_uri' => 'https://cars.app.autohome.com.cn/',
    'headers'  => [
        'User-Agent' => 'Android\t6.0.1\tautohome\t8.3.0\tAndroid'
    ],
    'handler'  => $handleStack
]);

//品牌列表页
$url = "/cars_v8.3.0/cars/brands-pm2.json";

$response = $client->get($url);
$contents = $response->getBody()->getContents();
$contents = json_decode($contents, true);
$contents = $contents["result"]["brandlist"];

foreach ($contents as $index => $item) {

    $letter = $item["letter"];

    foreach ($item['list'] as $v) {

        $brands[] = [
            'id'      => $v['id'],
            'name'    => $v['name'],
            'imgurl'  => $v['imgurl'],
            'lettter' => $letter
        ];
    }
};

var_dump($brands);

//品牌介绍页
$tasks = function ($brands) {
    foreach ($brands as $brand) {
        $url = "/cars_v8.3.0/cars/getbrandinfo-pm2-b{$brand['id']}.json";
        yield new Request('GET', $url);
    }
};

$pool = new Pool($client, $tasks($brands), [
    'concurrency' => $concurrency,
    'fulfilled'   => function ($response, $index) use (&$brands) {
        $contents = $response->getBody()->getContents();
        $contents = json_decode($contents, true);
        $contents = $contents["result"]["list"];
        $contents = $contents ? $contents[0]["decription"] : '';
        $brands[$index]["desc"] = $contents;
    },
    'rejected'    => function ($reason, $index) {
        var_dump("Error: ${index}: ${reason}");
    }
]);

$pool->promise()->wait();

var_dump($brands);



