<?php

namespace USync\TreeBuilding\Compiler; 

use USync\AST\Node;
use USync\Context;

class CountPass implements PassInterface
{
    public function execute(Node $node, Context $context)
    {
        $context->incr('node');
    }
}
