#!/bin/bash

# This script expects to be invoked from the gax-php root

set -e

ROOT_DIR=$(pwd)
PROTO_CLIENT_PHP_DIR=${ROOT_DIR}/proto-client-php
SAMI_EXECUTABLE=${ROOT_DIR}/vendor/sami/sami/sami.php
SAMI_CONFIG=${ROOT_DIR}/dev/src/doc-config.php

if [ -d "${PROTO_CLIENT_PHP_DIR}" ]; then
  echo ERROR: proto-client-php directory already exists
  exit 1
fi

git clone https://github.com/googleapis/proto-client-php.git ${PROTO_CLIENT_PHP_DIR}

php ${SAMI_EXECUTABLE} update ${SAMI_CONFIG}

# Clean up after doc gen completes
rm -rf ${PROTO_CLIENT_PHP_DIR}