<?php

namespace USync\AST;

class ValueNode extends Node
{
    /**
     * @var mixed
     */
    protected $value;

    public function __construct($name, Node $parent = null, $value)
    {
        parent::__construct($name, $parent);

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
