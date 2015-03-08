<?php

namespace USync\Helper;

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

    public function exists($path)
    {
        $name = $this->getLastPathSegment($path);
        if (field_info_field($name)) {
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
        $name = $this->getLastPathSegment($path);
        if ($info = field_info_field($name)) {
            return $info;
        }
        $this->getContext()->logCritical(sprintf("%s does not exist", $path));
    }

    public function deleteExistingObject($path)
    {
        $name = $this->getLastPathSegment($path);
        $field = $this->getExistingObject($name);

        if (!$field) {
            $this->getContext()->log(sprintf("%s does not exists", $name), E_USER_WARNING);
            return false;
        }

        $nameList = array();
        foreach ($this->getInstances($name) as $instance) {
            $nameList[] = $this->instanceHelper->getInstanceName($instance['entity_type'], $instance['bundle'], $name);
        }
        if (!empty($nameList)) {
            $this->instanceHelper->deleteAll($nameList);
        }

        field_delete_field($name);
    }

    public function synchronize($path, array $object)
    {
        $context = $this->getContext();

        if (!isset($object['type'])) {
            $context->logCritical(sprintf("%s has no type", $path));
        }

        $name = $this->getLastPathSegment($path);
        $type = $object['type'];
        $typeInfo = field_info_field_types($type);

        if (empty($typeInfo)) {
            $context->logCritical(sprintf("%s: type %s does not exist", $name, $type));
        }

        if ($this->exists($path)) {
            $existing = $this->getExistingObject($path);
        } else {
            $existing = null;
        }

        $object['field_name'] = $name;
        if (empty($object['cardinality'])) {
            $object['cardinality'] = 1;
        }

        if ($existing) {
            $context->log(sprintf("%s: field exists", $name));

            $doDelete = false;
            $eType = $existing['type'];

            // Ensure the cardinality change if any is safe to proceed with
            $cardinality = $object['cardinality'] - $existing['cardinality'];
            if (0 !== $cardinality) {
                if (0 < $cardinality) {
                    $context->log(sprintf("%s: safe cardinality change", $name));
                } else {
                    // @todo Ensure there is data we can save in field
                    if (false) {
                        $context->log(sprintf("%s: safe cardinality change due to data shape", $name));
                    } else {
                        $context->logDataloss(sprintf("%s: unsafe cardinality change", $name));
                    }
                }
            }

            if ($type !== $eType) {

                $doDelete = true;
                $instances = $this->getInstances($name);

                if (empty($instances)) {
                    $context->logWarning(sprintf("%s: type change (%s -> %s): no instances", $name, $type, $eType));
                } else {
                  // @todo Ensure there is data if there is instances
                  if (false) {
                      $context->logWarning(sprintf("%s: type change (%s -> %s): existing instances are empty", $name, $type, $eType));
                  } else {
                        // @todo Safe should ensure schema is the same
                        if (false) {
                            $context->logWarning(sprintf("%s: type change (%s -> %s): field schema is the same", $name, $type, $eType));
                        } else {
                            $context->logDataloss(sprintf("%s: type change (%s -> %s): data loss detected - replace denied", $name, $type, $eType));
                        }
                    }
                }
            }

            if ($doDelete) {
                $this->delete($name);
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
