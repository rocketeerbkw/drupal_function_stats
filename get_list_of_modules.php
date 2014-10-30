<?php

/**
 * Download a full list of all modules on drupal.org by using the API. More info
 * at https://www.drupal.org/about-drupalorg/api.
 */

$url = 'https://www.drupal.org/api-d7/node.json?type=project_module';
$modules = array();

do {
  print $url . PHP_EOL;
  $options = array(
    'http' => array(
      'user_agent' => 'get_list_of_modules.php from https://github.com/rocketeerbkw/drupal_function_stats',
      'header' => 'Accept: application/json',
    ),
  );
  $context = stream_context_create($options);
  $response = file_get_contents($url, false, $context);
  $data = json_decode($response);

  foreach ($data->list as $module) {
    $modules[] = $module->field_project_machine_name;
  }

  $url = isset($data->next) ? $data->next : FALSE;

  // The next links are always provided without a format [#2253947].
  // Formatless URLs are 301 redirected to format URLs but strip all query
  // query parameters [#2364755].
  $url = str_replace('node?', 'node.json?', $url);
} while ($url);

$file = fopen('project_usage.txt', 'w');
fwrite($file, implode("\n", $modules));
fclose($file);
