<?php
/**
 * Copyright 2023 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\ApiCore\Traits;

trait TimeHelperTrait {
    const FORMAT = 'Y-m-d\TH:i:s.u\Z';
    const FORMAT_NO_MS = 'Y-m-d\TH:i:s\Z';
    const FORMAT_INTERPOLATE = 'Y-m-d\TH:i:s.%\s\Z';

    /**
     * Format a gRPC timestamp to match the format returned by the REST API.
     *
     * @param array $timestamp
     * @return string
     */
    private function formatTimestampFromApi(array $timestamp)
    {
        $timestamp += [
            'seconds' => 0,
            'nanos' => 0
        ];

        $dt = $this->createDateTimeFromSeconds($timestamp['seconds']);

        return $this->formatTimeAsString($dt, $timestamp['nanos']);
    }

    /**
     * Create a DateTimeImmutable instance from a UNIX timestamp (i.e. seconds since epoch).
     *
     * @param int $seconds The unix timestamp.
     * @return \DateTimeImmutable
     */
    private function createDateTimeFromSeconds($seconds)
    {
        return \DateTimeImmutable::createFromFormat(
            'U',
            (string) $seconds,
            new \DateTimeZone('UTC')
        );
    }

    /**
     * Create a Timestamp string in an API-compatible format.
     *
     * @param \DateTimeInterface $dateTime The date time object.
     * @param int|null $ns The number of nanoseconds. If null, subseconds from
     *        $dateTime will be used instead.
     * @return string
     */
    private function formatTimeAsString(\DateTimeInterface $dateTime, $ns)
    {
        $dateTime = $dateTime->setTimeZone(new \DateTimeZone('UTC'));
        if ($ns === null) {
            return $dateTime->format(self::FORMAT);
        } else {
            return sprintf(
                $dateTime->format(self::FORMAT_INTERPOLATE),
                $this->convertNanoSecondsToFraction($ns)
            );
        }
    }

    /**
     * Convert subseconds, expressed as a decimal to nanoseconds.
     *
     * @param int|string $subseconds Provide value as a whole number (i.e.
     *     provide 0.1 as 1).
     * @return int
     */
    private function convertFractionToNanoSeconds($subseconds)
    {
        return (int) str_pad($subseconds, 9, '0', STR_PAD_RIGHT);
    }
}