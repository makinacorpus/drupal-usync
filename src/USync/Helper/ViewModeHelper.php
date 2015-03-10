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
        list($entityType,, $name) = $this->getInstanceIdFromPath($path);
        $info = entity_get_info($entityType);

        return isset($info['view modes'][$name]);
    }

    public function fillDefaults($path, array $object, Context $context)
    {
        throw new \Exception("Not implemented");
    }

    protected function getFieldDefault($path, $entityType, $bundle, $fieldName, Context $context)
    {
        list($entityType, $bundle, $name) = $this->getInstanceIdFromPath($path);

        $default = array(
            'type'     => 'hidden',
            'label'    => 'hidden',
            'settings' => array(),
        );

        if (!$field = field_info_field($fieldName)) {
            // @todo Should we warn? This is surely an extra field.
            return $default;
        }
        if (!$field = field_info_field_types($field['type'])) {
            $context->logError("%s: %s field type does not exist", $path, $field['types']);
            return $default;
        }

        $formatter = null;
        if (!empty($field['default_formatter'])) {
            $formatter = $field['default_formatter'];
            if (!field_info_formatter_types($formatter)) {
                $context->logWarning(sprintf("%s: field %s defines non existing default formatter: %s", $path, $fieldName, $formatter));
                $formatter = null;
            }
        }

        if ($formatter) {
            $default = array(
                'type' => $formatter,
                'settings' => field_info_formatter_settings($formatter),
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
        list($entityType,, $name) = $this->getInstanceIdFromPath($path);
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
        $viewModes[$entityType][$name] = $name;
        variable_set(USYNC_VAR_VIEW_MODE, $viewModes);

        // First grab a list of everything that can be displayed in view
        // modes with both extra fields and real fields
        $instances = field_info_instances($entityType, $bundle);
        $bundleSettings = field_bundle_settings($entityType, $bundle);
        $extra = field_info_extra_fields($entityType, $bundle, 'display');

        $weight = 0;
        $displayExtra = array();
        $displayField = array();

        // Then deal with fields and such
        foreach ($object as $propertyName => $formatter) {

            if (isset($instances[$propertyName])) {

                $display = array();

                // We are working with a field
                if (!is_array($formatter)) {
                    if (true === $formatter || 'default' === $formatter) {
                        $formatter = array();
                    } else if (false === $formatter || null === $formatter || 'delete' === $formatter) {
                        continue;
                    } else if (!is_string($formatter)) {
                        $context->logWarning(sprintf("%s: %s invalid value for formatter", $path, $propertyName));
                        $formatter = array();
                    } else {
                        $display['type'] = $formatter;
                    }
                } else {
                    $display = $formatter;
                }

                // Merge default and save
                $displayField[$propertyName] = drupal_array_merge_deep(
                    $this->getFieldDefault($path, $entityType, $bundle, $propertyName, $context),
                    $display,
                    array('weight' => $weight++)
                );

            } else if (isset($extra[$propertyName])) {

                // We are working with and extra field
                if (!is_array($formatter)) {
                    if (true === $formatter || 'default' === $formatter) {
                        $formatter = array();
                    } else if (false === $formatter || null === $formatter || 'delete' === $formatter) {
                        continue;
                    } else {
                        $context->logWarning(sprintf("%s: %s extra fields can only be delete or default", $path, $propertyName));
                    }
                }

                // Merge default and save
                $displayExtra[$propertyName] = array('visible' => true, 'weight' => $weight++);

            } else {
                $context->logError(sprintf("%s: %s property is nor a field nor an extra field", $path, $propertyName));
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
            }
            field_update_instance($instance);
        }
        foreach (array_keys($extra) as $propertyName) {
            if (isset($displayExtra[$propertyName])) {
                $bundleSettings['extra_fields'][$propertyName] = $displayExtra[$propertyName];
            } else {
                $bundleSettings['extra_fields'][$propertyName] = array('visible' => false, 'weight' => $weight++);
            }
        }

        $bundleSettings['view_modes'][$name] = array('label' => $name, 'custom_settings' => true);
        $bundleSettings['extra_fields']['display'] = $displayExtra;
        field_bundle_settings($entityType, $bundle, $bundleSettings);
    }
}
