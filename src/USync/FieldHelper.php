<?php

namespace USync;

class FieldHelper extends AbstractHelper
{
    /**
     * @var \USync\FieldInstanceHelper
     */
    protected $instanceHelper;

    /**
     * Default constructor
     *
     * @param \USync\FieldInstanceHelper $instanceHelper
     */
    public function __construct(FieldInstanceHelper $instanceHelper)
    {
        parent::__construct('field');

        $this->instanceHelper = $instanceHelper;
    }

    /**
     * Get instance helper
     *
     * @return \USync\FieldInstanceHelper
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

    public function getFieldInfo($name)
    {
        return field_info_field($name);
    }

    public function delete($name)
    {
        $field = $this->getFieldInfo($name);

        if (!$field) {
            $this->log(sprintf("%s does not exists", $name), E_USER_WARNING);
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

    protected function sync($name, array $object)
    {
        if (!isset($object['type'])) {
            $this->logCritical(sprintf("%s has no type", $name));
        }

        $type = $object['type'];
        $typeInfo = field_info_field_types($type);

        if (empty($typeInfo)) {
            $this->logCritical(sprintf("%s: type %s does not exist", $name, $type));
        }

        $existing = $this->getFieldInfo($name);
        $object['field_name'] = $name;
        if (empty($object['cardinality'])) {
            $object['cardinality'] = 1;
        }

        if ($existing) {
            $this->log(sprintf("%s: field exists", $name));

            $doDelete = false;
            $eType = $existing['type'];

            // Ensure the cardinality change if any is safe to proceed with
            $cardinality = $object['cardinality'] - $existing['cardinality'];
            if (0 !== $cardinality) {
                if (0 < $cardinality) {
                    $this->log(sprintf("%s: safe cardinality change", $name));
                } else {
                    // @todo Ensure there is data we can save in field
                    if (false) {
                        $this->log(sprintf("%s: safe cardinality change due to data shape", $name));
                    } else {
                        $this->logDataloss(sprintf("%s: unsafe cardinality change", $name));
                    }
                }
            }

            if ($type !== $eType) {

                $doDelete = true;
                $instances = $this->getInstances($name);

                if (empty($instances)) {
                    $this->logWarning(sprintf("%s: type change (%s -> %s): no instances", $name, $type, $eType));
                } else {
                  // @todo Ensure there is data if there is instances
                  if (false) {
                      $this->logWarning(sprintf("%s: type change (%s -> %s): existing instances are empty", $name, $type, $eType));
                  } else {
                        // @todo Safe should ensure schema is the same
                        if (false) {
                            $this->logWarning(sprintf("%s: type change (%s -> %s): field schema is the same", $name, $type, $eType));
                        } else {
                            $this->logDataloss(sprintf("%s: type change (%s -> %s): data loss detected - replace denied", $name, $type, $eType));
                        }
                    }
                }
            }

            if (!empty($object['keep'])) {
                if ('all' === $object['keep']) {
                    drupal_array_merge_deep($existing, $object);
                } else if (is_array($object['keep'])) {
                    foreach ($object['keep'] as $key) {
                        if (array_key_exists($key, $existing)) {
                            $object[$key] = $existing[$key];
                        }
                    }
                } else {
                    $this->logError(sprintf("%s malformed 'keep' property, must be 'all' or an array of string property names", $name));
                }
            }
            if (!empty($object['drop'])) {
                if (is_array($object['drop'])) {
                    foreach ($object['drop'] as $key) {
                        if (isset($object[$key])) {
                            unset($object[$key]);
                        }
                    }
                } else {
                    $this->logError(sprintf("%s malformed 'drop' property, must be an array of string property names", $name));
                }
            }

            unset($object['keep'], $object['drop']);

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
