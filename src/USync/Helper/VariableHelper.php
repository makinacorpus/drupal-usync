<?php

namespace USync\Helper;

use USync\AST\Drupal\VariableNode;
use USync\AST\NodeInterface;
use USync\Context;

class VariableHelper extends AbstractHelper
{
    public function getType()
    {
        return 'variable';
    }

    public function exists(NodeInterface $node, Context $context)
    {
        return array_key_exists($node->getName(), $GLOBALS['conf']);
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        return variable_get($node->getName());
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        variable_del($node->getName());
    }

    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        variable_set($node->getName(), $node->getValue());
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof VariableNode;
    }
}
