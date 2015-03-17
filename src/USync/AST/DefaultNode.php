<?php

namespace USync\AST;

class DefaultNode extends ValueNode
{
    public function getValue()
    {
        return 'default';
    }
}
