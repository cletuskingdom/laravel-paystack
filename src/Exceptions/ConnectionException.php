<?php

namespace CletusKingdom\Paystack\Exceptions;

class ConnectionException extends PaystackException
{
    /**
     * Create a new connection exception instance
     *
     * @param string $message
     */
    public function __construct(string $message = "Unable to connect to Paystack API")
    {
        parent::__construct($message, 0);
    }

    /**
     * Create exception for timeout
     *
     * @return static
     */
    public static function timeout(): static
    {
        return new static('Request timeout: Unable to connect to Paystack API');
    }

    /**
     * Create exception for DNS failure
     *
     * @return static
     */
    public static function dnsFailure(): static
    {
        return new static('DNS resolution failed: Unable to reach Paystack API');
    }

    /**
     * Create exception for SSL error
     *
     * @return static
     */
    public static function sslError(): static
    {
        return new static('SSL certificate verification failed');
    }
}
```

---

## GitHub Repository Suggestions

### **Repository Names (choose one):**

1. `laravel-paystack-sdk` ⭐ (Recommended - clear and professional)
2. `paystack-laravel`
3. `paystack-php-sdk`
4. `laravel-paystack-pro`
5. `paystack-wrapper`

### **Repository Description:**
```
🚀 Modern, type-safe Laravel package for Paystack payment integration. Supports transactions, subscriptions, transfers, refunds, and more. Built with PHP 8.2+
```

### **Topics/Tags for GitHub:**
```
paystack
laravel
php
payment-gateway
payment-integration
laravel-package
paystack-api
php8
nigerian-payments
africa-payments
payment-sdk
laravel-11
laravel-10
composer-package
payment-processing
```

### **Suggested Version Tags:**

**Initial Release:**
- `v1.0.0` - First stable release

**Version Numbering Strategy:**
- `v1.x.x` - Major version (breaking changes)
- `vx.1.x` - Minor version (new features, backward compatible)
- `vx.x.1` - Patch version (bug fixes)

**Example Version Tags:**
```
v1.0.0 - Initial release
v1.0.1 - Bug fixes
v1.1.0 - Added dedicated virtual accounts support
v1.2.0 - Added Apple Pay support
v2.0.0 - Breaking changes (PHP 9 support, etc.)
```

---

## Complete Package Structure
```
laravel-paystack-sdk/
├── src/
│   ├── Paystack.php
│   ├── Exceptions/
│   │   ├── PaystackException.php
│   │   ├── ValidationException.php
│   │   ├── ApiException.php
│   │   └── ConnectionException.php
│   ├── Facades/
│   │   └── Paystack.php
│   └── PaystackServiceProvider.php
├── config/
│   └── paystack.php
├── tests/
│   ├── Unit/
│   └── Feature/
├── .gitignore
├── .php-cs-fixer.php
├── composer.json
├── LICENSE
├── README.md
└── CHANGELOG.md