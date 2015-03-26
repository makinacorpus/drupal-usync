<?php

namespace USync\AST\Drupal;

trait DrupalNodeTrait
{
    public function isDirty()
    {
        return $this->hasAttribute('dirty') && $this->getAttribute('dirty');
    }

    public function isMerge()
    {
        return $this->hasAttribute('merge') && $this->getAttribute('merge');
    }
}
