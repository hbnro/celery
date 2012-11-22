<?php

namespace Celery;

class Config
{

  private static $bag = array(
                    // strategies
                    'twitter' => array(
                      'enabled' => FALSE,
                      'consumer_key' => '',
                      'consumer_secret' => '',
                      'token_key' => '',
                      'token_secret' => '',
                    ),
                    'facebook' => array(
                      'enabled' => FALSE,
                      'app_id' => '',
                      'app_secret' => '',
                      'instance' => array(),
                      'connection' => array(
                        'scope' => 'email',
                      ),
                    ),
                  );



  public static function set($key, $value = NULL)
  {
    if (isset(static::$bag[$key])) {
      if (is_array(static::$bag[$key]) && is_array($value)) {
        static::$bag[$key] = array_merge(static::$bag[$key], $value);
      } else {
        static::$bag[$key] = $value;
      }
    } else {
      static::$bag[$key] = $value;
    }
  }

  public static function get($key, $default = FALSE)
  {
    return isset(static::$bag[$key]) ? static::$bag[$key] : $default;
  }

}
