<?php
/*
 * Copyright 2016, Google Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *     * Neither the name of Google Inc. nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
namespace Google\GAX;

/**
 * TODO: thorough documentation
 *
 * The RetrySettings class holds parameters for retrying and timeout for RPCs.
 * This class can be passed as an optional parameter to RPC methods, or as part
 * of an optional array in the constructor of a client object.
 *
 * Constructing a RetrySettings object
 *
 * Example using custom settings:
 * ```
 * $retrySettings = new RetrySettings([
 *
 * ]);
 * ```
 *
 * Example modifying an existing RetrySettings object
 * ```
 * $retrySettings = $existingRetrySettings->with([
 *
 * ]);
 * ```
 *
 * Example modifying the default settings
 * ```
 * $retrySettings = RetrySettings::createDefault()->with([
 *     'retriesEnabled' => false,
 *     'noRetriesRpcTimeoutMillis' => 20000,
 * ]);
 * ```
 *
 * Example constructing a GAPIC client with retries disabled for a particular method:
 * ```
 * $client = new Client([
 *     'retrySettingsArray' => [
 *         'noRetriesMethod' => ['retriesEnabled' => false],
 *         'anotherMethod' => [
 *             'initialRpcTimeoutMillis' => 10000,
 *             'maxRpcTimeoutMillis' => 30000,
 *             'totalTimeoutMillis' => 60000,
 *         ],
 *     ],
 * ]);
 * ```
 *
 * Example of disabling retries for a single call:
 * ```
 * $result = $client->anotherMethod($arg, [
 *     'retrySettings' => ['retriesEnabled' => false]
 * ]);
 * ```
 *
 * Holds the parameters for retry and timeout logic with exponential backoff. Actual
 * implementation of the logic is elsewhere.
 *
 * The intent of these settings is to be used with a call to a remote server, which
 * could either fail (and return an error code) or not respond (and cause a timeout).
 * When there is a failure or timeout, the logic should keep trying until the total
 * timeout has passed.
 */
class RetrySettings
{
    use ValidationTrait;

    private $retriesEnabled;

    private $retryableCodes;

    private $initialRetryDelayMillis;
    private $retryDelayMultiplier;
    private $maxRetryDelayMillis;
    private $initialRpcTimeoutMillis;
    private $rpcTimeoutMultiplier;
    private $maxRpcTimeoutMillis;
    private $totalTimeoutMillis;

    private $noRetriesRpcTimeoutMillis;

    /**
     * Constructs an instance.
     *
     * @param array $settings {
     *     Required. Settings for configuring the retry behavior. All parameters are required except
     *     $retriesEnabled and $noRetriesRpcTimeoutMillis, which are optional and have defaults
     *     determined based on the other settings provided.
     *
     *     @type bool    $retriesEnabled Optional. Enables retries. If not specified, the value is
     *                   determined using the $retryableCodes setting. If $retryableCodes is empty,
     *                   then $retriesEnabled is set to false; otherwise, it is set to true.
     *     @type integer $noRetriesRpcTimeoutMillis Optional. The timeout of the rpc call to be used
     *                   if $retriesEnabled is false, in milliseconds. It not specified, the value
     *                   of $initialRpcTimeoutMillis is used.
     *     @type array   $retryableCodes The Status codes that are retryable.
     *     @type integer $initialRetryDelayMillis The initial delay of retry in milliseconds.
     *     @type integer $retryDelayMultiplier The exponential multiplier of retry delay.
     *     @type integer $maxRetryDelayMillis The max delay of retry in milliseconds.
     *     @type integer $initialRpcTimeoutMillis The initial timeout of rpc call in milliseconds.
     *     @type integer $rpcTimeoutMultiplier The exponential multiplier of rpc timeout.
     *     @type integer $maxRpcTimeoutMillis The max timout of rpc call in milliseconds.
     *     @type integer $totalTimeoutMillis The max accumulative timeout in total.
     * }
     */
    public function __construct($settings)
    {
        $this->validateNotNull($settings, [
            'initialRetryDelayMillis',
            'retryDelayMultiplier',
            'maxRetryDelayMillis',
            'initialRpcTimeoutMillis',
            'rpcTimeoutMultiplier',
            'maxRpcTimeoutMillis',
            'totalTimeoutMillis',
            'retryableCodes'
        ]);
        $this->initialRetryDelayMillis = $settings['initialRetryDelayMillis'];
        $this->retryDelayMultiplier = $settings['retryDelayMultiplier'];
        $this->maxRetryDelayMillis = $settings['maxRetryDelayMillis'];
        $this->initialRpcTimeoutMillis = $settings['initialRpcTimeoutMillis'];
        $this->rpcTimeoutMultiplier = $settings['rpcTimeoutMultiplier'];
        $this->maxRpcTimeoutMillis = $settings['maxRpcTimeoutMillis'];
        $this->totalTimeoutMillis = $settings['totalTimeoutMillis'];
        $this->retryableCodes = $settings['retryableCodes'];
        $this->retriesEnabled = array_key_exists('retriesEnabled', $settings)
            ? $settings['retriesEnabled']
            : (count($this->retryableCodes) > 0);
        $this->noRetriesRpcTimeoutMillis = array_key_exists('noRetriesRpcTimeoutMillis', $settings)
            ? $settings['noRetriesRpcTimeoutMillis']
            : $this->initialRpcTimeoutMillis;
    }

