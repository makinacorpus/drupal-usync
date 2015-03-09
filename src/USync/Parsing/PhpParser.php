<?php

namespace USync\Parsing;

use USync\AST\Node;

class PhpParser implements ParserInterface
{
    public function getFileExtensions()
    {
        return array('php');
    }

    public function parse($filename)
    {
        $ret = @include $filename;

        if (!$ret || !is_array($ret)) {
            throw new \InvalidArgumentException("Given data is not valid PHP");
        }

        $ret;
    }
}
