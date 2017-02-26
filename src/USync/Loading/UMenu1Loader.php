<?php

namespace USync\Loading;

use MakinaCorpus\Umenu\DrupalMenuStorage;
use USync\AST\Drupal\MenuNode;
use USync\AST\NodeInterface;
use USync\Context;

/**
 * UMenu v1.x loader.
 */
class UMenu1Loader extends AbstractLoader
{
    /** @var DrupalMenuStorage */
    private $storage;

    static private $defaults = [
        'name'   => '',
        'title'       => 'node_content',
        'description' => null,
    ];

    /**
     * UMenuLoader constructor.
     *
     * @param \MakinaCorpus\Umenu\DrupalMenuStorage $storage
     */
    public function __construct(DrupalMenuStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'menu';
    }

    /**
     * {@inheritdoc}
     */
    public function exists(NodeInterface $node, Context $context)
    {
        return $this->storage->exists($node->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getExistingObject(NodeInterface $node, Context $context)
    {
        $existing = $this->storage->load($node->getName());

        if (!$existing) {
            $context->logCritical(sprintf("%s: does not exists", $node->getPath()));
        }

        return array_intersect_key($existing, self::$defaults) + self::$defaults;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        if ($menu = $this->getExistingObject($node, $context)) {
            $this->storage->delete($menu);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        // @todo
        throw new \Exception("Not implemented (yet) - sorry dude.");
    }

    /**
     * {@inheritdoc}
     */
    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        /* @var $node \USync\AST\Drupal\MenuNode */

        $object = ['name' => $node->getName()];
        if ($node->hasChild('name')) {
            $object['title'] = (string)$node->getChild('name')->getValue();
        }
        if ($node->hasChild('description')) {
            $object['description'] = (string)$node->getChild('description')->getValue();
        }

        $object += self::$defaults;

        if ($node->shouldDropOnUpdate()) {
            $context->log(sprintf("%s: deleting menu and children", $node->getPath()));
            $this->storage->delete($node->getName());
        }

        if ($this->storage->exists($node->getName())) {
            $this->storage->update($node->getName(), $object);
        } else {
            $this->storage->create($node->getName(), $object);
        }

        return $node->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess(NodeInterface $node)
    {
        return $node instanceof MenuNode;
    }
}
