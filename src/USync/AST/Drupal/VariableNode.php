<?php

namespace USync\AST\Drupal;

use USync\AST\ValueNode;

class VariableNode extends ValueNode implements DrupalNodeInterface
{
    use DrupalNodeTrait;
}
