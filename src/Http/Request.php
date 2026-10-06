<?php
declare(strict_types=1);
namespace MyVet\Http;
final class Request {
    public function method(): string { return $_SERVER['REQUEST_METHOD'] ?? 'GET'; }
    public function path(): string { return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; }
    public function query(string $key, ?string $default = null): ?string { return $_GET[$key] ?? $default; }
    public function body(): array { $body = json_decode(file_get_contents('php://input'), true); return is_array($body) ? $body : []; }
    public function bearerToken(): ?string { $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ''; return preg_match('/^Bearer\s+(.+)$/i', $header, $match) ? $match[1] : null; }
}
