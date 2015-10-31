<?php

namespace USync\Loading;

use USync\AST\NodeInterface;

/**
 * Specific interface for loaders that allows the loader to give explicit and
 * human readable information about the given node.
 *
 * In all methods in there, you are asked to call the Drupal t() translation
 * function whenever necessary, this is the responsability of the loader.
 *
 * All methods may return null.
 */
interface VerboseLoaderInterface
{
    /**
     * Get loader human name
     *
     * @return string
     */
    public function getLoaderName();

    /**
     * Get loader description, what it does, etc... will be displayed inside
     * the node information pane
     *
     * @return string
     */
    public function getLoaderDescription();

    /**
     * Get node human name
     *
     * @return string
     */
    public function getNodeName(NodeInterface $node);

    /**
     * From the current node, get information
     *
     * @return string[]
     *   Keys are headers, values are textual information
     *   If a single or more keys are numeric, the text will be displayed as-is
     *   When the value is an array, consider that the input is sub-section for
     *   display organisation
     */
    public function getNodeInformation(NodeInterface $node);
}
