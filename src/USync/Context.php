<?php

namespace USync;

class Context
{
    /**
     * Warnings are mostly wrongly defined stuff we can still import.
     */
    const BREAK_WARNING = 1;

    /**
     * Errors are mostly data loss and incompatible current configuration alltogether.
     */
    const BREAK_ERROR = 2;

    /**
     * Data loss is modifications we can do that will cause data loss.
     */
    const BREAK_DATALOSS = 3;

    /**
     * Leave everything pass.
     */
    const BREAK_FORCE = 4;

    /**
     * Break on.
     *
     * @var string
     */
    public $breakOn = self::BREAK_DATALOSS;

    /**
     * @var \USync\Runner
     */
    protected $runner;

    /**
     * Get runner
     *
     * @return \USync\Runner
     */
    public function getRunner()
    {
        if (null === $this->runner) {
            $this->runner = new Runner($this);
        }
        return $this->runner;
    }

    /**
     * Log message
     *
     * @param string $message
     * @param int $level
     */
    final public function log($message, $level = E_USER_NOTICE)
    {
        trigger_error(get_class($this) . ': ' . $message, $level);
    }

    /**
     * Log warning message
     *
     * @param string $message
     */
    final public function logWarning($message)
    {
        $this->log($message, E_USER_WARNING);

        if ($this->breakOn <= Context::BREAK_WARNING) {
            throw new \RuntimeException("Error level breakage");
        }
    }

    /**
     * Log data loss warning
     *
     * @param string $message
     */
    final public function logDataloss($message)
    {
        $this->log($message, E_USER_ERROR);

        if ($this->breakOn <= Context::BREAK_DATALOSS) {
            throw new \RuntimeException("Error level breakage");
        }
    }

    /**
     * Log error message
     *
     * @param string $message
     */
    final public function logError($message)
    {
        $this->log($message, E_USER_ERROR);

        if ($this->breakOn <= Context::BREAK_ERROR) {
            throw new \RuntimeException("Error level breakage");
        }
    }

    /**
     * Log error message
     *
     * @param string $message
     */
    final public function logCritical($message)
    {
        $this->log($message, E_USER_ERROR);

        throw new \RuntimeException("Critical error breakage");
    }
}
