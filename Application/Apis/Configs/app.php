<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/11/3
 * Time: 12:41
 */

return [
  'env'           => 'local',
  'debug'         => 'true',

  /*日志信息配置*/
  'log'           => 'daily',
  'log_level'     => 'debug',
  'log_max_files' => 7,

  'name'          => 'CbWorkerApis',
  'namespace'     => 'Application\Apis\\',
  'prefix'        => '/v1',

  'report'        => true,
  'statistic'     => [
    'address'     => ''
  ],

  'providers'     => [
    Illuminate\Bus\BusServiceProvider::class,
    Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Filesystem\FilesystemServiceProvider::class,
    Illuminate\Database\MigrationServiceProvider::class,
    Cbworker\Core\Queue\QueueServiceProvider::class,
    Cbworker\Core\Redis\RedisServiceProvider::class,
    Application\Apis\Providers\EventServiceProvider::class,
  ],

  'aliases'        => [
    'App'       => Illuminate\Support\Facades\App::class,
    'Cache'     => Illuminate\Support\Facades\Cache::class,
    'Config'    => Illuminate\Support\Facades\Config::class,
    'DB'        => Illuminate\Support\Facades\DB::class,
    'Schema'    => Illuminate\Support\Facades\Schema::class,
    'Event'     => Illuminate\Support\Facades\Event::class,
    'Log'       => Illuminate\Support\Facades\Log::class,
    'Queue'     => Illuminate\Support\Facades\Queue::class,
    'Redis'     => Illuminate\Support\Facades\Redis::class
  ]
];
