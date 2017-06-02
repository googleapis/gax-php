<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/logging/type/log_severity.proto

namespace Google\Logging\Type;

/**
 * <pre>
 * The severity of the event described in a log entry, expressed as one of the
 * standard severity levels listed below.  For your reference, the levels are
 * assigned the listed numeric values. The effect of using numeric values other
 * than those listed is undefined.
 * You can filter for log entries by severity.  For example, the following
 * filter expression will match log entries with severities `INFO`, `NOTICE`,
 * and `WARNING`:
 *     severity &gt; DEBUG AND severity &lt;= WARNING
 * If you are writing log entries, you should map other severity encodings to
 * one of these standard levels. For example, you might map all of Java's FINE,
 * FINER, and FINEST levels to `LogSeverity.DEBUG`. You can preserve the
 * original severity level in the log entry payload if you wish.
 * </pre>
 *
 * Protobuf enum <code>google.logging.type.LogSeverity</code>
 */
class LogSeverity
{
    /**
     * <pre>
     * (0) The log entry has no assigned severity level.
     * </pre>
     *
     * <code>DEFAULT = 0;</code>
     */
    const DEFAULT = 0;
    /**
     * <pre>
     * (100) Debug or trace information.
     * </pre>
     *
     * <code>DEBUG = 100;</code>
     */
    const DEBUG = 100;
    /**
     * <pre>
     * (200) Routine information, such as ongoing status or performance.
     * </pre>
     *
     * <code>INFO = 200;</code>
     */
    const INFO = 200;
    /**
     * <pre>
     * (300) Normal but significant events, such as start up, shut down, or
     * a configuration change.
     * </pre>
     *
     * <code>NOTICE = 300;</code>
     */
    const NOTICE = 300;
    /**
     * <pre>
     * (400) Warning events might cause problems.
     * </pre>
     *
     * <code>WARNING = 400;</code>
     */
    const WARNING = 400;
    /**
     * <pre>
     * (500) Error events are likely to cause problems.
     * </pre>
     *
     * <code>ERROR = 500;</code>
     */
    const ERROR = 500;
    /**
     * <pre>
     * (600) Critical events cause more severe problems or outages.
     * </pre>
     *
     * <code>CRITICAL = 600;</code>
     */
    const CRITICAL = 600;
    /**
     * <pre>
     * (700) A person must take an action immediately.
     * </pre>
     *
     * <code>ALERT = 700;</code>
     */
    const ALERT = 700;
    /**
     * <pre>
     * (800) One or more systems are unusable.
     * </pre>
     *
     * <code>EMERGENCY = 800;</code>
     */
    const EMERGENCY = 800;
}

