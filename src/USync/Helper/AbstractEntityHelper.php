<?php

namespace USync\Helper;

use USync\AST\Node;
use USync\AST\Path;

abstract class AbstractEntityHelper extends AbstractHelper
{
    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var \USync\Helper\FieldHelper
     */
    protected $fieldHelper;

    /**
     * Default constructor
     *
     * @param \USync\Helper\FieldHelper $fieldHelper
     * @param string $entityType
     */
    public function __construct(FieldHelper $fieldHelper, $entityType)
    {
        $this->entityType = $entityType;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * Process a single field instance
     *
     * @param string $name
     * @param string|array $object
     */
    protected function processFieldAll($path, array $object)
    {
        if (!empty($object['field'])) {
            foreach ($object['field'] as $key => $child) {
                if ('default' === $child) {
                    // @todo
                } else {
                    $this
                        ->getContext()
                        ->getRunner()
                        ->processObject(
                            $path . Path::SEP . $key,
                            $child,
                            $this->fieldHelper
                        );
                }
            }
        }
    }

    /**
     * Really create or update the entity type
     *
     * @param string $name
     * @param array $object
     */
    abstract protected function doSync($name, array $object);

    public function getType()
    {
        return 'entity_' . $this->entityType;
    }

    public function exists($path)
    {
        $bundle = $this->getLastPathSegment($path);
        $info = entity_get_info($this->entityType);

        return !empty($info) && !empty($info['bundles'][$bundle]);
    }

    public function synchronize($path, array $object)
    {
        $this->doSync($path, $object);
        $this->processFieldAll($path, $object);
    }
}
