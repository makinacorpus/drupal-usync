<?php

namespace USync\TreeBuilding;

use USync\AST\Node;
use USync\AST\NodeInterface;
use USync\AST\Path;
use USync\Context;

/**
 * Create an AST from the given extractors and path
 */
class ExtractingTreeBuilder
{
    /**
     * @var string[]
     */
    protected $pathMap;

    /**
     * Default constructor
     */
    public function __construct()
    {
        // Allow other modules to add stuff in there
        $this->pathMap = module_invoke_all('usync_path_map');

        drupal_alter('usync_path_map', $this->pathMap);

        foreach ($this->pathMap as $pattern => $class) {
            if (!class_exists($class)) {
                unset($this->pathMap[$pattern]);
                trigger_error(sprintf("Class '%s' does not exist", $class), E_USER_ERROR);
            }
        }
    }

    /**
     * Build the missing items in AST ommiting the last segment
     *
     * @param \USync\AST\NodeInterface $ast
     * @param \USync\AST\Path $path
     *
     * @return \USync\AST\NodeInterface
     *   The last parent
     */
    protected function fixTree(NodeInterface $ast, $path)
    {
        $segments = $path->getSegments();
        array_pop($segments);

        $node = $ast;

        foreach ($segments as $key) {
            if ($node->hasChild($key)) {
                $node = $node->getChild($key);
            } else {
                $child = new Node($key);
                $node->addChild($child);
                $node = $child;
            }
        }

        return $node;
    }

    /**
     * Parse data from structured array
     *
     * @param string|string[] $pathes
     * @param \USync\Loading\LoaderInterface[] $loaders
     * @param \Usync\Context $context
     *
     * @return \USync\AST\Node
     *   Fully built AST
     */
    public function parse($pathes, $loaders = [], Context $context)
    {
        $map = [];
        $ast = new Node('root');

        if (!is_array($pathes)) {
            $pathes = [$pathes];
        }
        foreach ($pathes as $path) {
            $map[] = new Path($path);
        }

        // Circular dependency breaker
        $done = [];

        while (!empty($map)) {

            /* @var $path \USync\AST\Path */
            $path = array_shift($map);
            $spath = $path->getPathAsString();

            if (isset($done[$spath])) {
                continue;
            }

            $done[$spath] = true;

            // @todo
            // Path map should come from a dynamic source to let the system
            // being extensible rather than hardcoded, this is the whole
            // long term goal
            foreach ($this->pathMap as $pattern => $class) {

                $attributes = $path->matches($pattern);

                if (false !== $attributes && class_exists($class)) {

                    /* @var $node \USync\AST\NodeInterface */
                    $node = new $class($path->getLastSegment());
                    $node->setAttributes($attributes);

                    foreach ($loaders as $loader) {
                        if ($loader->canProcess($node)) {
                            if ($loader->exists($node, $context)) {

                                // Build the node and update tree
                                $this->fixTree($ast, $path)->addChild($node);
                                $loader->updateNodeFromExisting($node, $context);

                                foreach ($loader->getExtractDependencies($node, $context) as $dpath) {
                                    // Even thought it's being done upper I'd
                                    // prefer to shortcut from there, node this
                                    // is not necessary
                                    if (isset($done[$dpath])) {
                                        continue;
                                    }

                                    $map[] = new Path($dpath);
                                }
                            } else {
                                $context->logWarning(sprintf('%s: does not exist', $spath));
                            }
                        }
                    }
                }
            }
        }

        return $ast;
    }
}
