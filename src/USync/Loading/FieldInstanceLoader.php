<?php

namespace USync\Loading;

use USync\AST\Drupal\FieldInstanceNode;
use USync\AST\NodeInterface;
use USync\Context;

class FieldInstanceLoader extends AbstractLoader implements VerboseLoaderInterface
{
    public function getType()
    {
        return 'field_instance';
    }

    public function exists(NodeInterface $node, Context $context)
    {
        /* @var $node FieldInstanceNode */
        if (field_info_instance($node->getEntityType(), $node->getName(), $node->getBundle())) {
            return true;
        } else {
            return false;
        }
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        /* @var $node FieldInstanceNode */
        $existing = field_info_instance($node->getEntityType(), $node->getName(), $node->getBundle());

        if (!$existing) {
            $context->logCritical(sprintf("%s: does not exists", $node->getPath()));
        }

        return $existing;
    }

    public function getDependencies(NodeInterface $node, Context $context)
    {
        /* @var $node FieldInstanceNode */
        return ['field.' . $node->getFieldName()];
    }

    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        /* @var $node FieldInstanceNode */
        // throw new \Exception("Not implemented");
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node FieldInstanceNode */
        $existing = field_info_instance($node->getEntityType(), $node->getName(), $node->getBundle());

        if (!$existing) {
            $context->logWarning(sprintf("%s: does not exists", $node->getPath()));
            return false;
        }

        field_delete_instance($existing);
    }

    /**
     * Find a label for the given field
     */
    protected function findFieldLabel($fieldName)
    {
        $map = field_info_field_map();
        if (!empty($map[$fieldName]['bundles'])) {
            foreach ($map[$fieldName]['bundles'] as $type => $bundles) {
                foreach ($bundles as $bundle) {
                    $instance = field_info_instance($type, $fieldName, $bundle);
                    if (!empty($instance['label']) && $instance['label'] !== $fieldName) {
                        return $instance['label'];
                    }
                }
            }
        }
    }

    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node FieldInstanceNode */
        $entityType = $node->getEntityType();
        $bundle     = $node->getBundle();
        $fieldName  = $node->getFieldName();

        $existing = field_info_instance($entityType, $fieldName, $bundle);
        $field = field_info_field($fieldName);

        $default = array(
            'entity_type' => $entityType,
            'bundle'      => $bundle,
            'field_name'  => $fieldName,
        );

        $object = $node->getValue();
        if (!is_array($object)) {
            $object = array();
        }

        if (empty($object['label'])) {
            // Field data 'label' key is not part of the Drupal signature
            // but this module will inject it anyway and should be persisted
            // along the field 'data' key in database
            if (empty($field['label'])) {
                if ($label = $this->findFieldLabel($fieldName)) {
                    $default['label'] = $label;
                }
            } else {
                $default['label'] = $field['label'];
            }
        }

        // This is a forced default from this module: never display new
        // fields without being explicitely told to
        $instance = $default + $object + array(
            'display' => array('default' => array('type' => 'hidden')),
        );

        // Propagate defaults set at the field level
        if (!empty($field['instance'])) {
            foreach ($field['instance'] as $key => $value) {
                if (!isset($instance[$key])) {
                    $instance[$key] = $value;
                }
            }
        }
        // Even thought this is not mandatory few modules such as the 'image'
        // module will attempt to access this attribute, without carying about
        // the field_update_instance() method documentation
        if (!isset($instance['settings'])) {
            $instance['settings'] = array();
        }

        // Deal with widget
        if (!isset($instance['widget'])) {
            if (isset($field['widget'])) {
                $instance['widget'] = $field['widget'];
            }
        }

        // Dynamically determine weight using position relative to parent node
        if ($node->hasParent() && !isset($object['widget']['weight'])) {
            $instance['widget']['weight'] = $node->getParent()->getChildPosition($node);
        }

        if ($existing) {
            $this->alter(self::HOOK_UPDATE, $node, $instance);
            field_update_instance($instance);
        } else {
            $this->alter(self::HOOK_INSERT, $node, $instance);
            field_create_instance($instance);
        }
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof FieldInstanceNode;
    }

    /**
     * {inheritdoc}
     */
    public function getLoaderName()
    {
        return t("Field instance");
    }

    /**
     * {inheritdoc}
     */
    public function getLoaderDescription()
    {
        return t("Loads this node as a field instance.");
    }

    /**
     * {inheritdoc}
     */
    public function getNodeName(NodeInterface $node)
    {
        /* @var $node FieldInstanceNode */
        $object = $node->getValue();

        if (!empty($object['label'])) {
            return (string)$object['label'];
        }

        $field = field_info_field($node->getFieldName());
        if (isset($field['label'])) {
            return $field['label'];
        }
    }

    /**
     * {inheritdoc}
     */
    public function getNodeInformation(NodeInterface $node)
    {
        return null; // Sorry, not implemented
    }
}
