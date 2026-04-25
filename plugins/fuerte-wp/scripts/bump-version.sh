#!/usr/bin/env bash

# Fuerte-WP Version Bump Script (Portable Perl Edition)
# Usage: ./scripts/bump-version.sh [version]
# Example: ./scripts/bump-version.sh 1.8.1

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Fuerte-WP Version Bump Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Get current version
if [ -f "$PLUGIN_DIR/fuerte-wp.php" ]; then
    CURRENT_VERSION=$(grep "Version:" "$PLUGIN_DIR/fuerte-wp.php" | head -1 | sed 's/.*Version: *//' | sed 's/ *$//')
else
    CURRENT_VERSION="unknown"
fi

echo -e "${YELLOW}Current version: ${GREEN}${CURRENT_VERSION}${NC}"
echo ""

# Prompt for version
read -rp "Enter new version (e.g., 1.8.1): " VERSION

# Validate version format
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.]+)?$ ]]; then
    echo -e "${RED}Error: Invalid version format.${NC}"
    exit 1
fi

echo -e "${GREEN}Bumping version to ${VERSION}${NC}"
echo ""

# Use perl for portable search and replace
update_file() {
    local pattern="$1"
    local file="$2"
    local label="$3"
    
    if [ -f "$file" ]; then
        if perl -i -pe "$pattern" "$file"; then
            echo -e "${GREEN}✓ Updated $label${NC}"
            return 0
        else
            echo -e "${RED}✗ Failed to update $label${NC}"
            return 1
        fi
    fi
}

COUNT=0

# 1. fuerte-wp.php (Version header) - preserve alignment
update_file "s/(Version:\s+)\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?/\${1}${VERSION}/" "$PLUGIN_DIR/fuerte-wp.php" "fuerte-wp.php (header)" && ((COUNT++))
# 2. fuerte-wp.php (Constant)
update_file "s/define\('FUERTEWP_VERSION', '[^']*'\)/define('FUERTEWP_VERSION', '${VERSION}')/" "$PLUGIN_DIR/fuerte-wp.php" "fuerte-wp.php (constant)"

# 3. composer.json
update_file "s/\"version\": \"[^\"]*\"/\"version\": \"${VERSION}\"/" "$PLUGIN_DIR/composer.json" "composer.json" && ((COUNT++))

# 4. README.txt
if [ -f "$PLUGIN_DIR/README.txt" ]; then
    perl -i -pe "s/Stable tag: .*/Stable tag: ${VERSION}/" "$PLUGIN_DIR/README.txt"
    perl -i -pe "s/^Version: .*/Version: ${VERSION}/" "$PLUGIN_DIR/README.txt"
    echo -e "${GREEN}✓ Updated README.txt${NC}"
    ((COUNT++))
fi

# 5. README.md
if [ -f "$PLUGIN_DIR/README.md" ]; then
    perl -i -pe "s/Stable tag: .*/Stable tag: ${VERSION}/" "$PLUGIN_DIR/README.md"
    echo -e "${GREEN}✓ Updated README.md${NC}"
    ((COUNT++))
fi

# 6. SECURITY.md (Table update)
if [ -f "$PLUGIN_DIR/SECURITY.md" ]; then
    # Update the supported version line and the comparison line
    perl -i -pe "s/\| [0-9]+\.[0-9]+\.[0-9]+   \| :white_check_mark: \|/| ${VERSION}   | :white_check_mark: |/" "$PLUGIN_DIR/SECURITY.md"
    perl -i -pe "s/\| <[0-9]+\.[0-9]+\.[0-9]+  \| :x:                \|/| <${VERSION}  | :x:                |/" "$PLUGIN_DIR/SECURITY.md"
    echo -e "${GREEN}✓ Updated SECURITY.md${NC}"
    ((COUNT++))
fi

# 7. tests/bootstrap.php (Test version constant)
if [ -f "$PLUGIN_DIR/tests/bootstrap.php" ]; then
    if perl -i -pe "s/define\('FUERTEWP_VERSION', '[^']*'\)/define('FUERTEWP_VERSION', '${VERSION}')/" "$PLUGIN_DIR/tests/bootstrap.php"; then
        echo -e "${GREEN}✓ Updated tests/bootstrap.php${NC}"
        ((COUNT++))
    else
        echo -e "${RED}✗ Failed to update tests/bootstrap.php${NC}"
    fi
fi

# 8. Library composer.json (HyperFields)
LIB_COMPOSER="$PLUGIN_DIR/vendor/estebanforge/hyperfields/composer.json"
if [ -f "$LIB_COMPOSER" ]; then
    if perl -i -pe "s/\"version\": \"[^\"]*\"/\"version\": \"${VERSION}\"/" "$LIB_COMPOSER"; then
        echo -e "${GREEN}✓ Updated library composer.json (HyperFields)${NC}"
        ((COUNT++))
    else
        echo -e "${RED}✗ Failed to update library composer.json${NC}"
    fi
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Version Bump Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "Version:    ${VERSION}"
echo ""

# Show changed files
echo -e "${YELLOW}Next steps:${NC}"
echo -e "1. Review the changes: ${BLUE}git status${NC}"
echo -e "2. Run tests: ${GREEN}composer test${NC}"
echo -e "3. Commit and tag:${NC}"
echo -e "   ${BLUE}git add .${NC}"
echo -e "   ${BLUE}git commit -m \"chore: bump version to ${VERSION}\"${NC}"
echo -e "   ${BLUE}git tag v${VERSION}${NC}"

exit 0
