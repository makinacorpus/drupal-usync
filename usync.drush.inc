<?php
/**
 * @file
 * Drush integration.
 */

use USync\AST\Drupal\DrupalNodeInterface;
use USync\Context;
use USync\Loading\Loader;
use USync\Logger\DrushLogger;
use USync\Serializing\YamlSerializer;
use USync\TreeBuilding\ExtractingTreeBuilder;
use USync\TreeBuilding\GraphBuilder;
use USync\TreeBuilding\Repository;

use Symfony\Component\Yaml\Yaml;

/**
 * Implements hook_drush_command().
 */
function usync_drush_command() {
  return array(
    'usync-run' => array(
      'description' => "Run synchronization",
      'aliases'     => array("usr"),
      'arguments'   => array(
        'sources'   => "Path to source file (folder or file) or a string in the form 'MODULE:SOURCE', use usync-list to list sources",
      ),
      'options'     => array(
        'all'       => "If no source specified, run all registered sources",
        'source'    => "Obsolete, kept for backward compatibility",
        'type'      => "File type to match (default is 'yaml'); this is used only when source is a file or a folder",
        'match'     => "Filters only components that matches the given path",
        'partial'   => "Do partial match (by prefix) only",
        'break'     => "Any of: 'warning', 'error', 'dataloss', 'force', indicates the minimal consistency level this run should respect",
        'progress'  => 'Show a progress bar',
      ),
      // 'bootstrap'   => DRUSH_BOOTSTRAP_FULL,
    ),
    'usync-tree' => array(
      'description' => "List all components in the given source",
      'aliases'     => array("ust"),
      'arguments'   => array(
        'sources'   => "Path to source file (folder or file) or a string in the form 'MODULE:SOURCE', use usync-list to list sources",
      ),
      'options'     => array(
        'all'       => "If no source specified, run all registered sources",
        'source'    => "Obsolete, kept for backward compatibility",
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
    'usync-strongarm-to-usync' => array(
      'description' => "Console helper to convert feature strongarm exports to µSync files",
      'aliases'     => array("usstus"),
    ),
    'usync-node-types-to-usync' => array(
      'description' => "Console helper to convert all node types to µSync files",
      'aliases'     => array("usnttus"),
    ),
  );
}

/**
 * Get graph using Drush common options.
 *
 * @param $sources
 * @return \USync\Context
 */
function _drush_usync_list($sources) {

  $doAll = drush_get_option('all');

  if (empty($sources)) {
    $sources = [drush_get_option('source')];
  }

  // Absolutely no idea why, sometime we get an array containing null, I guess
  // drush is guilty...
  $sources = array_filter($sources);

  if ($doAll && $sources) {
    drush_print(dt("You can not specify sources with the --all switch"));
    drush_die();
  }

  if ($doAll) {
    $repository = Repository::getInstance();

    $sources = [];
    foreach ($repository->getResourcesAll() as $resource) {
      $sources[] = $resource->getSource();
    }

  } else if (empty($sources)) {
    drush_print(dt("you must add sources as arguments if not using the --all switch"));
    drush_die();
  }

  $context = (new GraphBuilder($sources))->build();
  $context->setLogger(new DrushLogger());

  return $context;
}

/**
 * List all modules declaring stuff to sync.
 */
function drush_usync_list() {

  $rows = [[dt("Module"), dt("Source"), dt("Status")]];

  $repository = Repository::getInstance();

  $previous = null;
  foreach ($repository->getResourcesAll() as $resource) {

    $module = $resource->getModule();

    $rows[] = [
      $module === $previous ? '' : $module,
      $resource->getTargetInModule(),
      $resource->isFresh() ? dt("needs update") : dt("ok"),
    ];

    $previous = $module;
  }

  $repository->commit();

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

  $context  = _drush_usync_list(func_get_args());
  $list     = (new Loader())->listAll($context, $pathes, $partial);

  foreach ($list as $data) {
    /** @var $loader \USync\Loading\LoaderInterface */
    /** @var $node \USync\AST\NodeInterface */
    list($node, $loader, $mode) = $data;

    $output = [];

    switch ($mode) {

      case Loader::LOAD_DELETE:
        $output[] = " -";
        break;

      case Loader::LOAD_IGNORE:
        $output[] = "  ";
        break;

      case Loader::LOAD_SYNC:
        if ($loader->exists($node, $context)) {
          $output[] = " ~";
        } else {
          $output[] = " +";
        }
        break;
    }

    $output[] = $node->getPath();
    if ($node instanceof DrupalNodeInterface) {
      if ($node->isDirty()) {
        $output[] = "DIRTY";
      }
      if ($node->isMerge()) {
        $output[] = "MERGE";
      }
    }
    drush_print(implode(" ", $output));
  }

  _drush_usync_timer_table($context);
}

/**
 * Utility function to get the average of an array of numbers
 *
 * @param $array
 * @return float
 */
function _usync_average($array) {
  $count = 0;
  $total = 0;
  foreach ($array as $value) {
    $count++;
    $total += $value;
  }
  return round($total / $count);
}

/**
 * Prints a well-formatted usync timer table
 *
 * @param \USync\Context $context
 * @return object|\ReflectionClass
 */
function _drush_usync_timer_table(Context $context) {
  $rows = [];
  $rows[] = [dt("timer"), dt("cnt"), dt("min"), dt("max"), dt("avg"), dt("sum")];

  $allTimers = $context->getTimers();
  ksort($allTimers);

  foreach ($allTimers as $name => $timers) {

    $values = [];
    $total  = 0;

    foreach ($timers as $timer) {
      /** @var $timer \USync\Timer */
      $value = $timer->get();
      $total += $value;
      $values[] = $value;
    }

    if (1 === count($values)) {
      $rows[] = [$name, 1, '-', '-', '-', $total];
    } else {
      $rows[] = [
        $name,
        count($values),
        min($values),
        max($values),
        _usync_average($values),
        $total,
      ];
    }
  }

  drush_print("");

  return drush_print_table($rows, true);
}

/**
 * Utility function to extract a path
 *
 * @param $path
 * @param $type
 * @return bool|string
 */
function _drush_usync_extract_path($path, $type) {

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
  $graph   = $builder->parse($path, $loaders, $context);

  return $serializer->serialize($graph);
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

  echo _drush_usync_extract_path($pathes, $type);
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

    $context = _drush_usync_list(func_get_args());

    switch (drush_get_option('break', 'error')) {

      case 'warning':
        $context->setBreakOn(Context::BREAK_WARNING);
        break;

      case 'dataloss':
        $context->setBreakOn(Context::BREAK_DATALOSS);
        break;

      case 'force':
        $context->setBreakOn(Context::BREAK_FORCE);
        break;

      default:
      case 'error':
        $context->setBreakOn(Context::BREAK_ERROR);
        break;
    }

    (new Loader())->loadAll($context, $pathes, $partial);

    _drush_usync_timer_table($context);

    unset($tx); // Explicit commit.

  } catch (\Exception $e) {
    if ($tx) {
      try {
        $tx->rollback();
      } catch (Exception $e2) {}
    }
    throw $e;
  }
}

/**
 * Converts strongarm features files to usync yaml files.
 */
function drush_usync_node_types_to_usync() {

  $dropExisting = true;

  foreach (module_list() as $module) {

    if (!module_implements('node_info')) {
      continue;
    }

    $module_path  = drupal_get_path('module', $module);
    $node_types   = module_invoke($module, 'node_info');

    if (empty($node_types)) {
      continue;
    }

    foreach (array_keys($node_types) as $type) {

      drush_print(" * extracting node type '" . $type . "'");

      $usync_path = $module_path . '/usync-partial';
      $usync_file = $module_path . '/usync-partial/node-' . $type . '.yml';
      $msg_prefix = $module . ': ' . $type . ': ';

      $content = _drush_usync_extract_path('entity.node.' . $type, 'yaml');

      if (!$content) {
        drupal_set_message($msg_prefix . 'could not dump data');
        continue;
      }

      // Attempt to create path.
      if (!is_dir($usync_path)) {
        if (!mkdir($usync_path)) {
          drupal_set_message($msg_prefix . 'cannot create directory ' . $usync_path . ', dumping file in cli', 'error');
          drush_print($content);
          continue;
        }
      }

      $exists = false;

      if ($dropExisting && file_exists($usync_file)) {
        drush_print($msg_prefix . 'removing existing ' . $usync_file);
        if (!unlink($usync_file)) {
          drupal_set_message($msg_prefix . 'cannot delete existing file ' . $usync_path . ', dumping file in cli', 'error');
          drush_print($content);
          continue;
        }
      }

      if (file_exists($usync_file)) {
        // If file already exists, append what you can inside.
        drush_print($msg_prefix . 'using existing ' . $usync_file);
        if (false === ($handle = fopen($usync_file, 'a+'))) {
          drupal_set_message($msg_prefix . 'cannot open existing file ' . $usync_path . ', dumping file in cli', 'error');
          drush_print($content);
          continue;
        }
        fwrite($handle, "\n\n# Generated\n");
        $exists = true;
      } else {
        // Else just create a new file.
        drush_print($msg_prefix . 'generating ' . $usync_file);
        if (false === ($handle = fopen($usync_file, 'w+'))) {
          drupal_set_message($msg_prefix . 'cannot create file ' . $usync_path . ', dumping file in cli', 'error');
          drush_print($content);
          continue;
        }
      }

      if (!fwrite($handle, $content)) {
        drupal_set_message($msg_prefix . 'write fail in ' . $usync_path . ', dumping file in cli', 'error');
        drush_print($content);
      }
      fclose($handle);

      // Attempt to update the module info file.
      if (!$exists) {
        if (false === ($handle = fopen($module_path . '/' . $module . '.info', 'a+'))) {
          drupal_set_message($msg_prefix . 'error while opening info file', 'error');
          continue;
        }
        if (!fwrite($handle, "usync[] = usync-partial/node-" . $type . ".yml\n")) {
          drupal_set_message($msg_prefix . 'error while writing info file', 'error');
        }
        fclose($handle);
        drush_print($msg_prefix . 'wrote info file');
      }
    }
  }
}

/**
 * Converts strongarm features files to usync yaml files.
 */
function drush_usync_strongarm_to_usync() {

  foreach (module_list() as $module) {

    $module_path  = drupal_get_path('module', $module);
    $target_file  = $module_path . '/' . $module . '.strongarm.inc';
    $variables    = [];

    if (file_exists($target_file)) {
      include_once $target_file;
      drush_print($module . ": found file " . $target_file);
    }

    $function = $module . '_strongarm';
    if (function_exists($function)) {
      $values = $function();

      if (!empty($values)) {
        foreach ($values as $key => $value) {
          $variables[$key] = $value->value;
        }
      }
    }

    if (!empty($variables)) {
      $usync_path = $module_path . '/usync';
      $usync_file = $module_path . '/usync/variable.yml';

      // In case file could not be generated, we will need the content to
      // dumped into the stdout, no matter what.
      $content = Yaml::dump($variables, 32, 2);

      // Attempt to create path.
      if (!is_dir($usync_path)) {
        if (!mkdir($usync_path)) {
          drupal_set_message($module . ': cannot create directory ' . $usync_path . ', dumping file in cli', 'error');
          drush_print($content);
          break;
        }
      }

      $exists = false;

      if (file_exists($usync_file)) {
        // If file already exists, append what you can inside.
        drush_print($module . ': using existing ' . $usync_file);
        if (false === ($handle = fopen($usync_file, 'a+'))) {
          drupal_set_message($module . ': cannot open existing file ' . $usync_path . ', dumping file in cli', 'error');
          drush_print($content);
          break;
        }
        fwrite($handle, "\n\n# Generated from Strongarm\n");
        $exists = true;
      } else {
        // Else just create a new file.
        drush_print($module . ': generating ' . $usync_file);
        if (false === ($handle = fopen($usync_file, 'w+'))) {
          drupal_set_message($module . ': cannot create file ' . $usync_path . ', dumping file in cli', 'error');
          drush_print($content);
          break;
        }
      }

      if (!fwrite($handle, $content)) {
        drupal_set_message($module . ': write fail in ' . $usync_path . ', dumping file in cli', 'error');
        drush_print($content);
      }
      fclose($handle);

      // Attempt to update the module info file.
      if (!$exists) {
        if (false === ($handle = fopen($module_path . '/' . $module . '.info', 'a+'))) {
          drupal_set_message($module . ': error while opening info file', 'error');
          break;
        }
        if (!fwrite($handle, "usync[] = usync-partial/\n")) {
          drupal_set_message($module . ': error while writing info file', 'error');
        }
        fclose($handle);
        drush_print($module . ': wrote info file');
      }
    }
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
