<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class FieldNode extends Node implements DrupalNodeInterface
{
    use DrupalNodeTrait;

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
}
