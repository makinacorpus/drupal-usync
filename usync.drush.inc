<?php
/**
 * @file
 * Drush integration.
 */

use USync\AST\Drupal\DrupalNodeInterface;
use USync\AST\NodeInterface;
use USync\AST\Path;
use USync\AST\Processing\DrupalAttributesProcessor;
use USync\AST\Processing\DrupalProcessor;
use USync\AST\Processing\InheritProcessor;
use USync\AST\Processing\StatProcessor;
use USync\AST\Visitor;
use USync\Context;
use USync\Parsing\PathDiscovery;
use USync\Parsing\YamlReader;
use USync\Serializing\YamlSerializer;
use USync\TreeBuilding\ArrayTreeBuilder;
use USync\TreeBuilding\ExtractingTreeBuilder;

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
    'usync-tree' => array(
      'description' => "List all components in the given source",
      'aliases'     => array("ust"),
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
    'usync-extract' => array(
      'description' => "Export a file from the given components",
      'aliases'     => array("usx"),
      'options'     => array(
        'dest'      => "Target file",
        'match'     => "Filters only components that matches the given path",
        'type'      => "File type to match (default is 'yaml'); this is used only when destination is a folder",
      ),
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
function drush_usync_tree() {

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
  $visitor->addProcessor(new DrupalAttributesProcessor());
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
    /*
    if ($node instanceof DrupalNodeInterface) {
      if ($node->exists()) {
        $output[] = " ~";
      } else if (false) { // @todo Handle delete
        $output[] = " -";
      } else {
        $output[] = " +";
      }
      // @todo Handle modified?
    }
     */
    $output[] = " +";
    $output[] = $node->getPath();
    if ($node instanceof DrupalNodeInterface && $node->isDirty()) {
      $output[] = "DIRTY";
    }
    if ($node instanceof DrupalNodeInterface && $node->isMerge()) {
      $output[] = "MERGE";
    }
    if ($parent = $node->getBaseNode()) {
      $output[] = "(" . $parent->getPath() . ")";
    }
    drush_print(implode(" ", $output));
  }

  drush_print(dt("@count/@total displayed nodes", array(
    '@count' => count($found),
    '@total' => count($statProcessor),
  )));
}

/**
 * Run export.
 */
function drush_usync_extract() {

  $pathes = drush_get_option('match');
  // $dest   = drush_get_option('dest');
  $type   = drush_get_option('type');

  if (empty($pathes)) {
    drush_print(dt("--match=PATH option is mandatory"));
    drush_die();
  } else {
    $pathes = explode(',', $pathes);
  }

  if (!empty($type)) {
    $serializerClass = '\\USync\\Serializing\\' . ucfirst($type) . 'Serializer';
    if (!class_exists($serializerClass)) {
      trigger_error(sprintf("'%s': type is not supported", $type));
      return false;
    }
    $serializer = new $serializerClass();
  } else {
    $serializer = new YamlSerializer();
  }

  $loaders = usync_loader_list();
  $builder = new ExtractingTreeBuilder();
  $context = new Context();
  $graph   = $builder->parse($pathes, $loaders, $context);

  echo $serializer->serialize($graph);
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
  }

  $tx = null;

  try {
    $tx = db_transaction();

    $graph   = _drush_usync_list();
    $loaders = usync_loader_list();
    $context = new Context($graph);

    $statProcessor = new StatProcessor();
    $visitor = new Visitor();
    $visitor->addProcessor(new DrupalAttributesProcessor());
    $visitor->addProcessor(new InheritProcessor());
    $visitor->addProcessor($statProcessor);
    $visitor->execute($graph, $context);

    $visitor = new Visitor();
    $drupalProcessor = new DrupalProcessor($loaders);

    if (null !== $pathes) {
      $visitor->addProcessor(function (NodeInterface $node, Context $context) use ($drupalProcessor, $pathes, $partial) {
        foreach ($pathes as $pattern) {
          if (false !== Path::match($node->getPath(), $pattern, $partial)) {
            $drupalProcessor->execute($node, $context);
          }
        }
      });
    } else {
      $visitor->addProcessor($drupalProcessor);
    }

    $visitor->execute($graph, $context);

    // This seems, at some point, mandatory.
    field_cache_clear();
    menu_rebuild();

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
