<?php

namespace USync\AST\Drupal;

use USync\AST\NodeInterface;

interface DrupalNodeInterface extends NodeInterface
{
    public function exists();
}
