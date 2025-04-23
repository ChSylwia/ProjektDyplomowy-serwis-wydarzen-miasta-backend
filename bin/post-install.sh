
#!/usr/bin/env bash
set -euo pipefail

# Directory for our keys
JWT_DIR="config/jwt"

# Ensure directory exists
mkdir -p "$JWT_DIR"

# Paths
PRIVATE_KEY="$JWT_DIR/private.pem"
PUBLIC_KEY="$JWT_DIR/public.pem"

# Generate private key (4096 bits, AES-256 encrypt with your passphrase)
openssl genrsa \
  -out "$PRIVATE_KEY" \
  -aes256 \
  -passout pass:"${JWT_PASSPHRASE:?JWT_PASSPHRASE is not set}" \
  4096

# Extract public key from the private key
openssl rsa \
  -in "$PRIVATE_KEY" \
  -passin pass:"$JWT_PASSPHRASE" \
  -pubout \
  -out "$PUBLIC_KEY"

echo "✔ JWT keys generated in ${JWT_DIR}"
