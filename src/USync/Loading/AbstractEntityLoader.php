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

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'entity_' . $this->entityType;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(NodeInterface $node, Context $context)
    {
        if ($node instanceof EntityNode) {
            $info = entity_get_info($node->getEntityType());

            return !empty($info) && !empty($info['bundles'][$node->getName()]);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function canProcess(NodeInterface $node)
    {
        return $node instanceof EntityNode;
    }
}
