<?php

namespace USync\Parsing;

use USync\AST\Node;

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

        return Node::createNode($data);
    }
}
