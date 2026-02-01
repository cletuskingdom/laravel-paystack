# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-02-01

### Added

- Added `getPaymentData()` helper to automatically verify transactions.
- Added Webhook support with automatic signature verification.
- Added `redirectToGateway()` for fluent redirects.
- Initial release of the Laravel Paystack package.
- Basic payment initialization and verification.

### Fixed

- Fixed a bug where the Secret Key was not being pulled correctly from `.env`.
- Fixed a timeout issue with Guzzle requests.
