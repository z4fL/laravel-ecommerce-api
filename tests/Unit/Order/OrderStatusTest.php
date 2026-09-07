<?php

use App\Enum\OrderStatus;

describe('order status transitions', function () {
    it('allows only the configured forward transitions', function (OrderStatus $current, OrderStatus $target) {
        expect($current->canTransitionTo($target))->toBeTrue();
    })->with([
        [OrderStatus::PENDING_PAYMENT, OrderStatus::PAID],
        [OrderStatus::PENDING_PAYMENT, OrderStatus::CANCELLED],
        [OrderStatus::PAID, OrderStatus::PROCESSING],
        [OrderStatus::PAID, OrderStatus::CANCELLED],
        [OrderStatus::PROCESSING, OrderStatus::SHIPPED],
        [OrderStatus::SHIPPED, OrderStatus::COMPLETED],
    ]);

    it('rejects transitions that move backwards or leave a final status', function (OrderStatus $current, OrderStatus $target) {
        expect($current->canTransitionTo($target))->toBeFalse();
    })->with([
        [OrderStatus::PAID, OrderStatus::PENDING_PAYMENT],
        [OrderStatus::COMPLETED, OrderStatus::PAID],
        [OrderStatus::CANCELLED, OrderStatus::PAID],
        [OrderStatus::PROCESSING, OrderStatus::CANCELLED],
    ]);

    it('marks completed and cancelled as final', function (OrderStatus $status) {
        expect($status->isFinal())->toBeTrue();
    })->with([
        OrderStatus::COMPLETED,
        OrderStatus::CANCELLED,
    ]);

    it('does not mark active statuses as final', function (OrderStatus $status) {
        expect($status->isFinal())->toBeFalse();
    })->with([
        OrderStatus::PENDING_PAYMENT,
        OrderStatus::PAYMENT_FAILED,
        OrderStatus::PAID,
        OrderStatus::PROCESSING,
        OrderStatus::SHIPPED,
    ]);
});