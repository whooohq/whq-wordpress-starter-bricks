# Production Deployment Guide

## Preparing for Production

### Quick Command

```bash
composer production
```

This command performs the following:
1. Removes all development dependencies (testing tools, etc.)
2. Installs production dependencies from packagist
3. Optimizes the autoloader for maximum performance
4. Creates an authoritative classmap for faster loading

## What This Command Does

### 1. Removes Dev Dependencies

Removes packages only needed for development:
- `pestphp/pest` - Testing framework
- `pestphp/pest-plugin-mock` - Test mocking
- `phpunit/phpunit` - Testing framework

**Saves:** ~50MB of vendor directory space

### 2. Optimizes Autoloader

- `--optimize-autoloader`: Converts PSR-4/PSR-0 rules to classmap for faster loading
- `--classmap-authoritative`: Only uses classmap (no file system checks)
- `--prefer-dist`: Downloads archives instead of cloning VCS repos

**Performance:** 20-40% faster autoloading

### 3. Production Installation

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

- `--no-dev`: Skip development dependencies
- `--prefer-dist`: Use distribution archives (faster, smaller)
- `--optimize-autoloader`: Optimize autoloader for production
- `--no-interaction`: Never ask for confirmation (CI-friendly)

## When to Use

### Before Deploying

```bash
# Prepare for production deployment
composer production

# Deploy to production
# (using FTP, SFTP, Git, or deployment tool)
```

### For Release Zips

```bash
# Prepare plugin for distribution
composer production

# Create zip (excluding development files)
zip -r fuerte-wp.zip . -x "*.git*" "tests/*" "phpunit.xml" "Pest.php" ".github/*"
```

## Post-Production Setup

### After Production Build

The plugin is now optimized and ready for deployment with:

✅ **Smaller footprint** - No testing tools or dev dependencies
✅ **Faster loading** - Optimized autoloader with authoritative classmap
✅ **Production-ready** - Only essential dependencies included
✅ **No dev artifacts** - Test files excluded from autoloader

### Development Work

To continue development after running production build:

```bash
# Restore development environment
composer install
```

## Verification

### Check Production Build

```bash
# Verify dev dependencies are removed
composer show | grep -E "(pest|phpunit)"
# Should return empty

# Check autoloader is optimized
grep -o "optimizeAutoloader" vendor/composer/autoload_classmap.php
# Should return: optimizeAutoloader
```

### File Size Comparison

```bash
# Before production build
du -sh vendor/
# ~80MB (with dev dependencies)

# After production build
du -sh vendor/
# ~30MB (production only)
```

## Deployment Checklist

Before deploying to production:

- [ ] Run `composer production` to optimize for production
- [ ] Run tests locally: `composer test`
- [ ] Update version number in `fuerte-wp.php`
- [ ] Update CHANGELOG.md
- [ ] Create Git tag for version
- [ ] Test on staging environment
- [ ] Deploy to production

## Rollback

If you need to restore development environment:

```bash
# Restore all dependencies (including dev)
composer install
```

## Automated Build Script

For automated version updates and release zips:

```bash
./scripts/build-release.sh 1.8.0
```

This script:
1. Runs tests to ensure everything works
2. Updates version numbers in plugin files
3. Prepares production dependencies
4. Creates an optimized release zip

## Troubleshooting

### Issue: Autoloader not finding classes

**Solution:** Rebuild production autoloader
```bash
composer production
```

### Issue: Need to test after production build

**Solution:** Install dev dependencies again
```bash
composer install
composer test
```

### Issue: Plugin fails in production

**Solution:** Verify all dependencies are production-ready
```bash
composer show --tree
# Check for any dev-only dependencies
```

## Best Practices

1. **Always test before deploying:**
   ```bash
   composer test && composer production
   ```

2. **Keep production builds separate:**
   - Use `.gitignore` to exclude `vendor/` from version control
   - Run `composer production` on the server or in CI/CD

3. **Version your releases:**
   - Tag releases in Git (e.g., `v1.8.0`)
   - Update version in `fuerte-wp.php`

4. **Monitor production performance:**
   - Autoloader optimization should reduce load times
   - Smaller vendor directory = faster deployments

## Related Commands

| Command | Purpose |
|---------|---------|
| `composer production` | Prepare for production deployment |
| `composer install` | Install all dependencies (dev + prod) |
| `composer update` | Update dependencies to latest versions |
| `composer show` | Show installed packages |
| `composer outdated` | Show packages with updates available |

## Support

For issues or questions:
- GitHub Issues: https://github.com/EstebanForge/Fuerte-WP/issues
- Documentation: See `tests/README.md` for testing information
