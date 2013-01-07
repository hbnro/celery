<?php

namespace Celery\Handle;

class Twitter
{

  private static $req = NULL;
  private static $connected = NULL;
  private static $screen_name = '';
  private static $user_id = -1;
  private static $data = array();

  private static $request_token_url = 'http://twitter.com/oauth/request_token';
  private static $access_token_url = 'http://twitter.com/oauth/access_token';
  private static $authorize_url = 'http://twitter.com/oauth/authorize';
  private static $api_url = 'http://api.twitter.com/1/';



  public static function __callStatic($method, $arguments)
  {
    $type   = 'GET';
    $data   = array();
    $test   = array_pop($arguments);
    $params = array_pop($arguments);

    is_array($params) ? $data = $params : $params && $arguments []= $params;

    if (is_array($test)) {
      $data = $test;
    } elseif ($test === 'POST') {
      $type = $test;
    } else {
      $test && $arguments []= $test;
    }


    $extra = join('/', $arguments);
    $url   = $method . ($extra ? "/$extra" : '');

    return static::api_call($url, $data, $type);
  }



  public static function connect()
  {
  }

  public static function disconnect()
  {
    $_SESSION['__TWAUTH'] = NULL;
  }

  public static function credentials()
  {
    if ( ! static::$data) {
      static::$data = static::api_call('account/verify_credentials');
    }
    return static::$data;
  }

  public static function screen_name()
  {
    return static::$screen_name;
  }

  public static function user_id()
  {
    return static::$user_id;
  }

  public static function is_logged()
  {
    if (static::$connected === NULL) {
      extract(\Celery\Config::get('twitter'));

      static::$req = \Celery\OAuth::make($consumer_key, $consumer_secret, $token_key, $token_secret);

      if ($token_key && $token_secret) {
        static::$connected = TRUE;
      } else {
        if ( ! empty($_GET['oauth_token'])) {
          $token = $_GET['oauth_token'];
          \Celery\OAuth::set(static::$req, $token);

          parse_str(\Celery\OAuth::exec(static::$req, static::$access_token_url), $test);
          $_SESSION['__TWAUTH'] = $test;

          \Celery\OAuth::set(static::$req, $test['oauth_token'], $test['oauth_token_secret']);
        } else {
          $test = ! empty($_SESSION['__TWAUTH']) ? $_SESSION['__TWAUTH'] : array();
        }


        if ( ! empty($test['oauth_token']) && ! empty($test['oauth_token'])) {
          \Celery\OAuth::set(static::$req, $test['oauth_token'], $test['oauth_token_secret']);
        }

        ! empty($test['screen_name']) && static::$screen_name = $test['screen_name'];
        ! empty($test['user_id']) && static::$user_id = (string) $test['user_id'];

        static::$connected = static::$user_id > 0;
      }
    }
    return static::$connected;
  }

  public static function login_url()
  {
    parse_str(\Celery\OAuth::exec(static::$req, static::$request_token_url), $test);

    if ( ! empty($test['oauth_token'])) {
      return static::$authorize_url . '?oauth_token=' . $test['oauth_token'];
    } else {
      throw new \Exception(strtr(key($test), '_', ' '));
    }
  }

  public static function api_call($url, array $vars = array(), $method = 'GET')
  {
    if (static::is_logged()) {
      $url  = strpos($url, '://') === FALSE ? rtrim(static::$api_url, '/') . "/$url.json" : $url;
      $test = \Celery\OAuth::exec(static::$req, $url, $vars, $method, TRUE);

      if (strpos($test, '"error"') === FALSE) {
        $test = preg_replace('/(\w+)":(\d+)/', '\\1":"\\2"', $test);
      }

      return json_decode($test);
    }
  }

  public static function linkify($text)
  {
    static $set = array(// TODO: better unicode support?
              '/(\w{3,5}:\/\/([-\w\.]+)+(d+)?(\/([\w\/_\.]*(\?\S+)?)?)?)/' => '<a href="\\1">\\1</a>',
              '/(?<!\w)#([\wñáéíóú]+)(?=\b)/iu' => '<a href="http://twitter.com/search?q=%23\\1">#\\1</a>',
              '/(?<!\w)@(\w+)(?=\b)/u' => '<a href="http://twitter.com/\\1">@\\1</a>',
            );


    $text = preg_replace(array_keys($set), $set, $text);

    return $text;
  }

  public static function status_limit()
  {
    return static::api_call('account/rate_limit_status');
  }

  public static function search_by($text, $limit = 20)
  {
    $limit > 0 && $data['rpp'] = $limit;

    $data['q'] = $text;

    return static::api_call('http://search.twitter.com/search.json', $data);
  }

}
