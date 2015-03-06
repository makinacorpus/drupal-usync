<?php

namespace USync\Discovery;

interface ParserInterface
{
    /**
     * From given blob parse data and return a nice formatted array for
     * usage with the Config class
     *
     * @param string $filename
     */
    public function parse($filename);
}
