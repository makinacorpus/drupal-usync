<?php

namespace USync;

use USync\AST\NodeInterface;

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
     * @var callack[]
     */
    private $listeners = [];

    /**
     * @var Timer[][]
     */
    private $timers = [];

    /**
     * Root node we are working on
     *
     * @var NodeInterface
     */
    private $graph;

    /**
     * Arbitrary counters
     *
     * @var int[]
     */
    private $counters = [];

    /**
     * Break on.
     *
     * @var string
     */
    public $breakOn = self::BREAK_DATALOSS;

    /**
     * Set graph
     *
     * @param NodeInterface $graph
     */
    public function setGraph(NodeInterface $graph)
    {
        if ($this->graph) {
            throw new \Exception("Cannot change graph");
        }

        $this->graph = $graph;
    }

    /**
     * Get graph
     *
     * @return NodeInterface
     */
    public function getGraph()
    {
        if (!$this->graph) {
            throw new \LogicException("Graph is not set");
        }
        return $this->graph;
    }

    /**
     * Add a listener
     *
     * @param string $type
     * @param callable $callback
     */
    final public function on($type, callable $callback)
    {
        $this->listeners[$type][] = $callback;
    }

    final public function incr($name)
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = 0;
        }
        $this->counters[$name]++;
    }

    final public function decr($name)
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = 0;
        }
        $this->counters[$name]--;
    }

    final public function count($name)
    {
        if (!isset($this->counters[$name])) {
            return 0;
        }
        return $this->counters[$name];
    }

    /**
     * Start a timer
     *
     * @param string $name
     *
     * @return Timer
     */
    final public function time($name)
    {
        return $this->timers[$name][] = new Timer($name);
    }

    /**
     * Get all timers
     *
     * @return Timer[][]
     *   First dimension keys are timer names, values are Timer instances
     */
    final public function getTimers()
    {
        return $this->timers;
    }

    final public function resetTimers()
    {
        $this->timers = [];
    }

    /**
     * Notifiy an event
     *
     * @param string $type
     * @param ... $arguments
     */
    final public function notify($type)
    {
        if (empty($this->listeners[$type])) {
            return;
        }

        $args = func_get_args();
        array_shift($type);
        array_unshift($args, $this);

        foreach ($this->listeners[$type] as $callback) {
            call_user_func_array($callback, $args);
        }
    }

    /**
     * Log message
     *
     * @param string $message
     * @param int $level
     */
    final public function log($message, $level = E_USER_NOTICE, $willBreak = false)
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
