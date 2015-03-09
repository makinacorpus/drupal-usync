<?php

namespace USync\Helper;

use USync\AST\Node;
use USync\AST\Path;
use USync\Context;

abstract class AbstractHelper implements HelperInterface
{
    /**
     * @var \USync\Context
     */
    protected $context;

    public function setContext(Context $context)
    {
        $this->context = $context;
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

    public function rename($path, $newpath, $force = false)
    {
        if (!$this->exists($path)) {
            $this->context->logCritical(sprintf("%s rename: does not exists", $path));
        }

        if ($this->exists($newpath)) {
            if ($force) {
                $this->context->logWarning(sprintf("%s rename: %s already exists", $newpath, $path));
            } else {
                $this->context->logError(sprintf("%s rename: %s already exists", $newpath, $path));
            }
            $this->deleteExistingObject($path);
        }

        $this->synchronize($newpath, $this->getExistingObject($path));
        $this->deleteExistingObject($path);
    }
}
