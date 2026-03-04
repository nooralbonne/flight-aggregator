<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Logger extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Error Logging Threshold
     * --------------------------------------------------------------------------
     *
     * You can enable error logging by setting a threshold over zero. The
     * threshold determines which errors are logged.
     *
     *   0 = Disables logging, Error logging TURNED OFF
     *   1 = Emergency Messages  - System is unusable
     *   2 = Alert Messages      - Action Must Be Taken Immediately
     *   3 = Critical Messages   - Application component unavailable, unexpected exception.
     *   4 = Runtime Errors      - Don't always need immediate action, but should be monitored
     *   5 = Warnings            - Exceptional occurrences that are not errors
     *   6 = Notices             - Normal but significant events
     *   7 = Info Messages       - Interesting events, like user logging in, etc.
     *   8 = Debug Messages      - Detailed debug information
     *   9 = All Messages
     *
     * You can also pass an array with threshold levels to show only specified
     * error types:
     *
     *     $threshold = [1, 2, 3, 4, 5]; // Only show Emergency through Warnings
     *
     * @var int|list<int>
     */
    public $threshold = 9; // Log everything in development

    /**
     * --------------------------------------------------------------------------
     * Date Format for Logs
     * --------------------------------------------------------------------------
     *
     * Each item that is logged has an associated date. You can use PHP date
     * codes to set your own date formatting
     */
    public string $dateFormat = 'Y-m-d H:i:s';

    /**
     * --------------------------------------------------------------------------
     * Log Handlers
     * --------------------------------------------------------------------------
     *
     * The logging system supports multiple handlers that act as, well, handlers
     * for the log. By default, the file-based logging is enabled. Each handler
     * can have a threshold that determines the minimum log level it will handle.
     *
     * @var array<string, array<string, array<int, int>|bool|string>>
     */
    public array $handlers = [
        'CodeIgniter\Log\Handlers\FileHandler' => [
            'handles' => [
                'critical',
                'alert',
                'emergency',
                'debug',
                'error',
                'info',
                'notice',
                'warning',
            ],
            'path'    => WRITEPATH . 'logs/',
            'filePermissions' => 0644,
        ],
    ];
}
