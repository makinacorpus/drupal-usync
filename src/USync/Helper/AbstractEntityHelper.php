<?php

namespace USync\Helper;

use USync\AST\Node;
use USync\Context;

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

    public function getType()
    {
        return 'entity_' . $this->entityType;
    }

    public function exists(Node $node, Context $context)
    {
        $info = entity_get_info($this->entityType);

        return !empty($info) && !empty($info['bundles'][$node->getName()]);
    }
}
