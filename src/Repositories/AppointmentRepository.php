<?php
declare(strict_types=1);
namespace MyVet\Repositories;
final class AppointmentRepository {
    public function __construct(private DemoStore $store) {}
    public function forUser(int $userId, string $role): array { $field=$role==='veterinarian'?'veterinarian_id':'owner_id'; return array_values(array_filter($this->store->all('appointments'),fn($item)=>$item[$field]===$userId)); }
    public function forVeterinarianDate(int $vetId, string $date): array { return array_values(array_filter($this->store->all('appointments'),fn($item)=>$item['veterinarian_id']===$vetId && $item['appointment_date']===$date && in_array($item['status'],['pending','approved'],true))); }
    public function create(array $data): array { $records=$this->store->all('appointments'); $record=['id'=>$this->store->nextId('appointments'), ...$data, 'duration_minutes'=>15, 'status'=>'pending', 'cancel_reason'=>null]; $records[]=$record; $this->store->replace('appointments',$records); return $record; }
    public function find(int $id): ?array { foreach($this->store->all('appointments') as $item) if($item['id']===$id) return $item; return null; }
    public function updateStatus(int $id, string $status, ?string $reason = null): ?array { $records=$this->store->all('appointments'); foreach($records as $index=>$item) if($item['id']===$id) { $records[$index]['status']=$status; if($status==='cancelled') $records[$index]['cancel_reason']=$reason; $this->store->replace('appointments',$records); return $records[$index]; } return null; }
    public function reschedule(int $id, string $date, string $time): ?array { $records=$this->store->all('appointments'); foreach($records as $index=>$item) if($item['id']===$id) { $records[$index]['appointment_date']=$date; $records[$index]['appointment_time']=$time; $records[$index]['status']='pending'; $this->store->replace('appointments',$records); return $records[$index]; } return null; }
    public function extendAndShift(int $id, int $minutes): ?array {
        $records=$this->store->all('appointments'); $current=null; foreach($records as $item) if($item['id']===$id) $current=$item; if(!$current) return null;
        foreach($records as $index=>$item) { if($item['veterinarian_id']===$current['veterinarian_id'] && $item['appointment_date']===$current['appointment_date'] && $item['appointment_time']>$current['appointment_time'] && in_array($item['status'],['pending','approved'],true)) $records[$index]['appointment_time']=$this->addMinutes($item['appointment_time'],$minutes); if($item['id']===$id) $records[$index]['duration_minutes']=($item['duration_minutes'] ?? 15)+$minutes; }
        usort($records, fn($a,$b)=>$a['appointment_date']<=>$b['appointment_date'] ?: $a['appointment_time']<=>$b['appointment_time']); $this->store->replace('appointments',$records); return $this->find($id);
    }
    private function addMinutes(string $time, int $minutes): string { $value=(int)substr($time,0,2)*60+(int)substr($time,3,2)+$minutes; return sprintf('%02d:%02d',intdiv($value,60),$value%60); }
}
