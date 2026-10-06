<?php
declare(strict_types=1);
namespace MyVet\Repositories;

final class DemoStore {
    private array $data;
    public function __construct(private string $path) {
        if (!is_file($path)) $this->seed();
        $this->data = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
    public function all(string $collection): array { return $this->data[$collection] ?? []; }
    public function replace(string $collection, array $records): void { $this->data[$collection] = array_values($records); $this->persist(); }
    public function nextId(string $collection): int { return empty($this->data[$collection]) ? 1 : max(array_column($this->data[$collection], 'id')) + 1; }
    private function persist(): void { file_put_contents($this->path, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX); }
    private function seed(): void {
        $this->data = [
            'users' => [
                ['id'=>1, 'name'=>'Sofía Gómez', 'email'=>'owner@myvet.test', 'password_hash'=>password_hash('owner123', PASSWORD_DEFAULT), 'role'=>'owner'],
                ['id'=>2, 'name'=>'Dra. Valentina Ruiz', 'email'=>'vet@myvet.test', 'password_hash'=>password_hash('vet123', PASSWORD_DEFAULT), 'role'=>'veterinarian'],
            ],
            'pets' => [
                ['id'=>1, 'owner_id'=>1, 'name'=>'Milo', 'species'=>'Perro', 'breed'=>'Golden retriever', 'birth_date'=>'2022-04-15'],
                ['id'=>2, 'owner_id'=>1, 'name'=>'Luna', 'species'=>'Gato', 'breed'=>'Siamés', 'birth_date'=>'2024-01-10'],
            ],
            'owner_veterinarians' => [['id'=>1, 'owner_id'=>1, 'veterinarian_id'=>2]],
            'invitations' => [],
            'appointments' => [
                ['id'=>1, 'pet_id'=>1, 'owner_id'=>1, 'veterinarian_id'=>2, 'appointment_date'=>'2026-10-08', 'appointment_time'=>'09:30', 'duration_minutes'=>15, 'reason'=>'Control anual', 'status'=>'approved', 'cancel_reason'=>null],
                ['id'=>2, 'pet_id'=>2, 'owner_id'=>1, 'veterinarian_id'=>2, 'appointment_date'=>'2026-10-08', 'appointment_time'=>'10:15', 'duration_minutes'=>15, 'reason'=>'Vacunación', 'status'=>'pending', 'cancel_reason'=>null],
            ],
            'tokens' => [],
        ];
        $this->persist();
    }
}
