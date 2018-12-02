<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/12/2
 * Time: 10:06
 */
namespace Application\Apis\Services;


class b
{
  public function getDetail($_id) {
    return \Application\Apis\Models\b::where('id', $_id)->first();
  }
}