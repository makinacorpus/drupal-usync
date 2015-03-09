<?php

namespace USync\Parsing;

interface ParserInterface
{
    /**
     * Return a list of supported file extensions
     *
     * @return string[]
     */
    public function getFileExtensions();

    /**
     * From given blob parse data and return a nice formatted array for
     * usage with the Node class
     *
     * @param string $filename
     *
     * @return array
     *   PHP array of parsed raw data
     */
    public function parse($filename);
}
