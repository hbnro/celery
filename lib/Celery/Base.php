<?php

namespace Celery;

class Base
{

  private static $set = array(
                    'twitter' => '\\Celery\\Handle\\Twitter',
                    'facebook' => '\\Celery\\Handle\\Facebook',
                  );



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

  public static function disconnect($provider)
  {
    $set = static::handlers();

    if ( ! empty($set[$provider]['enabled'])) {
      $klass = $set[$provider]['class'];
      $klass::logout();

      return TRUE;
    }
  }

  public static function connect()
  {
    $set = static::handlers();

    if ( ! empty($set[$provider]['enabled'])) {
      var_dump($set[$provider]);
    }
  }

}
