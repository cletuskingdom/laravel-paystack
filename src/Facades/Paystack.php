<?php

namespace CletusKingdom\Paystack\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array initializeTransaction(array $data)
 * @method static array verifyTransaction(string $reference)
 * @method static array listTransactions(array $params = [])
 * @method static array fetchTransaction(int $id)
 * @method static array chargeAuthorization(array $data)
 * @method static array createCustomer(array $data)
 * @method static array listCustomers(array $params = [])
 * @method static array fetchCustomer(string $emailOrCode)
 * @method static array updateCustomer(string $customerCode, array $data)
 * @method static array createPlan(array $data)
 * @method static array listPlans(array $params = [])
 * @method static array fetchPlan(string $planCode)
 * @method static array updatePlan(string $planCode, array $data)
 * @method static array createSubscription(array $data)
 * @method static array listSubscriptions(array $params = [])
 * @method static array fetchSubscription(string $subscriptionCode)
 * @method static array createSubaccount(array $data)
 * @method static array listSubaccounts(array $params = [])
 * @method static array fetchSubaccount(string $subaccountCode)
 * @method static array updateSubaccount(string $subaccountCode, array $data)
 * @method static array createTransferRecipient(array $data)
 * @method static array listTransferRecipients(array $params = [])
 * @method static array initiateTransfer(array $data)
 * @method static array listTransfers(array $params = [])
 * @method static array createRefund(string $transactionReference, int|null $amount = null)
 * @method static array listRefunds(array $params = [])
 * @method static array resolveAccountNumber(string $accountNumber, string $bankCode)
 * @method static array listBanks(string $country = 'nigeria', array $params = [])
 * @method static string generateReference(string|null $prefix = null)
 * @method static bool isSuccessful(array|null $response = null)
 * @method static mixed getData(array|null $response = null)
 * @method static string|null getMessage(array|null $response = null)
 * @method static string|null getAuthorizationUrl(array|null $response = null)
 * @method static float toNaira(int $kobo)
 * @method static bool verifyWebhookSignature(string $payload, string $signature)
 *
 * @see \CletusKingdom\Paystack\Paystack
 */
class Paystack extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-paystack';
    }
}