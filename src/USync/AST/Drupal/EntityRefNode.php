<?php

namespace USync\AST\Drupal;

use USync\AST\RefNode;

class EntityRefNode extends RefNode implements DrupalNodeInterface
{
    public function exists()
    {
        throw new \Exception("Not implemented yet");
    }
}
