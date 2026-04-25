# Composer Commands Reference

## Available Commands

### Code Quality

#### `composer cs:fix`
Fixes code style issues using PHP CS Fixer.

**Usage:**
```bash
composer cs:fix
```

**What it does:**
- Runs PHP CS Fixer on all PHP files
- Excludes vendor, node_modules, and tests directories
- Uses PSR-12 coding standard
- Fixes PHP 8.0+ migration issues
- Auto-caches fixes for faster subsequent runs

**When to use:**
- Before committing code
- After making changes to plugin files
- As part of pre-commit hooks
- Before creating releases

**Configuration:** `.php-cs-fixer.php`

---

### Testing

#### `composer test`
Runs all tests using Pest v4.

**Usage:**
```bash
composer test
```

**What it does:**
- Executes all unit and integration tests
- Uses Pest v4 testing framework
- Outputs colored, readable test results

**When to use:**
- Before committing code
- After making code changes
- During feature development

#### `composer test:coverage`
Runs tests with coverage report.

**Usage:**
```bash
composer test:coverage
```

**What it does:**
- Runs all tests with code coverage
- Generates coverage report
- Shows which code is tested vs untested

---

### Production Build

#### `composer production`
Prepares plugin for production deployment.

**Usage:**
```bash
composer production
```

**What it does:**
1. Runs `cs:fix` to ensure code quality
2. Removes dev dependencies (Pest, Brain Monkey, PHPUnit)
3. Installs production dependencies only
4. Optimizes autoloader for performance
5. Creates authoritative classmap (20-40% faster)

**Benefits:**
- Smaller vendor directory (~30MB vs ~80MB)
- Faster autoloading
- Production-ready code
- No development artifacts

**When to use:**
- Before deploying to production
- Before creating release zips
- In CI/CD pipelines

**See also:** `DEPLOYMENT.md` for complete deployment guide

---

### Version Management

#### `composer version-bump`
Bumps version across all plugin files.

**Usage:**
```bash
composer version-bump
# Or specify version directly
composer version-bump 1.8.0
```

**What it does:**
Updates version number in:
1. `fuerte-wp.php` - Main plugin file (Version header + constant)
2. `composer.json` - Package version
3. `README.md` - Stable tag if present
4. `README.txt` - WordPress readme format
5. `SECURITY.md` - Version section if present
6. `package.json` - For any JS assets (if exists)
7. `fuertewpasset.php` - Constants file (if exists)

**Features:**
- Validates semver format (e.g., 1.8.0, 1.8.0-beta.1)
- Creates backups before modifying
- Shows git diff of changes
- Prompts for diff review
- Provides next steps

**Interactive prompts:**
- Version to bump (or use command line argument)
- Show diff of changes (y/N)

**When to use:**
- Before releasing a new version
- After adding new features
- When creating release tags

**Version format:** Semantic Versioning (semver)
- Major.Minor.Patch (e.g., 1.8.0)
- Optional prerelease suffix (e.g., 1.8.0-beta.1)

---

## Command Quick Reference

| Command | Purpose | Run Before |
|---------|---------|------------|
| `composer cs:fix` | Fix code style | Committing |
| `composer test` | Run tests | Committing |
| `composer test:coverage` | Run tests with coverage | Releases |
| `composer production` | Prepare for production | Deploying |
| `composer version-bump` | Bump version | Releasing |

---

## Typical Workflows

### Development Workflow

```bash
# Make changes to code
vim includes/my-file.php

# Fix code style
composer cs:fix

# Run tests
composer test

# Commit
git add .
git commit -m "Add new feature"
```

### Release Workflow

```bash
# Bump version
composer version-bump 1.8.0

# Review changes
git diff

# Run tests
composer test

# Fix any issues
composer cs:fix

# Prepare for production
composer production

# Commit and tag
git add .
git commit -m "chore: bump version to 1.8.0"
git tag v1.8.0
git push origin main --tags
```

### Deployment Workflow

```bash
# Prepare for production
composer production

# Create release zip (using build script)
./scripts/build-release.sh 1.8.0

# Deploy to server
# (using your deployment method)
```

---

## Related Documentation

- **Testing:** `tests/README.md` - Comprehensive testing guide
- **Deployment:** `DEPLOYMENT.md` - Production deployment guide
- **Quick Start:** `tests/QUICK_START.md` - Quick testing reference

---

## Troubleshooting

### `cs:fix` not working

**Issue:** PHP CS Fixer not installed
```bash
composer require friendsofphp/php-cs-fixer --dev
```

### `test` fails with "Class not found"

**Issue:** Autoloader not updated
```bash
composer dump-autoload
```

### `production` removes too much

**Issue:** Dev dependencies removed but still needed
```bash
# Restore development environment
composer install
```

### `version-bump` shows "Invalid version format"

**Issue:** Version doesn't follow semver
```bash
# Use correct format
composer version-bump 1.8.0
# Or with prerelease
composer version-bump 1.8.0-beta.1
```

---

## Best Practices

1. **Always run `cs:fix` before committing** - Ensures consistent code style
2. **Run tests before pushing** - Catch issues early
3. **Use `production` before deploying** - Optimize for production
4. **Version bump with care** - Follow semver guidelines
5. **Review changes after version bump** - Ensure all files updated correctly

---

## Integration with Git Hooks

Add to `.git/hooks/pre-commit`:

```bash
#!/bin/bash
# Run code style check
composer cs:fix

# Run tests
composer test

# If either fails, prevent commit
if [ $? -ne 0 ]; then
    echo "❌ Pre-commit checks failed. Please fix the issues before committing."
    exit 1
fi
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

---

## Support

For issues or questions:
- GitHub Issues: https://github.com/EstebanForge/Fuerte-WP/issues
- Documentation: See individual README files in each directory
