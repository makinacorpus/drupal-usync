<?php

namespace USync\AST\Drupal;

use USync\AST\ValueNode;

class VariableNode extends ValueNode implements DrupalNodeInterface
{
    public function exists()
    {
        throw new \Exception("Not implemented yet");
    }
}
