<?php

namespace USync\AST;

use USync\AST\Processing\CallableProcessor;
use USync\AST\Processing\ProcessorInterface;
use USync\Context;

/**
 * Visitor role is to proceed to a full graph traversal of the AST and
 * execute configured processors over it. Note that the processing will
 * always be bottom-top in order to ensure that each execute node is
 * fully populated. 
 */
class Visitor
{
    /**
     * @var 
     */
    protected $processors;

    /**
     * Add processor for the traversal
     *
     * @param callable|ProcessorInterface $processor
     */
    public function addProcessor($processor)
    {
        if ($processor instanceof ProcessorInterface) {
            $this->processors[] = $processor;
        } else if (is_callable($processor)) {
            $this->processors[] = new CallableProcessor($processor);
        } else {
            throw new \InvalidArgumentException("Processor is not callable or does not implement \USync\AST\Processing\ProcessorInterface");
        }
    }

    /**
     * Execute traversal of the graph using the given processors
     *
     * @param \USync\AST\Node $node
     */
    public function execute(Node $node, Context $context)
    {
        if (!$node->isTerminal()) {
            foreach ($node->getChildren() as $child) {
                $this->execute($child, $context);
            }
        }

        foreach ($this->processors as $processor) {
            $processor->execute($node, $context);
        }
    }
}
