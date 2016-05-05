<?php

namespace USync\Logger;

class DrushLogger extends DefaultLogger
{
    /**
     * Log message
     *
     * @param string $message
     * @param int $level
     */
    public function log($message, $level = E_USER_NOTICE, $willBreak = false)
    {
        if ($willBreak) {
            trigger_error($message, $level);

            return;
        }

        $prefix = '';
        $type = null;

        switch ($level) {

            case E_USER_NOTICE:
                $type = 'notice';
                break;

            case E_USER_WARNING:
                $type = 'warning';
                $prefix = 'WARN: ';
                break;

            case E_USER_ERROR:
                $type = 'error';
                $prefix = 'ERR: ';
                break;
        }

        // drush_log($message, $type);
        drush_print($prefix . $message);
    }
}
