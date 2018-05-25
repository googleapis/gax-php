#!/bin/bash

# This script expects to be invoked from the gax-php root

set -e

ROOT_DIR=$(pwd)
DOC_OUTPUT_DIR=${ROOT_DIR}/tmp_gh-pages
INDEX_FILE=${DOC_OUTPUT_DIR}/index.html
VERSION_FILE=${ROOT_DIR}/VERSION
PROTO_CLIENT_PHP_DIR=${ROOT_DIR}/proto-client-php
SAMI_EXECUTABLE=${ROOT_DIR}/vendor/sami/sami/sami.php

UPDATED_INDEX_FILE=$(cat << EndOfMessage
"<html><head><script>window.location.replace('/gax-php/${TRAVIS_TAG}/' + location.hash.substring(1))</script></head><body></body></html>"
EndOfMessage
)

if [ -d "${PROTO_CLIENT_PHP_DIR}" ]; then
  echo ERROR: proto-client-php directory already exists
  exit 1
fi

if [[ ! -z ${TRAVIS_TAG} ]]; then
  VERSION_FILE_CONTENTS="$(cat ${VERSION_FILE})"
  if [ ${VERSION_FILE_CONTENTS} != ${TRAVIS_TAG} ]; then
    echo ERROR: mismatched version and tag
    echo Travis tag: ${TRAVIS_TAG}
    echo VERSION file: ${VERSION_FILE_CONTENTS}
    exit 1
  fi
  cat ${UPDATED_INDEX_FILE} > ${INDEX_FILE}
  SAMI_CONFIG=${ROOT_DIR}/dev/src/Docs/sami-current-version-config.php
else
  SAMI_CONFIG=${ROOT_DIR}/dev/src/Docs/sami-master-config.php
fi

git clone https://github.com/googleapis/proto-client-php.git ${PROTO_CLIENT_PHP_DIR}
php ${SAMI_EXECUTABLE} update ${SAMI_CONFIG} -v

# Clean up after doc gen completes
rm -rf ${PROTO_CLIENT_PHP_DIR}
