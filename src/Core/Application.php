<?php

# @Author: crababy
# @Date:   2018-03-21T15:54:17+08:00
# @Last modified by:   crababy
# @Last modified time: 2018-09-03T14:17:20+08:00
# @License: http://www.opensource.org/licenses/mit-license.php MIT License
#

namespace Cbworker\Core;

use Closure;
use Illuminate\Support\Arr;
use Cbworker\Core\Events\EventServiceProvider;
use Illuminate\Support\ServiceProvider;
use Cbworker\Core\Log\LogServiceProvider;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Cbworker\Library\Helper;
use Cbworker\Library\StatisticClient;
use Illuminate\Container\Container;
use Workerman\Protocols\Http;

class Application extends Container implements ApplicationContract
{
  
  /**
   * The Cbworker framework version.
   *
   * @var string
   */
  const VERSION = '1.2.23';
  
  /**
   * The base path for the Cbworker installation.
   *
   * @var string
   */
  protected $basePath;
  
  /**
   * All of the registered service providers.
   *
   * @var array
   */
  protected $serviceProviders = [];
  
  /**
   * The names of the loaded service providers.
   *
   * @var array
   */
  protected $loadedProviders = [];
  
  /**
   * Indicates if the application has "booted".
   *
   * @var bool
   */
  protected $booted = false;
  
  /**
   * Indicates if the application has been bootstrapped before.
   *
   * @var bool
   */
  protected $hasBeenBootstrapped = false;
  
  /**
   * The array of booting callbacks.
   *
   * @var array
   */
  protected $bootingCallbacks = [];
  
  /**
   * The array of booted callbacks.
   *
   * @var array
   */
  protected $bootedCallbacks = [];
  
  /**
   * A custom callback used to configure Monolog.
   *
   * @var callable|null
   */
  protected $monologConfigurator;
  
  /**
   * The deferred services and their providers.
   *
   * @var array
   */
  protected $deferredServices = [];
  
  /**
   * The custom storage path defined by the developer.
   *
   * @var string
   */
  protected $storagePath;
  
  /**
   * The custom database path defined by the developer.
   *
   * @var string
   */
  protected $databasePath;
  
  protected $_request = null;
  
  protected $_response = null;
  
  protected $_connection = null;
  
  public function __construct($basePath = null)
  {
    if ($basePath) {
      $this->setBasePath($basePath);
    }
    
    $this->registerBaseBindings();
    
    $this->registerBaseServiceProviders();
    
    $this->registerCoreContainerAliases();
    
    $this->errorHandle();
  }
  
  /**
   * Get the version number of the application.
   *
   * @return string
   */
  public function version()
  {
    return static::VERSION;
  }
  
  /**
   * Register the basic bindings into the container.
   *
   * @return void
   */
  protected function registerBaseBindings()
  {
    static::setInstance($this);
    
    $this->instance('app', $this);
    
    $this->instance(Container::class, $this);
  }
  
  private function errorHandle()
  {
    $func = function () {
      echo 'register shutdown function ' . PHP_EOL;
    };
    register_shutdown_function($func);
  }
  
  /**
   * Run the given array of bootstrap classes.
   *
   * @param  array $bootstrappers
   * @return void
   */
  public function bootstrapWith(array $bootstrappers)
  {
    $this->hasBeenBootstrapped = true;
    
    foreach ($bootstrappers as $bootstrapper) {
      $this['events']->fire('bootstrapping: ' . $bootstrapper, [$this]);
      
      $this->make($bootstrapper)->bootstrap($this);
      
      $this['events']->fire('bootstrapped: ' . $bootstrapper, [$this]);
    }
  }
  
  /**
   * Register all of the base service providers.
   *
   * @return void
   */
  protected function registerBaseServiceProviders()
  {
    $this->register(new EventServiceProvider($this));
    $this->register(new LogServiceProvider($this));
  }
  
