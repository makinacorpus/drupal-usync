<?php
/**
 * @file
 * Drush integration.
 */

use USync\AST\NodeInterface;
use USync\AST\Path;
use USync\AST\Processing\InheritProcessor;
use USync\AST\Processing\StatProcessor;
use USync\AST\Visitor;
use USync\Context;
use USync\Runner;
use USync\Parsing\ArrayParser;
use USync\Parsing\PathDiscovery;
use USync\Parsing\YamlReader;
use USync\AST\Drupal\DrupalNodeInterface;

/**
 * Implements hook_drush_command().
 */
function usync_drush_command() {
  return array(
    'usync-run-test' => array(
      'description' => "Run test import; WARNING: this writes data into your site!",
      'aliases'     => array('urt'),
      'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
    'usync-list' => array(
      'description' => "List sources",
      'aliases'     => array("usl"),
      'options'     => array(
        'source'    => "Path to source file (folder or file)",
        'type'      => "File type to match (default is 'yaml')",
        'match'     => "Filters only components that matches the given path.",
        'partial'   => "Do partial match (by prefix) only",
      ),
      'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
  );
}

/**
 * List what's in the source
 */
function drush_usync_list() {

  $source  = drush_get_option('source');
  $pathes  = drush_get_option('match');
  $type    = drush_get_option('type');
  $partial = (bool)drush_get_option('partial');

  if (empty($source)) {
    drush_print(dt("--source=PATH option is mandatory"));
    drush_die();
  }
  if (!is_dir($source) && !is_file($source)) {
    drush_print(dt("@path does not exists", array('@path' => $source)));
    drush_die();
  }

  if (!empty($type)) {
    $readerClass = '\\USync\\Parsing\\' . ucfirst($type) . 'Reader';
    if (!class_exists($readerClass)) {
      drush_print(dt("'@type' type is not supported", array('@type' => $type)));
      drush_die();
    }
    $reader = new $readerClass();
  } else {
    $reader = new YamlReader();
  }

  if (is_file($source)) {
    $data = $reader->read($source);
  } else if (is_dir($source)) {
    $discovery = new PathDiscovery();
    $data = $discovery->discover($source, $reader);
  }

  if (empty($data)) {
    drush_print(dt("Could not parse file or folder"));
    drush_die();
  }

  if (empty($pathes)) {
    $pathes = null;
  } else {
    $pathes = explode(',', $pathes);
  }

  $parser = new ArrayParser();
  $graph = $parser->parse($data);

  $statProcessor = new StatProcessor();
  $visitor = new Visitor();
  $visitor->addProcessor(new InheritProcessor());
  $visitor->addProcessor($statProcessor);

  // This visitor has the only purpose to find Drupal revelant entries
  // in the graph, that would trigger a Drupal update. Any other info
  // is not revelant for Drupal users.
  $found = [];
  $visitor->addProcessor(function (NodeInterface $node, Context $context) use (&$found, $pathes, $partial) {

    // First check if the current node matches
    if (!$node instanceof DrupalNodeInterface) {
      return;
    }

    // Proceed to patch match.
    $matches = false;

    if (null !== $pathes) {
      foreach ($pathes as $pattern) {
        if (false !== Path::match($node->getPath(), $pattern, $partial)) {
          $matches = true;
          break;
        }
      }
    } else {
      $matches = true;
    }

    if ($matches) {
      $found[] = $node;
      drush_print(" - " . $node->getPath());
    }
  });

  $visitor->execute($graph, new Context($graph));
  drush_print(dt("@count/@total displayed nodes", array(
    '@count' => count($found),
    '@total' => count($statProcessor),
  )));
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
