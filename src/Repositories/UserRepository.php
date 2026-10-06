<?php
declare(strict_types=1);
namespace MyVet\Repositories;
use MyVet\Models\User;
final class UserRepository {
    public function __construct(private DemoStore $store) {}
    public function byEmail(string $email): ?array { foreach ($this->store->all('users') as $user) if (strtolower($user['email']) === strtolower($email)) return $user; return null; }
    public function find(int $id): ?User { foreach ($this->store->all('users') as $user) if ($user['id'] === $id) return User::fromArray($user); return null; }
    public function createToken(int $userId): string { $token = bin2hex(random_bytes(32)); $tokens = $this->store->all('tokens'); $tokens[] = ['token'=>$token, 'user_id'=>$userId]; $this->store->replace('tokens', $tokens); return $token; }
    public function userFromToken(string $token): ?User { foreach ($this->store->all('tokens') as $item) if (hash_equals($item['token'], $token)) return $this->find((int)$item['user_id']); return null; }
}
