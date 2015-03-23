<?php

namespace USync\Helper;

use USync\AST\Drupal\FieldInstanceNode;
use USync\AST\NodeInterface;
use USync\AST\Path;
use USync\Context;

class FieldInstanceHelper extends AbstractHelper
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
        if (!empty($field['label'])) {
            $default['label'] = $field['label'];
        }

        $object = $node->getValue();
        if (!is_array($object)) {
            $object = array();
        }

        $instance = $default + $object + array(
            'display'     => array('default' => array('type' => 'hidden')),
        );

        // Even thought this is not mandatory few modules such as the 'image'
        // module will attempt to access this attribute, without carying about
        // the field_update_instance() method documentation
        if (empty($instance['settings'])) {
            $instance['settings'] = array();
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
}
 