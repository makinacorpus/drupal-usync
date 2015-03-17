<?php

namespace USync\AST;

class DeleteNode extends ValueNode
{
    public function getValue()
    {
        return 'delete';
    }
}
