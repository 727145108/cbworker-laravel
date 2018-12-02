<?php

namespace Cbworker\Core;

use Exception;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class ProviderRepository
{
  /**
   * The application implementation.
   *
   * @var \Illuminate\Contracts\Foundation\Application
   */
  protected $app;
  
  /**
   * The filesystem instance.
   *
   * @var \Illuminate\Filesystem\Filesystem
   */
  protected $files;
  
  /**
   * The path to the manifest file.
   *
   * @var string
   */
  protected $manifestPath;
  
  /**
   * Create a new service repository instance.
   *
   * @param  \Illuminate\Contracts\Foundation\Application $app
   * @param  string $manifestPath
   * @return void
   */
  public function __construct(ApplicationContract $app, $manifestPath)
  {
    $this->app = $app;
    $this->manifestPath = $manifestPath;
  }
  
  /**
   * Register the application service providers.
   *
   * @param  array $providers
   * @return void
   */
  public function load(array $providers)
  {
    $manifest = $this->compileManifest($providers);
    
    // Next, we will register events to load the providers for each of the events
    // that it has requested. This allows the service provider to defer itself
    // while still getting automatically loaded when a certain event occurs.
    foreach ($manifest['when'] as $provider => $events) {
      $this->registerLoadEvents($provider, $events);
    }
    
    // We will go ahead and register all of the eagerly loaded providers with the
    // application so their services can be registered with the application as
    // a provided service. Then we will set the deferred service list on it.
    foreach ($manifest['eager'] as $provider) {
      $this->app->register($provider);
    }
    
    $this->app->addDeferredServices($manifest['deferred']);
  }
  
  /**
   * Determine if the manifest should be compiled.
   *
   * @param  array $manifest
   * @param  array $providers
   * @return bool
   */
  public function shouldRecompile($manifest, $providers)
  {
    return is_null($manifest) || $manifest['providers'] != $providers;
  }
  
  /**
   * Register the load events for the given provider.
   *
   * @param  string $provider
   * @param  array $events
   * @return void
   */
  protected function registerLoadEvents($provider, array $events)
  {
    if (count($events) < 1) {
      return;
    }
    
    $this->app->make('events')->listen($events, function () use ($provider) {
      $this->app->register($provider);
    });
  }
  
  /**
   * Compile the application service manifest file.
   *
   * @param  array $providers
   * @return array
   */
  protected function compileManifest($providers)
  {
    // The service manifest should contain a list of all of the providers for
    // the application so we can compare it on each request to the service
    // and determine if the manifest should be recompiled or is current.
    $manifest = $this->freshManifest($providers);
    
    foreach ($providers as $provider) {
      $instance = $this->createProvider($provider);
      
      // When recompiling the service manifest, we will spin through each of the
      // providers and check if it's a deferred provider or not. If so we'll
      // add it's provided services to the manifest and note the provider.
      if ($instance->isDeferred()) {
        foreach ($instance->provides() as $service) {
          $manifest['deferred'][$service] = $provider;
        }
        
        $manifest['when'][$provider] = $instance->when();
      }
      
      // If the service providers are not deferred, we will simply add it to an
      // array of eagerly loaded providers that will get registered on every
      // request to this application instead of "lazy" loading every time.
      else {
        $manifest['eager'][] = $provider;
      }
    }
    return array_merge(['when' => []], $manifest);
  }
  
  /**
   * Create a fresh service manifest data structure.
   *
   * @param  array $providers
   * @return array
   */
  protected function freshManifest(array $providers)
  {
    return ['providers' => $providers, 'eager' => [], 'deferred' => []];
  }
  
  /**
   * Create a new provider instance.
   *
   * @param  string $provider
   * @return \Illuminate\Support\ServiceProvider
   */
  public function createProvider($provider)
  {
    return new $provider($this->app);
  }
}
