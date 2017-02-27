<?php

namespace USync\Loading;

use USync\AST\NodeInterface;
use USync\Context;

/**
 * What a loader needs to do.
 */
interface LoaderInterface
{
    /**
     * Initialize the loader at environment init.
     */
    public function init();

    /**
     * Get internal component type, used for hooks mostly
     *
     * @return string
     */
    public function getType();

    /**
     * Does the object exists in site
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     */
    public function exists(NodeInterface $node, Context $context);

    /**
     * Get existing object
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     */
    public function getExistingObject(NodeInterface $node, Context $context);

    /**
     * Can this loader process the given node
     *
     * @param \USync\AST\NodeInterface $node
     */
    public function canProcess(NodeInterface $node);

    /**
     * Does this instance is able to proceed to quick and dirty operations
     *
     * Dirty operations allow the loader to go faster by bypassing the Drupal
     * API, disabling hooks, cache operations etc... By proceeding this way
     * to may gain several minutes on synchronization time on complex sites
     * but also may risk to end up with inconsistent data (especially if hooks
     * are bypassed).
     *
     * Implementation of what is dirty or not is up to the implementor and
     * cannot be known in advance. The usefulness of knowing that an loader
     * can do dirty things or not gives the ability to the caller to force
     * caches to be deleted only once bulk nodes have been processed.
     */
    public function canDoDirtyThings();

    /**
     * Get node dependencies as an array of paths
     *
     * Please note that dependencies are ordered
     *
     * @todo This is path dependent, while it should not, find a better way
     *
     * @param NodeInterface $node
     * @param Context $context
     *
     * @return string[]
     *   Array of paths
     */
    public function getDependencies(NodeInterface $node, Context $context);

    /**
     * Get node dependencies for extraction
     *
     * Please note that dependencies are ordered
     *
     * @todo This is path dependent, while it should not, find a better way
     *
     * @param NodeInterface $node
     * @param Context $context
     *
     * @return string[]
     *   Array of paths
     */
    public function getExtractDependencies(NodeInterface $node, Context $context);

    /**
     * Populate the given node attributes, children and values from the
     * existing object
     *
     * @param NodeInterface $node
     * @param Context $context
     */
    public function updateNodeFromExisting(NodeInterface $node, Context $context);

    /**
     * Delete existing object from site
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     * @param boolean $dirtyAllowed
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false);

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
     * Process inheritance
     *
     * @param NodeInterface $node
     * @param NodeInterface $parent
     * @param \USync\Context $context
     * @param boolean $dirtyAllowed
     */
    public function processInheritance(NodeInterface $node, NodeInterface $parent, Context $context, $dirtyAllowed = false);

    /**
     * Synchronize incoming object
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     * @param boolean $dirtyAllowed
     *
     * @return mixed
     *   Drupal identifier if possible
     */
    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false);
}
