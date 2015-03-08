<?php

namespace USync\Helper;

use USync\AbstractContextAware;
use USync\AST\Node;
use USync\AST\Path;

abstract class AbstractHelper extends AbstractContextAware implements HelperInterface
{
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
            $this->getContext()->logCritical(sprintf("%s rename: does not exists", $path));
        }

        if ($this->exists($newpath)) {
            if ($force) {
                $this->getContext()->logWarning(sprintf("%s rename: %s already exists", $newpath, $path));
            } else {
                $this->getContext()->logError(sprintf("%s rename: %s already exists", $newpath, $path));
            }
            $this->deleteExistingObject($path);
        }

        $this->synchronize($newpath, $this->getExistingObject($path));
        $this->deleteExistingObject($path);
    }
}
