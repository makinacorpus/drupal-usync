<?php

namespace USync\Parsing;

class PhpReader implements ReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFileExtensions()
    {
        return array('php');
    }

    /**
     * {@inheritdoc}
     */
    public function read($filename)
    {
        $ret = @include $filename;

        if (!$ret || !is_array($ret)) {
            throw new \InvalidArgumentException("Given data is not valid PHP");
        }

        return $ret;
    }
}
