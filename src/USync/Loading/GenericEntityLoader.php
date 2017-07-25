<?php

namespace USync\Loading;

use USync\AST\NodeInterface;
use USync\Context;

class GenericEntityLoader extends AbstractEntityLoader
{
    /**
     * {@inheritDoc}
     */
    public function getExistingObject(NodeInterface $node, Context $context)
    {
        // Nothing to do here...
        return ['type' => $this->entityType];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        // Nothing to do here...
    }

    /**
     * {@inheritDoc}
     */
    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        // Nothing to do here...
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(NodeInterface $node, Context $context)
    {
        /* @var $node \USync\AST\Drupal\EntityNode */
        $order = [];

        /** @var \USync\AST\Drupal\EntityNode $node */
        $entityType = $node->getEntityType();
        $bundle = $node->getBundle();

        // First, fields
        $field = [];
        foreach (field_info_instances($entityType, $bundle) as $instance) {
            $field[] = 'entity.'.$entityType.'.'.$bundle.'.field.'.$instance['field_name'];
            $order[] = isset($instance['weight']) ? $instance['weight'] : 0;
        }

        array_multisort($order, $field);

        return $field;
    }

    /**
     * {@inheritDoc}
     */
    public function getExtractDependencies(NodeInterface $node, Context $context)
    {
        /* @var $node \USync\AST\Drupal\EntityNode */
        $ret = $this->getDependencies($node, $context);

        /** @var \USync\AST\Drupal\EntityNode $node */
        $entityType = $node->getEntityType();
        $bundle = $node->getBundle();

        // Let's go for view modes too
        $view = [];
        $view[] = 'view.'.$entityType.'.'.$bundle.'.default';
        foreach (entity_get_info($entityType)['view modes'] as $viewMode => $settings) {
            $view[] = 'view.'.$entityType.'.'.$bundle.'.'.$viewMode;
        }

        return array_merge($ret, $view);
    }

    /**
     * {@inheritDoc}
     */
    public function canProcess(NodeInterface $node)
    {
        $canProcess = parent::canProcess($node);

        if ($canProcess) {
            /** @var \USync\AST\Drupal\EntityNode $node */
            if (in_array($node->getEntityType(), ['node', 'vocabulary'])) {
                // 'node' and 'vocabulary' types have specific implementations
                return false;
            }
        }

        return $canProcess;
    }
}
