<?php

namespace USync\AST;

class ValueNode extends Node
{
    /**
     * @var mixed
     */
    protected $value;

    public function __construct($name, $value = null)
    {
        parent::__construct($name);

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
