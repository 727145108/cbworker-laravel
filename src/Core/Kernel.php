<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/12/1
 * Time: 13:08
 */

namespace Cbworker\Core;

use Illuminate\Contracts\Foundation\Application as App;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Support\Facades\Facade;
use Workerman\Lib\Timer;

class Kernel implements KernelContract
{
  protected $app;
  
  /**
   * The bootstrap classes for the application.
   *
   * @var array
   */
  protected $bootstrappers = [
    \Cbworker\Core\Bootstrap\LoadConfiguration::class,
    \Cbworker\Core\Bootstrap\RegisterFacades::class,
    \Cbworker\Core\Bootstrap\RegisterProviders::class,
    \Cbworker\Core\Bootstrap\BootProviders::class,
  ];
  
  public function __construct(App $app)
  {
    $this->app = $app;
  }
  
  /**
   * Bootstrap the application for HTTP requests.
   *
   * @return void
   */
  public function bootstrap()
  {
    if (! $this->app->hasBeenBootstrapped()) {
      $this->app->bootstrapWith($this->bootstrappers());
    }
  }
  
  /**
   * Get the bootstrap classes for the application.
   *
   * @return array
   */
  protected function bootstrappers()
  {
    return $this->bootstrappers;
  }
  
  public function handle($request)
  {
  }
  
  /**
   * Perform any final actions for the request lifecycle.
   *
   * @return void
   */
  public function terminate($request, $response)
  {
    if ($request->uri() === '/favicon.ico') {
      $response->close();
      return;
    }
    $response->send();
    $response->destroy();
  }
  
  /**
   * Get the Laravel application instance.
   *
   * @return \Illuminate\Contracts\Foundation\Application
   */
  public function getApplication()
  {
    return $this->app;
  }
  
  public function run() {
    $this->_worker->runAll();
  }
}