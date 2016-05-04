<?php

namespace USync\TreeBuilding;

use USync\Parsing\PathDiscovery;
use USync\Parsing\YamlReader;

class GraphBuilder
{
    protected $sources = [];

    public function __construct($sources)
    {
        $this->addSources($sources);
    }

    final public function addSources($sources)
    {
        foreach ($sources as $source) {
            $this->addSource($source);
        }
    }

    final protected function getModuleList()
    {
        return usync_module_list();
    }

    /**
     * Expand module source
     *
     * @param string $source
     *
     * return string[]
     */
    final protected function expandSource($source)
    {
        if (false === strpos($source, ':')) {
            return [$source];
        }

        $ret = [];

        // This is a source from a module
        list($module, $target) = explode(':', $source, 2);

        if (!module_exists($module)) {
            throw new \InvalidArgumentException(sprintf("'%s' module is not enable or does not exist", $module));
        }

        $list = $this->getModuleList();

        if (!isset($list[$module])) {
            throw new \InvalidArgumentException(sprintf("'%s' module does not declare any synchronization sources", $module));
        }

        $targets = [];

        // This is valid, user asked module: without any target defined, the
        // whole module will be imported
        if (empty($target)) {
            $targets = $list[$module];
        } else if (!isset($list[$target])) {
            throw new \InvalidArgumentException(sprintf("'%s' module: '%s' source is not declared", $module, $target));
        } else {
            $targets = [$target];
        }

        $path = drupal_get_path('module', $module);
        foreach ($targets as $target) {
            $ret[] = $path.'/'.$target;
        }

        return $ret;
    }

    /**
     * Add a single source
     *
     * @param string $source
     *   - "MODULE:" will add all declared module sources
     *   - "MODULE:SOURCE" will add the targeted module source
     *   - any other arbitrary strings will be considered as a Drupal root
     *     relative filename
     */
    public function addSource($source)
    {
        foreach ($this->expandSource($source) as $path) {
            // Avoid duplicates
            if (!in_array($path, $this->sources)) {
                $this->sources[] = $path;
            }
        }
    }

    /**
     * Build graph from provided sources
     */
    public function buildRawArray()
    {
        $type = null;
        $full = [];

        foreach ($this->sources as $filename) {

            if (!is_dir($filename) && !is_file($filename)) {
                throw new \InvalidArgumentException(sprintf("%s: file does not exists", $filename));
            }

            if (!empty($type)) {
                $readerClass = '\\USync\\Parsing\\' . ucfirst($type) . 'Reader';
                if (!class_exists($readerClass)) {
                    throw new \InvalidArgumentException(sprintf("'%s': type is not supported", $type));
                }
                $reader = new $readerClass();
            } else {
                $reader = new YamlReader();
            }

            if (is_file($filename)) {
                $data = $reader->read($filename);
            } else if (is_dir($filename)) {
                $discovery = new PathDiscovery();
                $data = $discovery->discover($filename, $reader);
            }

            if (empty($data)) {
                throw new \RuntimeException(sprintf("%s: Could not parse file or folder", $filename));
            }

            $full = drupal_array_merge_deep($full, $data);
        }

        return $full;
    }

    /**
     * Build graph from provided sources
     */
    public function build()
    {
        return (new ArrayTreeBuilder())->parse($this->buildRawArray());
    }
}
