<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class EntityNode extends Node implements DrupalNodeInterface
{
    public function exists()
    {
        throw new \Exception("Not implemented yet");
    }
}