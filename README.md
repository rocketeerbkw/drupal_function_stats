# Drupal Function Stats

A few scripts to gather some fun/interesting stats about functions in the Drupal contrib space.

You'll need a few things first:

  * A directory that contains the source code of all contrib modules. I used [Gotta Download Them All](https://www.drupal.org/sandbox/greggles/1481160) but there may be better options.
  * Redis
  * Composer

Currently this script parses contrib files for functions. It normalizes function names and keeps track of how many times the function was declared across all of contrib. This can be used, for example, to produce a list of "Top Drupal Hooks Used" (see https://gist.github.com/webchick/4409685).

  1. Download dependencies `composer install`
  2. Edit the path to the directory that contains all contrib modules in `process_files.php`
  3. Run `php process_files.php`
  4. You can get the top functions from redis with `zrevrange gdtaf:function_list 0 10 WITHSCORES`

The script `redis_load_core_hooks.php` will parse the list of all Drupal core hooks from [api.drupal.org](https://api.drupal.org) and store them in redis as `gdtaf:hooks_6` and `gdtaf:hooks_7`.

The script `get_list_of_modules.php` will parse the list of all contrib modules from https://www.drupal.org/project/usage and save it as `project_usage.txt`. This can take awhile if the pages aren't cached so I've also added `project_usage.txt` to the repo.
