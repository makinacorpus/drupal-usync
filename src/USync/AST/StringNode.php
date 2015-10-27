<?php

namespace USync\AST;

class StringNode extends ValueNode
{
    public function __construct($name, $value = null)
    {
        parent::__construct($name, (string)$value);
    }
}
