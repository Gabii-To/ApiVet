<?php
declare(strict_types=1);
namespace MyVet\Http;
final class Response {
    public static function json(array $data, int $status = 200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
    public static function error(string $error, string $message, int $status, array $details = []): never { self::json(array_filter(['error' => $error, 'message' => $message, 'details' => $details ?: null]), $status); }
}
