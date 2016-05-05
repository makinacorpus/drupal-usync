<?php

namespace USync;

class Timer
{
    private $name;
    private $start;
    private $stop;

    public function __construct($name)
    {
        $this->start = microtime(true);
    }

    public function stop()
    {
        if ($this->stop) {
            throw new USyncException("Timer has already been stopped");
        }

        $this->stop = microtime(true);
    }

    public function get()
    {
        if (!$this->stop) {
            throw new USyncException("Timer has never been started");
        }

        return round(($this->stop - $this->start) * 1000);
    }
}
