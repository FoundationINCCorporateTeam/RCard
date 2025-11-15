#!/bin/bash

# RCard Platform Setup Script
# This script sets up the RCard platform for first-time use

echo "╔═══════════════════════════════════════╗"
echo "║   RCard Platform Setup Script        ║"
echo "╚═══════════════════════════════════════╝"
echo ""

# Check PHP version
echo "Checking PHP version..."
php_version=$(php -r "echo PHP_VERSION;")
required_version="8.0.0"

if [ "$(printf '%s\n' "$required_version" "$php_version" | sort -V | head -n1)" != "$required_version" ]; then 
    echo "❌ Error: PHP 8.0 or higher is required. Current version: $php_version"
    exit 1
fi
echo "✅ PHP version: $php_version"

# Check OpenSSL extension
echo ""
echo "Checking PHP extensions..."
if php -m | grep -q "openssl"; then
    echo "✅ OpenSSL extension is installed"
else
    echo "❌ Error: OpenSSL extension is required but not installed"
    exit 1
fi

# Create directories
echo ""
echo "Creating directories..."
directories=(
    "jsondata/users"
    "jsondata/cards"
    "jsondata/loans"
    "jsondata/transactions"
    "jsondata/settings"
    "jsondata/fraud_reports"
)

for dir in "${directories[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        echo "  ✓ Created $dir"
    else
        echo "  • $dir already exists"
    fi
done

# Set permissions
echo ""
echo "Setting permissions..."
chmod -R 755 .
chmod -R 700 jsondata
echo "✅ Permissions set"

# Generate encryption key
echo ""
echo "Generating encryption key..."
if [ ! -f ".env" ]; then
    encryption_key=$(openssl rand -base64 32)
    echo "RCARD_ENCRYPTION_KEY=$encryption_key" > .env
    echo "✅ Encryption key generated and saved to .env"
    echo "⚠️  IMPORTANT: Keep the .env file secure and never commit it to version control!"
else
    echo "• .env file already exists, skipping key generation"
fi

# Create seed data
echo ""
read -p "Do you want to create test data? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Creating seed data..."
    php seed.php
fi

echo ""
echo "╔═══════════════════════════════════════╗"
echo "║   Setup Complete!                    ║"
echo "╚═══════════════════════════════════════╝"
echo ""
echo "Next steps:"
echo "1. Start the development server: php -S localhost:8000"
echo "2. Open your browser to http://localhost:8000"
echo "3. Login with test accounts:"
echo "   • alice / password123"
echo "   • bob / password123"
echo "   • charlie / password123"
echo "   • sponsor1 / password123"
echo ""
echo "For production deployment, see README.md"
echo ""
