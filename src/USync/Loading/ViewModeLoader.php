<?php

namespace USync\Loading;

use USync\AST\Drupal\ViewNode;
use USync\AST\NodeInterface;
use USync\AST\Path;
use USync\Context;

class ViewModeLoader extends AbstractLoader
{
    public function getType()
    {
        return 'view_mode';
    }

    public function exists(NodeInterface $node, Context $context)
    {
        /* @var $node ViewNode */
        $entityType = $node->getEntityType();
        $name       = $node->getName();
        $info       = entity_get_info($entityType);

        return isset($info['view modes'][$name]);
    }

    protected function getFieldDefault(NodeInterface $node, $entityType, $bundle, $fieldName, Context $context)
    {
        /* @var $node ViewNode */
        $entityType = $node->getEntityType();
        $bundle     = $node->getBundle();

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
            $context->logError("%s: %s field type does not exist", $node->getPath(), $field['types']);
            return $default;
        }

        $formatter = null;
        if (!empty($field['default_formatter'])) {
            $formatter = $field['default_formatter'];
            if (!field_info_formatter_types($formatter)) {
                $context->logWarning(sprintf("%s: field %s defines non existing default formatter: %s", $node->getPath(), $fieldName, $formatter));
                $formatter = null;
            }
        }

        if ($formatter) {
            $default = array(
                'type' => $formatter,
                'label' => 'hidden',
                'settings' => field_info_formatter_settings($formatter),
            );
        }

        return $default;
    }

    public function getDependencies(NodeInterface $node, Context $context)
    {
        return [];
    }

    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        throw new \Exception("Not implemented");
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        // @todo Can we really delete a view mode?
        // @todo Ensure we are the source or override it, at the very least
        // @todo Does it really makes sense?
        throw new \Exception("Not implemented");
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        /* @var $node ViewNode */
        $entityType = $node->getEntityType();
        $name       = $node->getName();
        $info       = entity_get_info($entityType);

        return $info['view modes'][$name];
    }

    public function rename(NodeInterface $node, $newpath, Context $context, $force = false, $dirtyAllowed = false)
    {
        // @todo Can we really rename a view mode?
        // @todo Ensure we are the source or override it, at the very least
        // @todo Does it really makes sense?
        throw new \Exception("Not implemented");
    }

    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node ViewNode */
        $entityType = $node->getEntityType();
        $bundle     = $node->getBundle();
        $name       = $node->getName();

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
        foreach ($node->getValue() as $propertyName => $formatter) {

            if (isset($instances[$propertyName])) {

                $display = array();

                // We are working with a field
                if (!is_array($formatter)) {
                    if (true === $formatter || 'default' === $formatter) {
                        $formatter = array();
                    } else if (false === $formatter || null === $formatter || 'delete' === $formatter) {
                        continue;
                    } else if (!is_string($formatter)) {
                        $context->logWarning(sprintf("%s: %s invalid value for formatter", $node->getPath(), $propertyName));
                        $formatter = array();
                    } else {
                        $display['type'] = $formatter;
                    }
                } else {
                    $display = $formatter;
                }

                // Merge default and save
                $displayField[$propertyName] = drupal_array_merge_deep(
                    $this->getFieldDefault($node, $entityType, $bundle, $propertyName, $context),
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
                        $context->logWarning(sprintf("%s: %s extra fields can only be delete or default", $node->getPath(), $propertyName));
                    }
                }

                // Merge default and save
                $displayExtra[$propertyName] = array('visible' => true, 'weight' => $weight++);

            } else {
                $context->logError(sprintf("%s: %s property is nor a field nor an extra field", $node->getPath(), $propertyName));
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
            if ($dirtyAllowed) {
                $data = $instance;
                unset( // From _field_write_instance()
                    $data['id'],
                    $data['field_id'],
                    $data['field_name'],
                    $data['entity_type'],
                    $data['bundle'],
                    $data['deleted']
                );
                db_update('field_config_instance')
                    ->condition('id', $instance['id'])
                    ->fields(array('data' => serialize($data)))
                    ->execute();
            } else {
              field_update_instance($instance);
            }
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
        if ($dirtyAllowed) {
            // Hopefully nothing about display is really cached into the
            // internal field cache class, except the raw display array
            // into each instance, but nothing will use that except this
            // specific view mode implementation, we are going to delay
            // a few cache clear calls at the very end of the processing.
            // From field_bundle_settings().
          variable_set('field_bundle_settings_' . $entityType . '__' . $bundle, $bundleSettings);
        } else {
            field_bundle_settings($entityType, $bundle, $bundleSettings);
        }

        if ($dirtyAllowed) {
            // From field_info_cache_clear().
            drupal_static_reset('field_view_mode_settings');
            entity_info_cache_clear();
        }
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof ViewNode;
    }
}
