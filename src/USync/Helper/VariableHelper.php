<?php

namespace USync\Helper;

use USync\AST\Node;
use USync\Context;

class VariableHelper extends AbstractHelper
{
    public function getType()
    {
        return 'variable';
    }

    public function exists(Node $node, Context $context)
    {
        return array_key_exists($node->getName(), $GLOBALS['conf']);
    }

    public function getExistingObject(Node $node, Context $context)
    {
        return variable_get($node->getName());
    }

    public function deleteExistingObject(Node $node, Context $context)
    {
        variable_del($node->getName());
    }

    public function synchronize(Node $node, Context $context)
    {
        variable_set($node->getName(), $node->getValue());
    }
}
