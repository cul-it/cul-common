<?php

function get_json($url) {
  return file_get_contents($url);
}

function get_and_cache_json($cid, $url, $refresh = FALSE) {
  static $json;
  if (($cached = cache_get($cid, 'cache')) && ! empty($cached->data) && ! $refresh) {
    $json = $cached->data;
  }
  else {
    $json = get_json($url);
    // code offered by John Fereira to deal with encoding issues with Ares data
    $encoding =  mb_detect_encoding($json, "auto");
    $json = mb_convert_encoding($json, $encoding, "UTF-8");
    cache_set($cid, $json, 'cache');
  }
  return $json;
}

function output_json_string($json) {
  # drupal_json() doesn't work when you have a json string for some reason
  # even when I try to trick it with PHP's json_encode(), json_decode() combo
  # but the following works, based on code of drupal_json
  drupal_add_http_header('Content-Type', 'text/javascript; charset=utf-8');
  echo $json;
}

