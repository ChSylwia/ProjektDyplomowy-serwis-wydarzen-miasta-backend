#!/bin/bash
mkdir -p config/jwt
php bin/console lexik:jwt:generate-keypair --overwrite
