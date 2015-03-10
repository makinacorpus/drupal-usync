<?php

namespace USync\AST;

class DefaultNode extends Node
{
    public function getValue()
    {
        return 'default';
    }

    public function isTerminal()
    {
        return true;
    }
}
