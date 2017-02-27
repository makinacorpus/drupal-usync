<?php

namespace USync\Loading;

use USync\AST\NodeInterface;
use USync\AST\Path;
use USync\Context;
use USync\TreeBuilding\ArrayTreeBuilder;

use Symfony\Component\Yaml\Yaml;

abstract class AbstractLoader implements LoaderInterface
{
    /**
     * Update
     */
    const HOOK_UPDATE = 'update';

    /**
     * Insert
     */
    const HOOK_INSERT = 'insert';

    /**
     * {@inheritDoc}
     */
    public function init()
    {
    }

    /**
     * Invoke a hook to modules to allow them to alter data
     *
     * @param string $hook
     * @param NodeInterface $node
     *   Node from the graph
     * @param array $object
     *   Drupal object being saved
     */
    protected function alter($hook, NodeInterface $node, array &$object)
    {
        drupal_alter('usync_' . $hook . '_' . $this->getType(), $object, $node);
    }

    /**
     * {@inheritdoc}
     */
    public function canDoDirtyThings()
    {
        return false;
    }

    /**
     * Optionnaly implement this in order to rely on the
     * updateNodeFromExisting() default implementation
     *
     * @param array $array
     */
    protected function fixDrupalExistingArray(array &$array)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(NodeInterface $node, Context $context)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtractDependencies(NodeInterface $node, Context $context)
    {
        return $this->getDependencies($node, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        // Dirty and very quick way of doing this.
        $existing = $this->getExistingObject($node, $context);

        if (!$existing) {
            throw new \InvalidArgumentException(sprintf("There is no existing '%s'", $node->getPath()));
        }
        if (is_object($existing)) {
          $existing = (array)$existing;
        }

        $array = Yaml::parse(Yaml::dump($existing));
        $array = $existing;

        $this->fixDrupalExistingArray($array);
        foreach (array_reverse(explode(Path::SEP, $node->getPath())) as $key) {
            $array = [$key => $array];
        }
        $tree = (new ArrayTreeBuilder())->parse($array);
        if ($new = $tree->find($node->getPath())) {
            $node->mergeWith(reset($new));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename(NodeInterface $node, $newpath, Context $context, $force = false, $dirtyAllowed = false)
    {
        throw new \Exception("Not implemented");

        /*
        if (!$this->exists($node, $context)) {
            $context->logCritical(sprintf("%s: rename: does not exists", $node->getPath()));
        }

        if ($this->exists($newpath, $context)) {
            if ($force) {
                $context->logWarning(sprintf("%s: rename: %s already exists", $node->getPath(), $newpath));
            } else {
                $context->logError(sprintf("%s: rename: %s already exists", $node->getPath(), $newpath));
            }
            $this->deleteExistingObject($node, $context);
        }

        $this->synchronize($newpath, $this->getExistingObject($node, $context), $context);
        $this->deleteExistingObject($node, $context);
         */
    }

    /**
     * {@inheritdoc}
     */
    public function processInheritance(NodeInterface $node, NodeInterface $parent, Context $context, $dirtyAllowed = false)
    {
        $context->getLogger()->logWarning(sprintf("%s: %s: inheritance is not implemented, skipped", $node->getPath(), get_class($this)));
    }
}
