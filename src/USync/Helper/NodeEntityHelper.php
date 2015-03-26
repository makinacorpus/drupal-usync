<?php

namespace USync\Helper;

use USync\AST\Drupal\EntityNode;
use USync\AST\NodeInterface;
use USync\Context;

class NodeEntityHelper extends AbstractEntityHelper
{
    public function __construct()
    {
        parent::__construct('node');
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        $bundle = $node->getName();
        $exists = (int)db_query("SELECT 1 FROM {node} WHERE type = :type", array(':type' => $bundle));

        if ($exists) {
            $context->logDataloss(sprintf("%s: node type has nodes", $node->getPath()));
        }

        node_type_delete($bundle);
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        if (!$this->exists($node, $context)) {
            $context->logCritical(sprintf("%s: node type does not exist", $node->getPath()));
        }

        return (array)node_type_load($node->getName());
    }

    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node EntityNode */
        $bundle = $node->getName();

        $object = $node->getValue();
        if (!is_array($object)) {
            $object = array();
        }

        if ($node->isMerge() && ($existing = $this->getExistingObject($node, $context))) {
            $info = $object + $existing;
        } else {
            $info = array(
                'type'        => $bundle,
                'base'        => 'node_content',
                'custom'      => false,
                'modified'    => false,
                //'locked'     => true,
            ) + $object + array(
                'has_title'   => true,
                'title_label' => t("Title"), // Fallback to default language
                'module'      => 'node',
                'orig_type'   => null,
                'locked'      => false,
            );
        }

        if (empty($info['name'])) {
            $context->logWarning(sprintf('%s: has no name', $node->getPath()));
            $info['name'] = $bundle;
        }

        node_type_save((object)$info);
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof EntityNode /* && 'node' === $node->getEntityType() */;
    }
}
