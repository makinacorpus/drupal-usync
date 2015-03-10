<?php

namespace USync\Helper;

use USync\Context;

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

    public function synchronize($path, array $object, Context $context)
    {
        $bundle = $this->getLastPathSegment($path);

        $info = array(
            'type'        => $bundle,
            'base'        => 'node_content',
            'custom'      => false,
            'modified'    => false,
            //'locked'     => true,
        ) + $object + array(
            'has_title'   => true,
            'title_label' => t("Title"), // Fallback to default language
            'module'      => 'node',
            'orig_type'   => null,
            'locked'      => false,
        );

        if (empty($info['name'])) {
            $context->logWarning(sprintf('%s has no name', $bundle));
            $info['name'] = $bundle;
        }

        node_type_save((object)$info);
    }

    public function fillDefaults($path, array $object, Context $context)
    {
        throw new \Exception("Not implemetend");
    }

    public function deleteExistingObject($path, Context $context)
    {
        $bundle = $this->getLastPathSegment($path);
        $exists = (int)db_query("SELECT 1 FROM {node} WHERE type = :type", array(':type' => $bundle));

        if ($exists) {
            $context->logDataloss(sprintf("%s node type has nodes"));
        }

        node_type_delete($bundle);
    }

    public function getExistingObject($path, Context $context)
    {
        if (!$this->exists($path, $context)) {
            $context->logCritical(sprintf("%s node types does not exist"));
        }

        return (array)node_type_load($this->getLastPathSegment($path));
    }
}
