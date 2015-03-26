<?php

namespace USync\AST\Processing; 

use USync\AST\Node;
use USync\AST\ValueNode;
use USync\Context;

/**
 * Marks dirty nodes as such.
 */
class DrupalAttributesProcessor implements ProcessorInterface
{
    public function execute(Node $node, Context $context)
    {
        foreach (array('dirty', 'merge') as $key) {
            if ($node->hasChild($key)) {
                $dirty = $node->getChild($key);
                if ($dirty instanceof ValueNode) {
                    $node->setAttribute($key, (bool)$dirty->getValue());
                    $node->removeChild($key);
                }
            }
        }
    }
}
