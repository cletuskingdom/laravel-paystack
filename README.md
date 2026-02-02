## Installation

Install via Composer:

```bash
composer require cletuskingdom/laravel-paystack
```

Publish the config file:

```bash
php artisan vendor:publish --tag=paystack-config
```

Add your Paystack keys to `.env`:

```env
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxx
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxx
```

## Usage

### Using Facade

```php
use CletusKingdom\Paystack\Facades\Paystack;

// Initialize transaction
$response = Paystack::initializeTransaction([
    'email' => 'customer@email.com',
    'amount' => 10000, // Amount in Naira
]);

if (Paystack::isSuccessful($response)) {
    $url = Paystack::getAuthorizationUrl();
    return redirect($url);
}
```

### Using Dependency Injection

```php
use CletusKingdom\Paystack\Paystack;

class PaymentController extends Controller
{
    public function __construct(protected Paystack $paystack)
    {
    }

    public function pay()
    {
        $response = $this->paystack->initializeTransaction([
            'email' => 'customer@email.com',
            'amount' => 10000,
        ]);

        return redirect($this->paystack->getAuthorizationUrl());
    }
}
```
