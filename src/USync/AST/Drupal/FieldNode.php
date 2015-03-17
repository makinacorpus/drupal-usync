<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class FieldNode extends Node implements DrupalNodeInterface
{
    /**
     * Get field name
     *
     * This is an alias to getName();
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->name;
    }

    public function exists()
    {
        throw new \Exception("Not implemented yet");
    }
}
