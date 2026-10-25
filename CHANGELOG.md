# Changelog

This changelog documents **only the two most recent published GitHub releases** of `local_xpquests`. Each entry is based on a Git comparison against the immediately preceding **published** release. The `V 1.0.4` commit is included in the `1.0.3...1.0.5` comparison, but there is no separate published GitHub release for `1.0.4`, so it is not documented as an independent release. Commits after the `1.0.5` tag are excluded.

## [1.0.5] - 2026-10-15

Comparison: [1.0.3...1.0.5](https://github.com/EduardoKrausME/moodle-local_xpquests/compare/1.0.3...1.0.5)

### Changed

- Updated GitHub Actions CI's PostgreSQL service from **PostgreSQL 16 to PostgreSQL 17**.
- Updated the MariaDB CI service from **MariaDB 10 to MariaDB 11.4**, replacing the empty-password environment setting with `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD`.
- Replaced the MariaDB `mysqladmin ping` readiness check with `healthcheck.sh --connect --innodb_initialized` and adjusted health-check retry parameters.
- Updated the plugin metadata in `version.php` from `2026101200` / `1.0.3` to `2026101501` / `1.0.5`.
- Removed the explanatory `// Moodle 4.1.` inline comment from the `$plugin->requires = 2022112800;` line; the minimum required Moodle build itself did not change.

### Scope

The tag comparison includes intermediate version `1.0.4` commits, but only `.github/workflows/ci.yml` and `version.php` differ between the two published releases. No runtime functionality or application source files changed in this comparison.

## [1.0.3] - 2026-10-12

Comparison: [1.0.2...1.0.3](https://github.com/EduardoKrausME/moodle-local_xpquests/compare/1.0.2...1.0.3)

### Changed

- Updated the PHP 8.3 CI test matrix from `MOODLE_502_STABLE` to **`MOODLE_503_STABLE` (Moodle 5.3)**.
- Updated the CI setup to check out the dependency repository `EduardoKrausME/moodle-local_personalxp` into `extra-plugins/personalxp`, and to pass `--extra-plugins ./extra-plugins` to `moodle-plugin-ci install`.
- Hardened the auto-release workflow's version parsing by passing the GitHub commit message through the `RELEASE_MESSAGE` environment variable rather than interpolating event text directly into a shell command.
- Added the plugin icon at `pix/icon.png`.
- Updated `version.php` metadata from `2026100300` / `1.0.2` to `2026101200` / `1.0.3`.

### Scope

Only `.github/workflows/ci.yml`, `.github/workflows/release.yml`, `pix/icon.png`, and `version.php` changed between these tags. No runtime application source files changed in this release.

---

This file summarizes verified changes between published GitHub release tags, not unreleased commits or unverified runtime behavior.
