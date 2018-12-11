<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

use \Workerman\Worker;
use \Cbworker\Core\Application;
use \Workerman\Lib\Timer;

require_once __DIR__ . '/../../vendor/autoload.php';

define('HEARTBEAT_TIME', 60);

$app = new Application(
  realpath(__DIR__)
);

$app->singleton(
  Illuminate\Contracts\Http\Kernel::class,
  Cbworker\Core\Kernel::class
);

$app->singleton(
  'dispatcher',
  Cbworker\Core\Http\Dispatcher::class
);


$_worker = new Worker('http://127.0.0.1:8282');
$_worker->count = 1;
$_worker->onWorkerStart = function ($worker) use ($app) {
  $_kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);
  $_kernel->bootstrap();
  if($worker->id === 0) {
    Timer::add(1, function() use ($worker) {
      $now_time = time();
      foreach ($worker->connections as $connection) {
        if(!isset($connection->lastMessageTime)) {
          $connection->lastMessageTime = $now_time;
          continue;
        }
        if($now_time - $connection->lastMessageTime > HEARTBEAT_TIME) {
          $connection->close();
        }
      }
    });
  }
};
$_worker->onConnect = function ($connection) {
  //echo $connection->id . " onConnect \n";
};
$_worker->onMessage = function ($connection, $data) use ($app) {
  //echo $connection->id . " onMessage \n";
  $_request = new \Cbworker\Core\Http\HttpRequest($data);
  $_response = new \Cbworker\Core\Http\HttpResponse($connection);
  $app->make('dispatcher')->dispatch($_request, $_response);
};
$_worker->onClose = function ($connection) {
  //echo $connection->id . " Close \n";
};
$_worker->onWorkerReload = function ($worker) {

};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
  Worker::runAll();
}
