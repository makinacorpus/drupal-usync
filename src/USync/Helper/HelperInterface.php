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
     */
    public function deleteExistingObject(NodeInterface $node, Context $context);

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
     * @param boolean $force
     * @param \USync\Context $context
     */
    public function rename(NodeInterface $node, $newpath, $force = false, Context $context);

    /**
     * Synchronize incoming object
     *
     * @param \USync\AST\NodeInterface $node
     * @param \USync\Context $context
     */
    public function synchronize(NodeInterface $node, Context $context);

    /**
     * Can this helper process the given node
     *
     * @param \USync\AST\NodeInterface $node
     */
    public function canProcess(NodeInterface $node);
}
