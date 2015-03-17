<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class ViewNode extends Node implements DrupalNodeInterface
{
    public function exists()
    {
        throw new \Exception("Not implemented yet");
    }

    public function getEntityType()
    {
        return $this->getProperty('type');
    }

    public function getBundle()
    {
        return $this->getProperty('bundle');
    }
}