    /**
     * Creates a new instance of RetrySettings that updates the settings in the existing instance
     * with the settings specified in the $settings parameter.
     *
     * @param array $settings {
     *     Settings for configuring the retry behavior. All parameters are optional - all unset
     *     parameters will default to the value in the existing instance.
     *
     *     @type bool    $retriesEnabled Enables retries.
     *     @type integer $noRetriesRpcTimeoutMillis Optional. The timeout of the rpc call to be used
     *                   if $retriesEnabled is false, in milliseconds.
     *     @type array   $retryableCodes The Status codes that are retryable.
     *     @type integer $initialRetryDelayMillis The initial delay of retry in milliseconds.
     *     @type integer $retryDelayMultiplier The exponential multiplier of retry delay.
     *     @type integer $maxRetryDelayMillis The max delay of retry in milliseconds.
     *     @type integer $initialRpcTimeoutMillis The initial timeout of rpc call in milliseconds.
     *     @type integer $rpcTimeoutMultiplier The exponential multiplier of rpc timeout.
     *     @type integer $maxRpcTimeoutMillis The max timout of rpc call in milliseconds.
     *     @type integer $totalTimeoutMillis The max accumulative timeout in total.
     * }
     * @return RetrySettings
     */
    public function with($settings)
    {
        $existingSettings = [
            'initialRetryDelayMillis' => $this->getInitialRetryDelayMillis(),
            'retryDelayMultiplier' => $this->getRetryDelayMultiplier(),
            'maxRetryDelayMillis' => $this->getMaxRetryDelayMillis(),
            'initialRpcTimeoutMillis' => $this->getInitialRpcTimeoutMillis(),
            'rpcTimeoutMultiplier' => $this->getRpcTimeoutMultiplier(),
            'maxRpcTimeoutMillis' => $this->getMaxRpcTimeoutMillis(),
            'totalTimeoutMillis' => $this->getTotalTimeoutMillis(),
            'retryableCodes' => $this->getRetryableCodes(),
            'retriesEnabled' => $this->retriesEnabled(),
            'noRetriesRpcTimeoutMillis' => $this->getNoRetriesRpcTimeoutMillis(),
        ];
        return new RetrySettings($settings + $existingSettings);
    }

    /**
     * @return bool Returns true if retries are enabled, otherwise returns false.
     */
    public function retriesEnabled()
    {
        return $this->retriesEnabled;
    }

    /**
     * @return int The timeout of the rpc call to be used if $retriesEnabled is false, in
     *             milliseconds.
     */
    public function getNoRetriesRpcTimeoutMillis()
    {
        return $this->noRetriesRpcTimeoutMillis;
    }

    /**
     * @return int[] Status codes to retry
     */
    public function getRetryableCodes()
    {
        return $this->retryableCodes;
    }

    /**
     * @return int The initial retry delay in milliseconds. If $this->retriesEnabled()
     *             is false, this setting is unused.
     */
    public function getInitialRetryDelayMillis()
    {
        return $this->initialRetryDelayMillis;
    }

    /**
     * @return float The retry delay multiplier. If $this->retriesEnabled()
     *               is false, this setting is unused.
     */
    public function getRetryDelayMultiplier()
    {
        return $this->retryDelayMultiplier;
    }

    /**
     * @return int The maximum retry delay in milliseconds. If $this->retriesEnabled()
     *             is false, this setting is unused.
     */
    public function getMaxRetryDelayMillis()
    {
        return $this->maxRetryDelayMillis;
    }

    /**
     * @return int The initial rpc timeout in milliseconds. If $this->retriesEnabled()
     *             is false, this setting is used to set the timeout of the rpc.
     */
    public function getInitialRpcTimeoutMillis()
    {
        return $this->initialRpcTimeoutMillis;
    }

    /**
     * @return float The rpc timeout multiplier. If $this->retriesEnabled()
     *               is false, this setting is unused.
     */
    public function getRpcTimeoutMultiplier()
    {
        return $this->rpcTimeoutMultiplier;
    }

    /**
     * @return int The maximum rpc timeout in milliseconds. If $this->retriesEnabled()
     *             is false, this setting is unused - use totalTimeoutMillis to set the
     *             timeout in that case.
     */
    public function getMaxRpcTimeoutMillis()
    {
        return $this->maxRpcTimeoutMillis;
    }

    /**
     * @return int The total time in milliseconds to spend on the call, including all
     *             retry attempts and delays between attempts .If $this->retriesEnabled()
     *             is false, this setting is unused - use initialRpcTimeoutMillis to set
     *             the timeout for the rpc.
     */
    public function getTotalTimeoutMillis()
    {
        return $this->totalTimeoutMillis;
    }
}
