<?php

namespace USync\AST\Processing; 

use USync\AST\Node;
use USync\Context;

class StatProcessor implements ProcessorInterface, \Countable
{
    /**
     * Node count
     *
     * @var int
     */
    protected $count = 0;

    /**
     * Get node count
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Reset internals
     */
    public function reset()
    {
        $this->count = 0;
    }

    public function execute(Node $node, Context $context)
    {
        ++$this->count;
    }
}
