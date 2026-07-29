# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Fixed

- Fix images display in PDF: Make them display at their original position instead of at the end of the document.
- Fixed approval comments rendering as raw HTML in PDF
- Fixed PDF crash when exporting Problems with linked items lacking serial/inventory fields
- Fixed Change and Problem description exported as a single unstructured text block
- Fixed Change analysis and plan fields rendering as raw HTML in PDF
- Fixed rich text content rendered as visible HTML tags and unformatted text in PDF exports
- Fixed Change approval comments rendering as visible HTML entities in PDF exports
- Fixed Changes linked items showing phantom empty entries for users with broad entity access
- Fix image rendering in Change/Problem descriptions and Problem task PDF exports

## [4.1.3] - 2026-06-24

### Fixed

- Fix image (field : description) in to pdf
- Fix massive action PDF export redirecting to item list instead of generating the PDF

### Added

- PDF export mass action for software installations

## [4.1.2] - 2026-01-08

### Fixed

- Avoids a CSRF check error if print is clicked multiple times
- Fixes some SQL errors during export
- Clean HTML tables to fit PDF page width to avoid overflow
- Fix translation for "Observer"

## [4.1.1] - 2025-10-30

### Fixed

- Fix error message `Unknown '__s' function`

## [4.1.0] - 2025-10-01

### Added

- GLPI 11 compatibility

### Fixed

- Fixed table formatting and border in PDF
- Fixed table cell size in PDF generation

## [4.0.2] - 2025-09-30

- Fix missing images in exported Knowledge Base PDFs
- Enhanced display of HTML content

## [4.0.1] - 2025-03-10

### Added

- Checking the compatibility of the Branding plugin

## [4.0.0] - 2025-03-06

### Added

- New option to use the Branding plugin logo in PDF headers
