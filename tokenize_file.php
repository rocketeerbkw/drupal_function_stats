<?php

require "vendor/autoload.php";
$redis = new Predis\Client(NULL, ['prefix' => 'gdtaf']);

$all_modules = $argv[1];
$file = $argv[2];

// The name of the module should be the name of the first directory after
// $modules_dir.
$module_dir = str_replace($all_modules, '', $file);
$module_name = substr($module_dir, 0, strpos($module_dir, '/'));

// Get all tokens from the file.
$tokens = token_get_all(file_get_contents($file));

// Aggregate all tokens on one line. Discard any token that's not a function
// declaration or string.
$tokens_by_line = array();
foreach ($tokens as $token) {
  if (!in_array($token[0], array(T_FUNCTION, T_STRING))) {
    continue;
  }

  $tokens_by_line[$token[2]][] = $token;
}

// Discard all lines that aren't function declarations.
$function_tokens = array();
foreach ($tokens_by_line as $tokens) {
  // If the line only has one token, it can't be a function declaration.
  if (count($tokens) == 1) {
    continue;
  }

  // If none of the tokens on a line are T_FUNCTION, it can't be a function
  // delcaration.
  $has_function = FALSE;
  foreach ($tokens as $token) {
    if ($token[0] === T_FUNCTION) {
      $has_function = TRUE;
    }
  }

  if (!$has_function) {
    continue;
  }

  // Get the function name.
  $func_name = $tokens[1][1];

  $function_tokens[] = $func_name;
}

// Remove module "namespace" from function. Turns MODULE_function_name() into
// function_name().
$functions = array_map(function($value) use ($module_name) {
  return str_replace(array('_' . $module_name, $module_name . '_'), array($module_name, ''), $value);
}, $function_tokens);

foreach ($functions as $function) {
  $redis->zincrby('function_list', 1, $function);
}

$redis->sadd('processed_files', $module_dir);

//print_r($functions);
print 'Done.' . PHP_EOL;
