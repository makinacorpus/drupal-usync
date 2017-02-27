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
                $context->getLogger()->logCritical(sprintf("%s: name attribute is not a string", $node->getPath()));
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
            $context->getLogger()->logCritical(sprintf("%s: does not exists", $node->getPath()));
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

    private function writeRole($role)
    {
        $role->name = trim($role->name);

        if (!isset($role->weight)) {
            $role->weight = db_query("SELECT MAX(weight) + 1 FROM {role}")->fetchField();
        }

        module_invoke_all('user_role_presave', $role);

        if (isset($role->rid)) {
            drupal_write_record('role', $role, 'rid');
            module_invoke_all('user_role_update', $role);
        } else {
            drupal_write_record('role', $role);
            module_invoke_all('user_role_insert', $role);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processInheritance(NodeInterface $node, NodeInterface $parent, Context $context, $dirtyAllowed = false)
    {
        // For roles, we are only going to inherit from permissions and merge
        // them into our own object.
        if (!$parent->hasChild('permission')) {
            return $context->getLogger()->log(sprintf("%s: parent %s has no permissions to inherit from", $node->getPath(), $parent->getPath()));
        }

        $node->getChild('permission')->mergeWith($parent->getChild('permission'));
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

            $valid = [];
            foreach (module_implements('permission') as $module) {
                $modulePermissions = module_invoke($module, 'permission');
                if ($modulePermissions) {
                    foreach (array_keys($modulePermissions) as $permission) {
                        $valid[$permission] = $module;
                    }
                }
            }

            foreach ($node->getChild('permission')->getChildren() as $permission) {
                $name = $permission->getValue();

                if (!is_string($name)) {
                    $context->getLogger()->logWarning(sprintf("%s: permission is not a string value, ignoring", $permission->getPath()));
                    continue;
                }
                if (!isset($valid[$name])) {
                    $context->getLogger()->logWarning(sprintf("%s: permission does not exists, ignoring", $permission->getPath()));
                    continue;
                }

                $rolePermissions[$name] = $valid[$name];
            }
        }

        $role = (object)$object;
        $this->writeRole($role);

        if ($rolePermissions) {
            db_query("DELETE FROM {role_permission} WHERE rid = ?", [$role->rid]);
            $q = db_insert('role_permission')->fields(['rid', 'permission', 'module']);
            foreach ($rolePermissions as $permission => $module) {
                $q->values([$role->rid, $permission, $module]);
            }
            $q->execute();
            //user_role_revoke_permissions($role->rid, $valid);
            //user_role_grant_permissions($role->rid, $rolePermissions);
            drupal_static_reset('user_access');
            drupal_static_reset('user_role_permissions');
        }
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof RoleNode;
    }
}
