<?php

namespace App\PaymentGateways\Enums;

enum PaymentGatewayErrorType: string
{
    case CONFIGURATION = 'configuration';
    case NETWORK = 'network';
    case MALFORMED_RESPONSE = 'malformed_response';
    case CLIENT_ERROR = 'client_error';
    case SERVER_ERROR = 'server_error';

    public function isRetryable(): bool
    {
        return match ($this) {
            self::NETWORK,
            self::MALFORMED_RESPONSE,
            self::SERVER_ERROR => true,
            self::CONFIGURATION,
            self::CLIENT_ERROR => false,
        };
    }
}
