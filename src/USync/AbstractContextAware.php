<?php

namespace USync;

abstract class AbstractContextAware implements ContextAwareInterface
{
    /**
     * @var \USync\Context
     */
    protected $context;

    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }
}
