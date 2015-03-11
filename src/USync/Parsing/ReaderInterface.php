<?php

namespace USync\Parsing;

interface ReaderInterface
{
    /**
     * Return a list of supported file extensions
     *
     * @return string[]
     */
    public function getFileExtensions();

    /**
     * From given blob read data and return a nice formatted array for
     * usage with the Node class
     *
     * @param string $filename
     *
     * @return array
     *   PHP array of read raw data
     */
    public function read($filename);
}
