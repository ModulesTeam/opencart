<?php

namespace Mundipagg;

class LogMessages
{
    const LOG_HEADER = 'Mundipagg Opencart 1.2.9 |';
    
    /** Error messages */
    const UNKNOWN_ORDER_STATUS = 'Unknown order status received';
    const INVALID_CREDIT_CARD_REQUEST = 'Invalid credit card request';
    const MALFORMED_REQUEST = 'Malformed request';
    const ORDER_ID_NOT_FOUND = 'Order id not found';
    const API_REQUEST_FAIL = 'MundiPagg api request failed';
    const UNKNOWN_API_RESPONSE = 'Unknown MundiPagg api response';
    const UNABLE_TO_CREATE_ORDER = 'Unable to create Order in Mundipagg';
    const UNABLE_TO_CREATE_MUNDI_ORDER = 'Unable to create order in mundipagg_order';
    const UNABLE_TO_SAVE_MUNDI_CHARGE = 'Unable to save charge in table mundipagg_charge';
    const UNABLE_TO_CANCEL_MUNDI_CHARGE = 'Unable to cancel charge';
    const UNKNOWN_WEBHOOK_TYPE = 'Unknown webhook type received';
    const UNABLE_TO_UPDATE_ORDER_AMOUNT = 'Unable to update the order amount';
    const CANNOT_SAVE_CREDIT_CARD_DATA = 'Cannot save credit card data';
    const CANNOT_DELETE_CREDIT_CARD_DATA = 'Cannot delete credit card data';
    const CANNOT_GET_CREDIT_CARD_DATA = 'Cannot get credit card data';
    const CANNOT_GET_CUSTOMER_DATA = 'Cannot get customer data';
    const CANNOT_CREATE_TWO_CREDIT_CARDS_ORDER = 'Cannot create two credit cards order';
    const CANNOT_CREATE_SAVE_BOLETO_LINK = 'Cannot save boleto link';
    const CANNOT_UPDATE_ORDER_AMOUNT = 'Cannot update order amount';

    /** Debug Messages */
    const ORDER_NOT_FOUND_IN_ORDER_TABLE = 'Mundipagg order id not found in mundipagg_order table';

    /** Info Messages */
    const ORDER_ALREADY_UPDATED = 'Order already updated';
    const ORDER_STATUS_CHANGED = 'Order status changed';
    const ERROR_DURING_STATUS_UPDATE = 'Error during order status update';
    const REQUEST_INFO = 'Request information';
    const CREATE_ORDER_MUNDIPAGG_REQUEST = 'Create a Mundipagg order';
    const CREATE_ORDER_MUNDIPAGG_RESPONSE = 'Response from Mundipagg';
    const UPDATE_CHARGE_MUNDIPAGG_REQUEST = 'Update a Mundipagg charge';
    const UPDATE_CHARGE_MUNDIPAGG_RESPONSE = 'Response from Mundipagg';
    const ORDER_CREATED = 'Received an order created';

    const CREATE_PLAN_MUNDIPAGG_REQUEST = 'Create a Mundipagg plan';
    const CREATE_PLAN_MUNDIPAGG_RESPONSE = 'Plan creation response from Mundipagg';

    /** Warning Messages */
    const UNABLE_TO_SAVE_TRANSACTION = 'Unable to save transaction into mundipagg_transaction table';

    /** Webhook */
    const INVALID_WEBHOOK_REQUEST = 'Module received an invalid webhook request from MundiPagg';
    const INVALID_WEBHOOK_ORDER_STATUS = 'Module received an unknown webhook order status from Mundipagg';
    const INVALID_WEBHOOK_CHARGE_STATUS = 'Module received an unknown webhook charge status from Mundipagg';
    const CANNOT_SET_ORDER_STATUS = 'Unable to set order status';
    const CREATE_WEBHOOK_RECEIVED = 'WebHook create received';
}
