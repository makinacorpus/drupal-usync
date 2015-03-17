<?php

namespace USync\Helper;

use USync\AST\Drupal\EntityNode;
use USync\AST\Node;
use USync\Context;

abstract class AbstractEntityHelper extends AbstractHelper
{
    /**
     * @var string
     */
    protected $entityType;

    /**
     * Default constructor
     *
     * @param string $entityType
     */
    public function __construct($entityType)
    {
        $this->entityType = $entityType;
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

    public function canProcess(Node $node)
    {
        return $node instanceof EntityNode;
    }
}
