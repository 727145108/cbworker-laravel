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

$_worker = new Worker();
$_worker->count = 1;
$_worker->onWorkerStart = function ($worker) use ($app) {
  $_kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);
  $_kernel->bootstrap();
  Timer::add(0.5, function() use ($app) {
    while (true) {
      try {
        $job = app('queue')->connection()->pop();
        if($job) {
          echo "Run Job ==> " . $job->getName() . "\tattempts:" . $job->attempts() . "\n";
          $job->fire();
        }
      } catch (\Exception $e) {
        logger()->error("Job Run Error:", [$e->getMessage()]);
        return;
      }
    }
  });
};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
  Worker::runAll();
}
