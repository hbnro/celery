<?php

namespace Celery;

class Base
{

  private static $set = array(
                    'twitter' => '\\Celery\\Handle\\Twitter',
                    'facebook' => '\\Celery\\Handle\\Facebook',
                  );

  public static function __callStatic($method, $arguments)
  {
    @list($for) = $arguments;

    return static::prompt($for, $method, TRUE, TRUE);
  }


  public static function handlers()
  {
    $out = array();

    foreach (static::$set as $id => $klass) {
      $tmp = \Celery\Config::get($id);

      $out[$id] = array(
        'class' => $klass,
        'enabled' => $enabled = ! empty($tmp['enabled']),
        'connected' => $enabled && ($logged = $klass::is_logged()),
        'url_for_login' => $enabled && ! $logged ? $klass::login_url() : '',
      );
    }

    return $out;
  }

  public static function provider()
  {
    foreach (static::handlers() as $id => $one) {
      if ($one['enabled'] && $one['connected']) {
        return $id;
      }
    }
  }

  public static function is_logged($on = FALSE)
  {
    return static::prompt($on, 'connected', TRUE);
  }

  public static function login_url($for = FALSE)
  {
    return static::prompt($for, 'url_for_login', TRUE);
  }


  private static function prompt($id, $key, $enabled = FALSE, $callback = FALSE)
  {
    $set = static::handlers();

    if ( ! empty($set[$id][$key])) {
      return $enabled && empty($set[$id]['enabled']) ? FALSE : ( ! empty($set[$id][$key]) ? $set[$id][$key] : FALSE);
    }


    foreach ($set as $provider => $one) {
      if ($enabled) {
        $klass = $one['class'];

        if ( ! empty($one['enabled'])) {
          return $callback ? $klass::$key() : ( ! empty($one[$key]) ? $one[$key] : FALSE);
        }
      } elseif ( ! empty($one[$key])) {
        return $one[$key];
      } elseif ($callback) {
        return $klass::$key();
      }
    }
  }

}
