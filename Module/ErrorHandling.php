<?php
namespace ErrorHandling;

require_once dirname(__FILE__) . "/Debug.php";

$_SEVERITY_TO_STRING = [
    E_ERROR               => 'E_ERROR',
    E_WARNING             => 'E_WARNING',
    E_PARSE               => 'E_PARSE',
    E_NOTICE              => 'E_NOTICE',
    E_CORE_ERROR          => 'E_CORE_ERROR',
    E_CORE_WARNING        => 'E_CORE_WARNING',
    E_COMPILE_ERROR       => 'E_COMPILE_ERROR',
    E_COMPILE_WARNING     => 'E_COMPILE_WARNING',
    E_USER_ERROR          => 'E_USER_ERROR',
    E_USER_WARNING        => 'E_USER_WARNING',
    E_USER_NOTICE         => 'E_USER_NOTICE',
    E_STRICT              => 'E_STRICT',
    E_RECOVERABLE_ERROR   => 'E_RECOVERABLE_ERROR',
    E_DEPRECATED          => 'E_DEPRECATED',
    E_USER_DEPRECATED     => 'E_USER_DEPRECATED',
];

function StyledErrorHandler($severity, $message, $file, $line, array $context) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) { return false;}
    
    global $_SEVERITY_TO_STRING;
    $severityString = $_SEVERITY_TO_STRING[$severity] ?? 'E_UNKNOWN';

    OutputDebugLog($severity, $message, $file, $line, $context);
    echo "<div style='color: #000; background-color: #ff7777bf; border: 1px solid #dc6363; font-size: 90%; margin: 0 0 .5em; padding: .4em; overflow: hidden; border-radius: 5px;'><b>{$severityString}</b>: {$message} in <b>{$file}</b> on line <b>{$line}</b></div>";
}

function PlainErrorHandler($severity, $message, $file, $line, array $context) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) { return false;}
    
    global $_SEVERITY_TO_STRING;
    $severityString = $_SEVERITY_TO_STRING[$severity] ?? 'E_UNKNOWN';

    OutputDebugLog($severity, $message, $file, $line, $context);
    echo "<br><b>{$severityString}</b>: {$message} in <b>{$file}</b> on line <b>{$line}</b><br>";
}

function OutputDebugLog($severity, $message, $file, $line, array $context) {
    global $_SEVERITY_TO_STRING;
    $severityString = $_SEVERITY_TO_STRING[$severity] ?? 'E_UNKNOWN';
    \Debug::LogError("
RuntimeError Occured:
    Severity: {$severityString}
    Message : {$message}
    File    : {$file}
    Line    : {$line}
");
}

function SeverityToString($severity) {
    switch($severity)
    {
        case E_ERROR:               return 'E_ERROR';
        case E_WARNING:             return 'E_WARNING';
        case E_PARSE:               return 'E_PARSE';
        case E_NOTICE:              return 'E_NOTICE';
        case E_CORE_ERROR:          return 'E_CORE_ERROR';
        case E_CORE_WARNING:        return 'E_CORE_WARNING';
        case E_COMPILE_ERROR:       return 'E_COMPILE_ERROR';
        case E_COMPILE_WARNING:     return 'E_COMPILE_WARNING';
        case E_USER_ERROR:          return 'E_USER_ERROR';
        case E_USER_WARNING:        return 'E_USER_WARNING';
        case E_USER_NOTICE:         return 'E_USER_NOTICE';
        case E_STRICT:              return 'E_STRICT';
        case E_RECOVERABLE_ERROR:   return 'E_RECOVERABLE_ERROR';
        case E_DEPRECATED:          return 'E_DEPRECATED';
        case E_USER_DEPRECATED:     return 'E_USER_DEPRECATED';
    }
    return 'E_UNKNOWN';
}