<?php

use App\Enum\PaymentStatus;

it('allows pending payments to reach terminal payment outcomes', function () {
    expect(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::PAID))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::FAILED))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::EXPIRED))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::CANCELLED))->toBeTrue();
});

it('allows paid payments to be refunded', function () {
    expect(PaymentStatus::PAID->canTransitionTo(PaymentStatus::REFUNDED))->toBeTrue();
});

it('rejects transitions from terminal non-paid statuses', function (PaymentStatus $status) {
    expect($status->canTransitionTo(PaymentStatus::PAID))->toBeFalse()
        ->and($status->canTransitionTo(PaymentStatus::REFUNDED))->toBeFalse();
})->with([
    PaymentStatus::FAILED,
    PaymentStatus::EXPIRED,
    PaymentStatus::CANCELLED,
    PaymentStatus::REFUNDED,
]);
