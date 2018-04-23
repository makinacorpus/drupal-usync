<?php

namespace USync\Loading;


use USync\AST\Drupal\ImageStyleNode;
use USync\AST\NodeInterface;
use USync\Context;

class ImageStyleLoader extends AbstractLoader
{
    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'image_style';
    }

    /**
     * Return the filter name depending on the tree structure
     *
     * @param NodeInterface $node
     * @param Context $context
     * @return array|mixed|string
     */
    protected function getStyleName(NodeInterface $node, Context $context)
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
     * {@inheritDoc}
     */
    public function exists(NodeInterface $node, Context $context)
    {
        return image_style_load($node->getName()) !== false;
    }

    /**
     * Returns a fully loaded input filter structure.
     *
     * @param NodeInterface $node
     * @param Context $context
     * @return bool|\stdClass
     */
    protected function loadExistingImageStyle(NodeInterface $node, Context $context)
    {
        return image_style_load($node->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getExistingObject(NodeInterface $node, Context $context)
    {
        return $this->loadExistingImageStyle($node, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function canProcess(NodeInterface $node)
    {
        return $node instanceof ImageStyleNode;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        return image_style_delete($this->getExistingObject($node, $context));
    }

    /**
     * {@inheritDoc}
     */
    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        if (($style = $this->loadExistingImageStyle($node, $context)) === false) {
            $object = [
                'name' => $this->getStyleName($node, $context),
            ];
            $style = image_style_save($object);
        }
        $weight = 0;

        // Handle effects as well.
        if ($node->hasChild('effects')) {

            $valid = array_keys(image_effect_definitions());
            $index = 0;

            foreach ($node->getChild('effects')->getChildren() as $effectNode) {
                $name = $effectNode->getName();
                if (is_numeric($name)) {
                    if (array_key_exists('type', $effectNode->getValue())) {
                        $name = $effectNode->getValue()['type'];
                    } else {
                        $context->logWarning(sprintf("%s: effect has no type, ignoring", $effectNode->getPath()));
                        continue;
                    }
                }

                if (!in_array($name, $valid)) {
                    $context->logWarning(sprintf("%s: effect does not exists, ignoring", $effectNode->getPath()));
                    continue;
                }

                $effect = [];
                // FIXME Handle effects more appropriately
                if (isset($style['effects']) && count($style['effects']) > $index) {
                    $chunk = array_slice($style['effects'], $index++, 1);
                    $old_effect = reset($chunk);
                    if ($old_effect['name'] == $name) {
                        $effect = $old_effect;
                    } else {
                        // Structure change, better delete all old effects.
                        foreach (array_values($style['effects']) as $effect) {
                            image_effect_delete($effect);
                        }
                    }
                }

                $effect['name'] = $name;
                $effect['data'] = $effectNode->getValue();
                if (isset($effect['data']['type'])) {
                    unset($effect['data']['type']);
                }
                $effect['isid'] = $style['isid'];
                $effect['weight'] = $weight++;
                image_effect_save($effect);
            }
        }
    }
}
