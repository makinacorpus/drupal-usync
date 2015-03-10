<?php

namespace USync\Helper;

use USync\Context;

class FieldHelper extends AbstractHelper
{
    /**
     * @var \USync\Helper\FieldInstanceHelper
     */
    protected $instanceHelper;

    /**
     * Default constructor
     *
     * @param \USync\Helper\FieldInstanceHelper $instanceHelper
     */
    public function __construct(FieldInstanceHelper $instanceHelper)
    {
        $this->instanceHelper = $instanceHelper;
    }

    /**
     * Get instance helper
     *
     * @return \USync\Helper\FieldInstanceHelper
     */
    public function getInstanceHelper()
    {
        return $this->instanceHelper;
    }

    /**
     * Get all instances of the given field
     *
     * @param string $name
     */
    protected function getInstances($name)
    {
        $ret = array();

        foreach (field_info_instances() as $bundles) {
            foreach ($bundles as $instance) {
                if ($instance['field_name'] === $name) {
                    $ret[] = $instance;
                }
            }
        }

        return $ret;
    }

    public function getType()
    {
        return 'field';
    }

    public function exists($path, Context $context)
    {
        $name = $this->getLastPathSegment($path);
        if (field_info_field($name)) {
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
        $name = $this->getLastPathSegment($path);
        if ($info = field_info_field($name)) {
            return $info;
        }
        $context->logCritical(sprintf("%s: does not exist", $path));
    }

    public function deleteExistingObject($path, Context $context)
    {
        $name = $this->getLastPathSegment($path);
        $field = $this->getExistingObject($path, $context);

        if (!$field) {
            $context->logWarning(sprintf("%s: does not exists", $path));
            return false;
        }

        $nameList = array();
        foreach ($this->getInstances($name) as $instance) {
            $nameList[] = $this->instanceHelper->getInstanceIdFromPath($instance['entity_type'], $instance['bundle'], $name);
        }
        if (!empty($nameList)) {
            foreach ($nameList as $name) {
                $this->instanceHelper->deleteExistingObject($name, $context);
            }
        }

        field_delete_field($name);
    }

    public function synchronize($path, array $object, Context $context)
    {
        if (!isset($object['type'])) {
            $context->logCritical(sprintf("%s: has no type", $path));
        }

        $name = $this->getLastPathSegment($path);
        $type = $object['type'];
        $typeInfo = field_info_field_types($type);

        if (empty($typeInfo)) {
            $context->logCritical(sprintf("%s: type %s does not exist", $path, $type));
        }

        if ($this->exists($path, $context)) {
            $existing = $this->getExistingObject($path, $context);
        } else {
            $existing = null;
        }

        if (array_key_exists('settings', $object) && !is_array($object['settings'])) {
            // @todo Log
            $object['settings'] = array();
        }

        $object['field_name'] = $name;
        if (empty($object['cardinality'])) {
            $object['cardinality'] = 1;
        }

        if ($existing) {
            $doDelete = false;
            $eType = $existing['type'];

            // Ensure the cardinality change if any is safe to proceed with
            $cardinality = $object['cardinality'] - $existing['cardinality'];
            if (0 !== $cardinality) {
                if (0 < $cardinality) {
                    $context->log(sprintf("%s: safe cardinality change", $path));
                } else {
                    // @todo Ensure there is data we can save in field
                    if (false) {
                        $context->log(sprintf("%s: safe cardinality change due to data shape", $path));
                    } else {
                        $context->logDataloss(sprintf("%s: unsafe cardinality change", $path));
                    }
                }
            }

            if ($type !== $eType) {

                $doDelete = true;
                $instances = $this->getInstances($name);

                if (empty($instances)) {
                    $context->logWarning(sprintf("%s: type change (%s -> %s): no instances", $path, $type, $eType));
                } else {
                  // @todo Ensure there is data if there is instances
                  if (false) {
                      $context->logWarning(sprintf("%s: type change (%s -> %s): existing instances are empty", $path, $type, $eType));
                  } else {
                        // @todo Safe should ensure schema is the same
                        if (false) {
                            $context->logWarning(sprintf("%s: type change (%s -> %s): field schema is the same", $path, $type, $eType));
                        } else {
                            $context->logDataloss(sprintf("%s: type change (%s -> %s): data loss detected", $path, $type, $eType));
                        }
                    }
                }
            }

            if ($doDelete) {
                $this->deleteExistingObject($name, $context);
                field_create_field($object);
                // @todo Recreate instances
            } else {
                field_update_field($object);
            }
        } else {
            field_create_field($object);
        }
    }
}
