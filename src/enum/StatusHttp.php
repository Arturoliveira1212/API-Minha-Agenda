<?php

namespace MinhaAgenda\Enum;

enum StatusHttp: int {
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;

    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case NOT_MODIFIED = 304;

    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case CONFLICT = 409;
    case UNPROCESSABLE_ENTITY = 422;

    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;

    public static function statusSucesso(): array {
        return [
            self::OK,
            self::CREATED,
            self::ACCEPTED,
            self::NO_CONTENT
        ];
    }

    public static function statusEhSucesso(StatusHttp $status): bool {
        return in_array($status, self::statusSucesso());
    }
}