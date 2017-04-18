<?php

namespace USync\Loading;

use USync\AST\Drupal\EntityNode;
use USync\AST\NodeInterface;
use USync\Context;

abstract class AbstractEntityLoader extends AbstractLoader
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
    public function __construct($entityType = null)
    {
        $this->entityType = $entityType;
    }

    public function getType()
    {
        return 'entity_' . $this->entityType;
    }

    public function exists(NodeInterface $node, Context $context)
    {
        $info = entity_get_info($this->entityType);

        return !empty($info) && !empty($info['bundles'][$node->getName()]);
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof EntityNode;
    }
}
