<?php

require "vendor/autoload.php";
$redis = new Predis\Client(NULL, ['prefix' => 'gdtaf']);

$dir = "/Users/rocketeerbkw/gotta_download_them_all/allmodules/";

try {
  // Iterate through all the modules we have downloaded.
  $all_modules = new FilesystemIterator($dir);

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

    $c = 0;
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

      // Get all tokens from the file..
      $tokens = token_get_all(file_get_contents($module_info));

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

      $redis->sadd('processed_files', $path_from_module);

      //print_r($functions);
      print 'Done.' . PHP_EOL;
      $tokens = NULL;

      $c++;
    }
  }
}
catch (UnexpectedValueException $e) {
  print "Directory path error: " . $e->getMessage() . PHP_EOL;
}
