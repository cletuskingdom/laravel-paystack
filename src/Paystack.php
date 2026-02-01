<?php

namespace CletusKingdom\Paystack;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use CletusKingdom\Paystack\Exceptions\PaystackException;
use CletusKingdom\Paystack\Exceptions\ValidationException;

class Paystack
{
    protected Client $client;
    protected string $secretKey;
    protected string $baseUrl;
    protected ?array $lastResponse = null;

    public function __construct()
    {
        $this->secretKey = Config::get('paystack.secretKey');
        $this->baseUrl = Config::get('paystack.paymentUrl', 'https://api.paystack.co/');

        $this->client = new Client([
            'base_uri' => rtrim($this->baseUrl, '/') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'http_errors' => false, // Handle errors manually
        ]);
    }

    /**
     * Perform HTTP request to Paystack API
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $options = [];
            
            if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $endpoint, $options);
            $this->lastResponse = json_decode($response->getBody()->getContents(), true);

            // Check if response indicates an error
            if (!$this->isSuccessful($this->lastResponse)) {
                $message = $this->lastResponse['message'] ?? 'Unknown error occurred';
                throw new PaystackException($message);
            }

            return $this->lastResponse;

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new PaystackException($body['message'] ?? 'Request failed');
            }
            
            throw new PaystackException('Connection error: ' . $e->getMessage());
        }
    }

    /**
     * Validate required fields
     */
    private function validateRequired(array $data, array $required): void
    {
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            throw new ValidationException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Validate email address
     */
    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email address: ' . $email);
        }
    }

    /**
     * Convert amount to kobo (Paystack's smallest currency unit)
     */
    private function toKobo(float $amount): int
    {
        return (int) ($amount * 100);
    }

    /**
     * Convert kobo to naira
     */
    public function toNaira(int $kobo): float
    {
        return $kobo / 100;
    }

    // ==================== TRANSACTIONS ====================

    /**
     * Initialize a payment transaction
     * 
     * @param array $data ['email' => required, 'amount' => required, 'reference' => optional, ...]
     */
    public function initializeTransaction(array $data): array
    {
        $this->validateRequired($data, ['email', 'amount']);
        $this->validateEmail($data['email']);

        // Auto-generate reference if not provided
        $data['reference'] ??= $this->generateReference();
        
        // Convert amount to kobo
        $data['amount'] = $this->toKobo($data['amount']);

        return $this->makeRequest('POST', 'transaction/initialize', $data);
    }

    /**
     * Verify a transaction by reference
     */
    public function verifyTransaction(string $reference): array
    {
        if (empty($reference)) {
            throw new ValidationException('Transaction reference is required');
        }

        return $this->makeRequest('GET', "transaction/verify/{$reference}");
    }

    /**
     * List all transactions with optional filters
     * 
     * @param array $params ['perPage' => 50, 'page' => 1, 'status' => 'success', ...]
     */
    public function listTransactions(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'transaction' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a single transaction
     */
    public function fetchTransaction(int $id): array
    {
        return $this->makeRequest('GET', "transaction/{$id}");
    }

    /**
     * Charge authorization (recurring payment)
     * 
     * @param array $data ['authorization_code' => required, 'email' => required, 'amount' => required]
     */
    public function chargeAuthorization(array $data): array
    {
        $this->validateRequired($data, ['authorization_code', 'email', 'amount']);
        $this->validateEmail($data['email']);

        $data['amount'] = $this->toKobo($data['amount']);

        return $this->makeRequest('POST', 'transaction/charge_authorization', $data);
    }

    /**
     * Export transactions
     */
    public function exportTransactions(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'transaction/export' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Partial debit
     */
    public function partialDebit(array $data): array
    {
        $this->validateRequired($data, ['authorization_code', 'currency', 'amount', 'email']);
        
        $data['amount'] = $this->toKobo($data['amount']);
        
        return $this->makeRequest('POST', 'transaction/partial_debit', $data);
    }

    // ==================== CUSTOMERS ====================

    /**
     * Create a customer
     * 
     * @param array $data ['email' => required, 'first_name' => optional, 'last_name' => optional, ...]
     */
    public function createCustomer(array $data): array
    {
        $this->validateRequired($data, ['email']);
        $this->validateEmail($data['email']);

        return $this->makeRequest('POST', 'customer', $data);
    }

    /**
     * List all customers
     */
    public function listCustomers(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'customer' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a customer by email or customer code
     */
    public function fetchCustomer(string $emailOrCode): array
    {
        return $this->makeRequest('GET', "customer/{$emailOrCode}");
    }

    /**
     * Update a customer
     */
    public function updateCustomer(string $customerCode, array $data): array
    {
        return $this->makeRequest('PUT', "customer/{$customerCode}", $data);
    }

    /**
     * Validate a customer (Know Your Customer)
     */
    public function validateCustomer(string $customerCode, array $data): array
    {
        $this->validateRequired($data, ['first_name', 'last_name', 'type', 'value', 'country', 'bvn']);
        
        return $this->makeRequest('POST', "customer/{$customerCode}/identification", $data);
    }

    /**
     * Whitelist or blacklist a customer
     */
    public function setCustomerRiskAction(string $customerCode, string $riskAction): array
    {
        if (!in_array($riskAction, ['allow', 'deny', 'default'])) {
            throw new ValidationException('Risk action must be: allow, deny, or default');
        }

        return $this->makeRequest('POST', "customer/set_risk_action", [
            'customer' => $customerCode,
            'risk_action' => $riskAction
        ]);
    }

    /**
     * Deactivate authorization
     */
    public function deactivateAuthorization(string $authorizationCode): array
    {
        return $this->makeRequest('POST', 'customer/deactivate_authorization', [
            'authorization_code' => $authorizationCode
        ]);
    }

    // ==================== PLANS ====================

    /**
     * Create a subscription plan
     * 
     * @param array $data ['name' => required, 'amount' => required, 'interval' => required]
     */
    public function createPlan(array $data): array
    {
        $this->validateRequired($data, ['name', 'amount', 'interval']);

        $validIntervals = ['daily', 'weekly', 'monthly', 'biannually', 'annually'];
        if (!in_array($data['interval'], $validIntervals)) {
            throw new ValidationException(
                'Invalid interval. Must be: ' . implode(', ', $validIntervals)
            );
        }

        $data['amount'] = $this->toKobo($data['amount']);

        return $this->makeRequest('POST', 'plan', $data);
    }

    /**
     * List all plans
     */
    public function listPlans(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'plan' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a plan
     */
    public function fetchPlan(string $planCode): array
    {
        return $this->makeRequest('GET', "plan/{$planCode}");
    }

    /**
     * Update a plan
     */
    public function updatePlan(string $planCode, array $data): array
    {
        if (isset($data['amount'])) {
            $data['amount'] = $this->toKobo($data['amount']);
        }

        return $this->makeRequest('PUT', "plan/{$planCode}", $data);
    }

    // ==================== SUBSCRIPTIONS ====================

    /**
     * Create a subscription
     * 
     * @param array $data ['customer' => required, 'plan' => required, ...]
     */
    public function createSubscription(array $data): array
    {
        $this->validateRequired($data, ['customer', 'plan']);

        return $this->makeRequest('POST', 'subscription', $data);
    }

    /**
     * List subscriptions
     */
    public function listSubscriptions(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'subscription' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a subscription
     */
    public function fetchSubscription(string $subscriptionCode): array
    {
        return $this->makeRequest('GET', "subscription/{$subscriptionCode}");
    }

    /**
     * Enable a subscription
     */
    public function enableSubscription(array $data): array
    {
        $this->validateRequired($data, ['code', 'token']);

        return $this->makeRequest('POST', 'subscription/enable', $data);
    }

    /**
     * Disable a subscription
     */
    public function disableSubscription(array $data): array
    {
        $this->validateRequired($data, ['code', 'token']);

        return $this->makeRequest('POST', 'subscription/disable', $data);
    }

    /**
     * Generate update subscription link
     */
    public function generateSubscriptionLink(string $subscriptionCode): array
    {
        return $this->makeRequest('GET', "subscription/{$subscriptionCode}/manage/link");
    }

    /**
     * Send update subscription link
     */
    public function sendSubscriptionLink(string $subscriptionCode): array
    {
        return $this->makeRequest('POST', "subscription/{$subscriptionCode}/manage/email");
    }

    // ==================== SUBACCOUNTS ====================

    /**
     * Create a subaccount
     * 
     * @param array $data ['business_name' => required, 'settlement_bank' => required, 'account_number' => required, 'percentage_charge' => required]
     */
    public function createSubaccount(array $data): array
    {
        $this->validateRequired($data, ['business_name', 'settlement_bank', 'account_number', 'percentage_charge']);

        return $this->makeRequest('POST', 'subaccount', $data);
    }

    /**
     * List subaccounts
     */
    public function listSubaccounts(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'subaccount' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a subaccount
     */
    public function fetchSubaccount(string $subaccountCode): array
    {
        return $this->makeRequest('GET', "subaccount/{$subaccountCode}");
    }

    /**
     * Update a subaccount
     */
    public function updateSubaccount(string $subaccountCode, array $data): array
    {
        return $this->makeRequest('PUT', "subaccount/{$subaccountCode}", $data);
    }

    // ==================== TRANSFER RECIPIENTS ====================

    /**
     * Create a transfer recipient
     * 
     * @param array $data ['type' => required, 'name' => required, 'account_number' => required, 'bank_code' => required]
     */
    public function createTransferRecipient(array $data): array
    {
        $this->validateRequired($data, ['type', 'name', 'account_number', 'bank_code']);

        $validTypes = ['nuban', 'mobile_money', 'basa'];
        if (!in_array($data['type'], $validTypes)) {
            throw new ValidationException('Invalid type. Must be: ' . implode(', ', $validTypes));
        }

        return $this->makeRequest('POST', 'transferrecipient', $data);
    }

    /**
     * Bulk create transfer recipients
     */
    public function bulkCreateTransferRecipient(array $batch): array
    {
        return $this->makeRequest('POST', 'transferrecipient/bulk', ['batch' => $batch]);
    }

    /**
     * List transfer recipients
     */
    public function listTransferRecipients(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'transferrecipient' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a transfer recipient
     */
    public function fetchTransferRecipient(string $recipientCode): array
    {
        return $this->makeRequest('GET', "transferrecipient/{$recipientCode}");
    }

    /**
     * Update a transfer recipient
     */
    public function updateTransferRecipient(string $recipientCode, array $data): array
    {
        return $this->makeRequest('PUT', "transferrecipient/{$recipientCode}", $data);
    }

    /**
     * Delete a transfer recipient
     */
    public function deleteTransferRecipient(string $recipientCode): array
    {
        return $this->makeRequest('DELETE', "transferrecipient/{$recipientCode}");
    }

    // ==================== TRANSFERS ====================

    /**
     * Initiate a transfer
     * 
     * @param array $data ['source' => required, 'amount' => required, 'recipient' => required]
     */
    public function initiateTransfer(array $data): array
    {
        $this->validateRequired($data, ['source', 'amount', 'recipient']);

        $data['amount'] = $this->toKobo($data['amount']);

        return $this->makeRequest('POST', 'transfer', $data);
    }

    /**
     * Finalize a transfer
     */
    public function finalizeTransfer(string $transferCode, string $otp): array
    {
        return $this->makeRequest('POST', 'transfer/finalize_transfer', [
            'transfer_code' => $transferCode,
            'otp' => $otp
        ]);
    }

    /**
     * Initiate bulk transfer
     */
    public function initiateBulkTransfer(array $transfers): array
    {
        // Convert amounts to kobo
        foreach ($transfers as &$transfer) {
            if (isset($transfer['amount'])) {
                $transfer['amount'] = $this->toKobo($transfer['amount']);
            }
        }

        return $this->makeRequest('POST', 'transfer/bulk', [
            'currency' => 'NGN',
            'source' => 'balance',
            'transfers' => $transfers
        ]);
    }

    /**
     * List transfers
     */
    public function listTransfers(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'transfer' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a transfer
     */
    public function fetchTransfer(string $transferCode): array
    {
        return $this->makeRequest('GET', "transfer/{$transferCode}");
    }

    /**
     * Verify a transfer
     */
    public function verifyTransfer(string $reference): array
    {
        return $this->makeRequest('GET', "transfer/verify/{$reference}");
    }

    // ==================== REFUNDS ====================

    /**
     * Create a refund
     * 
     * @param string $transactionReference
     * @param int|null $amount Amount in kobo (null for full refund)
     */
    public function createRefund(string $transactionReference, ?int $amount = null): array
    {
        $data = ['transaction' => $transactionReference];
        
        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        return $this->makeRequest('POST', 'refund', $data);
    }

    /**
     * List refunds
     */
    public function listRefunds(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'refund' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a refund
     */
    public function fetchRefund(string $reference): array
    {
        return $this->makeRequest('GET', "refund/{$reference}");
    }

    // ==================== VERIFICATION ====================

    /**
     * Resolve account number
     */
    public function resolveAccountNumber(string $accountNumber, string $bankCode): array
    {
        $this->validateRequired(compact('accountNumber', 'bankCode'), ['accountNumber', 'bankCode']);

        return $this->makeRequest('GET', "bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}");
    }

    /**
     * Validate account
     */
    public function validateAccount(array $data): array
    {
        $this->validateRequired($data, ['account_name', 'account_number', 'account_type', 'bank_code', 'country_code', 'document_type']);

        return $this->makeRequest('POST', 'bank/validate', $data);
    }

    /**
     * Resolve BVN
     */
    public function resolveBvn(string $bvn): array
    {
        return $this->makeRequest('GET', "bank/resolve_bvn/{$bvn}");
    }

    /**
     * Resolve card BIN
     */
    public function resolveCardBin(string $bin): array
    {
        return $this->makeRequest('GET', "decision/bin/{$bin}");
    }

    // ==================== MISCELLANEOUS ====================

    /**
     * List banks
     */
    public function listBanks(string $country = 'nigeria', array $params = []): array
    {
        $params['country'] = $country;
        $query = http_build_query($params);
        
        return $this->makeRequest('GET', "bank?{$query}");
    }

    /**
     * List countries
     */
    public function listCountries(): array
    {
        return $this->makeRequest('GET', 'country');
    }

    /**
     * List states
     */
    public function listStates(string $country = 'NG'): array
    {
        return $this->makeRequest('GET', "address_verification/states?country={$country}");
    }

    // ==================== DISPUTES ====================

    /**
     * List disputes
     */
    public function listDisputes(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'dispute' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a dispute
     */
    public function fetchDispute(string $disputeId): array
    {
        return $this->makeRequest('GET', "dispute/{$disputeId}");
    }

    /**
     * Update a dispute
     */
    public function updateDispute(string $disputeId, array $data): array
    {
        return $this->makeRequest('PUT', "dispute/{$disputeId}", $data);
    }

    /**
     * Add evidence to dispute
     */
    public function addDisputeEvidence(string $disputeId, array $data): array
    {
        return $this->makeRequest('POST', "dispute/{$disputeId}/evidence", $data);
    }

    /**
     * Resolve a dispute
     */
    public function resolveDispute(string $disputeId, array $data): array
    {
        $this->validateRequired($data, ['resolution', 'message']);

        return $this->makeRequest('PUT', "dispute/{$disputeId}/resolve", $data);
    }

    // ==================== PAYMENT PAGES ====================

    /**
     * Create a payment page
     */
    public function createPage(array $data): array
    {
        $this->validateRequired($data, ['name']);

        if (isset($data['amount'])) {
            $data['amount'] = $this->toKobo($data['amount']);
        }

        return $this->makeRequest('POST', 'page', $data);
    }

    /**
     * List payment pages
     */
    public function listPages(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'page' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch a payment page
     */
    public function fetchPage(string $pageId): array
    {
        return $this->makeRequest('GET', "page/{$pageId}");
    }

    /**
     * Update a payment page
     */
    public function updatePage(string $pageId, array $data): array
    {
        if (isset($data['amount'])) {
            $data['amount'] = $this->toKobo($data['amount']);
        }

        return $this->makeRequest('PUT', "page/{$pageId}", $data);
    }

    /**
     * Check slug availability
     */
    public function checkSlugAvailability(string $slug): array
    {
        return $this->makeRequest('GET', "page/check_slug_availability/{$slug}");
    }

    // ==================== SETTLEMENTS ====================

    /**
     * List settlements
     */
    public function listSettlements(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'settlement' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch settlement transactions
     */
    public function fetchSettlementTransactions(string $settlementId, array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = "settlement/{$settlementId}/transactions" . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    // ==================== DEDICATED VIRTUAL ACCOUNTS ====================

    /**
     * Create a dedicated virtual account
     */
    public function createDedicatedAccount(array $data): array
    {
        $this->validateRequired($data, ['customer']);

        return $this->makeRequest('POST', 'dedicated_account', $data);
    }

    /**
     * List dedicated accounts
     */
    public function listDedicatedAccounts(array $params = []): array
    {
        $query = http_build_query($params);
        $endpoint = 'dedicated_account' . ($query ? '?' . $query : '');
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Fetch dedicated account
     */
    public function fetchDedicatedAccount(string $accountId): array
    {
        return $this->makeRequest('GET', "dedicated_account/{$accountId}");
    }

    /**
     * Deactivate a dedicated account
     */
    public function deactivateDedicatedAccount(string $accountId): array
    {
        return $this->makeRequest('DELETE', "dedicated_account/{$accountId}");
    }

    /**
     * Split a dedicated account transaction
     */
    public function splitDedicatedAccountTransaction(array $data): array
    {
        $this->validateRequired($data, ['customer', 'subaccount']);

        return $this->makeRequest('POST', 'dedicated_account/split', $data);
    }

    /**
     * Remove split from dedicated account
     */
    public function removeDedicatedAccountSplit(string $accountNumber): array
    {
        return $this->makeRequest('DELETE', "dedicated_account/split?account_number={$accountNumber}");
    }

    /**
     * Fetch bank providers
     */
    public function fetchBankProviders(): array
    {
        return $this->makeRequest('GET', 'dedicated_account/available_providers');
    }

    // ==================== APPLE PAY ====================

    /**
     * Register Apple Pay domain
     */
    public function registerApplePayDomain(string $domainName): array
    {
        return $this->makeRequest('POST', 'apple-pay/domain', [
            'domainName' => $domainName
        ]);
    }

    /**
     * List Apple Pay domains
     */
    public function listApplePayDomains(): array
    {
        return $this->makeRequest('GET', 'apple-pay/domain');
    }

    /**
     * Unregister Apple Pay domain
     */
    public function unregisterApplePayDomain(string $domainName): array
    {
        return $this->makeRequest('DELETE', 'apple-pay/domain', [
            'domainName' => $domainName
        ]);
    }

    // ==================== HELPERS ====================

    /**
     * Generate a unique transaction reference
     */
    public function generateReference(?string $prefix = null): string
    {
        $timestamp = time();
        $random = strtoupper(Str::random(10));
        $reference = "TXN_{$timestamp}_{$random}";
        
        return $prefix ? "{$prefix}_{$reference}" : $reference;
    }

    /**
     * Check if response is successful
     */
    public function isSuccessful(?array $response = null): bool
    {
        $response = $response ?? $this->lastResponse;
        return isset($response['status']) && $response['status'] === true;
    }

    /**
     * Get data from response
     */
    public function getData(?array $response = null): mixed
    {
        $response = $response ?? $this->lastResponse;
        return $response['data'] ?? null;
    }

    /**
     * Get message from response
     */
    public function getMessage(?array $response = null): ?string
    {
        $response = $response ?? $this->lastResponse;
        return $response['message'] ?? null;
    }

    /**
     * Get last response
     */
    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }

    /**
     * Get authorization URL from transaction initialization response
     */
    public function getAuthorizationUrl(?array $response = null): ?string
    {
        $response = $response ?? $this->lastResponse;
        
        if ($this->isSuccessful($response)) {
            return $response['data']['authorization_url'] ?? null;
        }
        
        return null;
    }

    /**
     * Get access code from transaction initialization response
     */
    public function getAccessCode(?array $response = null): ?string
    {
        $response = $response ?? $this->lastResponse;
        
        if ($this->isSuccessful($response)) {
            return $response['data']['access_code'] ?? null;
        }
        
        return null;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $hash = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($hash, $signature);
    }
}