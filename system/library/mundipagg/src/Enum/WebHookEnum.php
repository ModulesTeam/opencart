<?php

namespace Mundipagg\Enum;

class WebHookEnum
{
    const TYPE_CHARGE = 'charge';
    const TYPE_ORDER = 'order';

    const ACTION_PAID = 'paid';
    const ACTION_CREATED = 'created';
    const ACTION_CANCELED = 'canceled';
    const ACTION_OVERPAID = 'overpaid';
    const ACTION_UNDERPAID = 'underpaid';
    const ACTION_REFUNDED = 'refunded';
    const ACTION_PAYMENT_FAILED = 'payment_failed';
    const ACTION_PARTIAL_CANCELED = "partial_canceled";
}
