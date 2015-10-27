<?php

namespace USync\AST\Processing; 

use USync\AST\ExpressionNode;
use USync\AST\Node;
use USync\Context;

/**
 * Processes expressions.
 */
class ExpressionProcessor implements ProcessorInterface
{
    public function execute(Node $node, Context $context)
    {
        if ($node instanceof ExpressionNode) {
            // FIXME This is very disturbing to evaluate this way, but I do
            // not have much choices for now
            // @todo Later use Symfony Expression Language component instead
            $node->evaluateExpression();
        }
    }
}
