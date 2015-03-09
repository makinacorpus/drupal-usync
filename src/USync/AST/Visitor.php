<?php

namespace USync\AST;

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
     * Add processor for the traversa
     * @param ProcessorInterface $processor
     */
    public function addProcessor(ProcessorInterface $processor)
    {
        $this->processors[] = $processor;
    }

    /**
     * Execute traversal of the graph using the given processors
     *
     * @param \USync\AST\Node $node
     */
    public function execute(Node $node, Context $context)
    {
        foreach ($node->getChildren() as $child) {
            if (!$child->isTerminal()) {

                $this->execute($child, $context);

                foreach ($this->processors as $processor) {
                    $processor->execute($node, $context);
                }
            }
        }
    }
}
