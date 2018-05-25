#!/bin/bash

# This script expects to be invoked from the gax-php root

set -e

ROOT_DIR=$(pwd)
DOC_OUTPUT_DIR=${ROOT_DIR}/tmp_gh-pages
INDEX_FILE=${DOC_OUTPUT_DIR}/index.html
PROTO_CLIENT_PHP_DIR=${ROOT_DIR}/proto-client-php
SAMI_EXECUTABLE=${ROOT_DIR}/vendor/sami/sami/sami.php

SAMI_CONFIG=${ROOT_DIR}/dev/src/Docs/sami-current-version-config.php

read -r -d '' UPDATED_INDEX_FILE << EOF
<html><head><script>window.location.replace('/gax-php/${TRAVIS_TAG}/' + location.hash.substring(1))</script></head><body></body></html>
EOF

if [ -d "${PROTO_CLIENT_PHP_DIR}" ]; then
  echo ERROR: proto-client-php directory already exists
  exit 1
fi
git clone https://github.com/googleapis/proto-client-php.git ${PROTO_CLIENT_PHP_DIR}

if [[ ! -z ${TRAVIS_TAG} ]]; then
  FILE_VERSION="$(cat ${ROOT_DIR}/VERSION)"
  if [ ${FILE_VERSION} != ${TRAVIS_TAG} ]; then
    echo ERROR: mismatched version and tag
    echo Travis tag: ${TRAVIS_TAG}
    echo VERSION file: ${FILE_VERSION}
    exit 1
  fi
  SAMI_CONFIG=${ROOT_DIR}/dev/src/Docs/sami-current-version-config.php
  php ${SAMI_EXECUTABLE} update ${SAMI_CONFIG} -v
  cat ${UPDATED_INDEX_FILE} > ${INDEX_FILE}
else
  SAMI_CONFIG=${ROOT_DIR}/dev/src/Docs/sami-master-config.php
  php ${SAMI_EXECUTABLE} update ${SAMI_CONFIG} -v
fi

# Clean up after doc gen completes
rm -rf ${PROTO_CLIENT_PHP_DIR}
