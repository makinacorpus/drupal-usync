<?php

namespace USync\AST\Processing; 

use USync\AST\Node;
use USync\AST\ValueNode;
use USync\Context;

/**
 * Marks dirty nodes as such.
 */
class DirtyProcessor implements ProcessorInterface
{
    public function execute(Node $node, Context $context)
    {
        if ($node->hasChild('dirty')) {
            $dirty = $node->getChild('dirty');
            if ($dirty instanceof ValueNode) {
                $node->setAttribute('dirty', (bool)$dirty->getValue());
                $node->removeChild('dirty');
            }
        }
    }
}
