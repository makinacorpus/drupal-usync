<?php

namespace USync\Parsing;

use USync\AST\Node;

class PhpReader implements ReaderInterface
{
    public function getFileExtensions()
    {
        return array('php');
    }

    public function read($filename)
    {
        $ret = @include $filename;

        if (!$ret || !is_array($ret)) {
            throw new \InvalidArgumentException("Given data is not valid PHP");
        }

        return $ret;
    }
}
