<?php

namespace USync\AST\Processing; 

use USync\AST\Node;
use USync\Context;

class CallableProcessor implements ProcessorInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * Default constructor
     *
     * @param callable $callback
     *   Must implement the same interface than ProcessorInterface::execute()
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function execute(Node $node, Context $context)
    {
        return call_user_func($this->callback, $node, $context);
    }
}
