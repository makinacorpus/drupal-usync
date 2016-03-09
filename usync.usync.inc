<?php
/**
 * @file
 * Our hooks.
 */

/**
 * Implements hook_usync_path_map();
 */
function usync_usync_path_map() {
  return [
    'field.?name'                         => '\USync\AST\Drupal\FieldNode',
    'entity.?type.?bundle'                => '\USync\AST\Drupal\EntityNode',
    'entity.?type.?bundle.field.?name'    => '\USync\AST\Drupal\FieldInstanceNode',
    'security.filter.?name'               => '\USync\AST\Drupal\InputFilterNode',
    'menu.?name'                          => '\USync\AST\Drupal\MenuNode',
    'menu.?menu.item.%'                   => '\USync\AST\Drupal\MenuItemNode',
    'menu.?menu.item.%.item.%'            => '\USync\AST\Drupal\MenuItemNode',
    'menu.?menu.item.%.item.%.item.%'     => '\USync\AST\Drupal\MenuItemNode',
    'security.role.?name'                 => '\USync\AST\Drupal\RoleNode',
    'view.?type.?bundle.?name'            => '\USync\AST\Drupal\ViewNode',
    'variable.?name'                      => '\USync\AST\Drupal\VariableNode',
  ];
}
