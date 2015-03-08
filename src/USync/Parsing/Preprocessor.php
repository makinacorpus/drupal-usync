<?php

namespace USync\Parsing;

use USync\AbstractContextAware;
use USync\AST\Node;

/**
 * This is not a real parser, this will just browse the data tree and execute
 * pre-requisites such as the inherit statements and stuff.
 */
class Preprocessor extends AbstractContextAware
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

    /**
     * Populate every entry using the inherit keyword of the top level items
     *
     * @param \USync\AST\Node $node
     */
    protected function populateInheritance(Node $node)
    {
        $sorted = array();
        $orphans = array();

        foreach ($node->getChildren() as $key => $child) {

            if ($child->hasChild('inherit')) {
                $parent = $child->getChild('inherit')->getValue();

                if (!is_string($parent)) {
                    $this->getContext()->logCritical(sprintf("%s: malformed inherit directive", $key));
                }
                if (!$node->hasChild($parent)) {
                    $this->getContext()->logCritical(sprintf("%s: cannot inherit from non existing: %s", $key, $parent));
                }
                if ($key === $parent) {
                    $this->getContext()->logCritical(sprintf("%s: cannot inherit from itself", $key));
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
                $this->getContext()->logCritical(sprintf("Circular dependency detected"));
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

    function execute(Node $node)
    {
        foreach ($node->getChildren() as $child) {
            if (!$child->isTerminal()) {
                $this->execute($child);
                $this->populateInheritance($child);
            }
        }
    }
}
