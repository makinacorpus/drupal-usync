<?php

namespace USync\Parsing;

use USync\AST\Node;

class PathDiscovery
{
    static public function discover($path, ParserInterface $parser)
    {
        $data = array();

        $ext = $parser->getFileExtensions();
        if (empty($ext)) {
            $pattern = '*';
        } else if (1 === count($ext)) {
            $pattern = '*.' . $ext;
        } else {
            $pattern = '*.{' . implode(',', $ext) . '}';
        }

        foreach (glob($path . '/' . $pattern, GLOB_BRACE | GLOB_NOESCAPE) as $filename) {

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
