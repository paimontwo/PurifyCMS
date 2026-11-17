<?php
/**
 * PurifyPaymentGateway - The Abstract Blueprint for v3.4.1
 * Every payment driver must implement this high-performance contract.
 */
abstract class PurifyPaymentGateway {
    
    /**
     * Step 1: Token/Session Acquisition
     * Should return a structured array with status and necessary token/redirect data.
     */
    abstract public function getToken($order_id, $amount, $customer, $items);

    /**
     * Step 2: Protocol Verification (Webhooks/Callbacks)
     * Should return a boolean indicating success of the signature check.
     */
    abstract public function verifyCallback($post_data);

}
?>
