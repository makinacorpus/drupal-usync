<?php
/**
 * @file
 * Drush integration.
 */

use USync\AST\Drupal\DrupalNodeInterface;
use USync\AST\NodeInterface;
use USync\AST\Path;
use USync\AST\Processing\DrupalProcessor;
use USync\AST\Processing\InheritProcessor;
use USync\AST\Processing\StatProcessor;
use USync\AST\Visitor;
use USync\Context;
use USync\Parsing\ArrayParser;
use USync\Parsing\PathDiscovery;
use USync\Parsing\YamlReader;

/**
 * Implements hook_drush_command().
 */
function usync_drush_command() {
  return array(
    'usync-run' => array(
      'description' => "Run synchronization",
      'aliases'     => array("usr"),
      'options'     => array(
        'source'    => "Path to source file (folder or file) or a string in the form 'MODULE:SOURCE', use usync-list to list sources",
        'type'      => "File type to match (default is 'yaml'); this is used only when source is a file or a folder",
        'match'     => "Filters only components that matches the given path",
        'partial'   => "Do partial match (by prefix) only",
      ),
      'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
    'usync-stat' => array(
      'description' => "List all components in the given source",
      'aliases'     => array("uss"),
      'options'     => array(
        'source'    => "Path to source file (folder or file) or a string in the form 'MODULE:SOURCE', use usync-list to list sources",
        'type'      => "File type to match (default is 'yaml'); this is used only when source is a file or a folder",
        'match'     => "Filters only components that matches the given path",
        'partial'   => "Do partial match (by prefix) only",
      ),
      'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
    'usync-list' => array(
      'description' => "List sources modules",
      'aliases'     => array("usl"),
      'options'     => array(),
      'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
  );
}

/**
 * Get graph using Drush common options.
 *
 * @return \USync\AST\NodeInterface
 */
function _drush_usync_list() {

  $source  = drush_get_option('source');
  $type    = drush_get_option('type');
  $module  = null;

  if (empty($source)) {
    drush_print(dt("--source=PATH option is mandatory"));
    drush_die();
  }

  if (strpos($source, ':')) {
    list($module, $source) = explode(':', $source);
    if (!module_exists($module)) {
      drush_print(dt("@module does not exist", array('@module' => $module)));
      drush_die();
    }
    if (empty($source)) {
      $graph = usync_module_graph_get($module);
    } else {
      $graph = usync_module_graph_get($module, [$source]);
    }
  } else {
    $graph = usync_graph_create([$source], $type);
  }

  if (empty($graph)) {
    drush_die();
  }

  return $graph;
}

/**
 * List all modules declaring stuff to sync.
 */
function drush_usync_list() {
  $rows = [
    [dt("Module"), dt("Source")]
  ];
  foreach (usync_module_list() as $module => $sources) {
    foreach ($sources as $source) {
      $rows[] = [$module, $source];
      $module = ''; // Display module name once.
    }
  }
  drush_print_table($rows, true);
}

/**
 * List what's in the source.
 */
function drush_usync_stat() {

  $pathes  = drush_get_option('match');
  $partial = (bool)drush_get_option('partial');

  if (empty($pathes)) {
    $pathes = null;
  } else {
    $pathes = explode(',', $pathes);
  }

  $graph = _drush_usync_list();
  $found = [];

  $statProcessor = new StatProcessor();
  $visitor = new Visitor();
  $visitor->addProcessor(new InheritProcessor());
  $visitor->addProcessor($statProcessor);

  $visitor->addProcessor(function (NodeInterface $node, Context $context) use (&$found, $pathes, $partial) {
    // This visitor has the only purpose to find Drupal revelant entries in the
    // graph, that would trigger a Drupal update. Any other info isn't revelant
    // for Drupal users.
    if (!$node instanceof DrupalNodeInterface) {
      return;
    }
    if (null !== $pathes) { // Proceed to patch match.
      foreach ($pathes as $pattern) {
        if (false !== Path::match($node->getPath(), $pattern, $partial)) {
          $found[] = $node;
          break;
        }
      }
    } else {
      $found[] = $node;
    }
  });

  $visitor->execute($graph, new Context($graph));

  foreach ($found as $node) {
    $output = [];
    $output[] = " - " . $node->getPath();
    if ($parent = $node->getBaseNode()) {
      $output[] = "extends " . $parent->getPath();
    }
    drush_print(implode(" ", $output));
  }

  drush_print(dt("@count/@total displayed nodes", array(
    '@count' => count($found),
    '@total' => count($statProcessor),
  )));
}

/**
 * Run sync.
 */
function drush_usync_run() {

  $pathes  = drush_get_option('match');
  $partial = (bool)drush_get_option('partial');

  if (empty($pathes)) {
    $pathes = null;
  } else {
    $pathes = explode(',', $pathes);
    // @todo
    drush_print(dt("--match=PATH is not implemented yet"));
    drush_die();
  }

  $tx = null;

  try {
    $tx = db_transaction();

    $graph   = _drush_usync_list();
    $helpers = usync_helper_list();
    $context = new Context($graph);

    $statProcessor = new StatProcessor();
    $visitor = new Visitor();
    $visitor->addProcessor(new InheritProcessor());
    $visitor->addProcessor($statProcessor);
    $visitor->execute($graph, $context);

    $visitor = new Visitor();
    $visitor->addProcessor(new DrupalProcessor($helpers));
    $visitor->execute($graph, $context);

    menu_rebuild(); // Sorry.

    unset($tx); // Explicit commit.

  } catch (\Exception $e) {
    if ($tx) {
      try {
        $tx->rollback();
      } catch (Exception $e2) {}
    }
    drupal_set_message($e->getMessage(), 'error');
  }
}
