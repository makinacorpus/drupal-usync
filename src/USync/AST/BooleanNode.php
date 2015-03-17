<?php

namespace USync\AST;

class BooleanNode extends ValueNode
{
    public function __construct($name, $value = null)
    {
        parent::__construct($name, (bool)$value);
    }
}
