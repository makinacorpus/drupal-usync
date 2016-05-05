<?php

namespace USync\Loading;

use USync\AST\BooleanValueNode;
use USync\AST\DefaultNode;
use USync\AST\DeleteNode;
use USync\AST\Drupal\DrupalNodeInterface;
use USync\AST\NodeInterface;
use USync\AST\NullValueNode;
use USync\AST\Path;
use USync\AST\Visitor;
use USync\Context;

class Loader
{
    /*
     * Topoligical sort, with depth-first search algorithm
     *   https://en.wikipedia.org/wiki/Topological_sorting
     *
          L ← Empty list that will contain the sorted nodes
          while there are unmarked nodes do
              select an unmarked node n
              visit(n) 

          function visit(node n)
              if n has a temporary mark then stop (not a DAG)
              if n is not marked (i.e. has not been visited yet) then
                  mark n temporarily
                  for each node m with an edge from n to m do
                      visit(m)
                  mark n permanently
                  unmark n temporarily
                  add n to head of L
     */

    private function visitDependencies($node, $map, &$stack, &$sorted, &$missing, Context $context)
    {
        if ($node->done) {
            return;
        }
        if ($node->marked) {
            $context->getLogger()->logCritical(sprintf("'%s': circular dependency", $node->path));
            return;
        }

        $node->marked = true;

        foreach ($node->vertices as $key) {

            if (!isset($map[$key])) {
                $context->getLogger()->logWarning(sprintf("'%s': unmet dependency: '%s'", $node->path, $key));
                $missing[] = $key;
                continue;
            }

            $this->visitDependencies($map[$key], $map, $stack, $sorted, $missing, $context);
        }

        // Mark n permanently, unmark temporarily
        unset($stack[$node->path]);
        $node->marked = false;
        $node->done = true;

        $sorted[] = $node->path;
    }

    protected function resolveDependencies($dependencyMap, Context $context)
    {
        $timer = $context->time('loader:dependencies');

        $sorted   = [];
        $stack    = [];
        $map      = [];
        $missing  = [];

        foreach ($dependencyMap as $path => $dependencies) {
            $stack[$path] = (object)[
                'path'      => $path,
                'done'      => false,
                'marked'    => false,
                'vertices'  => $dependencies,
            ];
        }

        $map = $stack;

        while ($stack) {
            foreach ($stack as $node) {
                if ($node->done) {
                    continue;
                }
                $this->visitDependencies($node, $map, $stack, $sorted, $missing, $context);
            }
        }

        $timer->stop();

        return $sorted;
    }

    protected function extractObjects(Context $context)
    {
        $dependencyMap = [];

        $timer = $context->time('loader:extract');

        // @todo un-hardcode this
        $loaders = usync_loader_list();

        $visitor = new Visitor();
        $visitor->addProcessor(function (NodeInterface $node, Context $context) use ($loaders, &$dependencyMap) {
            $path = $node->getPath();
            foreach ($loaders as $loader) {
                if ($loader->canProcess($node)) {
                    $dependencyMap[$path] = [];
                    foreach ($loader->getDependencies($node, $context) as $dependency) {
                        $dependencyMap[$path][] = $dependency;
                    }
                }
            }
        });
        $visitor->execute($context->getGraph(), $context);

        $timer->stop();

        return $this->resolveDependencies($dependencyMap, $context);
    }

