<?php

namespace USync\AST;

use USync\AST\Processing\CallableProcessor;
use USync\AST\Processing\ProcessorInterface;
use USync\Context;

/**
 * Visitor role is to proceed to a full graph traversal of the AST and
 * execute configured processors over it. Note that the processing will
 * by default be bottom-top in order to ensure that each execute node is
 * fully populated. 
 */
class Visitor
{
    /**
     * Top bottom traversal
     */
    const TOP_BOTTOM = 1;

    /**
     * Bottom top traversal
     */
    const BOTTOM_TOP = 2;

    /**
     * @var 
     */
    protected $processors = [];

    /**
     * @var int
     */
    protected $way = self::BOTTOM_TOP;

    /**
     * Default constructor
     *
     * @param int $way
     */
    public function __construct($way = self::BOTTOM_TOP)
    {
        $this->way = (int)$way;
    }

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
        switch ($this->way) {

            case self::TOP_BOTTOM:
                return $this->executeTopBottom($node, $context);

            case self::BOTTOM_TOP:
            default:
                return $this->executeBottomTop($node, $context);
        }
    }

    /**
     * Execute processors on current node
     *
     * @param Node $node
     * @param Context $context
     */
    protected function executeProcessorsOnNode(Node $node, Context $context)
    {
        foreach ($this->processors as $processor) {
            $processor->execute($node, $context);
        }
    }

    /**
     * Execute traversal of the graph using the given processors
     *
     * @param \USync\AST\Node $node
     */
    protected function executeTopBottom(Node $node, Context $context)
    {
        $this->executeProcessorsOnNode($node, $context);

        if (!$node->isTerminal()) {
            foreach ($node->getChildren() as $child) {
                $this->executeTopBottom($child, $context);
            }
        }
    }

    /**
     * Execute traversal of the graph using the given processors
     *
     * @param \USync\AST\Node $node
     */
    protected function executeBottomTop(Node $node, Context $context)
    {
        if (!$node->isTerminal()) {
            foreach ($node->getChildren() as $child) {
                $this->executeBottomTop($child, $context);
            }
        }

        $this->executeProcessorsOnNode($node, $context);
    }
}
