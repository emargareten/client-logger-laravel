<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Logger Class
    |--------------------------------------------------------------------------
    |
    | This configuration option sets the logger class that will be used to
    | log the HTTP client requests. The logger class must implement the
    | ClientLoggerInterface, by default it uses DefaultClientLogger.
    |
    */
    'logger' => \Emargareten\ClientLogger\DefaultClientLogger::class,

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the channel that the logger will use
    | to log the HTTP client requests. If this option set to null the
    | default channel configured in config/logging.php will be used.
    |
    */
    'channel' => env('HTTP_CLIENT_LOGGER_CHANNEL'),

    /*
     | --------------------------------------------------------------------------
     | Log Levels
     | --------------------------------------------------------------------------
     | Set the log level for different HTTP status codes. The log level is determined
     | by the response status code. Each status code can be set to a specific log
     | level (e.g. ['404' => 'critical']) or use a range (2xx, 3xx, 4xx, 5xx).
     | If the value for a status code is null, the log for that status
     | code/range won't be logged.
     */
    'level' => [
        '2xx' => 'info',
        '3xx' => 'info',
        '4xx' => 'error',
        '5xx' => 'error',
    ],

    /*
     | --------------------------------------------------------------------------
     | Default Log Message
     | --------------------------------------------------------------------------
     | This configuration option sets the default message that will be used when
     | logging the HTTP client requests and responses. This message is usually
     | overridden at runtime by the logger class.
     */
    'message' => 'HTTP Client Request',

    /*
     * --------------------------------------------------------------------------
     * Hidden Headers / Parameters
     * --------------------------------------------------------------------------
     * This configuration option sets the headers that will be masked in the
     * request log with asterisks. This is useful for hiding sensitive
     * information like passwords, tokens, etc.
     */
    'hidden_request_headers' => [
        'authorization',
        'php-auth-pw',
    ],

    'hidden_request_params' => [
        'password',
        'password_confirmation',
    ],

    'hidden_response_headers' => [

    ],

    'hidden_response_params' => [

    ],

    /*
     |--------------------------------------------------------------------------
     | Content Words Limit
     | --------------------------------------------------------------------------
     | This configuration option sets the maximum number of words that will be
     | logged when response content is text. Set this to null to log the
     | entire response content.
     */
    'content_words_limit' => 100,
];
