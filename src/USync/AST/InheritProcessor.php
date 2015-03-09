<?php

namespace USync\AST; 

use USync\Context;

class InheritProcessor implements ProcessorInterface
{
    /**
     * List of path patterns that can use inheritance
     *
     * @todo Use this
     */
    static protected $inheritable = array(
        'entity.%',
        'field.%',
        'image.%',
        'view.%.%',
        'view.%.%.%',
    );

    public function execute(Node $node, Context $context)
    {
        $sorted = array();
        $orphans = array();

        foreach ($node->getChildren() as $key => $child) {

            if ($child->hasChild('inherit')) {
                $parent = $child->getChild('inherit')->getValue();

                if (!is_string($parent)) {
                    $context->logCritical(sprintf("%s: malformed inherit directive", $key));
                }
                if (!$node->hasChild($parent)) {
                    $context->logCritical(sprintf("%s: cannot inherit from non existing: %s", $key, $parent));
                }
                if ($key === $parent) {
                    $context->logCritical(sprintf("%s: cannot inherit from itself", $key));
                }

                $orphans[$key] = $parent;
            } else {
                $sorted[$key] = array();
            }
        }

        while (!empty($orphans)) {
            $count = count($orphans);
            foreach ($orphans as $key => $parent) {
                if (isset($sorted[$parent])) {
                    $sorted[$parent][] = $key;
                    $sorted[$key] = array();
                    unset($orphans[$key]);
                }
            }
            if (count($orphans) === $count) {
                $context->logCritical(sprintf("Circular dependency detected"));
            }
        }

        foreach (array_filter($sorted) as $parent => $children) {
            foreach ($children as $name) {
                $node
                  ->getChild($name)
                  ->setBaseNode(
                      $node->getChild($parent)
                  );
            }
        }
    }
}
