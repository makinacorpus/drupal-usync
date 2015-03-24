<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class EntityNode extends Node implements DrupalNodeInterface
{
    use DrupalNodeTrait;

    public function getEntityType()
    {
        return $this->getAttribute('type');
    }

    public function getBundle()
    {
        return $this->getAttribute('bundle');
    }
}
