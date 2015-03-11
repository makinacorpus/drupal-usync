<?php

namespace USync\Helper;

use USync\AST\Path;
use USync\Context;

class FieldInstanceHelper extends AbstractHelper
{
    /**
     * Get instance identifiers from path
     *
     * @param string $path
     *
     * @return string
     *   Entity type, bundle and field name.
     */
    protected function getInstanceIdFromPath($path)
    {
        $parts = array_reverse(explode(Path::SEP, $path));

        $fieldName = array_shift($parts);
        array_shift($parts); // 'field'
        $bundle = array_shift($parts);
        $entityType = array_shift($parts);

        return array($entityType, $bundle, $fieldName);
    }

    public function getType()
    {
        return 'field_instance';
    }

    public function exists($path, Context $context)
    {
        list($entityType, $bundle, $fieldName) = $this->getInstanceIdFromPath($path);

        if (field_info_instance($entityType, $fieldName, $bundle)) {
            return true;
        } else {
            return false;
        }
    }

    public function fillDefaults($path, array $object, Context $context)
    {
        throw new \Exception("Not implemented");
    }

    public function getExistingObject($path, Context $context)
    {
        list($entityType, $bundle, $fieldName) = $this->getInstanceIdFromPath($path);

        if ($existing = field_info_instance($entityType, $fieldName, $bundle)) {
            return $existing;
        }
        $context->logCritical(sprintf("%s: does not exists", $path));
    }

    public function deleteExistingObject($path, Context $context)
    {
        list($entityType, $bundle, $fieldName) = $this->getInstanceIdFromPath($path);
        $existing = field_info_instance($entityType, $fieldName, $bundle);

        if (!$existing) {
            $context->logWarning(sprintf("%s: does not exists", $path));
            return false;
        }

        field_delete_instance($existing);
    }

    public function synchronize($path, array $object, Context $context)
    {
        list($entityType, $bundle, $fieldName) = $this->getInstanceIdFromPath($path);
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

        $instance = $default + $object + array(
            'display'     => array('default' => array('type' => 'hidden')),
        );

        // Even thought this is not mandatory few modules such as the 'image'
        // module will attempt to access this property, without carying about
        // the field_update_instance() method documentation
        if (empty($instance['settings'])) {
            $instance['settings'] = array();
        }

        if ($existing) {
            $this->alter(self::HOOK_UPDATE, $path, $instance);
            field_update_instance($instance);
        } else {
            $this->alter(self::HOOK_INSERT, $path, $instance);
            field_create_instance($instance);
        }
    }
}
 