<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/12/2
 * Time: 11:20
 */

namespace Application\Apis\Listeners;


use Application\Apis\Events\Warehouse;

class WarehouseListener
{
  function __construct()
  {
  }
  
  public function handle(Warehouse $warehouse) {
    print_r('WarehouseListener handle');
  }
  
  public function failed(Warehouse $warehouse, $exception) {
    print_r('WarehouseListener handle');
  }
}