<?php
/*
 * Copyright 2018, Google Inc.
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

use Sami\Sami;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

// Before continuing, verify that we are running PHP 7 or above
if (version_compare(phpversion(), '7', '<')) {
    throw new RuntimeException("PHP must be >= 7.0 to build docs, found version " . phpversion());
}

$gaxRootDir = realpath(__DIR__ . '/../..');
$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('GPBMetadata')
    ->in($dir = "$gaxRootDir/src")
    ->in("$gaxRootDir/proto-client-php/src")
;

$versions = GitVersionCollection::create($dir)
    ->addFromTags("0.20.0")
    ->addFromTags("0.20.1")
    ->addFromTags("0.21.0")
    ->addFromTags("0.21.1")
    ->addFromTags("0.21.2")
    ->addFromTags("0.22.0")
    ->addFromTags("0.22.1")
    ->addFromTags("0.23.0")
    ->addFromTags("0.24.0")
    ->addFromTags("0.25.0")
    ->addFromTags("0.26.0")
    ->addFromTags("0.27.0")
    ->addFromTags("0.28.0")
    ->addFromTags("0.29.0")
    ->addFromTags("0.30.0")
    ->addFromTags("0.31.0")
    ->addFromTags("0.31.1")
    ->addFromTags("0.31.3")
    ->addFromTags("0.32.0")
    ->addFromTags("0.33.0")
    ->add('master', 'master branch')
;

return new Sami($iterator, array(
    'title'                => 'Google ApiCore and Proto Client PHP',
    'versions'             => $versions,
    'build_dir'            => "$gaxRootDir/tmp_gh-pages/%version%",
    'cache_dir'            => "$gaxRootDir/cache/%version%",
    'default_opened_level' => 1,
));
