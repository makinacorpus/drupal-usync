<?php

namespace USync\AST;

class NullNode extends ValueNode
{
    public function getValue()
    {
        return null;
    }
}
