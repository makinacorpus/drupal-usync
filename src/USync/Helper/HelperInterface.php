<?php

namespace USync\Helper;

use USync\AST\Node;
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
     * @param \USync\AST\Node $node
     * @param \USync\Context $context
     */
    public function exists(Node $node, Context $context);

    /**
     * Delete existing object from site
     *
     * @param \USync\AST\Node $node
     * @param \USync\Context $context
     */
    public function deleteExistingObject(Node $node, Context $context);

    /**
     * Get existing object
     *
     * @param \USync\AST\Node $node
     * @param \USync\Context $context
     */
    public function getExistingObject(Node $node, Context $context);

    /**
     * Rename an existing object
     *
     * @param \USync\AST\Node $node
     * @param string $newpath
     * @param boolean $force
     * @param \USync\Context $context
     */
    public function rename(Node $node, $newpath, $force = false, Context $context);

    /**
     * Synchronize incoming object
     *
     * @param \USync\AST\Node $node
     * @param \USync\Context $context
     */
    public function synchronize(Node $node, Context $context);
}
