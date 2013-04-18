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

    if (empty($set[$provider]['enabled'])) {
      throw new \Exception("Unable disconnect from '$provider' provider");
    }

    $klass = $set[$provider]['class'];
    $klass::disconnect();
  }

  public static function connect($provider)
  {
    $set = static::handlers();

    if (empty($set[$provider]['enabled'])) {
      throw new \Exception("Unable use '$provider' provider to connect");
    }

    $klass = $set[$provider]['class'];
    $klass::connect();
  }

}
