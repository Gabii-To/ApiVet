<?php
declare(strict_types=1);
namespace MyVet\Models;
final class Pet {
    public function __construct(public int $id, public int $ownerId, public string $name, public string $species, public ?string $breed, public string $birthDate) {}
    public static function fromArray(array $data): self { return new self((int)$data['id'], (int)$data['owner_id'], $data['name'], $data['species'], $data['breed'] ?? null, $data['birth_date']); }
    public function toArray(): array { return ['id'=>$this->id, 'owner_id'=>$this->ownerId, 'name'=>$this->name, 'species'=>$this->species, 'breed'=>$this->breed, 'birth_date'=>$this->birthDate]; }
}
