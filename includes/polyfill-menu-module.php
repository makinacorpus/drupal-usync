<?php
/**
 * @file
 * Those are some menu module functions we would need without the menu module.
 *
 * All those functions are pretty much a set of copy/pasted functions.
 */

/**
 * Failsafe.
 */
if (function_exists('menu_load') || module_exists('menu')) {
  return;
}

/**
 * Load the data for a single custom menu.
 *
 * @param $menu_name
 *   The unique name of a custom menu to load.
 * @return
 *   Array defining the custom menu, or FALSE if the menu doesn't exist.
 */
function menu_load($menu_name) {
  $all_menus = menu_load_all();
  return isset($all_menus[$menu_name]) ? $all_menus[$menu_name] : FALSE;
}

/**
 * Load all custom menu data.
 *
 * @return
 *   Array of custom menu data.
 */
function menu_load_all() {
  $custom_menus = &drupal_static(__FUNCTION__);
  if (!isset($custom_menus)) {
    if ($cached = cache_get('menu_custom', 'cache_menu')) {
      $custom_menus = $cached->data;
    }
    else {
      $custom_menus = db_query('SELECT * FROM {menu_custom}')->fetchAllAssoc('menu_name', PDO::FETCH_ASSOC);
      cache_set('menu_custom', $custom_menus, 'cache_menu');
    }
  }
  return $custom_menus;
}

/**
 * Save a custom menu.
 *
 * @param $menu
 *   An array representing a custom menu:
 *   - menu_name: The unique name of the custom menu (composed of lowercase
 *     letters, numbers, and hyphens).
 *   - title: The human readable menu title.
 *   - description: The custom menu description.
 *
 * Modules should always pass a fully populated $menu when saving a custom
 * menu, so other modules are able to output proper status or watchdog messages.
 *
 * @see menu_load()
 */
function menu_save($menu) {
  $status = db_merge('menu_custom')
    ->key(array('menu_name' => $menu['menu_name']))
    ->fields(array(
      'title' => $menu['title'],
      'description' => $menu['description'],
    ))
    ->execute();
  menu_cache_clear_all();

  switch ($status) {
    case SAVED_NEW:
      // Make sure the menu is present in the active menus variable so that its
      // items may appear in the menu active trail.
      // @see menu_set_active_menu_names()
      $active_menus = variable_get('menu_default_active_menus', array_keys(menu_get_menus()));
      if (!in_array($menu['menu_name'], $active_menus)) {
        $active_menus[] = $menu['menu_name'];
        variable_set('menu_default_active_menus', $active_menus);
      }

      module_invoke_all('menu_insert', $menu);
      break;

    case SAVED_UPDATED:
      module_invoke_all('menu_update', $menu);
      break;
  }
}

/**
 * Delete a custom menu and all contained links.
 *
 * Note that this function deletes all menu links in a custom menu. While menu
 * links derived from router paths may be restored by rebuilding the menu, all
 * customized and custom links will be irreversibly gone. Therefore, this
 * function should usually be called from a user interface (form submit) handler
 * only, which allows the user to confirm the action.
 *
 * @param $menu
 *   An array representing a custom menu:
 *   - menu_name: The unique name of the custom menu.
 *   - title: The human readable menu title.
 *   - description: The custom menu description.
 *
 * Modules should always pass a fully populated $menu when deleting a custom
 * menu, so other modules are able to output proper status or watchdog messages.
 *
 * @see menu_load()
 *
 * menu_delete_links() will take care of clearing the page cache. Other modules
 * should take care of their menu-related data by implementing
 * hook_menu_delete().
 */
function menu_delete($menu) {
  // Delete all links from the menu.
  menu_delete_links($menu['menu_name']);

  // Remove menu from active menus variable.
  $active_menus = variable_get('menu_default_active_menus', array_keys(menu_get_menus()));
  foreach ($active_menus as $i => $menu_name) {
    if ($menu['menu_name'] == $menu_name) {
      unset($active_menus[$i]);
      variable_set('menu_default_active_menus', $active_menus);
    }
  }

  // Delete the custom menu.
  db_delete('menu_custom')
    ->condition('menu_name', $menu['menu_name'])
    ->execute();

  menu_cache_clear_all();
  module_invoke_all('menu_delete', $menu);
}

/**
 * Return an associative array of the custom menus names.
 *
 * @param $all
 *   If FALSE return only user-added menus, or if TRUE also include
 *   the menus defined by the system.
 * @return
 *   An array with the machine-readable names as the keys, and human-readable
 *   titles as the values.
 */
function menu_get_menus($all = TRUE) {
  if ($custom_menus = menu_load_all()) {
    if (!$all) {
      $custom_menus = array_diff_key($custom_menus, menu_list_system_menus());
    }
    foreach ($custom_menus as $menu_name => $menu) {
      $custom_menus[$menu_name] = t($menu['title']);
    }
    asort($custom_menus);
  }
  return $custom_menus;
}

