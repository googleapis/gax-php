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

namespace Google\ApiCore;

/**
 * Encapsulates the call settings for an API call.
 */
class CallSettings
{
    use ArrayTrait;

    private $retrySettings;
    private $userHeaders;
    private $transportOptions;
    private $timeoutMillis;

    /**
     * Constructs an array mapping method names to CallSettings.
     *
     * @param string $serviceName
     *     The fully-qualified name of this service, used as a key into
     *     the client config file.
     * @param array $clientConfig
     *     An array parsed from the standard API client config file.
     * @param array $retryingOverrides
     *     A dictionary of method names to RetrySettings that
     *     override those specified in $clientConfig.
     * @throws ValidationException
     * @return CallSettings[] $callSettings
     */
    public static function load(
        $serviceName,
        $clientConfig,
        $retryingOverrides
    ) {
        $callSettings = [];

        $serviceConfig = $clientConfig['interfaces'][$serviceName];
        foreach ($serviceConfig['methods'] as $methodName => $methodConfig) {
            $phpMethodKey = lcfirst($methodName);
            $retrySettings = self::constructRetry(
                $methodConfig,
                $serviceConfig['retry_codes'],
                $serviceConfig['retry_params']
            );
            if (isset($retryingOverrides[$phpMethodKey])) {
                $retrySettingsOverride = $retryingOverrides[$phpMethodKey];
                if (is_array($retrySettingsOverride)) {
                    $retrySettings = $retrySettings->with($retrySettingsOverride);
                } elseif ($retrySettingsOverride instanceof RetrySettings) {
                    $retrySettings = $retrySettingsOverride;
                } else {
                    throw new ValidationException(
                        "Unexpected value in retryingOverrides for method " .
                        "$phpMethodKey: $retrySettingsOverride"
                    );
                }
            }

            $callSettings[$phpMethodKey] = new CallSettings([
                'timeoutMillis' => $methodConfig['timeout_millis'],
                'retrySettings' => $retrySettings
            ]);
        }
        return $callSettings;
    }

    private static function constructDefaultRetrySettings()
    {
        return new RetrySettings([
            'retriesEnabled' => false,
            'initialRetryDelayMillis' => 100,
            'retryDelayMultiplier' => 1.3,
            'maxRetryDelayMillis' => 60000,
            'initialRpcTimeoutMillis' => 20000,
            'rpcTimeoutMultiplier' => 1,
            'maxRpcTimeoutMillis' => 20000,
            'totalTimeoutMillis' => 600000,
            'retryableCodes' => []]);
    }

    private static function constructRetry(
        $methodConfig,
        $retryCodes,
        $retryParams
    ) {
        if (empty($methodConfig['retry_codes_name']) || empty($methodConfig['retry_params_name'])) {
            // Construct a RetrySettings object with retries disabled
            return self::constructDefaultRetrySettings();
        }

        $retryCodesName = $methodConfig['retry_codes_name'];
        $retryParamsName = $methodConfig['retry_params_name'];

        if (!array_key_exists($retryCodesName, $retryCodes)) {
            throw new ValidationException("Invalid retry_codes_name setting: '$retryCodesName'");
        }
        if (!array_key_exists($retryParamsName, $retryParams)) {
            throw new ValidationException("Invalid retry_params_name setting: '$retryParamsName'");
        }

        foreach ($retryCodes[$retryCodesName] as $status) {
            if (!ApiStatus::isValidStatus($status)) {
                throw new ValidationException("Invalid status code: '$status'");
            }
        }

        $retryParameters = self::convertArrayFromSnakeCase($retryParams[$retryParamsName]);

        $retrySettings = $retryParameters + [
            'retryableCodes' => $retryCodes[$retryCodesName],
        ];

        return new RetrySettings($retrySettings);
    }

    /**
     * Construct an instance.
     *
     * @param array $settings {
     *     Optional.
     *
     *     @type RetrySettings $retrySettings
     *           Retry settings to use for this method.
     *     @type array $userHeaders
     *           An array of headers to be included in the request.
     *     @type array $transportOptions
     *           Transport specific options. A key of `grpc` or `rest` containing
     *           the approriate options should be supplied.
     *     @type int $timeoutMillis The timeout for the request.
     * }
     */
    public function __construct(array $settings = [])
    {
        $this->retrySettings = $this->pluck('retrySettings', $settings, false);
        $this->userHeaders = $this->pluck('userHeaders', $settings, false);
        $this->transportOptions = $this->pluck('transportOptions', $settings, false);
        $this->timeoutMillis = $this->pluck('timeoutMillis', $settings, false);
    }

    /**
     * Creates a new instance of CallSettings that updates the settings in the existing instance
     * with the settings specified in the $settings parameter.
     *
     * @param array $settings {
     *     Optional.
     *
     *     @type RetrySettings $retrySettings
     *           Retry settings to use for this method.
     *     @type array $userHeaders
     *           An array of headers to be included in the request.
     *     @type array $transportOptions
     *           Transport specific options. A key of `grpc` or `rest` containing
     *           the approriate options should be supplied.
     *     @type int $timeoutMillis The timeout for the request.
     * }
     * @return CallSettings
     */
    public function with(array $settings)
    {
        $existingSettings = $this->toArray();
        return new CallSettings($settings + $existingSettings);
    }

    /**
     * @return RetrySettings
     */
    public function getRetrySettings()
    {
        if (empty($this->retrySettings)) {
            // always return RetrySettings
            return self::constructDefaultRetrySettings();
        }
        return $this->retrySettings;
    }

    /**
     * @return array User headers
     */
    public function getUserHeaders()
    {
        return $this->userHeaders;
    }

    /**
     * @return array Transport options
     */
    public function getTransportOptions()
    {
        return $this->transportOptions;
    }

    /**
     * @return int Timeout milliseconds
     */
    public function getTimeoutMillis()
    {
        return $this->timeoutMillis;
    }

    /**
     * Returns a new CallSettings merged from this and another CallSettings object.
     *
     * @param CallSettings $otherSettings
     *     A CallSettings whose values override those in this object. If
     *     null, then a copy of this object is returned.
     * @return CallSettings
     */
    public function merge(CallSettings $otherSettings = null)
    {
        if (is_null($otherSettings)) {
            return $this->with([]);
        }
        return $this->with($otherSettings->toArray());
    }

    /**
     * @return array
     */
    private function toArray()
    {
        return array_filter([
            'retrySettings' => $this->retrySettings,
            'userHeaders' => $this->getUserHeaders(),
            'transportOptions' => $this->getTransportOptions(),
            'timeoutMillis' => $this->getTimeoutMillis(),
        ]);
    }

    private static function convertArrayFromSnakeCase($settings)
    {
        $camelCaseSettings = [];
        foreach ($settings as $key => $value) {
            $camelCaseKey = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            $camelCaseSettings[lcfirst($camelCaseKey)] = $value;
        }
        return $camelCaseSettings;
    }
}
