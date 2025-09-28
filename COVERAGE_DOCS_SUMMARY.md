# Coverage and Documentation Summary

This repository bundles generated HTML reports (coverage and documentation) alongside Markdown guides. The sections below describe the available artefacts and how to regenerate them.

## Generated artefacts

### Coverage reports (`coverage/`)

- `index.html` – Main dashboard summarising line and branch coverage.
- `dashboard.html` – Alternate entry point with graph-style summaries.
- `test-report.html` – Consolidated PHPUnit output.

### Documentation (`docs/`)

- `index.html` – Static site wrapper for the Markdown documents.
- `README.md` – High-level overview of the SDK and architecture.
- `api-reference.md` – Detailed JSON-RPC method and data model reference.
- `protocol-compliance.md` – TCK status report and behavioural highlights.

## Current status

- PHPUnit test suite: `composer test` (no coverage instrumentation enabled by default).
- A2A TCK: `python3 ../a2a-tck/run_tck.py --sut-url http://localhost:8081 --category all`.
- Coverage HTML artefacts were produced with Xdebug or PCOV; regenerate them if code or tests change.

## Regenerating coverage locally

```bash
# Install optional coverage driver (for example, Xdebug)
pecl install xdebug

echo "zend_extension=xdebug.so" > /tmp/a2a-xdebug.ini
php -c /tmp/a2a-xdebug.ini -d xdebug.mode=coverage \
    ./vendor/bin/phpunit --coverage-html coverage --coverage-text
```

Adjust the PHP configuration path to match your environment. The resulting HTML files replace the existing ones under `coverage/`.

## Regenerating documentation

All Markdown documents are stored in source control. To rebuild the static site or convert Markdown to HTML, use your preferred tool (for example, `mkdocs`, `pandoc`, or GitHub Pages). Update the Markdown first, then regenerate HTML assets if required.

## Reporting checklist

1. Verify that `composer test` completes without failures.
2. Confirm that the A2A TCK passes across all categories.
3. Refresh the coverage HTML when tests or implementation details change.
4. Keep Markdown guides (`README.md`, `docs/*`) synchronised with behavioural changes.
