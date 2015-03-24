<?php

namespace USync\AST\Drupal;

trait DrupalNodeTrait
{
    public function isDirty()
    {
        return $this->hasAttribute('dirty') && $this->getAttribute('dirty');
    }
}
