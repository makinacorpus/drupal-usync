<?php

namespace USync\AST;

/**
 * Node that contains something that should be evaluated.
 */
class MacroReferenceNode extends StringNode
{
    public function getMacroPath()
    {
        return substr($this->getValue(), 1);
    }
}
