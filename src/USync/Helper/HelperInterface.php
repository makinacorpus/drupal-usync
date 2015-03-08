<?php

namespace USync\Helper;

use USync\ContextAwareInterface;

/**
 * What a helper needs to do.
 */
interface HelperInterface extends ContextAwareInterface
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
     * @param string $path
     */
    public function exists($path);

    /**
     * Fill defaults into the given object
     *
     * @param string $path
     * @param array $object
     *
     * @return array
     */
    public function fillDefaults($path, array $object);

    /**
     * Delete existing object from site
     *
     * @param string $path
     */
    public function deleteExistingObject($path);

    /**
     * Get existing object
     *
     * @param string $path
     */
    public function getExistingObject($path);

    /**
     * Rename an existing object
     *
     * @param string $path
     * @param string $newpath
     * @param boolean $force
     */
    public function rename($path, $newpath, $force = false);

    /**
     * Synchronize incoming object
     *
     * @param string $path
     * @param array $object
     */
    public function synchronize($path, array $object);
}
