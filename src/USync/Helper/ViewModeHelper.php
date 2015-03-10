<?php

namespace USync\Helper;

use USync\AST\Path;
use USync\Context;

class ViewModeHelper extends AbstractHelper
{
    /**
     * Get instance identifiers from path
     *
     * @param string $path
     *
     * @return string
     *   Entity type, view mode name.
     */
    protected function getInstanceIdFromPath($path)
    {
        $parts = array_reverse(explode(Path::SEP, $path));

        $viewMode = array_shift($parts);
        $bundle = array_shift($parts);
        $entityType = array_shift($parts);

        return array($entityType, $bundle, $viewMode);
    }

    public function getType()
    {
        return 'view_mode';
    }

    public function exists($path, Context $context)
    {
        list($entityType, $bundle, $name) = $this->getInstanceIdFromPath($path);
        $info = entity_get_info($entityType);

        return isset($info['view modes'][$name]);
    }

    public function fillDefaults($path, array $object, Context $context)
    {
        throw new \Exception("Not implemented");
    }

    protected function getFieldDefault($name, $entityType, $bundle, $fieldName, Context $context)
    {
        $default = array(
            'type' => 'hidden',
            'label' => 'hidden',
        );

        if (!$instance = field_info_instance($entityType, $fieldName, $bundle)) {
            // @todo Should we warn? This is surely an extra field.
            return $default; 
        }
        if (!$field = field_info_field($fieldName)) {
            // @todo Should we warn? This is surely an extra field.
            return $default;
        }

        $formatter = null;
        if (!empty($field['default_formatter'])) {
            $formatter = $field['default_formatter'];
            if (!field_info_formatter_types($formatter)) {
                $context->logWarning(sprintf("%s defines non existing default formatter: %s", $fieldName, $formatter));
                $formatter = null;
            }
        }
        if ($formatter) {
            $default = drupal_array_merge_deep(
                $default,
                array('settings' => field_info_formatter_settings($formatter))
            );
        }

        // Deal with instance level display
        if (isset($instance['display'][$name])) {
            $default = drupal_array_merge_deep(
                $default,
                $instance['display'][$name]
            );
        }

        return $default;
    }

    public function deleteExistingObject($path, Context $context)
    {
        // @todo Can we really delete a view mode?
        // @todo Ensure we are the source or override it, at the very least
        // @todo Does it really makes sense?
        throw new \Exception("Not implemented");
    }

    public function getExistingObject($path, Context $context)
    {
        list($entityType, $bundle, $name) = $this->getInstanceIdFromPath($path);
        $info = entity_get_info($entityType);

        return $info['view modes'][$name];
    }

    public function rename($path, $newpath, $force = false, Context $context)
    {
        // @todo Can we really rename a view mode?
        // @todo Ensure we are the source or override it, at the very least
        // @todo Does it really makes sense?
        throw new \Exception("Not implemented");
    }

    public function synchronize($path, array $object, Context $context)
    {
        list($entityType, $bundle, $name) = $this->getInstanceIdFromPath($path);

        // First populate the variable that will be used during the
        // hook_entity_info_alter() call to populate the view modes
        $viewModes = variable_get(USYNC_VAR_VIEW_MODE, array());
        $data = array('label' => $name, 'custom settings' => true);
        $viewModes[$entityType]['view'][$name] = $data;
        variable_set(USYNC_VAR_VIEW_MODE, $viewModes);

        // First grab a list of everything that can be displayed in view
        // modes with both extra fields and real fields
        $instances = field_info_instances($entityType, $bundle);
        $extra = field_info_extra_fields($entityType, $bundle, 'display');

        $weight = 0;
        $displayExtra = array();
        $displayField = array();

        // Then deal with fields and such
        foreach ($object as $propertyName => $formatter) {
            if (isset($instances[$propertyName])) {

                // We are working with a field
                if (!is_array($formatter)) {
                    if (null === $formatter || 'delete' === $formatter) {
                        // Force removal - do nothing will be done below
                        continue;
                    }
                    if ($formatter !== true || $formatter !== 'default') {
                        $context->logWarning(sprintf("%s extra field display can only be 'delete', ~, 'default' or true", $propertyName));
                    }
                    // Fallback.
                    $formatter = array();
                }

                // Merge default and save
                $displayField[$propertyName] = drupal_array_merge_deep(
                    $this->getFieldDefault($name, $entityType, $bundle, $propertyName, $context),
                    $formatter,
                    array('weight' => $weight++)
                );

            } else if (isset($extra[$propertyName])) {

                // We are working with and extra field
                if (null === $formatter || 'delete' === $formatter) {
                    // Force removal - do nothing will be done below
                    continue;
                }
                if ($formatter !== true || $formatter !== 'default') {
                    $context->logWarning(sprintf("%s extra field display can only be 'delete', ~, 'default' or true", $propertyName));
                }

                // Merge default and save
                $displayExtra[$propertyName] = array('visible' => true, 'weight' => $weight++);

            } else {
                $context->logError(sprintf("%s property is nor a field nor an extra field", $propertyName));
            }
        }


        // Iterate over the fields and update each instance: we don't
        // need to do it with the $displayExtra property since it is
        // already the correctly formatted variable
        foreach ($displayField as $fieldName => $display) {
            $instances[$fieldName]['display'][$name] = $display;
        }

        // Remove non configured fields and extra fields from display
        foreach ($instances as $fieldName => $instance) {
            if (!isset($displayField[$fieldName])) {
                $instance['display'][$name] = array('type' => 'hidden');
                field_update_instance($instance);
            }
        }
        foreach (array_keys($extra) as $propertyName) {
            if (!isset($displayExtra[$propertyName])) {
                $displayExtra[$propertyName] = array('visible' => false, 'weight' => $weight++);
            }
        }

        $variable = 'field_bundle_settings_' . $entityType . '__' . $bundle;
        $existing = variable_get($variable, array());
        $existing['view_modes'][$name] = $data;
        $existing['extra_fields']['display'] = $displayExtra;
        variable_set($variable, $existing);
    }
}
