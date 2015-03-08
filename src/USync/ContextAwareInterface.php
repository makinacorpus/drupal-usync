<?php

namespace USync;

interface ContextAwareInterface
{
    /**
     * Set context
     *
     * @param \USync\Context $context
     */
    public function setContext(Context $context);

    /**
     * Get context
     *
     * @return \USync\Context
     */
    public function getContext();
}
