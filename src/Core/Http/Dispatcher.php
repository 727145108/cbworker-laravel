<?php
/**
 * Created by PhpStorm.
 * User: xiayiyun
 * Date: 2018/12/1
 * Time: 20:23
 */

namespace Cbworker\Core\Http;

use \Cbworker\Core\AbstractInterface\Dispatcher as DispatcherContract;
use Cbworker\Core\AbstractInterface\DependencyResolverTrait;
use Illuminate\Contracts\Foundation\Application;
use Cbworker\Library\StatisticClient;

class Dispatcher implements DispatcherContract
{
  use DependencyResolverTrait;
  
  protected $app;
  
  protected $_controller;
  
  protected $_class;
  
  protected $_method;
  
  public function __construct(Application $app)
  {
    $this->app = $app;
  }
  
  protected function methodDispatch() {
  
    if (config('app.report')) {
      StatisticClient::tick(config('app.name'), ucfirst($this->_class), $this->_method);
    }
    
    $controller = config('app.namespace') . 'Controller\\' . ucfirst($this->_class) . 'Controller';
    try {
      $this->app->instance(Controller::class, ltrim($controller, '\\'));
      $this->_controller = $this->app->make(ltrim($controller, '\\'));
      $parameters = $this->resolveClassMethodDependencies( array(), $this->_controller, $this->_method);
  
      if (method_exists($this->_controller, 'callAction')) {
        return $this->_controller->callAction($this->_method, $parameters);
      }
      $this->_controller->{$this->_method}(...array_values($parameters));
      
    } catch (\Exception $ex) {
      if (config('app.debug')) {
        response()->setCode($ex->getCode());
        response()->setMessage($ex->getMessage());
      } else {
        response()->setCode($ex->getCode());
      }
      logger()->error('methodDispatch Exception', ['code' => $ex->getCode(), 'message' => $ex->getMessage()]);
    }
  
    if (config('app.report')) {
      StatisticClient::report(config('app.name'), ucfirst($this->_class), $this->_method, response()->getCode() == 200 ? 1 : 0, response()->getCode(), json_encode(response()->getMessage()), config('app.statistic.address', ''));
    }
  
  }
  
  
  public function dispatch($request, $response)
  {
    $this->app->instance('request', $request);
    $this->app->instance('response', $response);
  
    if (request()->uri() === '/favicon.ico') {
      response()->close('');
      return;
    }
    list(, $this->_class, $this->_method) = explode('/', request()->uri());
    logger()->info('Request:', ['class' => ucfirst($this->_class), 'method' => $this->_method]);
    logger()->info('User_Agent:', request()->server());
    logger()->info('Params:', request()->post());
    
    try {
      $this->methodDispatch();
    } catch (\Exception $ex) {
      if (config('app.debug')) {
        response()->setCode($ex->getCode());
        response()->setMessage($ex->getMessage());
      } else {
        response()->setCode($ex->getCode());
        //$this->response()->setCode(-99);
      }
    }
    logger()->info('Response:', response()->build());
    response()->send();
  }
}