<?php

namespace USync\Helper;

use USync\AST\Node;
use USync\AST\Path;

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
        $parts = explode(Path::SEP, $path);

        return array_reverse(array(
            array_shift($parts),
            array_shift($parts),
            array_shift($parts),
        ));
    }

    public function getType()
    {
        return 'field';
    }

    public function exists($path)
    {
        list($entityType, $bundle, $fieldName) = $this->getInstanceIdFromPath($path);

        if (field_info_instance($entityType, $fieldName, $bundle)) {
            return true;
        } else {
            return false;
        }
    }

    public function fillDefaults($path, array $object)
    {
        throw new \Exception("Not implemented");
    }

    public function getExistingObject($path)
    {
        list($entityType, $bundle, $fieldName) = $this->getInstanceIdFromPath($path);

        if ($existing = field_info_instance($entityType, $fieldName, $bundle)) {
            return $existing;
        }
        $this->context->logCritical(sprintf("%s does not exists", $path));
    }

    public function deleteExistingObject($path)
    {
        list($entityType, $bundle, $fieldName) = $this->getInstanceIdFromPath($path);
        $existing = field_info_instance($entityType, $fieldName, $bundle);

        if ($existing) {
            $this->context->logWarning(sprintf("%s does not exists", $path));
            return false;
        }

        field_delete_instance($existing);
    }

    public function synchronize($name, array $object)
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
