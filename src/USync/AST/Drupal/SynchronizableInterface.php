<?php

namespace USync\AST\Drupal;

use USync\AST\NodeInterface;

interface SynchronizableInterface extends DrupalNodeInterface
{
    public function exists();

    public function delete();

    public function isExternal();

    public function update();

    public function isMarkedForDeletion();

    public function isMarkedForMerge();
}
