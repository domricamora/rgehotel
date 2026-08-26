<?php
require dirname(__DIR__) . '/app/Core/helpers.php';
require dirname(__DIR__) . '/app/Services/PayMongo.php';

use App\Services\PayMongo;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

check(PayMongo::checkoutUrl([
    'id' => 'cs_test_123',
    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123'],
]) === 'https://checkout.paymongo.com/cs_test_123', 'accepts a valid PayMongo checkout URL');

check(PayMongo::checkoutUrl([
    'id' => 'cs_test_123',
    'attributes' => ['checkout_url' => 'javascript:alert(1)'],
]) === null, 'rejects unsafe checkout URL schemes');

check(PayMongo::checkoutUrl([
    'id' => 'cs_test_123',
    'attributes' => [],
]) === null, 'rejects a response without checkout URL');

fwrite(STDOUT, "PayMongo checks passed\n");
