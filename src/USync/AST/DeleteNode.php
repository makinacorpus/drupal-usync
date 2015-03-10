<?php

namespace USync\AST;

class DeleteNode extends Node
{
    public function getValue()
    {
        return 'delete';
    }

    public function isTerminal()
    {
        return true;
    }
}
