<?php

namespace USync\TreeBuilding;

class Resource
{
    private $storedChecksum;
    private $currentChecksum;
    private $filename;
    private $source;
    private $module;
    private $broken = false;

    public function __construct($source, $filename, $checksum = null, $module = null)
    {
        if (!file_exists($filename)) {
            trigger_error(sprintf("'%s': resource file does not exists", $filename), E_USER_ERROR);
            $this->broken = true;
        }
        if (!is_readable($filename)) {
            trigger_error(sprintf("'%s': cannot read resource file", $filename), E_USER_ERROR);
            $this->broken = true;
        }

        $this->source = $source;
        $this->filename = $filename;
        $this->storedChecksum = $checksum;
        $this->module = $module;
    }

    public function getCurrentChecksum()
    {
        if (!$this->currentChecksum) {

            if (!is_file($this->filename)) {
                // @todo fixme
                return uniqid();
            }

            $this->currentChecksum = md5_file($this->filename);
        }

        return $this->currentChecksum;
    }

    public function getStoredChecksum()
    {
        return $this->storedChecksum;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function isBroken()
    {
        return $this->broken;
    }

    public function getTargetInModule()
    {
        if ($this->module) {
            return substr($this->source, strlen($this->module) + 1);
        }

        return $this->source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function isFresh()
    {
        return !$this->storedChecksum || $this->storedChecksum !== $this->getCurrentChecksum();
    }

    public function markAsUpToDate()
    {
        $this->storedChecksum = $this->getCurrentChecksum();
    }
}
