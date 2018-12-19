<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/12/11
 * Time: 21:22
 */

namespace Application\Apis\Jobs;

use Cbworker\Core\Queue\SerializesModels;
use Cbworker\Core\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;


class TestJob extends Job implements ShouldQueue
{
  use InteractsWithQueue, SerializesModels;
  
  protected $order_id;
  
  protected $timer;
  
  public function __construct($order_id, $timer) {
    $this->order_id = $order_id;
    $this->timer = $timer;
  }
  
  public function handle() {
    if($this->order_id == 60) {
      throw new \Exception('error', -354);
    } else {
      echo "Test Job Handle {$this->order_id} \t {$this->timer} \n";
    }
  }
}