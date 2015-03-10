<?php

namespace USync\AST;

class NullNode extends Node
{
    public function getValue()
    {
        return null;
    }

    public function isTerminal()
    {
        return true;
    }
}
