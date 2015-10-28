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
use USync\AST\Processing\ExpressionProcessor;
use USync\AST\Processing\InheritProcessor;
use USync\AST\Processing\StatProcessor;
use USync\AST\Visitor;
use USync\Context;
use USync\Serializing\YamlSerializer;
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
      // 'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
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
      // 'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
    'usync-list' => array(
      'description' => "List sources modules",
      'aliases'     => array("usl"),
      'options'     => array(),
      // 'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
    'usync-extract' => array(
      'description' => "Export a file from the given components",
      'aliases'     => array("usx"),
      'options'     => array(
        'dest'      => "Target file",
        'match'     => "Filters only components that matches the given path",
        'type'      => "File type to match (default is 'yaml'); this is used only when destination is a folder",
      ),
      // 'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
    'usync-field-default-value' => array(
      'description' => "Console helper to set field default values",
      'aliases'     => array("usfdv"),
      'options'     => array(
        'entity'    => "Coma separated list of entity types",
        'field'     => "Coma separated list of field types",
      ),
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
    drush_print(dt("Graph is empty", array('@module' => $module)));
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

  $graph    = _drush_usync_list();
  $found    = [];
  $context  = new Context($graph);

  $statProcessor = new StatProcessor();

  $visitor = new Visitor();
  $visitor->addProcessor(new DrupalAttributesProcessor());
  $visitor->addProcessor(new InheritProcessor());
  $visitor->addProcessor($statProcessor);
  $visitor->execute($graph, $context);

  $visitor = new Visitor(Visitor::TOP_BOTTOM);
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

  $visitor->execute($graph, $context);

  foreach ($found as $node) {
    $output = [];

    if ($node instanceof DrupalNodeInterface) {
      if (false && $node->exists()) {
        $output[] = " ~";
      } else if ($node->shouldDelete()) { // @todo Handle delete
        $output[] = " -";
      } else {
        $output[] = " +";
      }
      // @todo Handle modified?
    }

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
    $visitor->addProcessor(new ExpressionProcessor());
    $visitor->addProcessor(new DrupalAttributesProcessor());
    $visitor->addProcessor(new InheritProcessor());
    $visitor->execute($graph, $context);

    $visitor = new Visitor(Visitor::TOP_BOTTOM);
    $visitor->addProcessor($statProcessor);
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
    // DO NOT EVER call menu_rebuild() manually, if you do this, Drupal will
    // mix-up the menu links you manually saved using menu_link_save() and
    // those still in cache at some point, and loose menu items parenting.
    // This wrong behavior cost me a few hours to debug.
    variable_set('menu_rebuild_needed', 1);

    unset($tx); // Explicit commit.

  } catch (\Exception $e) {
    if ($tx) {
      try {
        $tx->rollback();
      } catch (Exception $e2) {}
    }
    print $e->getTraceAsString();
    drupal_set_message($e->getMessage(), 'error');
  }
}

/**
 * Let's go for a terrible function.
 */
function drush_usync_field_default_value() {

  $allowedEntityTypes = drush_get_option('entity');
  if (empty($allowedEntityTypes)) {
    $allowedEntityTypes = null;
  } else {
    $allowedEntityTypes = explode(',', $allowedEntityTypes);
  }

  $allowedFieldTypes = drush_get_option('field');
  if (empty($allowedFieldTypes)) {
    $allowedFieldTypes = null;
  } else {
    $allowedFieldTypes = explode(',', $allowedFieldTypes);
  }

  $fieldCount = [];

  $instances = field_info_instances();
  $potential_defaults = [];
  $shouldUpdate = [];

  $countAutodetectedInstances = 0;
  $countSkippedInstances = 0;
  $countInstances = 0;

  $currentPosition = 0;
  $maxPosition = 0;

  foreach ($instances as $entity_type => $bundles) {

    if (!empty($allowedEntityTypes) && !in_array($entity_type, $allowedEntityTypes)) {
      unset($instances[$entity_type]);
      continue;
    }

    foreach ($bundles as $bundle_name => $bundle_instances) {
      foreach ($bundle_instances as $field_name => $instance) {

        $field = field_info_field($field_name);

        if (!empty($allowedFieldTypes) && !in_array($field['type'], $allowedFieldTypes)) {
          unset($instances[$entity_type][$bundle_name][$field_name]);
          continue;
        }

        $countInstances++;

        if (!empty($instance['default_value'])) {
          // Prune already set defaults and add potential default values into
          // our nice array for faster lookup later.
          $potential_defaults[$field_name][] = $instance['default_value'];
          unset($instances[$entity_type][$bundle_name][$field_name]);
          $countSkippedInstances++;
          continue;

        } else if (!isset($field['columns']['value'])) {
          // We handle only fields with a 'value' column, seems dumb, but
          // don't want to do any other thing that something the user can
          // input by itself.
          unset($instances[$entity_type][$bundle_name][$field_name]);
          $countSkippedInstances++;
          continue;
        }

        if (!isset($fieldCount[$field_name])) {
          $fieldCount[$field_name] = 0;
        }
        $fieldCount[$field_name]++;

        $maxPosition++;
      }
    }
  }

  drush_print("Skipped " . $countSkippedInstances . "/" . $countInstances . " instances");

  foreach ($instances as $entity_type => $bundles) {
    $entity_info = entity_get_info($entity_type);

    if (empty($instances[$entity_type])) {
      drush_print(" - Entity '" . $entity_info['label'] . "' [" . $entity_type . "]");
      continue;
    }
    drush_print(" + Entity '" . $entity_info['label'] . "' [" . $entity_type . "]");

    foreach ($bundles as $bundle_name => $field_instances) {
      $bundle_info = $entity_info['bundles'][$bundle_name];

      if (empty($instances[$entity_type][$bundle_name])) {
        drush_print("    - Bundle '" . $bundle_info['label'] . "' [" . $bundle_name . "]");
        continue;
      }
      drush_print("    + Bundle '" . $bundle_info['label'] . "' [" . $bundle_name . "]");

      foreach ($field_instances as $field_name => $instance) {
        $field = field_info_field($field_name);

        // In all cases, remove this instance from array so we won't have
        // to parse it back and forth when dealing with field level user
        // set default values.
        $currentPosition++;
        unset($instances[$entity_type][$bundle_name][$field_name]);
        $fieldCount[$field_name]--;

        drush_print("       - [" . $currentPosition . "/" . $maxPosition . "] Instance '" . $instance['label'] . "' [" . $field_name . "] [" . $field['type'] . "]");

        $is_fixed = false;

        if ($options = list_allowed_values($field, $instance, $entity_type)) {
          $is_fixed = true;
        }
        // We never know what could happen in there.
        if (!is_array($options)) {
          $options = [];
          $is_fixed = false;
        }

        if ('list_' === substr($field['type'], 0, 5) && empty($options)) {
          drush_print("Field is a list type but has no default allowed values, this is probably due to the fact that a module is manually altering the form. Please fix it.");
          continue;
        }

        if (!empty($potential_defaults[$field_name])) {
          foreach ($potential_defaults[$field_name] as $defaults) {
            foreach ($defaults as $item) {
              if (!isset($options[$item['value']])) {
                $options[$item['value']] = $item['value'];
              }
            }
          }
        }

        $user_choice = false;

        // Ask in potentials and in other field values.
        if (!empty($options)) {
          if ($is_fixed) {
            $options['0000other'] = "Manually type a value";
          }
          if (!$field['required']) {
            $options['0000empty'] = "Empty is default";
          }

          ksort($options);

          $user_choice = drush_choice($options, "Select a value");
          if ('0000other' === $user_choice) {
            $user_choice = false;
          }
        }

        // Or let the user choose another value.
        if (!$is_fixed && false === $user_choice) {
          $user_choice = drush_prompt("Manually enter the value (type \"n\" for no value)");
          if ('n' === trim($user_choice)) {
            $user_choice = false;
          }
        }

        if (false !== $user_choice) {

          if ('0000empty' === $user_choice) {
            $default_value = [['value' => null]];
          } else {
            $default_value = [['value' => $user_choice]];
          }

          // Ask if want to use as default for this field in general.
          if (0 < $fieldCount[$field_name] && drush_confirm("Use this value for other instances of the same field ?")) {
            // Then, just go for it.
            foreach ($instances as $__entity_type => $__bundles) {
              foreach ($__bundles as $__bundle_name => $__bundle_instances) {
                foreach ($__bundle_instances as $__field_name => $__instance) {
                  if ($__field_name === $field_name) {
                    drush_print(" + Auto-detected " . $__entity_type . '/' . $__bundle_name . '/' . $__field_name);
                    $__instance['default_value'] = $default_value;
                    field_update_instance($__instance);
                    unset($instances[$__entity_type][$__bundle_name][$__field_name]);
                    $countAutodetectedInstances++;
                    $currentPosition++;
                    $fieldCount[$field_name]--;
                  }
                }
              }
            }
          }

          // If changed save instance.
          $instance['default_value'] = $default_value;
          field_update_instance($instance);

        } else {
          if (0 < $fieldCount[$field_name] && drush_confirm("Do not set a value for other instances of the same field ?")) {
            // Then, just go for it.
            foreach ($instances as $__entity_type => $__bundles) {
              foreach ($__bundles as $__bundle_name => $__bundle_instances) {
                foreach ($__bundle_instances as $__field_name => $__instance) {
                  if ($__field_name === $field_name) {
                    drush_print(" + Auto-detected " . $__entity_type . '/' . $__bundle_name . '/' . $__field_name);
                    unset($instances[$__entity_type][$__bundle_name][$__field_name]);
                    $countAutodetectedInstances++;
                    $currentPosition++;
                    $fieldCount[$field_name]--;
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
