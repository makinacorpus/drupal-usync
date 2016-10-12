<?php

namespace USync;

use USync\AST\NodeInterface;
use USync\Logger\DefaultLogger;

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
     * @var DefaultLogger
     */
    private $logger;

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
     * Get logger
     *
     * @return \USync\Logger\DefaultLogger
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new DefaultLogger();
            $this->logger->breakOn($this->breakOn);
        }

        return $this->logger;
    }

    /**
     * Set logger
     *
     * @param DefaultLogger $logger
     */
    public function setLogger(DefaultLogger $logger)
    {
        $this->logger = $logger;
        $this->logger->breakOn($this->breakOn);
    }

    /**
     * Set break level
     *
     * @param integer $level
     */
    public function setBreakOn($level)
    {
        $this->breakOn = (int) $level;
        if ($this->logger) {
            $this->logger->breakOn($this->breakOn);
        }
    }

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
     *
     * @deprecated
     */
    final public function log($message, $level = E_USER_NOTICE, $willBreak = false)
    {
        $this->getLogger()->log($message, $level, $willBreak);
    }

    /**
     * Log warning message
     *
     * @param string $message
     *
     * @deprecated
     */
    final public function logWarning($message)
    {
        $this->getLogger()->logWarning($message);
    }

    /**
     * Log data loss warning
     *
     * @param string $message
     *
     * @deprecated
     */
    final public function logDataloss($message)
    {
        $this->getLogger()->logDataloss($message);
    }

    /**
     * Log error message
     *
     * @param string $message
     *
     * @deprecated
     */
    final public function logError($message)
    {
        $this->getLogger()->logError($message);
    }

    /**
     * Log error message
     *
     * @param string $message
     *
     * @deprecated
     */
    final public function logCritical($message)
    {
        $this->getLogger()->logCritical($message);
    }
}
