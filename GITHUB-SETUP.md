# GitHub Setup Guide for TRM Counseling Session Scheduler

## Step 1: Create GitHub Repository

1. Go to [GitHub.com](https://github.com/new)
2. Create a new repository named: `trm-counseling-scheduler`
3. Add description: "WordPress plugin for event-based counseling session booking"
4. Choose "Public" (so anyone can install updates)
5. Initialize with README (already provided)
6. Click "Create repository"

## Step 2: Push Code to GitHub

```bash
# Navigate to plugin directory
cd /path/to/trm-counseling-scheduler

# Initialize git (if not already done)
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit - TRM Counseling Session Scheduler v1.0.0"

# Add remote repository
git remote add origin https://github.com/yourusername/trm-counseling-scheduler.git

# Push to main branch
git branch -M main
git push -u origin main
```

## Step 3: Update Configuration Files

Replace `yourusername` in these files with your actual GitHub username:

1. **includes/class-trm-updater.php** (lines 9-10):
   ```php
   private $github_repo = 'yourusername/trm-counseling-scheduler';
   private $github_raw_url = 'https://raw.githubusercontent.com/yourusername/trm-counseling-scheduler/main';
   ```

2. **plugin-info.json** (lines 6, 9, 10):
   ```json
   "author_profile": "https://github.com/yourusername",
   "homepage": "https://github.com/yourusername/trm-counseling-scheduler",
   "download_link": "https://github.com/yourusername/trm-counseling-scheduler/releases/download/v1.0.0/trm-counseling-scheduler.zip",
   ```

3. **package.json** (lines 7, 10):
   ```json
   "homepage": "https://github.com/yourusername/trm-counseling-scheduler",
   "url": "https://github.com/yourusername/trm-counseling-scheduler.git"
   ```

## Step 4: Create Release on GitHub

1. Go to your repository: `https://github.com/yourusername/trm-counseling-scheduler`
2. Click "Releases" (or "Create a new release" if none exist)
3. Click "Draft a new release"
4. Fill in the details:
   - **Tag version**: `v1.0.0`
   - **Release title**: `Version 1.0.0 - Initial Release`
   - **Description**: Paste content from CHANGELOG section
5. Generate release notes from commits
6. **Attach binary** (ZIP of plugin):
   - Create a ZIP file of the plugin folder
   - Name it: `trm-counseling-scheduler.zip`
   - Drag it into the "Attach binaries" section
7. Check "Set as the latest release"
8. Click "Publish release"

## Step 5: Test Update Detection

1. Install the plugin on a WordPress site
2. Go to **Plugins** in WordPress Admin
3. The plugin should check GitHub for updates every 12 hours
4. To test immediately, add this to your theme's `functions.php`:
   ```php
   add_action('init', function() {
       if (function_exists('TRM_Updater')) {
           delete_transient('trm_update_check');
       }
   });
   ```
5. Refresh plugins page - should show "Update available" if versions differ

## How Updates Work

When a user has the plugin installed:

1. **Every 12 hours**, WordPress checks GitHub for updates:
   - Requests `package.json` to get remote version
   - Compares with installed version (from `trm-counseling-scheduler.php`)
   
2. **If new version found**:
   - Shows "Update available" notice on Plugins page
   - User clicks "Update now"
   - WordPress downloads the ZIP from the release
   - Installs the new version
   - User site is updated automatically

## Versioning Strategy

### Semantic Versioning: MAJOR.MINOR.PATCH

- **MAJOR** (1.x.x): Breaking changes, major features
- **MINOR** (x.1.x): New features, backwards compatible
- **PATCH** (x.x.1): Bug fixes

Examples:
- `1.0.0` → Initial release
- `1.1.0` → New event type support (new feature)
- `1.0.1` → Fix booking validation bug (bug fix)
- `2.0.0` → Complete rewrite (breaking changes)

## Creating New Releases

For each update:

1. **Update version numbers**:
   - `trm-counseling-scheduler.php`: Line 20
   - `package.json`: Line 3
   - `plugin-info.json`: Line 4

2. **Commit and push**:
   ```bash
   git add .
   git commit -m "Bump version to 1.1.0 - Add new features"
   git push
   ```

3. **Create GitHub Release**:
   - Tag: `v1.1.0`
   - Title: `Version 1.1.0 - New Features`
   - Description with changelog
   - Attach new ZIP

4. **Users will automatically see update** in their admin!

## Continuous Integration (Optional)

For automated releases, create `.github/workflows/release.yml`:

```yaml
name: Create Release
on:
  push:
    tags:
      - 'v*'
jobs:
  create-release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Create ZIP
        run: zip -r trm-counseling-scheduler.zip . -x "*.git*" ".github/*"
      - name: Create Release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name: ${{ github.ref }}
          release_name: Release ${{ github.ref }}
          body: See CHANGELOG.md
          draft: false
          prerelease: false
      - name: Upload Asset
        uses: actions/upload-release-asset@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          upload_url: ${{ steps.create_release.outputs.upload_url }}
          asset_path: ./trm-counseling-scheduler.zip
          asset_name: trm-counseling-scheduler.zip
          asset_content_type: application/zip
```

## Troubleshooting

### Updates not showing
- Check GitHub repository is **Public**
- Verify `package.json` has correct version
- Clear WordPress transient: `delete_transient('trm_update_check');`
- Check WP-Admin > Tools > Site Health for any issues

### Wrong download URL
- Ensure release ZIP file is named `trm-counseling-scheduler.zip`
- Verify release tag matches `v1.0.0` format
- Check URLs in `class-trm-updater.php` are correct

### Users can't download
- Make sure release ZIP is attached to GitHub release
- Verify file is not corrupted

## Support

For issues or questions about plugin distribution:
- GitHub Issues: `https://github.com/yourusername/trm-counseling-scheduler/issues`
- Documentation: See README.md

---

**Your plugin is now ready for distribution!** Users can install and automatically receive updates.
