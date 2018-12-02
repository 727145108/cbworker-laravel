<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/3
 * Time: 12:41
 */

return [
  'env'           => 'local',
  'log'           => 'daily',
  'log_level'     => 'error',
  'name'          => 'CbWorkerApis',
  'namespace'     => 'Application\\Apis\\',

  'Language'      => 'zh',

  'report'        => true,
  'statistic'     => [
    'address'     => ''
  ],
  
  'providers'     => [
    Illuminate\Database\DatabaseServiceProvider::class,
    Cbworker\Core\Redis\RedisServiceProvider::class,
    Application\Apis\Providers\EventServiceProvider::class,
  ],
  
  'aliases'        => [
    'App'       => Illuminate\Support\Facades\App::class,
    'Cache'     => Illuminate\Support\Facades\Cache::class,
    'Config'    => Illuminate\Support\Facades\Config::class,
    'DB'        => Illuminate\Support\Facades\DB::class,
    'Event'     => Illuminate\Support\Facades\Event::class,
    'Log'       => Illuminate\Support\Facades\Log::class,
    'Queue'     => Illuminate\Support\Facades\Queue::class,
    'Redis'     => Illuminate\Support\Facades\Redis::class
  ]
];
