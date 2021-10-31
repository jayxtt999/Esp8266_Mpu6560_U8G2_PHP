<?php
/**
 * Created by PhpStorm.
 * User: xietaotao
 * Date: 2021/4/28
 * Time: 11:09
 */

require __DIR__ . '/./vendor/autoload.php';

use Workerman\Crontab\Crontab;
use Workerman\Protocols\Http\Response;
use Workerman\Timer;
use Workerman\Worker;

define('APP_PATH', __DIR__);
define('LOG_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR);
$worker  = new Worker('http://127.0.0.1:3333');
$worker2 = new GlobalData\Server('127.0.0.1', 2207);

date_default_timezone_set('PRC');
Worker::$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'logs/workerman.log';


// 5个进程
$worker->count = 1;

$worker->onWorkerStart = function($worker) {
    $service = new Service();

    try {
        global $global;
        $global            = new \GlobalData\Client('127.0.0.1:2207');
        $config            = include __DIR__ . '/./config.php';
        $global->config    = $config;
        $global->rest    = 0;
        $global->stockData = [];
        Timer::add(1, [$service, 'loopStock']);


    } catch (\Exception $e) {
        $service->log($e->getMessage(), 'error');
    }


};
$worker->onMessage     = function(\Workerman\Connection\TcpConnection $connection, \Workerman\Protocols\Http\Request $request) {

    $service = new Service();

    try {

        global $global;
        $path = $request->path();
        $service->log('path:' . $path, 'api');
        switch ($path) {

            case '/list':
                $page     = (int)$request->get('page', 1);
                $pageSize = $request->get('page_size', 10);

                $stockData   = $global->stockData;
                $config      = $global->config;
                $stockConfig = $config['stock'];
                $count       = count($stockConfig);
                $countPage   = (int)ceil($count / $pageSize); #计算总页面数
                $start       = ($page - 1) * $pageSize;
                $stock       = array_slice($stockData, $start, $pageSize);
                if ($countPage !== $page) {
                    $nextPage = $page + 1;
                } else {
                    $nextPage = 0;
                }
                $data = [
                    'stock'     => $stock,
                    'next_page' => $nextPage,
                ];

                $response = $service->getResponse('success', $data);

                $connection->send($response);

                break;


            default:
                $response = $service->getResponse('hi~');
                $connection->send($response);
                break;

        }

    } catch (\Exception $e) {

        $response = $service->getResponse($e->getMessage());
        $connection->send($response);

    }


};

Worker::runAll();