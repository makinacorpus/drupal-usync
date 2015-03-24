<?php

namespace USync\AST\Drupal;

use USync\AST\Node;

class FieldInstanceNode extends Node implements DrupalNodeInterface
{
    use DrupalNodeTrait;

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

    public function getEntityType()
    {
        return $this->getAttribute('type');
    }

    public function getBundle()
    {
        return $this->getAttribute('bundle');
    }
}
