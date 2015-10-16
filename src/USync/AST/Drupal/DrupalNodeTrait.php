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

    public function shouldDelete()
    {
        // This is probably a bit ugly, but it works fine.
        // Note that $self only serves the purpose of letting Eclipse/PDT doing
        // working autocomplete, type hinting on $this does not work.

        /** @var $self \USync\AST\NodeInterface */
        $self = $this;

        if ($self->hasAttribute('delete') && $self->getAttribute('delete')) {
            return true;
        }

        if ($self->isTerminal()) {
            return !empty($self->getValue()['delete']);
        }

        return $self->hasChild('delete') && $self->getChild('delete')->getValue();
    }
}
