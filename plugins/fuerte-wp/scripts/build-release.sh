#!/usr/bin/env bash

# Fuerte-WP Production Build Script
# Usage: ./scripts/build-release.sh [version]

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Fuerte-WP Production Build Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Get version from argument or prompt
if [ -n "${1:-}" ]; then
    VERSION="$1"
else
    read -rp "Enter version (e.g., 1.8.0): " VERSION
fi

# Validate version format
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${RED}Error: Invalid version format. Use semver (e.g., 1.8.0)${NC}"
    exit 1
fi

echo -e "${YELLOW}Building Fuerte-WP v${VERSION}${NC}"
echo ""

# Step 1: Run tests
echo -e "${GREEN}[1/5] Running tests...${NC}"
if ! composer test > /dev/null 2>&1; then
    echo -e "${RED}Error: Tests failed. Please fix tests before building.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Tests passed${NC}"
echo ""

# Step 2: Update version in plugin file
echo -e "${GREEN}[2/5] Updating version to ${VERSION}...${NC}"
sed -i.bak "s/Version: .*/Version: ${VERSION}/" "${PLUGIN_DIR}/fuerte-wp.php"
sed -i.bak "s/define('FUERTEWP_VERSION', '[^']*')/define('FUERTEWP_VERSION', '${VERSION}')/" "${PLUGIN_DIR}/fuerte-wp.php"
sed -i.bak "s/\"version\": \"[^\"]*\"/\"version\": \"${VERSION}\"/" "${PLUGIN_DIR}/composer.json"
rm -f "${PLUGIN_DIR}/fuerte-wp.php.bak"
echo -e "${GREEN}✓ Version updated${NC}"
echo ""

# Step 3: Prepare for production
echo -e "${GREEN}[3/5] Installing production dependencies...${NC}"
cd "$PLUGIN_DIR"
composer production
echo -e "${GREEN}✓ Production dependencies installed${NC}"
echo ""

# Step 4: Create release zip
echo -e "${GREEN}[4/5] Creating release zip...${NC}"
ZIP_NAME="fuerte-wp-${VERSION}.zip"
ZIP_PATH="${PLUGIN_DIR}/../${ZIP_NAME}"

# Create zip excluding development files
cd "$PLUGIN_DIR"
zip -rq "$ZIP_PATH" . \
    -x "*.git*" \
    -x "tests/*" \
    -x "tests/**/*" \
    -x "phpunit.xml" \
    -x "Pest.php" \
    -x ".github/*" \
    -x ".github/**/*" \
    -x "node_modules/*" \
    -x ".DS_Store" \
    -x "*.bak" \
    -x "composer.lock" \
    -x "vendor/bin/*" \
    -x "vendor/phpunit/*" \
    -x "vendor/pestphp/*"

echo -e "${GREEN}✓ Release zip created: ${ZIP_NAME}${NC}"
echo ""

# Step 5: Get file sizes
echo -e "${GREEN}[5/5] Build Summary${NC}"
VENDOR_SIZE=$(du -sh "${PLUGIN_DIR}/vendor" | cut -f1)
ZIP_SIZE=$(du -h "$ZIP_PATH" | cut -f1)

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Build Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "Version:      ${VERSION}"
echo -e "Vendor size:  ${VENDOR_SIZE}"
echo -e "Release zip:  ${ZIP_NAME} (${ZIP_SIZE})"
echo -e "Location:     ${ZIP_PATH}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "1. Review the release zip contents"
echo -e "2. Test the zip in a staging environment"
echo -e "3. Commit and tag the release:"
echo -e "   git add ."
echo -e "   git commit -m \"Release v${VERSION}\""
echo -e "   git tag v${VERSION}"
echo -e "   git push origin main --tags"
echo ""
echo -e "${YELLOW}To restore development environment:${NC}"
echo -e "   composer install"
echo ""
