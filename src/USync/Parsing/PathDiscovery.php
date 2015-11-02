<?php

namespace USync\Parsing;

class PathDiscovery
{
    static public function discover($path, ReaderInterface $reader)
    {
        $data = array();

        $ext = $reader->getFileExtensions();
        if (empty($ext)) {
            $pattern = '*';
        } else if (1 === count($ext)) {
            $pattern = '*.' . $ext;
        } else {
            $pattern = '*.{' . implode(',', $ext) . '}';
        }

        foreach (glob($path . '/' . $pattern, GLOB_BRACE | GLOB_NOESCAPE) as $filename) {

            $additions = $reader->read($filename);

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

            $current = drupal_array_merge_deep($additions, $current);
        }

        return $data;
    }
}
