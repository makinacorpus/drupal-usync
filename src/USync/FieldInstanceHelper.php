<?php

namespace USync;

class FieldInstanceHelper extends AbstractHelper
{
    public function __construct()
    {
        parent::__construct('field_instance');
    }

    public function getInstanceName($entityType, $bundle, $fieldName)
    {
        return implode('.', array($entityType, $bundle, $fieldName));
    }

    public function delete($name)
    {
        list($entityType, $bundle, $fieldName) = explode('.', $name, 3);
        $existing = field_info_instance($entityType, $fieldName, $bundle);

        if ($existing) {
            $this->log(sprintf("%s does not exists", $name), E_USER_WARNING);
            return false;
        }

        field_delete_instance($existing);
    }

    protected function sync($name, array $object)
    {
        list($entityType, $bundle, $fieldName) = explode('.', $name, 3);
        $existing = field_info_instance($entityType, $fieldName, $bundle);

        $instance = array(
            'entity_type' => $entityType,
            'bundle'      => $bundle,
            'field_name'  => $fieldName,
        ) + $object;

        // Even thought this is not mandatory few modules such as the 'image'
        // module will attempt to access this property, without carying about
        // the field_update_instance() method documentation
        if (empty($instance['settings'])) {
            $instance['settings'] = array();
        }

        if ($existing) {
            $this->alter(self::HOOK_UPDATE, $name, $instance);
            field_update_instance($instance);
        } else {
            $this->alter(self::HOOK_INSERT, $name, $instance);
            field_create_instance($instance);
        }
    }
}
