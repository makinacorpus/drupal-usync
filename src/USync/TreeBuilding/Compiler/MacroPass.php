<?php

namespace USync\TreeBuilding\Compiler; 

use USync\AST\MacroReferenceNode;
use USync\AST\Node;
use USync\AST\Path;
use USync\Context;

/**
 * Replaces macro references with a clone of their target
 */
class MacroPass implements PassInterface
{
    public function execute(Node $node, Context $context)
    {
        if ($node instanceof MacroReferenceNode) {

            $path = $node->getMacroPath();

            // Provided that users might want some things hardcoded, everything
            // under the 'macro' root node will be considered as macros
            if (false === strpos($path, Path::SEP)) {
                $path = 'macro' . Path::SEP . $path;
            }

            $macro = (new Path($path))->find($context->getGraph());

            if (!$macro) {
                throw new CompilerException(sprintf("'%s': '%s' macro does not exist", $node->getPath(), $node->getValue()));
            }
            if (1 !== count($macro)) {
                throw new CompilerException(sprintf("'%s': '%s' multiple targets found", $node->getPath(), $node->getValue()));
            }

            $node
                ->getParent()
                ->replaceChild(
                    $node->getName(),
                    reset($macro)->duplicate($node->getName())
                )
            ;
        }
    }
}
