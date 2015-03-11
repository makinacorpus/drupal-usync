<?php
/**
 * @file
 * Drush integration.
 */

use USync\Context;
use USync\Runner;
use USync\Parsing\PathDiscovery;
use USync\Parsing\YamlParser;

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

    //$discovery = new PathDiscovery();
    //$graph = $discovery->discover(__DIR__ . '/sample/reallife', new YamlParser());

    // Another example, using one file only
    $parser = new YamlParser();
    $graph = \USync\AST\Node::createNode($parser->parse(__DIR__ . '/sample/blog.yml'));

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
