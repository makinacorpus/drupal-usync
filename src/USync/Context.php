<?php

namespace USync;

use USync\AST\Node;

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
     * @var \USync\AST\Node
     */
    protected $graph;

    public function __construct(Node $graph)
    {
        $this->graph = $graph;
    }

    /**
     * Get current config graph
     *
     * @return \USync\AST\Node
     */
    public function getGraph()
    {
        return $this->graph;
    }

    /**
     * Log message
     *
     * @param string $message
     * @param int $level
     */
    final public function log($message, $level = E_USER_NOTICE)
    {
        switch ($level) {

            case E_USER_NOTICE:
                drupal_set_message($message);
                break;

            default:
                trigger_error($message, $level);
        }
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
