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
     * @param string $name
     * @param array $object
     */
    protected function alter($hook, $path, array &$object)
    {
        drupal_alter('usync_' . $hook . '_' . $this->getType(), $object, $path);
    }

    /**
     * Helper function to get the key of a certain node.
     *
     * @param string $path
     *
     * @return string
     */
    public function getLastPathSegment($path)
    {
        $parts = explode(Path::SEP, $path);
        return $parts[count($parts) - 1];
    }

    public function rename($path, $newpath, $force = false, Context $context)
    {
        if (!$this->exists($path, $context)) {
            $context->logCritical(sprintf("%s rename: does not exists", $path));
        }

        if ($this->exists($newpath, $context)) {
            if ($force) {
                $context->logWarning(sprintf("%s rename: %s already exists", $newpath, $path));
            } else {
                $context->logError(sprintf("%s rename: %s already exists", $newpath, $path));
            }
            $this->deleteExistingObject($path, $context);
        }

        $this->synchronize($newpath, $this->getExistingObject($path, $context), $context);
        $this->deleteExistingObject($path, $context);
    }
}
