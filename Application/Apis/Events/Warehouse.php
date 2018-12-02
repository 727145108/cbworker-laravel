<?php

namespace Application\Apis\Events;
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/12/2
 * Time: 11:21
 */
use \Application\Apis\Models\b;
class Warehouse extends Event
{
  public $b;
  
  function __construct(b $b)
  {
    $this->b = $b;
  }
}