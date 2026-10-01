<?php

/**
 * Shared order-creation logic.
 *
 * Used by:
 * - public/checkout.php        (Cash on Delivery)
 * - public/stripe-return.php   (after Stripe payment is confirmed)
 */

if (!function_exists('placeOrder')) {

    /**
     * Inserts the order + order_items rows and reduces stock.
     * Returns the new order id.
     */
    function placeOrder(
        mysqli $mysqli,
        int $userId,
        array $cartItems,
        float $totalAmount,
        string $shippingAddress,
        string $paymentMethod,
        string $paymentStatus
    ): int {

        $mysqli->begin_transaction();

        try {

            $orderNumber = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);

            $stmt = $mysqli->prepare(
                "INSERT INTO orders
                (
                    user_id,
                    order_number,
                    total_amount,
                    payment_method,
                    payment_status,
                    order_status,
                    shipping_address
                )
                VALUES (?, ?, ?, ?, ?, 'processing', ?)"
            );

            $stmt->bind_param(
                "isdsss",
                $userId,
                $orderNumber,
                $totalAmount,
                $paymentMethod,
                $paymentStatus,
                $shippingAddress
            );

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Failed to create order.');
            }

            $orderId = $stmt->insert_id;
            $stmt->close();

            foreach ($cartItems as $item) {

                $stmt = $mysqli->prepare(
                    "INSERT INTO order_items
                    (order_id, product_id, quantity, unit_price, subtotal)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "iiidd",
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal']
                );

                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new Exception('Failed to create order items.');
                }

                $stmt->close();

                $stmt = $mysqli->prepare(
                    "UPDATE products SET stock = stock - ? WHERE id = ?"
                );

                $stmt->bind_param("ii", $item['quantity'], $item['product_id']);

                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new Exception('Failed to update product stock.');
                }

                $stmt->close();
            }

            $mysqli->commit();

            return $orderId;

        } catch (Throwable $e) {

            $mysqli->rollback();
            throw $e;
        }
    }
}