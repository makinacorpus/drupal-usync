<?php

namespace USync\Loading;

use USync\AST\Drupal\FieldNode;
use USync\AST\NodeInterface;
use USync\Context;

class FieldLoader extends AbstractLoader implements VerboseLoaderInterface
{
    /**
     * @var \USync\Loading\FieldInstanceLoader
     */
    protected $instanceLoader;

    /**
     * Default constructor
     *
     * @param \USync\Loading\FieldInstanceLoader $instanceLoader
     */
    public function __construct(FieldInstanceLoader $instanceLoader)
    {
        $this->instanceLoader = $instanceLoader;
    }

    /**
     * Get instance loader
     *
     * @return \USync\Loading\FieldInstanceLoader
     */
    public function getInstanceLoader()
    {
        return $this->instanceLoader;
    }

    /**
     * Get all instances of the given field
     *
     * @param string $name
     * @return array
     */
    protected function getInstances($name)
    {
        $ret = array();

        foreach (field_info_instances() as $bundles) {
            foreach ($bundles as $instance) {
                if (isset($instance['field_name']) && $instance['field_name'] === $name) {
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

    public function exists(NodeInterface $node, Context $context)
    {
        /* @var $node FieldNode */
        $name = $node->getName();

        if (field_info_field($name)) {
            return true;
        } else {
            return false;
        }
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        /* @var $node FieldNode */
        $name = $node->getName();
        if ($info = field_info_field($name)) {
            return $info;
        }
        $context->logCritical(sprintf("%s: does not exist", $node->getPath()));
    }

    protected function fixDrupalExistingArray(array &$array)
    {
        unset(
            $array['entity_types'],
            $array['foreign keys'],
            $array['indexes'],
            $array['storage'], // @todo Should exclude only defaults
            $array['id'],
            $array['field_name'],
            $array['active'],
            $array['deleted'],
            $array['translatable'],
            $array['columns'],
            $array['module'],
            $array['bundles']
        );

        foreach (['translatable', 'locked'] as $property) {
            if (empty($array[$property]) || !$array[$property]) {
                unset($array[$property]);
            }
        }
    }

    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        /* @var $node FieldNode */
        return parent::updateNodeFromExisting($node, $context);
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node FieldNode */
        $name = $node->getName();
        $field = $this->getExistingObject($node, $context);

        if (!$field) {
            $context->logWarning(sprintf("%s: does not exists", $node->getPath()));
            return false;
        }

        $nameList = array();
        foreach ($this->getInstances($name) as $instance) {
            $nameList[] = $this->instanceLoader->getInstanceIdFromNode($instance['entity_type'], $instance['bundle'], $name);
        }
        if (!empty($nameList)) {
            foreach ($nameList as $name) {
                $this->instanceLoader->deleteExistingObject($node, $context);
            }
        }

        field_delete_field($name);
    }

    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node FieldNode */
        $object = $node->getValue();
        if (!is_array($object)) {
            $object = array();
        }

        if (!isset($object['type'])) {
            $context->logCritical(sprintf("%s: has no type", $node->getPath()));
        }

        $name = $node->getName();
        $type = $object['type'];
        $typeInfo = field_info_field_types($type);

        if (empty($typeInfo)) {
            $context->logCritical(sprintf("%s: type %s does not exist", $node->getPath(), $type));
        }

        if ($this->exists($node, $context)) {
            $existing = $this->getExistingObject($node, $context);
        } else {
            $existing = null;
        }

        if (array_key_exists('settings', $object) && !is_array($object['settings'])) {
            $context->log(sprintf("%s: no settings provided, defaulting with empty array", $node->getPath()));
            $object['settings'] = array();
        }

        $object['field_name'] = $name;
        if (empty($object['cardinality'])) {
            $object['cardinality'] = 1;
        }

        // Consistency check, prior to do anything, ensure the database tables
        // are not already there, this happens, sometimes
        if (!$existing) {
            if (empty($object['storage']) || 'field_sql_storage' === $object['storage']['type']) {

                // Prevents warning on unspecified key
                if (!isset($object['deleted'])) {
                    $object['deleted'] = false;
                }

                $tables = [
                    _field_sql_storage_tablename($object),
                    _field_sql_storage_revision_tablename($object),
                ];

                foreach ($tables as $table) {
                    if (db_table_exists($table)) {
                        $context->logDataloss(sprintf("%s: %s: table already exists prior to creating field", $node->getPath(), $table));

                        // If code has not broken here, then go for deletion
                        db_drop_table($table);
                    }
                }
            }
        }

        if ($existing) {
            $doDelete = false;
            $eType = $existing['type'];

            // Ensure the cardinality change if any is safe to proceed with
            $cardinality = $object['cardinality'] - $existing['cardinality'];
            if (0 !== $cardinality) {
                if (0 < $cardinality || -1 == $object['cardinality']) {
                    $context->log(sprintf("%s: safe cardinality change", $node->getPath()));
                } else {
                    // @todo Ensure there is data we can save in field
                    if (false) {
                        $context->log(sprintf("%s: safe cardinality change due to data shape", $node->getPath()));
                    } else {
                        $context->logDataloss(sprintf("%s: unsafe cardinality change", $node->getPath()));
                    }
                }
            }

            if ($type !== $eType) {

                $doDelete = true;
                $instances = $this->getInstances($name);

                if (empty($instances)) {
                    $context->logWarning(sprintf("%s: type change (%s -> %s): no instances", $node->getPath(), $type, $eType));
                } else {
                  // @todo Ensure there is data if there is instances
                  if (false) {
                      $context->logWarning(sprintf("%s: type change (%s -> %s): existing instances are empty", $node->getPath(), $type, $eType));
                  } else {
                        // @todo Safe should ensure schema is the same
                        if (false) {
                            $context->logWarning(sprintf("%s: type change (%s -> %s): field schema is the same", $node->getPath(), $type, $eType));
                        } else {
                            $context->logDataloss(sprintf("%s: type change (%s -> %s): data loss detected", $node->getPath(), $type, $eType));
                        }
                    }
                }
            }

            if ($doDelete) {
                $this->deleteExistingObject($node, $context);
                field_create_field($object);
                // @todo Recreate instances
            } else {
                field_update_field($object);
            }
        } else {
            field_create_field($object);
        }
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof FieldNode;
    }

    /**
     * {inheritdoc}
     */
    public function getLoaderName()
    {
        return t("Field");
    }

    /**
     * {inheritdoc}
     */
    public function getLoaderDescription()
    {
        return t("Loads this node as a field.");
    }

    /**
     * {inheritdoc}
     */
    public function getNodeName(NodeInterface $node)
    {
        $object = $node->getValue();

        if (!empty($object['label'])) {
            return (string)$object['label'];
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
