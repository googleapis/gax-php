<?php
/*
 * Copyright 2022 Google LLC
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

use Google\ApiCore\ValidationException;

trait ResourceHelperTrait {
    private static $templateMap;

    public static abstract function registerTemplates();

    private static function loadTemplates(string $configPath, string $serviceName)
    {
        if (!is_null(self::$templateMap)) {
            return;
        }

        $descriptors = require($configPath);
        $templates = $descriptors['interfaces'][$serviceName]['templateMap'] ?? [];
        self::$templateMap = [];
        foreach ($templates as $name => $template) {
             self::$templateMap[$name] = new PathTemplate($template);
        }
    }

    private static function getTemplate($key)
    {
        if (is_null(self::$templateMap)) {
            self::registerTemplates();
        }
        return self::$templateMap[$key] ?? null;
    }

    public static function parseName($formattedName, $template = null)
    {
        if (is_null(self::$templateMap)) {
            self::registerTemplates();
        }
        if ($template) {
            if (!isset(self::$templateMap[$template])) {
                throw new ValidationException("Template name $template does not exist");
            }

            return self::$templateMap[$template]->match($formattedName);
        }

        foreach (self::$templateMap as $templateName => $pathTemplate) {
            try {
                return $pathTemplate->match($formattedName);
            } catch (ValidationException $ex) {
                // Swallow the exception to continue trying other path templates
            }
        }

        throw new ValidationException("Input did not match any known format. Input: $formattedName");
    }
}
