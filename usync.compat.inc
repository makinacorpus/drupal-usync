<?php
/**
 * @file
 * Compatibility layer for existing modules.
 */

use USync\AST\Node;

/**
 * Implements hook_usync_update_COMPONENT_alter().
 */
function image_usync_update_field_instance_alter(array &$object, Node $node) {
  $field = field_info_field($object['field_name']);
  if ('image' === $field['type'] && empty($object['settings']['default_image'])) {
    $object['settings']['default_image'] = false;
  }
}
