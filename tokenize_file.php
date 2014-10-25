<?php

require "vendor/autoload.php";

use Pharborist\Parser;
use Pharborist\Filter;
use Pharborist\Functions\FunctionDeclarationNode;

$redis = new Predis\Client(NULL, ['prefix' => 'gdtaf']);

$all_modules = $argv[1];
$file = $argv[2];

// The name of the module should be the name of the first directory after
// $modules_dir.
$module_dir = str_replace($all_modules, '', $file);
$module_name = substr($module_dir, 0, strpos($module_dir, '/'));

$tree = Parser::parseFile($file);
$functions = $tree->children(Filter::isInstanceOf(new FunctionDeclarationNode()));
print 'Found ' . $functions->count() . ' functions.' . PHP_EOL;
foreach ($functions as $func) {
  // Get the name of the function w/o the module namespace
  $clean_func = preg_replace('/^_?' . $module_name . '/i', 'MODULE', $func->getName());

  print $func->getName() . ' -> ' . $clean_func . PHP_EOL;
}
