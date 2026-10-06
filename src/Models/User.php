<?php
declare(strict_types=1);
namespace MyVet\Models;
final class User {
    public function __construct(public int $id, public string $name, public string $email, public string $role) {}
    public static function fromArray(array $data): self { return new self((int)$data['id'], $data['name'], $data['email'], $data['role']); }
    public function toArray(): array { return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $this->role]; }
}
