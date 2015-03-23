<?php

namespace USync\Helper;

use USync\AST\NodeInterface;
use USync\Context;

/**
 * What a helper needs to do.
 */
interface HelperInterface
{
    /**
     * Get internal component type, used for hooks mostly
     *
     * @return string
     */
    public function getType();

    /**
     * Does this instance is able to proceed to quick and dirty operations
     *
     * Dirty operations allow the helper to go faster by bypassing the Drupal
     * API, disabling hooks, cache operations etc... By proceeding this way
     * to may gain several minutes on synchronization time on complex sites
     * but also may risk to end up with inconsistent data (especially if hooks
     * are bypassed).
     *
     * Implementation of what is dirty or not is up to the implementor and
     * cannot be known in advance. The usefulness of knowing that an helper
     * can do dirty things or not gives the ability to the caller to force
     * caches to be deleted only once bulk nodes have been processed.
     */
    public function canDoDirtyThings();

    /**
     * Does the object exists in site
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     */
    public function exists(NodeInterface $node, Context $context);

    /**
     * Delete existing object from site
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     * @param boolean $dirtyAllowed
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false);

    /**
     * Get existing object
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     */
    public function getExistingObject(NodeInterface $node, Context $context);

    /**
     * Rename an existing object
     *
     * @param \USync\AST\NodeInterface $node
     * @param string $newpath
     * @param \USync\Context $context
     * @param boolean $force
     * @param boolean $dirtyAllowed
     */
    public function rename(NodeInterface $node, $newpath, Context $context, $force = false, $dirtyAllowed = false);

    /**
     * Synchronize incoming object
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     * @param boolean $dirtyAllowed
     */
    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false);

    /**
     * Can this helper process the given node
     *
     * @param \USync\AST\NodeInterface $node
     */
    public function canProcess(NodeInterface $node);
}
