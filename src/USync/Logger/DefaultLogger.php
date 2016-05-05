<?php

namespace USync\Logger;

use USync\Context;

class DefaultLogger
{
    /**
     * Break on.
     *
     * @var string
     */
    protected $breakOn = Context::BREAK_DATALOSS;

    /**
     * Set level at which it will break
     *
     * @param string $level
     *   One of the \USync\Context::BREAK_* constant
     */
    public function breakOn($level)
    {
        $this->breakOn = $level;

        return $this;
    }

    /**
     * Log message
     *
     * @param string $message
     * @param int $level
     */
    public function log($message, $level = E_USER_NOTICE, $willBreak = false)
    {
        if ($willBreak) {
            trigger_error($message, $level);

            return;
        }

        switch ($level) {

            case E_USER_NOTICE:
                drupal_set_message($message);
                break;

            default:
                //trigger_error($message, $level);
                drupal_set_message($message, 'warning');
        }
    }

    /**
     * Log warning message
     *
     * @param string $message
     */
    final public function logWarning($message)
    {
        $this->log($message, E_USER_WARNING, $this->breakOn <= Context::BREAK_WARNING);

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
        $this->log($message, E_USER_ERROR, $this->breakOn <= Context::BREAK_DATALOSS);

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
        $this->log($message, E_USER_ERROR, $this->breakOn <= Context::BREAK_ERROR);

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
