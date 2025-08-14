<?php

namespace MinhaAgenda\Enum;

enum TipoToken: string {
    case ACCESS_TOKEN = 'access_token';
    case REFRESH_TOKEN = 'refresh_token';
}