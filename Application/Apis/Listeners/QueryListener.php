<?php

/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/12/2
 * Time: 11:11
 */
namespace Application\Apis\Listeners;

use Illuminate\Database\Events\QueryExecuted;

class QueryListener
{
  public function __construct()
  {
  }
  
  public function handle(QueryExecuted $event) {
    $sql = str_replace("?", "'%s'", $event->sql);
    $sql = vsprintf(str_replace("?", "'%s'", $event->sql), $event->bindings) . " \t[" . $event->time . ' ms] ';
    // 把SQL写入到日志文件中
    logger()->info("SQL:", [$sql]);
  }
}