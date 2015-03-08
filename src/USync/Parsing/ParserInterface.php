<?php

namespace USync\Parsing;

interface ParserInterface
{
    /**
     * From given blob parse data and return a nice formatted array for
     * usage with the Node class
     *
     * @param string $filename
     */
    public function parse($filename);
}
