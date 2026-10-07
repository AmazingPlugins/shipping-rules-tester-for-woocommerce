# Releasing

Merging to `main` does not publish this plugin.

When the release is ready:

1. Set the plugin header, `Plugin::VERSION`, and the readme `Stable tag` to the same version.
2. Merge that change to `main`.
3. Push a GitHub tag with that version.

```bash
git tag 1.2.9
git push origin 1.2.9
```

`.github/workflows/quality.yml` runs the tests, then deploys the tag to the WordPress.org plugin directory when the tag matches the header and the stable tag. Don't tag a version that is already on WordPress.org.
