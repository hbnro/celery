<?php

namespace Celery;

class OAuth
{

  public static function make($consumer_key, $consumer_secret, $token = '', $token_secret = '')
  {
    $R = new \stdClass;

    $R->info = array();
    $R->token = $token;
    $R->token_secret = $token_secret;
    $R->consumer_key = $consumer_key;
    $R->consumer_secret = $consumer_secret;

    return $R;
  }

  public static function parse($request, $url, $vars = array(), $method = 'GET', $callback = 'SHA1') {// TODO: improve?
    $data['oauth_version'] = '1.0';
    $data['oauth_timestamp'] = time();
    $data['oauth_signature_method'] = strtoupper("hmac-$callback");
    $data['oauth_consumer_key'] = $request->consumer_key;
    $data['oauth_nonce'] = md5(uniqid(mt_rand(), TRUE));
    $data['oauth_token'] = $request->token;

    foreach ($vars as $key => $val) {
      $vars[$key] = $val;
    }

    $test = array_merge($data, $vars);
    uksort($test, 'strcmp');

    $data['oauth_signature'] = static::encode(static::sign($request, $url, $test, $method, $callback));

    return array(
      'request' => $vars,
      'oauth' => $data,
    );
  }

  public static function exec($request, $url, $vars = array(), $method = 'GET')
  {
    $parts  = @parse_url($url);
    $scheme = strtolower($parts['scheme']);
    $host   = strtolower($parts['host']);
    $port   = ! empty($parts['port']) ? (int) $parts['port'] : 80;
    $url    = "$scheme://$host";

    ($port > 0) && (($scheme === 'http') && ($port !== 80)) OR (($scheme === 'https') && ($port !== 443)) && $out .= ":$port";

    $url .= $parts['path'];

    @parse_str($parts['query'], $test);

    ! empty($test) && $vars = array_merge($vars, $test);

    $vars     = static::parse($request, $url, $vars, $method);
    $query    = str_replace('+', '%20', http_build_query($vars['request'], NULL, '&'));
    $headers  = array('Expect:');
    $resource = curl_init();

    switch ($method) {
      case 'POST'; // TODO: manage @uploads?
        ! empty($query) && curl_setopt($resource, CURLOPT_POSTFIELDS, trim($query, '='));

        curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($resource, CURLOPT_POST, TRUE);
      break;
      default;
        ! empty($query) && $url .= '?' . trim($query, '=');

        $method <> 'GET' && curl_setopt($resource, CURLOPT_CUSTOMREQUEST, $method);
      break;
    }

    $tmp = 'Authorization: OAuth realm="' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '"';

    foreach ($vars['oauth'] as $key => $val) {
      $tmp .= ",$key=" . '"' . htmlspecialchars($val) . '"';
    }

    $headers []= $tmp;

    curl_setopt($resource, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($resource, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($resource, CURLOPT_URL, $url);

    $out = curl_exec($resource);

    $request->info = curl_getinfo($resource);
    $request->info['content_out'] = $out;

    return $out;
  }

  public static function sign($request, $url, $vars = array(), $method = 'GET', $callback = 'SHA1')
  {
    $key  = static::encode($request->consumer_secret) . '&' . static::encode($request->token_secret);
    $old  = static::encode(str_replace('+', '%20', http_build_query($vars, NULL, '&')));
    $test = "$method&" . static::encode($url) . "&$old";

    if (function_exists('hash_hmac')) {
      $test = hash_hmac($callback, $test, $key, TRUE);
    } else {//TODO: fallback is still needed?
      if (strlen($key) > 64) {
        $key = pack('H*', $callback($key));
      }

      $key  = str_pad($key, 64, chr(0x00));
      $lpad = str_repeat(chr(0x36), 64);
      $rpad = str_repeat(chr(0x5c), 64);

      $hmac = pack('H*', $callback(($key ^ $lpad) . $test));
      $test = pack('H*', $callback(($key ^ $rpad) . $hmac));
    }

    return base64_encode($test);
  }

  public static function encode($test)
  {
    if (is_scalar($test)) {
      $test = str_replace('%7E', '~', rawurlencode($test));
    } elseif (is_array($test)) {
      $test = array_map('static::encode', $test);
    }

    return $test;
  }

  public static function set($request, $token, $secret = NULL)
  {
    $request->token = $token;
    $request->token_secret = $secret;
  }

}
