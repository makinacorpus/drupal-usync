<?php

namespace USync\AST;

class NullValueNode extends ValueNode
{
    public function getValue()
    {
        return null;
    }
}
