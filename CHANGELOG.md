# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [4.0.3] - 2026-06-30

### Fixed

- Fixed table formatting and border in PDF
- Fixed table cell size in PDF generation
- Fixed approval comments rendering as raw HTML in PDF
- Fixed PDF crash when exporting Problems with linked items lacking serial/inventory fields
- Fixed PHP warnings flood when exporting Changes with linked items lacking serial/inventory fields
- Fixed Change and Problem description exported as a single unstructured text block
- Fixed Change analysis and plan fields rendering as raw HTML in PDF
- Fixed rich text content rendered as visible HTML tags and unformatted text in PDF exports
- Fixed Change approval comments rendering as visible HTML entities in PDF exports
- Fixed Changes linked items showing phantom empty entries for users with broad entity access

## [4.0.2] - 2025-09-30

- Fix missing images in exported Knowledge Base PDFs
- Enhanced display of HTML content

## [4.0.1] - 2025-03-10

### Added

- Checking the compatibility of the Branding plugin

## [4.0.0] - 2025-03-06

### Added

- New option to use the Branding plugin logo in PDF headers
