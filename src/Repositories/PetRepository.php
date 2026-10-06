<?php
declare(strict_types=1);
namespace MyVet\Repositories;
final class PetRepository {
    public function __construct(private DemoStore $store) {}
    public function forOwner(int $ownerId): array { return array_values(array_filter($this->store->all('pets'), fn($pet) => $pet['owner_id'] === $ownerId)); }
    public function findForOwner(int $petId, int $ownerId): ?array { foreach ($this->forOwner($ownerId) as $pet) if ($pet['id'] === $petId) return $pet; return null; }
    public function create(int $ownerId, array $data): array { $pets = $this->store->all('pets'); $pet = ['id'=>$this->store->nextId('pets'), 'owner_id'=>$ownerId, 'name'=>$data['name'], 'species'=>$data['species'], 'breed'=>$data['breed'] ?? null, 'birth_date'=>$data['birth_date']]; $pets[]=$pet; $this->store->replace('pets',$pets); return $pet; }
    public function patientsForVeterinarian(int $veterinarianId): array {
        $petIds = array_unique(array_column(array_filter($this->store->all('appointments'), fn($appointment) => $appointment['veterinarian_id'] === $veterinarianId), 'pet_id'));
        $owners = []; foreach ($this->store->all('users') as $user) $owners[$user['id']] = $user['name'];
        return array_values(array_map(function (array $pet) use ($owners) {
            $birthDate = new \DateTimeImmutable($pet['birth_date']);
            $age = $birthDate->diff(new \DateTimeImmutable('today'))->y;
            return ['id'=>$pet['id'], 'name'=>$pet['name'], 'species'=>$pet['species'], 'breed'=>$pet['breed'], 'owner_name'=>$owners[$pet['owner_id']] ?? 'Sin propietario', 'age'=>"$age años"];
        }, array_filter($this->store->all('pets'), fn($pet) => in_array($pet['id'], $petIds, true))));
    }
}
