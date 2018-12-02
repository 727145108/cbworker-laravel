<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/12/1
 * Time: 20:23
 */

namespace Cbworker\Core\AbstractInterface;


interface Dispatcher
{
   public function dispatch($request, $response);
}