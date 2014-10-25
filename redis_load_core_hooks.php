<?php

/**
 * Get a list of all hooks provided by Drupal core and add them as a set in
 * Redis.
 */

require "vendor/autoload.php";
$redis = new Predis\Client(NULL, ['prefix' => 'gdtaf:']);

$versions = array(6, 7);
$url = 'https://api.drupal.org/api/drupal/includes%21module.inc/group/hooks/';

foreach ($versions as $version) {
  // Download the list from api.drupal.org.
  $html = file_get_contents($url . $version);
  //print $html;
  $hooks = array();
  preg_match_all('#a href="[^"]+function/(hook_[a-z_]+)/#', $html, $hooks);

  foreach ($hooks[1] as $hook) {
    $redis->zadd('hooks_' . $version, 1, preg_replace('/^hook/', 'MODULE', $hook));
  }
}
