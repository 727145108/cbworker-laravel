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
  
  public function __construct() {
  
  }
  
  public function handle() {
    echo "Test Job Handle \n";
  }
}