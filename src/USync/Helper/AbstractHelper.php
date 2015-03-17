<?php

namespace USync\Helper;

use USync\AST\Node;
use USync\AST\Path;
use USync\Context;

abstract class AbstractHelper implements HelperInterface
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
     * Invoke a hook to modules to allow them to alter data
     *
     * @param string $hook
     * @param Node $node
     *   Node from the graph
     * @param array $object
     *   Drupal object being saved
     */
    protected function alter($hook, Node $node, array &$object)
    {
        drupal_alter('usync_' . $hook . '_' . $this->getType(), $object, $node);
    }

    public function rename(Node $node, $newpath, $force = false, Context $context)
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
}
