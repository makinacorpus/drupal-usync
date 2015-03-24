<?php

namespace USync\AST\Drupal;

use USync\AST\NodeInterface;

interface DrupalNodeInterface extends NodeInterface
{
    /**
     * Is dirty injection allowed
     *
     * Dirty mode will allow the helper to proceed to fast but potential
     * unsafe import operations, for example allowing not to run module
     * hooks and disallow some cache reset operations.
     *
     * @return boolean
     */
    public function isDirty();
}
