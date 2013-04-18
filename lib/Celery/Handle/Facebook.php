<?php

namespace Celery\Handle;

class Facebook
{

  private static $me = NULL;
  private static $self = NULL;
  private static $connected = NULL;

  private static $repl_regex = array(
                    '/[^a-z0-9]|\s+/i' => ' ',
                    '/\s([a-z])/ie' => 'ucfirst("\\1")',
                  );

  public static function __callStatic($method, $arguments)
  {
    if (! static::$self) {
      extract(\Celery\Config::get('facebook'));

      $instance['appId'] = $app_id;
      $instance['secret'] = $app_secret;

      static::$self = new \Facebook($instance);
    }

    $method = preg_replace(array_keys(static::$repl_regex), static::$repl_regex, $method);

    if (method_exists(static::$self, $method)) {
      try {
        return call_user_func_array(array(static::$self, $method), $arguments);
      } catch (\Exception $e) {}
    }
  }

  public static function connect()
  {
  }

  public static function disconnect()
  {
    header('Location: ' . static::logout_url());
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

  public static function login_url()
  {
    extract(\Celery\Config::get('facebook'));

    return ! empty($connection) ? static::get_login_url($connection) : FALSE;
  }

  public static function logout_url(array $params = array())
  {
    return static::get_logout_url($params);
  }

  public static function me()
  {
    if (static::is_logged()) {
      return static::$me;
    }
  }

}
