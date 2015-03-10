<?php

namespace USync\AST;

class BooleanNode extends ValueNode
{
    public function __construct($name, Node $parent = null, $value)
    {
        parent::__construct($name, $parent, (bool)$value);
    }
}
