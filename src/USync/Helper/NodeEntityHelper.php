<?php

namespace USync\Helper;

class NodeEntityHelper extends AbstractEntityHelper
{
    /**
     * Default constructor
     *
     * @param \USync\Helper\FieldHelper $fieldHelper
     */
    public function __construct(FieldHelper $fieldHelper)
    {
        parent::__construct($fieldHelper, 'node');
    }

    protected function doSync($name, array $object)
    {
        $info = array(
            'type'       => $name,
            'base'       => 'node_content',
            'custom'     => false,
            'modified'   => false,
            'locked'     => true,
        ) + $object + array(
            'has_title'   => true,
            'title_label' => t("Title"), // Fallback to default language
            'module'      => 'node',
            'orig_type'   => null,
        );

        if (empty($info['name'])) {
            $this->getContext()->logWarning(sprintf('%s has no name', $name));
            $info['name'] = $name;
        }

        node_type_save((object)$info);
    }

    public function fillDefaults($path, array $object)
    {
        throw new \Exception("Not implemetend");
    }

    public function deleteExistingObject($path)
    {
        $bundle = $this->getLastPathSegment($path);
        $exists = (int)db_query("SELECT 1 FROM {node} WHERE type = :type", array(':type' => $bundle));

        if ($exists) {
            $this->getContext()->logDataloss(sprintf("%s node type has nodes"));
        }

        node_type_delete($bundle);
    }

    public function getExistingObject($path)
    {
        if (!$this->exists($path)) {
            $this->getContext()->logCritical(sprintf("%s node types does not exist"));
        }

        return (array)node_type_load($this->getLastPathSegment($path));
    }
}
