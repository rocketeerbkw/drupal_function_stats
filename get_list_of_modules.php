<?php

/**
 * Download a full list of all modules on drupal.org by scraping
 * https://www.drupal.org/project/usage. This can take awhile the first time as
 * the pages aren't cached.
 *
 * Note: there is a one-liner at https://www.drupal.org/node/1057386#comment-4376134
 *       but it no longer works since the usage page is now using a pager.
 */

$url = 'https://www.drupal.org/project/usage?page=';
$modules = array();
$page = 0;

do {
  $html = file_get_contents($url . $page);

  $matches = array();
  preg_match_all('#href="[^"]+usage/([^"]+)"#', $html, $matches);

  $modules = array_merge($modules, $matches[1]);

  $page++;
} while (strstr($html, 'Go to next page') !== FALSE);

$file = fopen('project_usage.txt', 'w');
fwrite($file, implode("\n", $modules));
fclose($file);
