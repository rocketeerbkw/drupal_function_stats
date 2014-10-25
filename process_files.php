<?php

// Need this to overcome some Pharborist errors
function myErrorHandler($errno, $errstr, $errfile, $errline) {
  if ( E_RECOVERABLE_ERROR===$errno ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
  }
  return false;
}
set_error_handler('myErrorHandler');

require "vendor/autoload.php";
$redis = new Predis\Client(NULL, ['prefix' => 'gdtaf']);

$dir = "/Users/rocketeerbkw/gotta_download_them_all/allmodules/";

try {
  // Iterate through all the modules we have downloaded.
  $all_modules = new FilesystemIterator($dir);

  $c = 0;
  foreach ($all_modules as $module_dir_path => $module_dir_info) {
    // Skip files that might be at this level. Only interested in module
    // directories.
    if (!$module_dir_info->isDir()) {
      continue;
    }

    // Look for a .info file
    chdir($module_dir_path);
    $info_files = glob('*.info');

    if ($info_files === FALSE || count($info_files) === 0) {
      // No .info files
      continue;
    }

    // Let's assume there's only one .info and it's the first result.
    $module_name = str_replace('.info', '', $info_files[0]);

    // For every module, get all the functions it defines.
    $cur_module = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($module_dir_path, FilesystemIterator::SKIP_DOTS));

    foreach ($cur_module as $module_path => $module_info) {
      // Skip directorys (just in case).
      if ($module_info->isDir()) {
        continue;
      }

      // Skip git stuff.
      if (strstr($module_info->getPath(), '.git') !== FALSE) {
        continue;
      }

      // Only interested in module, install, inc, php extensions.
      if (!in_array($module_info->getExtension(), array('module', 'install', 'inc', 'php'))) {
        continue;
      }

      // The path of the file if the module folder was root.
      $path_from_module = str_replace($dir, '', $module_info->getPathName());

      print $path_from_module . ': ';

      // Check if we've processed this file before.
      if ($redis->sismember('processed_files', $path_from_module)) {
        print ' already processed.' . PHP_EOL;
        continue;
      }

      //print $module_info->getPathName() . ' ~ ' . $module_info->getFilename() . ' ~ ' . $module_info->getExtension() . PHP_EOL;
      //print_r($module_info->getFileInfo()) . PHP_EOL;
      //$command = 'php tokenize_file.php ' . escapeshellarg($dir) . ' ' . escapeshellarg($module_info->getPathName());
      //passthru(escapeshellcmd($command));

      // Find and track each function found in the file.
      try {
        $tree = Pharborist\Parser::parseFile($module_info->getPathName());
        $functions = $tree->children(Pharborist\Filter::isInstanceOf(
          new Pharborist\Functions\FunctionDeclarationNode()
        ));
        foreach ($functions as $func) {
          // Get the name of the function w/o the module namespace
          $clean_func = preg_replace('/^_?' . $module_name . '/i', 'MODULE', $func->getName());

          $redis->zincrby('function_list', 1, $clean_func);
          print '.';
        }

        $redis->sadd('processed_files', $path_from_module);

        print ' Done.' . PHP_EOL;
      }
      catch (Exception $e) {
        print ' Error ' . $e->getMessage() . PHP_EOL;
      }
    }

    $c++;
    if ($c > 10) {
      //break;
    }
  }
}
catch (UnexpectedValueException $e) {
  print "Directory path error: " . $e->getMessage() . PHP_EOL;
}
