<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class FieldInstanceNode extends Node implements DrupalNodeInterface
{
    /**
     * Get associated field
     *
     * @return \USync\AST\Drupal\FieldNode
     */
    public function getField()
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Get bundle this instance is attached to
     *
     * @return string
     */
    public function getBundle()
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Get entity type this instance is attached to
     *
     * @return string
     */
    public function getEntityType()
    {
        throw new \Exception("Not implemented");
    }

    public function exists()
    {
        throw new \Exception("Not implemented");
    }
}
