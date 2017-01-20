<?php

namespace USync\Loading;

use USync\AST\Drupal\ViewNode;
use USync\AST\NodeInterface;
use USync\Context;
use USync\TreeBuilding\ArrayTreeBuilder;

class ViewModeLoader extends AbstractLoader
{
    const USYNC_ALL_KEYWORD = '__all__';

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

        return isset($info) && ('default' === $name || isset($info['view modes'][$name]));
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
        /* @var $node ViewNode */
        return [];

        // @todo add entity and fields
    }

    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        /* @var $node ViewNode */
        $data = $this->getExistingObject($node, $context);

        $builder = new ArrayTreeBuilder();
        foreach ($builder->parseWithoutRoot($data) as $child) {
            $node->addChild($child);
        }
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        $entityType = $node->getEntityType();
        $bundle     = $node->getBundle();
        $name       = $node->getName();

        // Nothing to do really, except removing the custom setting from the
        // field bundle settings variable, but just to be sure we are going to
        // drop everything we can from the field config itself firs

        $instances = field_info_instances($entityType, $bundle);
        $bundleSettings = field_bundle_settings($entityType, $bundle);

        foreach ($instances as $instance) {
            if (isset($instance['display'][$name])) {
                unset($instance['display'][$name]);
                field_update_instance($instance);
            }
        }

        $bundleSettings['view_modes'][$name] = ['label' => $name, 'custom_settings' => false];
        foreach (array_keys($bundleSettings['extra_fields']['display']) as $extraField) {
            unset($bundleSettings['extra_fields']['display'][$extraField][$name]);
        }
        field_bundle_settings($entityType, $bundle, $bundleSettings);
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        /* @var $node ViewNode */
        $entityType = $node->getEntityType();
        $bundle     = $node->getBundle();
        $name       = $node->getName();

        $instances = field_info_instances($entityType, $bundle);
        $extra = $this->getExtraFieldsDisplay($entityType, $bundle);

        $data = [];
        $order = [];

        // This one is not easy
        foreach ($instances as $fieldName => $instance) {
            if (isset($instance['display'][$name]) && 'hidden' !== $instance['display'][$name]['type']) {
                $item = $instance['display'][$name];
                unset($item['weight'], $item['module']);
                if ('hidden' === $item['label']) {
                    unset($item['label']);
                }
                if (empty($item['settings'])) {
                    unset($item['settings']);
                }
                $data[$fieldName] = $item;
                $order[] = $instance['display'][$name]['weight'];
            }
        }
        foreach ($extra as $extraName => $displays) {
            if (isset($displays['display'][$name]) && $displays['display'][$name]['visible']) {
                $data[$extraName] = true;
                $order[] = $displays['display'][$name]['weight'];
            }
        }

        array_multisort($order, $data);

        return $data;
    }

    public function rename(NodeInterface $node, $newpath, Context $context, $force = false, $dirtyAllowed = false)
    {
        /* @var $node ViewNode */
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
        $viewModes = variable_get(USYNC_VAR_VIEW_MODE, []);
        $viewModes[$entityType][$name] = $name;
        variable_set(USYNC_VAR_VIEW_MODE, $viewModes);

        // First grab a list of everything that can be displayed in view
        // modes with both extra fields and real fields
        $instances = field_info_instances($entityType, $bundle);
        $bundleSettings = field_bundle_settings($entityType, $bundle);
        $extra = $this->getExtraFieldsDisplay($entityType, $bundle);

        $weight = 0;
        $displayExtra = [];
        $displayField = [];

        // Then deal with fields and such
        foreach ($node->getValue() as $propertyName => $formatter) {

            if (self::USYNC_ALL_KEYWORD === $propertyName) {

                // We activate all instances with their default formatter
                foreach (array_keys($instances) as $field_name) {
                    $displayField[$field_name] = drupal_array_merge_deep(
                      $this->getFieldDefault($node, $entityType, $bundle, $field_name, $context),
                      ['weight' => $weight++]
                    );
                }

            } elseif (isset($instances[$propertyName])) {

                $display = array();

                // We are working with a field
                if (!is_array($formatter)) {
                    if (true === $formatter || 'default' === $formatter) {
                        $formatter = array();
                    } else if (false === $formatter || null === $formatter || 'delete' === $formatter) {
                        // We may have used '__all__' property , so better check it
                        if (isset($displayField[$propertyName])) {
                            unset($displayField[$propertyName]);
                        }
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
                $displayExtra[$propertyName] = ['visible' => true, 'weight' => $weight++];

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
                    ->fields(['data' => serialize($data)])
                    ->execute();
            } else {
              field_update_instance($instance);
            }
        }
        foreach (array_keys($extra) as $propertyName) {
            if (isset($displayExtra[$propertyName])) {
                $bundleSettings['extra_fields']['display'][$propertyName][$name] = $displayExtra[$propertyName];
            } else {
                $bundleSettings['extra_fields']['display'][$propertyName][$name] = ['visible' => false, 'weight' => $weight++];
            }
        }

        $bundleSettings['view_modes'][$name] = ['label' => $name, 'custom_settings' => true];

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
            // From field_update_instance()
            cache_clear_all('*', 'cache_field', true);
            // From field_info_cache_clear()
            drupal_static_reset('field_view_mode_settings');
            // We need to clear cache in order for later view modes to
            // load the right instance and prevent them for overriding
            // what we actually did here
            entity_info_cache_clear();
            _field_info_field_cache()->flush();
        }
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof ViewNode;
    }

    protected function getExtraFieldsDisplay($entityType, $bundle)
    {
        // Resets a webform's static cache to ensure to get the "webform" extra
        // field information. The webform features may have been enabled for the
        // current content type after the static cache has been built.
        drupal_static_reset('webform_node_types');
        return field_info_extra_fields($entityType, $bundle, 'display');
    }
}
