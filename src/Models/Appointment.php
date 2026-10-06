<?php
declare(strict_types=1);
namespace MyVet\Models;
final class Appointment {
    public function __construct(public int $id, public int $petId, public int $ownerId, public int $veterinarianId, public string $date, public string $time, public string $reason, public string $status) {}
    public static function fromArray(array $data): self { return new self((int)$data['id'], (int)$data['pet_id'], (int)$data['owner_id'], (int)$data['veterinarian_id'], $data['appointment_date'], $data['appointment_time'], $data['reason'], $data['status']); }
    public function toArray(): array { return ['id' => $this->id, 'pet_id' => $this->petId, 'owner_id' => $this->ownerId, 'veterinarian_id' => $this->veterinarianId, 'appointment_date' => $this->date, 'appointment_time' => $this->time, 'reason' => $this->reason, 'status' => $this->status]; }
}
