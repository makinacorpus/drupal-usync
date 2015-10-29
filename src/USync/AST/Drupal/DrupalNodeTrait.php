<?php

namespace USync\AST\Drupal;

trait DrupalNodeTrait
{
    /**
     * {inheritdoc}
     */
    public function isDirty()
    {
        return $this->hasAttribute('dirty') && $this->getAttribute('dirty');
    }

    /**
     * {inheritdoc}
     */
    public function isMerge()
    {
        return $this->hasAttribute('merge') && $this->getAttribute('merge');
    }

    /**
     * {inheritdoc}
     */
    public function shouldIgnore()
    {
        // This is probably a bit ugly, but it works fine.
        // Note that $self only serves the purpose of letting Eclipse/PDT doing
        // working autocomplete, type hinting on $this does not work.

        /** @var $self \USync\AST\NodeInterface */
        $self = $this;

        if ($self->hasAttribute('ignore') && $self->getAttribute('ignore')) {
            return true;
        }

        if ($self->isTerminal()) {
            return !empty($self->getValue()['ignore']);
        }

        return $self->hasChild('ignore') && $self->getChild('ignore')->getValue();
    }

    /**
     * {inheritdoc}
     */
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

    /**
     * {inheritdoc}
     */
    public function setDrupalIdentifier($identifier)
    {
        /** @var $self \USync\AST\NodeInterface */
        $self = $this;
        $self->setAttribute('drupal_identifier', $identifier);
    }

    /**
     * {inheritdoc}
     */
    public function getDrupalIdentifier()
    {
        /** @var $self \USync\AST\NodeInterface */
        $self = $this;
        if ($self->hasAttribute('drupal_identifier')) {
            return $self->getAttribute('drupal_identifier');
        }
    }
}