    /**
     * Implementation for execute()
     *
     * @param Node $node
     * @param Context $context
     * @param \USync\Loading\LoaderInterface $loader
     */
    protected function load(NodeInterface $node, Context $context, LoaderInterface $loader)
    {
        if ($node instanceof DeleteNode || $node instanceof NullValueNode) {
            $mode = 'delete';
        } else if ($node instanceof DefaultNode) {
            $mode = 'sync';
        } else if ($node instanceof BooleanValueNode) {
            if ($node->getValue()) {
                $mode = 'sync';
            } else {
                $mode = 'delete';
            }
        } else if ($node instanceof DrupalNodeInterface && $node->shouldIgnore()) {
            $mode = 'ignore';
        } else {
            if ($node instanceof DrupalNodeInterface && $node->shouldDelete()) {
                $mode = 'delete';
            } else {
                $mode = 'sync';
            }
        }

        $dirtyAllowed = $node->hasAttribute('dirty') && $node->getAttribute('dirty');
        $dirtyPrefix = $dirtyAllowed ? '! ' : '';

        switch ($mode) {

            case 'ignore':
                $context->getLogger()->log(sprintf(" ? %s%s", $dirtyPrefix, $node->getPath()));
                return;

            case 'delete':
                $context->getLogger()->log(sprintf(" - %s%s", $dirtyPrefix, $node->getPath()));
                if ($loader->exists($node, $context)) {
                    $loader->deleteExistingObject($node, $context, $dirtyAllowed);
                }
                return;

            case 'sync':
                /*
                $object = $node->getValue();

                if (!is_array($object)) {
                    $object = array();
                }
                 */

                if ($loader->exists($node, $context)) {
                    $context->getLogger()->log(sprintf(" ~ %s%s", $dirtyPrefix, $node->getPath()));

                    /*
                    $existing = $loader->getExistingObject($node, $context);

                    // Proceed to merge accordingly to 'keep' and 'drop' keys.
                    if (!empty($object['keep'])) {
                        if ('all' === $object['keep']) {
                            drupal_array_merge_deep($existing, $object);
                        } else if (is_array($object['keep'])) {
                            foreach ($object['keep'] as $key) {
                                if (array_key_exists($key, $existing)) {
                                    $object[$key] = $existing[$key];
                                }
                            }
                        } else {
                            $context->logError(sprintf("%s: malformed 'keep' attribute, must be 'all' or an array of string attribute names", $node->getPath()));
                        }
                    }
                    if (!empty($object['drop'])) {
                        if (is_array($object['drop'])) {
                            foreach ($object['drop'] as $key) {
                                if (isset($object[$key])) {
                                    unset($object[$key]);
                                }
                            }
                        } else {
                            $context->logError(sprintf("%s: malformed 'drop' attribute, must be an array of string attribute names", $node->getPath()));
                        }
                    }
                     */
                } else {
                    $context->getLogger()->log(sprintf(" + %s%s", $dirtyPrefix, $node->getPath()));
                }

                // unset($object['keep'], $object['drop']);

                $identifier = $loader->synchronize($node, $context, $dirtyAllowed);

                if ($identifier && $node instanceof DrupalNodeInterface) {
                    $node->setDrupalIdentifier($identifier);
                }
                break;
        }
    }

    public function loadAll(Context $context, $pathes = null, $partial = false)
    {
        $list = $this->extractObjects($context);

        $loaders = usync_loader_list();

        // Proceed with sorted dependency map, inject all the things!
        foreach ($list as $path) {
            $nodes = (new Path($path))->find($context->getGraph());
            foreach ($nodes as $node) {
                foreach ($loaders as $loader) {
                    if ($loader->canProcess($node)) {
                        $timer = $context->time('load:' . $loader->getType());
                        $this->load($node, $context, $loader);
                        $timer->stop();
                    }
                }
            }
        }

        /*
        $visitor = new Visitor(Visitor::TOP_BOTTOM);
        $drupalProcessor = new DrupalProcessor($loaders);

        if (null !== $pathes) {
          $visitor->addProcessor(function (NodeInterface $node, Context $context) use ($drupalProcessor, $pathes, $partial) {
            foreach ($pathes as $pattern) {
              if (false !== Path::match($node->getPath(), $pattern, $partial)) {
                $drupalProcessor->execute($node, $context);
              }
            }
          });
        } else {
          $visitor->addProcessor($drupalProcessor);
        }

        $visitor->execute($context->getGraph(), $context);
         */

        // This seems, at some point, mandatory.
        field_cache_clear();
        // DO NOT EVER call menu_rebuild() manually, if you do this, Drupal will
        // mix-up the menu links you manually saved using menu_link_save() and
        // those still in cache at some point, and loose menu items parenting.
        // This wrong behavior cost me a few hours to debug.
        variable_set('menu_rebuild_needed', 1);
    }
}
