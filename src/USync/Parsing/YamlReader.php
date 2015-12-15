<?php

namespace USync\Parsing;

use Symfony\Component\Yaml\Yaml;

class YamlReader implements ReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFileExtensions()
    {
        return array('yml', 'yaml');
    }

    /**
     * {@inheritdoc}
     */
    public function read($filename)
    {
        if (!class_exists('\Symfony\Component\Yaml\Yaml')) {
            if (!@include_once __DIR__ . '/../../../vendor/autoload.php') {
                throw new \LogicException("Unable to find the \Symfony\Component\Yaml\Yaml class");
            }
        }

        $ret = Yaml::parse(file_get_contents($filename));

        if (!$ret) {
            throw new \InvalidArgumentException("Given data is not valid Yaml");
        }

        return $ret;
    }
}
