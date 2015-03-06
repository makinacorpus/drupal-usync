<?php

namespace USync\Discovery;

use USync\Config;
use USync\USync;

class PathDiscovery
{
    static public function discover($path, $pattern = '*', ParserInterface $parser)
    {
        $data = array();

        foreach (glob($path . '/' . $pattern) as $filename) {

            $additions = $parser->parse($filename);

            // Fetch section name
            $parts = explode('.', basename($filename));
            array_pop($parts);

            $current = &$data;
            foreach ($parts as $part) {
                if (empty($current[$part])) {
                    $current[$part] = array();
                }
                $current = &$current[$part];
            }

            $current = $additions;
        }

        return new Config($data);
    }
}
