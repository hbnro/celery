<?php

namespace Celery\Handle;

class Facebook
{

  private static $me = NULL;
  private static $self = NULL;
  private static $connected = NULL;



  public static function __callStatic($method, $arguments)
  {
    static $repl = array(
              '/[^a-z0-9]|\s+/i' => ' ',
              '/\s([a-z])/ie' => 'ucfirst("\\1")',
            );


    if ( ! static::$self) {
      extract(\Celery\Config::get('facebook'));

      $instance['appId'] = $app_id;
      $instance['secret'] = $app_secret;

      static::$self = new \Facebook($instance);
    }

    $method = preg_replace(array_keys($repl), $repl, $method);

    if (method_exists(static::$self, $method)) {
      try {
        return call_user_func_array(array(static::$self, $method), $arguments);
      } catch (\Exception $e) {}
    }
  }


  public static function query($fql, $callback = '')
  {
    return static::api(array(
      'callback' => $callback,
      'method' => 'fql.query',
      'query' => $fql,
    ));
  }

  public static function is_logged()
  {
    if (static::$connected === NULL) {
      $test = headers_list();

      if (array_key_exists('X-Facebook-User', $test)) {
        static::$me = (array) json_decode($test['X-Facebook-User']);
      } else {
        static::$me = ! empty($_SESSION['__FBAUTH']) ? $_SESSION['__FBAUTH'] : array();
      }

      if (static::get_user()) {
        $_SESSION['__FBAUTH'] = static::$me = static::api('/me');
        static::$connected = !! static::$me;
      } else {
        static::$connected = FALSE;
      }
    }
    return static::$connected;
  }

  final public static function login_url()
  {
    extract(\Celery\Config::get('facebook'));
    return ! empty($connection) ? static::get_login_url($connection) : FALSE;
  }

  public static function logout()
  {
    extract(\Celery\Config::get('facebook'));

    foreach (array_keys($_SESSION) as $key) {
      if (strpos($key, "fb_{$app_id}_") === 0) {
        unset($_SESSION[$key]);
      }
    }

    unset($_SESSION['__FBAUTH']);
  }

  public static function me()
  {
    if (static::is_logged()) {
      return static::$me;
    }
  }

}
