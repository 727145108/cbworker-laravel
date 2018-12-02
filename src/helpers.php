<?php

use Illuminate\Container\Container;
use Illuminate\Support\Str;

if (! function_exists('app')) {
	/**
	 * Get the available container instance.
	 *
	 * @param  string  $abstract
	 * @param  array   $parameters
	 * @return mixed|\Cbworker\Core\Application
	 */
	function app($abstract = null, array $parameters = [])
	{
	    if (is_null($abstract)) {
	        return Container::getInstance();
	    }

	    return empty($parameters)
	        ? Container::getInstance()->make($abstract)
	        : Container::getInstance()->makeWith($abstract, $parameters);
	}
}

if (! function_exists('config')) {
	/**
	 * Get / set the specified configuration value.
	 *
	 * If an array is passed as the key, we will assume you want to set an array of values.
	 *
	 * @param  array|string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	function config($key = null, $default = null)
	{
	    if (is_null($key)) {
	        return app('config');
	    }

	    if (is_array($key)) {
	        return app('config')->set($key);
	    }

	    return app('config')->get($key, $default);
	}
}

if (! function_exists('info')) {
  /**
   * Write some information to the log.
   *
   * @param  string  $message
   * @param  array   $context
   * @return void
   */
  function info($message, $context = [])
  {
    return app('log')->info($message, $context);
  }
}

if (! function_exists('logger')) {
  /**
   * Log a debug message to the logs.
   *
   * @param  string  $message
   * @param  array  $context
   * @return \Cbworker\Core\Application
   */
  function logger($message = null, array $context = [])
  {
    if (is_null($message)) {
      return app('log');
    }
    
    return app('log')->debug($message, $context);
  }
}
if (! function_exists('database_path')) {
  /**
   * Get the database path.
   *
   * @param  string  $path
   * @return string
   */
  function database_path($path = '')
  {
    return app()->databasePath($path);
  }
}

if (! function_exists('response')) {
  
  /**
   * @return \Cbworker\Core\Application|mixed
   */
  function response()
  {
    $factory = app('response');
    
    return $factory;
  }
}

if (! function_exists('request')) {
  
  /**
   * @return \Cbworker\Core\Application|mixed
   */
  function request()
  {
    return app('request');
  }
}

if (! function_exists('event')) {
  /**
   * Dispatch an event and call the listeners.
   *
   * @param  string|object  $event
   * @param  mixed  $payload
   * @param  bool  $halt
   * @return array|null
   */
  function event(...$args)
  {
    return app('events')->dispatch(...$args);
  }
}
