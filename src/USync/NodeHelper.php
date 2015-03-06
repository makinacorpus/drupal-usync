<?php

namespace USync;

class NodeHelper extends AbstractEntityHelper
{
    /**
     * Default constructor
     *
     * @param \USync\FieldHelper $fieldHelper
     */
    public function __construct(FieldHelper $fieldHelper)
    {
        parent::__construct($fieldHelper, 'node');
    }

    public function delete($name)
    {
        $exists = (int)db_query("SELECT 1 FROM {node} WHERE type = :type", array(':type' => $name));

        if ($exists) {
            $this->logDataloss(sprintf("%s type has nodes - delete denied"));
        }

        node_type_delete($name);
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
            $this->logWarning(sprintf('%s has no name', $name));
            $info['name'] = $name;
        }

        node_type_save((object)$info);
    }
}
