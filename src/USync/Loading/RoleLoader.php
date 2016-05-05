<?php

namespace USync\Loading;

use USync\AST\Drupal\RoleNode;
use USync\AST\NodeInterface;
use USync\Context;
use USync\AST\ValueNode;

class RoleLoader extends AbstractLoader
{
    public function getType()
    {
        return 'role';
    }

    /**
     * Get role name depending on structure
     *
     * @param NodeInterface $node
     * @param Context $context
     *
     * @return string
     */
    protected function getRoleName(NodeInterface $node, Context $context)
    {
        if ($node->hasChild('name')) {
            $value = $node->getChild('name')->getValue();

            if (!is_string($value)) {
                $context->logCritical(sprintf("%s: name attribute is not a string", $node->getPath()));
            }

            return $value;
        }

        return $node->getName();
    }

    /**
     * Load existing role from database
     *
     * @param NodeInterface $node
     * @param Context $context
     *
     * @return stdClass
     */
    protected function loadExistingRole(NodeInterface $node, Context $context)
    {
        switch ($node->getName()) {

            case 'anonymous':
                return user_role_load(DRUPAL_ANONYMOUS_RID);

            case 'authenticated':
                return user_role_load(DRUPAL_AUTHENTICATED_RID);

            default:
                return user_role_load_by_name($this->getRoleName($node, $context));
        }
    }

    public function exists(NodeInterface $node, Context $context)
    {
        if ($this->loadExistingRole($node, $context)) {
            return true;
        }

        return false;
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        $existing = (array)$this->loadExistingRole($node, $context);

        if (!$existing) {
            $context->logCritical(sprintf("%s: does not exists", $node->getPath()));
        }

        // @todo
        // Handle permissions as well.

        return array_intersect_key($existing, ['name']);
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        if ($role = $this->loadExistingRole($node, $context)) {
            user_role_delete($role);
        }
    }

    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        // @todo
        // Handle permissions as well.
    }

    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        $role = $this->loadExistingRole($node, $context);

        $object = [
            'name' => $this->getRoleName($node, $context),
        ];

        if ($role) {
            // Needs to fetch back the rid.
            $object['rid'] = $role->rid;
        }

        // Handle permissions as well.
        $rolePermissions = [];

        if ($node->hasChild('permission')) {

            $valid = array_keys(module_invoke_all('permission'));

            foreach ($node->getChild('permission')->getChildren() as $permission) {
                $name = $permission->getValue();

                if (!is_string($name)) {
                    $context->logWarning(sprintf("%s: permission is not a string value, ignoring", $permission->getPath()));
                    continue;
                }
                if (!in_array($name, $valid)) {
                    $context->logWarning(sprintf("%s: permission does not exists, ignoring", $permission->getPath()));
                    continue;
                }

                $rolePermissions[] = $name;
            }
        }

        $role = (object)$object;
        user_role_save($role);

        if ($rolePermissions) {
            user_role_revoke_permissions($role->rid, $valid);
            user_role_grant_permissions($role->rid, $rolePermissions);
        }
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof RoleNode;
    }
}