  /**
   * Register the core class aliases in the container.
   *
   * @return void
   */
  public function registerCoreContainerAliases()
  {
    foreach ([
               'app' => [\Cbworker\Core\Application::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class],
               'log' => [\Cbworker\Core\Writer::class, \Illuminate\Contracts\Logging\Log::class, \Psr\Log\LoggerInterface::class],
               'db' => [\Illuminate\Database\DatabaseManager::class],
               'db.connection' => [\Illuminate\Database\Connection::class, \Illuminate\Database\ConnectionInterface::class],
               'events' => [\Cbworker\Core\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
             ] as $key => $aliases) {
      foreach ($aliases as $alias) {
        $this->alias($key, $alias);
      }
    }
  }
  
  /**
   * Register a callback to run before a bootstrapper.
   *
   * @param  string $bootstrapper
   * @param  Closure $callback
   * @return void
   */
  public function beforeBootstrapping($bootstrapper, Closure $callback)
  {
    $this['events']->listen('bootstrapping: ' . $bootstrapper, $callback);
  }
  
  /**
   * Register a callback to run after a bootstrapper.
   *
   * @param  string $bootstrapper
   * @param  Closure $callback
   * @return void
   */
  public function afterBootstrapping($bootstrapper, Closure $callback)
  {
    $this['events']->listen('bootstrapped: ' . $bootstrapper, $callback);
  }
  
  /**
   * Determine if the application has been bootstrapped before.
   *
   * @return bool
   */
  public function hasBeenBootstrapped()
  {
    return $this->hasBeenBootstrapped;
  }
  
  
  /**
   * Get the path to the application configuration files.
   *
   * @param string $path Optionally, a path to append to the config path
   * @return string
   */
  public function configPath($path = '')
  {
    return $this->basePath . DIRECTORY_SEPARATOR . 'Configs' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
  }
  
  /**
   * Get the path to the bootstrap directory.
   *
   * @param string $path Optionally, a path to append to the bootstrap path
   * @return string
   */
  public function bootstrapPath($path = '')
  {
    return $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
  }
  
  /**
   * Get the path to the storage directory.
   *
   * @return string
   */
  public function runtimePath()
  {
    return $this->storagePath ?: $this->basePath . DIRECTORY_SEPARATOR . 'Runtime';
  }
  
  /**
   * Get the path to the database directory.
   *
   * @param string $path Optionally, a path to append to the database path
   * @return string
   */
  public function databasePath($path = '')
  {
    return ($this->databasePath ?: $this->basePath . DIRECTORY_SEPARATOR . 'database') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
  }
  
  /**
   * Determine if the application configuration is cached.
   *
   * @return bool
   */
  public function configurationIsCached()
  {
    return file_exists($this->getCachedConfigPath());
  }
  
  /**
   * Get the path to the cached services.php file.
   *
   * @return string
   */
  public function getCachedServicesPath()
  {
    return $this->runtimePath() . '/cache/services.php';
  }
  
  /**
   * Get the path to the configuration cache file.
   *
   * @return string
   */
  public function getCachedConfigPath()
  {
    return $this->runtimePath() . '/cache/config.php';
  }
  
  /**
   * Define a callback to be used to configure Monolog.
   *
   * @param  callable $callback
   * @return $this
   */
  public function configureMonologUsing(callable $callback)
  {
    $this->monologConfigurator = $callback;
    
    return $this;
  }
  
  /**
   * Determine if the application has a custom Monolog configurator.
   *
   * @return bool
   */
  public function hasMonologConfigurator()
  {
    return !is_null($this->monologConfigurator);
  }
  
  /**
   * Get the custom Monolog configurator for the application.
   *
   * @return callable
   */
  public function getMonologConfigurator()
  {
    return $this->monologConfigurator;
  }
  
  /**
   * Get the application's deferred services.
   *
   * @return array
   */
  public function getDeferredServices()
  {
    return $this->deferredServices;
  }
  
  /**
   * Set the application's deferred services.
   *
   * @param  array $services
   * @return void
   */
  public function setDeferredServices(array $services)
  {
    $this->deferredServices = $services;
  }
  
  /**
   * Add an array of services to the application's deferred services.
   *
   * @param  array $services
   * @return void
   */
  public function addDeferredServices(array $services)
  {
    $this->deferredServices = array_merge($this->deferredServices, $services);
  }
  
  /**
   * Register all of the configured providers.
   *
   * @return void
   */
  public function registerConfiguredProviders()
  {
    (new ProviderRepository($this, $this->getCachedServicesPath()))->load($this->config['app.providers']);
  }
  
  /**
   * Register a service provider with the application.
   *
   * @param  \Illuminate\Support\ServiceProvider|string $provider
   * @param  array $options
   * @param  bool $force
   * @return \Illuminate\Support\ServiceProvider
   */
  public function register($provider, $options = [], $force = false)
  {
    if (($registered = $this->getProvider($provider)) && !$force) {
      return $registered;
    }
    
    // If the given "provider" is a string, we will resolve it, passing in the
    // application instance automatically for the developer. This is simply
    // a more convenient way of specifying your service provider classes.
    if (is_string($provider)) {
      $provider = $this->resolveProvider($provider);
    }
    
    if (method_exists($provider, 'register')) {
      $provider->register();
    }
    
    $this->markAsRegistered($provider);
    // If the application has already booted, we will call this boot method on
    // the provider class so it has an opportunity to do its boot logic and
    // will be ready for any usage by this developer's application logic.
    if ($this->booted) {
      $this->bootProvider($provider);
    }
    
    return $provider;
  }
  
  /**
   * Get the registered service provider instance if it exists.
   *
   * @param  \Illuminate\Support\ServiceProvider|string $provider
   * @return \Illuminate\Support\ServiceProvider|null
   */
  public function getProvider($provider)
  {
    $name = is_string($provider) ? $provider : get_class($provider);
    
    return Arr::first($this->serviceProviders, function ($value) use ($name) {
      return $value instanceof $name;
    });
  }
  
  /**
   * Resolve a service provider instance from the class name.
   *
   * @param  string $provider
   * @return \Illuminate\Support\ServiceProvider
   */
  public function resolveProvider($provider)
  {
    return new $provider($this);
  }
  
  /**
   * Mark the given provider as registered.
   *
   * @param  \Illuminate\Support\ServiceProvider $provider
   * @return void
   */
  protected function markAsRegistered($provider)
  {
    $this->serviceProviders[] = $provider;
    
    $this->loadedProviders[get_class($provider)] = true;
  }
  
  
  /**
   * Resolve the given type from the container.
   *
   * (Overriding Container::make)
   *
   * @param  string $abstract
   * @return mixed
   */
  public function make($abstract = null, array $parameters = [])
  {
    $abstract = $this->getAlias($abstract);
    
    if (isset($this->deferredServices[$abstract])) {
      $this->loadDeferredProvider($abstract);
    }
    
    return parent::make($abstract, $parameters);
  }
  
  /**
   * Load and boot all of the remaining deferred providers.
   *
   * @return void
   */
  public function loadDeferredProviders()
  {
    // We will simply spin through each of the deferred providers and register each
    // one and boot them if the application has booted. This should make each of
    // the remaining services available to this application for immediate use.
    foreach ($this->deferredServices as $service => $provider) {
      $this->loadDeferredProvider($service);
    }
    
    $this->deferredServices = [];
  }
  
  /**
   * Load the provider for a deferred service.
   *
   * @param  string $service
   * @return void
   */
  public function loadDeferredProvider($service)
  {
    if (!isset($this->deferredServices[$service])) {
      return;
    }
    
    $provider = $this->deferredServices[$service];
    
    // If the service provider has not already been loaded and registered we can
    // register it with the application and remove the service from this list
    // of deferred services, since it will already be loaded on subsequent.
    if (!isset($this->loadedProviders[$provider])) {
      $this->registerDeferredProvider($provider, $service);
    }
  }
  
  public function Run()
  {
    if ($this->request()->uri() === '/favicon.ico') {
      $this->connection()->close('');
      return;
    }
    $request = array();
    /*
    $content_type = $this->request()->contentType();
    if (preg_match("/application\/json/i", $content_type)) {
      $request = json_decode($this->request()->rawData(), TRUE);
    } else if (preg_match("/text\/xml/i", $content_type)) {
      $request = $this->request()->rawData();
    } else {
      $request = $this->request->post();
    }*/
    $_info = explode('/', $this->request()->uri());
    $request['class'] = isset($_info[1]) && !empty($_info[1]) ? ucfirst($_info[1]) : 'Index';
    $request['method'] = isset($_info[2]) && !empty($_info[2]) ? $_info[2] : 'index';
    
    $this->logger()->info('', $request);
    $this->logger()->info("User_Agent:", $this->request()->server());
    $this->logger()->info("Params", $this->request()->post());
    try {
      $this->checkRequestLimit($request['class'], $request['method']);
      $this->methodDispatch($request);
    } catch (\Exception $ex) {
      $this->response()->setCode($ex->getCode());
      $this->response()->setMessage($ex->getMessage());
      $this->logger()->error('methodDispatch Exception', ['code' => $ex->getCode(), 'message' => $ex->getMessage()]);
    }
    $_headers = $this->response()->header();
    foreach ($_headers as $header) {
      Http::header($header);
    }
    $_responses = $this->response()->build();
    $this->connection()->send(json_encode($_responses, JSON_UNESCAPED_UNICODE));
    
    $this->logger()->info("Response", $_responses);
    unset($_headers);
    unset($_responses);
    unset($this->_request);
    unset($this->_response);
  }
  
  public function request()
  {
    return $this->_request;
  }
  
  public function response()
  {
    return $this->_response;
  }
  
  public function connection()
  {
    return $this->_connection;
  }
  
  /**
   * 访问频率限制
   * @return [type] [description]
   */
  private function checkRequestLimit($class, $method)
  {
    $clientIp = Helper::getClientIp();
    $apiLimitKey = "ApiLimit:{$class}:{$method}:{$clientIp}";
    $limitSecond = 10;
    $limitCount = 100000;
    $ret = $this->redis()->RedisCommands('get', $apiLimitKey);
    if (false === $ret) {
      $this->redis()->RedisCommands('setex', $apiLimitKey, $limitSecond, 1);
    } else {
      if ($ret >= $limitCount) {
        $this->redis()->RedisCommands('expire', $apiLimitKey, 10);
        $this->logger()->info("checkRequestLimit: Request Fast");
        throw new \Exception("Request faster", -9);
      } else {
        $this->redis()->RedisCommands('incr', $apiLimitKey);
      }
    }
    return true;
  }
  
  /**
   * Set the base path for the application.
   *
   * @param  string $basePath
   * @return $this
   */
  public function setBasePath($basePath)
  {
    $this->basePath = rtrim($basePath, '\/');
    return $this;
  }
  
  
  /**
   * Get the path to the application "app" directory.
   *
   * @param string $path Optionally, a path to append to the app path
   * @return string
   */
  public function path($path = '')
  {
    return $this->basePath . DIRECTORY_SEPARATOR . ($path ? DIRECTORY_SEPARATOR . $path : $path);
  }
  
  /**
   * 请求分发
   * @return [type] [description]
   */
  public function methodDispatch($request)
  {
    $controller = Config::getConf('App.NAMESPACE') . 'Controller\\' . $request['class'] . 'Controller';
    
    if (!class_exists($controller) || !method_exists($controller, $request['method'])) {
      throw new \Exception("Controller {$request['class']} or Method {$request['method']} is Not Exists", 1002);
    }
    
    if (Config::getConf('App.report')) {
      StatisticClient::tick(Config::getConf('App.Name'), $request['class'], $request['method']);
    }
    
    try {
      $handler_instance = new $controller($this);
      $handler_instance->{$request['method']}();
    } catch (\Exception $ex) {
      if (Config::getConf('App.Debug')) {
        $this->response()->setCode($ex->getCode());
        $this->response()->setMessage($ex->getMessage());
      } else {
        $this->response()->setCode($ex->getCode());
        //$this->response()->setCode(-99);
      }
      $this->logger()->error('methodDispatch Exception', ['code' => $ex->getCode(), 'message' => $ex->getMessage()]);
    }
    
    if (Config::getConf('App.report')) {
      StatisticClient::report(Config::getConf('App.Name'), $request['class'], $request['method'], $this->response()->getCode() == 200 ? 1 : 0, $this->response()->getCode(), $this->response()->getMessage(), Config::getConf('App.statistic.address', ''));
    }
  }
  
  private function __clone()
  {
  }
  
  
  /**
   * Get the base path of the Laravel installation.
   *
   * @return string
   */
  public function basePath()
  {
    // TODO: Implement basePath() method.
  }
  
  /**
   * Get or check the current application environment.
   *
   * @return string
   */
  public function environment()
  {
    // TODO: Implement environment() method.
  }
  
  /**
   * Determine if we are running in the console.
   *
   * @return bool
   */
  public function runningInConsole()
  {
    // TODO: Implement runningInConsole() method.
  }
  
  /**
   * Determine if the application is currently down for maintenance.
   *
   * @return bool
   */
  public function isDownForMaintenance()
  {
    // TODO: Implement isDownForMaintenance() method.
  }
  
  /**
   * Register a deferred provider and service.
   *
   * @param  string $provider
   * @param  string|null $service
   * @return void
   */
  public function registerDeferredProvider($provider, $service = null)
  {
    if ($service) {
      unset($this->deferredServices[$service]);
    }
    
    $this->register($instance = new $provider($this));
    
    if (!$this->booted) {
      $this->booting(function () use ($instance) {
        $this->bootProvider($instance);
      });
    }
  }
  
  /**
   * Determine if the given abstract type has been bound.
   *
   * (Overriding Container::bound)
   *
   * @param  string $abstract
   * @return bool
   */
  public function bound($abstract)
  {
    return isset($this->deferredServices[$abstract]) || parent::bound($abstract);
  }
  
  /**
   * Boot the application's service providers.
   *
   * @return void
   */
  public function boot()
  {
    if ($this->booted) {
      return;
    }
    
    // Once the application has booted we will also fire some "booted" callbacks
    // for any listeners that need to do work after this initial booting gets
    // finished. This is useful when ordering the boot-up processes we run.
    $this->fireAppCallbacks($this->bootingCallbacks);
    
    array_walk($this->serviceProviders, function ($p) {
      $this->bootProvider($p);
    });
    
    $this->booted = true;
    
    $this->fireAppCallbacks($this->bootedCallbacks);
  }
  
  /**
   * Boot the given service provider.
   *
   * @param  \Illuminate\Support\ServiceProvider $provider
   * @return mixed
   */
  protected function bootProvider(ServiceProvider $provider)
  {
    if (method_exists($provider, 'boot')) {
      return $this->call([$provider, 'boot']);
    }
  }
  
  /**
   * Register a new boot listener.
   *
   * @param  mixed $callback
   * @return void
   */
  public function booting($callback)
  {
    $this->bootingCallbacks[] = $callback;
  }
  
  /**
   * Register a new "booted" listener.
   *
   * @param  mixed $callback
   * @return void
   */
  public function booted($callback)
  {
    $this->bootedCallbacks[] = $callback;
    
    if ($this->isBooted()) {
      $this->fireAppCallbacks([$callback]);
    }
  }
  
  /**
   * Call the booting callbacks for the application.
   *
   * @param  array $callbacks
   * @return void
   */
  protected function fireAppCallbacks(array $callbacks)
  {
    foreach ($callbacks as $callback) {
      call_user_func($callback, $this);
    }
  }
  
  /**
   * Get the path to the cached packages.php file.
   *
   * @return string
   */
  public function getCachedPackagesPath()
  {
    // TODO: Implement getCachedPackagesPath() method.
  }
  
  
  /**
   * Flush the container of all bindings and resolved instances.
   *
   * @return void
   */
  public function flush()
  {
    parent::flush();
    
    $this->loadedProviders = [];
    $this->bootedCallbacks = [];
    $this->bootingCallbacks = [];
    $this->deferredServices = [];
    $this->serviceProviders = [];
  }
}
