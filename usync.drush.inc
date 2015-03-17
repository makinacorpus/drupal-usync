<?php
/**
 * @file
 * Drush integration.
 */

use USync\Context;
use USync\Runner;
use USync\Parsing\ArrayParser;
use USync\Parsing\PathDiscovery;
use USync\Parsing\YamlReader;

/**
 * Implements hook_drush_command().
 */
function usync_drush_command() {
  return array(
    'usync-run-test' => array(
      'description' => "Run test import",
      'aliases'     => array('urt'),
      'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
  );
}

/**
 * Run test.
 */
function drush_usync_run_test() {
  $tx = null;

  try {
    $tx = db_transaction();

    $reader = new YamlReader();

    $discovery = new PathDiscovery();
    $data = $discovery->discover(__DIR__ . '/sample/reallife', $reader);
    // Another example, using one file only
    //$data = $reader->read(__DIR__ . '/sample/blog.yml');

    $parser = new ArrayParser();
    $graph = $parser->parse($data);
    //print_r($graph);die();

    $runner = new Runner();
    $runner->run(new Context($graph));

    unset($tx); // Explicit commit.

  } catch (\Exception $e) {
    if ($tx) {
      try {
        $tx->rollback();
      } catch (Exception $e2) {}
    }

    drupal_set_message($e->getMessage(), 'error');
    return false;
  }

  return true;
}
