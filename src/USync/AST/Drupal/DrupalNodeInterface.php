<?php

namespace USync\AST\Drupal;

use USync\AST\NodeInterface;

interface DrupalNodeInterface extends NodeInterface
{
    /**
     * Is dirty injection allowed
     *
     * Dirty mode will allow the loader to proceed to fast but potential
     * unsafe import operations, for example allowing not to run module
     * hooks and disallow some cache reset operations.
     *
     * @return boolean
     */
    public function isDirty();

    /**
     * Tell if this node should do a proper merge
     *
     * Default behavior is to erase everything that is not specified.
     *
     * @return boolean
     */
    public function isMerge();

    /**
     * Should this node be ignore during sync.
     *
     * Use this when a node is a default Drupal object or provided by some
     * other module, but you need to define the node in the object hierarchy.
     *
     * @return boolean
     */
    public function shouldIgnore();

    /**
     * Should this node be deleted.
     *
     * @return boolean
     */
    public function shouldDelete();

    /**
     * Set Drupal identifier
     *
     * @param mixed $identifier
     */
    public function setDrupalIdentifier($identifier);

    /**
     * Get Drupal identifier
     *
     * @return mixed
     */
    public function getDrupalIdentifier();
}
