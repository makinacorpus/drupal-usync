<?php

namespace USync\TreeBuilding;

use Symfony\Component\Yaml\Yaml;

/**
 * This object is responsible for maintaining sources version and check for
 * updates if necessary
 */
class Repository
{
    static private $instance;

    /**
     * @return Repository
     */
    static public function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Repository(variable_get('file_private_path') . '/usync-status.yml');
        }

        return self::$instance;
    }

    /**
     * @var string
     */
    private $repositoryFile;

    /**
     * @var \USync\TreeBuilding\Resource[]
     */
    private $resources = [];

    /**
     * @var boolean
     */
    private $loaded = false;

    /**
     * @var boolean
     */
    private $updated = false;

    /**
     * @var string[][]
     */
    private $moduleList;

    /**
     * Default constructor
     *
     * @param string $repositoryFile
     */
    public function __construct($repositoryFile)
    {
        if (!is_file($repositoryFile)) {
            // Attempt to create file
            if (!touch($repositoryFile)) {
                throw new \RuntimeException(sprintf("'%s': cannot create repository file", $repositoryFile));
            }
        }

        if (!is_readable($repositoryFile)) {
            throw new \RuntimeException(sprintf("'%s': cannot read repository file", $repositoryFile));
        }
        if (!is_writable($repositoryFile)) {
            throw new \RuntimeException(sprintf("'%s': cannot write repository file", $repositoryFile));
        }

        $this->repositoryFile = $repositoryFile;
    }

    /**
     * @return \string[][]
     */
    public function getModuleList()
    {
        if (null === $this->moduleList) {
            $this->moduleList = [];

            foreach (system_list('module_enabled') as $module => $data) {
                if (!empty($data->info['usync'])) {

                    $sources = $data->info['usync'];
                    // For lazy people this module allows to declare only one source
                    // not using the info array form ([]). Lazy people are lazy there
                    // is no point in fighting against them.
                    if (!is_array($sources)) {
                        $sources = [$sources];
                    }

                    $this->moduleList[$module] = $sources;
                }
            }
        }

        return $this->moduleList;
    }

    private function readRespository()
    {
        if ($this->loaded) {
            return;
        }

        $data = Yaml::parse(file_get_contents($this->repositoryFile));
        $list = $this->getModuleList();

        foreach ($list as $module => $targets) {
            foreach ($targets as $target) {

                $source = $module . ':' . $target;

                // Ensure module is present in the repository
                if (!isset($data[$source])) {
                    $data[$source] = [];
                    $this->updated = true;
                }
                if (!isset($data[$source]['checksum'])) {
                    $data[$source]['checksum'] = null;
                }

                $data[$source]['module'] = $module;

                $filename = drupal_get_path('module', $module) . '/' . $target;

                $this->resources[$source] = new Resource($source, $filename, $data[$source]['checksum'], $module);
            }
        }

        $this->loaded = true;
    }

    /**
     * @return \USync\TreeBuilding\Resource[]
     */
    public function getResourcesAll()
    {
        $this->readRespository();

        return $this->resources;
    }

    /**
     * @return Resource[]
     */
    public function getOutdatedResources()
    {
        $ret = [];

        foreach ($this->resources as $resource) {
            if ($resource->isFresh()) {
                $ret[] = $resource;
            }
        }

        return $ret;
    }

    /**
     * Write changes to disk if necessary
     */
    public function commit()
    {
        if (!$this->updated) {
            return;
        }

        $data = [];

        foreach ($this->resources as $resource) {
            $data[$resource->getSource()] = [
                'checksum' => $resource->getStoredChecksum(),
            ];
        }

        file_put_contents($this->repositoryFile, Yaml::dump($data));
    }

    /**
     * @param string $source
     *   The resource cannot be a module name, it must point to a single file
     *
     * @return \USync\TreeBuilding\Resource
     */
    public function getResource($source)
    {
        $this->readRespository();

        if (!isset($this->resources[$source])) {
            throw new \InvalidArgumentException(sprintf("'%s': source is not declared", $source));
        }

        return $this->resources[$source];
    }
}
