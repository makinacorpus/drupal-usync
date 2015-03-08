<?php

namespace USync\AST;

class ValueNode extends Node
{
    /**
     * @var mixed
     */
    protected $value;

    public function __construct($value, Node $parent = null)
    {
        parent::__construct($parent);

        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isTerminal()
    {
        return true;
    }
}
