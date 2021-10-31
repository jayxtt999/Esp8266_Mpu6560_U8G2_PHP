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

$worker = new GlobalData\Server('127.0.0.1', 2207);

date_default_timezone_set('PRC');
Worker::$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'logs/workerman.log';


// 5个进程
$worker->count = 1;

Worker::runAll();