<?php

namespace USync\TreeBuilding\Compiler;

use USync\AST\Node;
use USync\AST\Processing\ProcessorInterface;
use USync\Context;

/**
 * Inheriting from the ProcessInterface has no other mean than keeping the
 * API compatibility with the Visitor class
 */
interface PassInterface extends ProcessorInterface
{
    public function execute(Node $node, Context $context);
}
