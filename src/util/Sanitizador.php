<?php

namespace MinhaAgenda\Util;

abstract class Sanitizador {
    public static function sanitizarEmail(string $email): string {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    public static function sanitizarUrl(string $url): string {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }

    public static function sanitizarString(string $texto): string {
        return htmlspecialchars(strip_tags(trim($texto)), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizarInteiro(string $numero): ?int {
        return filter_var($numero, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function sanitizarNumeroDecimal(string $numero): ?float {
        return filter_var($numero, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}