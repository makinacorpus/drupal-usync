<?php

namespace USync\Discovery;

class PhpParser implements ParserInterface
{
    public function parse($filename)
    {
        $ret = @include $filename;

        if (!$ret || !is_array($ret)) {
            throw new \InvalidArgumentException("Given data is not valid PHP");
        }

        return $ret;
    }
}
