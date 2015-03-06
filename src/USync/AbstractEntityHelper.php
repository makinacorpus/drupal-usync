<?php

namespace USync;

abstract class AbstractEntityHelper extends AbstractHelper
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var \USync\FieldHelper
     */
    protected $fieldHelper;

    /**
     * Default constructor
     *
     * @param \USync\FieldHelper $fieldHelper
     * @param string $type
     */
    public function __construct(FieldHelper $fieldHelper, $type)
    {
        parent::__construct('entity_' . $type);

        $this->type = $type;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * Process field instances
     *
     * @param array[] $objectList
     */
    protected function processFieldAll($bundle, array $objectList)
    {
        foreach ($objectList as $name => $object) {
            $this->processField($bundle, $name, $object);
        }
    }

    /**
     * Process a single field instance
     *
     * @param string $name
     * @param string|array $object
     */
    protected function processField($bundle, $name, $object)
    {
        if (is_string($object)) {
            $name   = $object;
            $object = array();
        }

        $instance = $this->fieldHelper->getInstanceHelper();

        $instance->sync(
            $instance->getInstanceName(
                $this->type,
                $bundle,
                $name
            ),
            $object
        );
    }

    /**
     * Really create or update the entity type
     *
     * @param string $name
     * @param array $object
     */
    abstract protected function doSync($name, array $object);

    public function delete($name)
    {
        $exists = (int)db_query("SELECT 1 FROM {node} WHERE type = :type", array(':type' => $name));

        if ($exists) {
            $this->logDataloss(sprintf("%s type has nodes - delete denied"));
            return false;
        }

        node_type_delete($name);
    }

    protected function sync($name, array $object)
    {
        $this->doSync($name, $object);

        if (!empty($object['field'])) {
            $this->processFieldAll($name, $object['field']);
        }
    }
}
