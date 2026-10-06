<?php
declare(strict_types=1);
namespace MyVet\Controllers;
use DateTimeImmutable;
use MyVet\Http\Request;
use MyVet\Http\Response;
use MyVet\Repositories\AppointmentRepository;
use MyVet\Repositories\PetRepository;
use MyVet\Repositories\VeterinarianRepository;
use MyVet\Services\AuthService;
final class AppointmentController {
    private const TIMES = ['09:00','09:15','09:30','09:45','10:00','10:15','10:30','10:45','11:00','11:15','11:30','11:45','12:00','12:15','12:30','12:45','13:00','13:15','13:30','13:45','14:00','14:15','14:30','14:45','15:00','15:15','15:30','15:45','16:00','16:15','16:30','16:45','17:00','17:15','17:30','17:45'];
    public function __construct(private AuthService $auth, private AppointmentRepository $appointments, private PetRepository $pets, private VeterinarianRepository $vets) {}
    public function index(Request $request): never { $user=$this->auth->user($request); $items=$this->appointments->forUser($user->id,$user->role); $status=$request->query('status'); if($status) $items=array_values(array_filter($items,fn($item)=>$item['status']===$status)); Response::json(['data'=>$items]); }
    public function availability(Request $request, int $vetId): never {
        $this->auth->user($request); $date=(string)$request->query('date',''); if(!$this->validDate($date)) Response::error('validation_error','La fecha debe tener formato YYYY-MM-DD y no puede estar en el pasado.',422);
        $busy=$this->appointments->forVeterinarianDate($vetId,$date); $slots=array_map(fn($time)=>['time'=>$time,'available'=>!$this->overlaps($time,15,$busy)],self::TIMES); Response::json(['veterinarian_id'=>$vetId,'date'=>$date,'slots'=>$slots]);
    }
    public function create(Request $request): never {
        $owner=$this->auth->requireRole($request,'owner'); $body=$request->body();
        foreach(['pet_id','veterinarian_id','appointment_date','appointment_time','reason'] as $field) if(empty($body[$field])) Response::error('validation_error',"$field es obligatorio.",422);
        $pet=$this->pets->findForOwner((int)$body['pet_id'],$owner->id); if(!$pet) Response::error('forbidden','La mascota no pertenece al propietario autenticado.',403);
        $vetId=(int)$body['veterinarian_id']; if(!$this->vets->isLinked($owner->id,$vetId)) Response::error('forbidden','El veterinario no está en tu lista.',403);
        if(!$this->validDate((string)$body['appointment_date']) || !in_array($body['appointment_time'],self::TIMES,true)) Response::error('validation_error','Fecha u horario inválidos.',422);
        $occupied=$this->appointments->forVeterinarianDate($vetId,$body['appointment_date']); if($this->overlaps($body['appointment_time'],15,$occupied)) Response::error('slot_unavailable','Ese horario ya está reservado.',409);
        $item=$this->appointments->create(['pet_id'=>(int)$body['pet_id'],'owner_id'=>$owner->id,'veterinarian_id'=>$vetId,'appointment_date'=>$body['appointment_date'],'appointment_time'=>$body['appointment_time'],'reason'=>trim($body['reason'])]); Response::json(['data'=>$item],201);
    }
    public function changeStatus(Request $request, int $id, string $action): never {
        $user=$this->auth->user($request); $item=$this->appointments->find($id); if(!$item) Response::error('not_found','Consulta no encontrada.',404);
        $status=['approve'=>'approved','complete'=>'completed','cancel'=>'cancelled'][$action];
        $allowed=($user->role==='veterinarian' && $item['veterinarian_id']===$user->id) || ($action==='cancel' && $user->role==='owner' && $item['owner_id']===$user->id && in_array($item['status'],['pending','approved'],true));
        if(!$allowed) Response::error('forbidden','No podés cambiar el estado de esta consulta.',403);
        $reason=trim((string)($request->body()['reason']??'')); if($action==='cancel' && $user->role==='veterinarian' && $reason==='') Response::error('validation_error','El motivo de cancelación es obligatorio para el veterinario.',422);
        Response::json(['data'=>$this->appointments->updateStatus($id,$status,$reason?:null)]);
    }
    public function reschedule(Request $request, int $id): never {
        $owner=$this->auth->requireRole($request,'owner'); $item=$this->appointments->find($id); if(!$item) Response::error('not_found','Consulta no encontrada.',404); if($item['owner_id']!==$owner->id || !in_array($item['status'],['pending','approved'],true)) Response::error('forbidden','No podés reprogramar esta consulta.',403);
        $body=$request->body(); $date=(string)($body['appointment_date']??''); $time=(string)($body['appointment_time']??''); if(!$this->validDate($date) || !in_array($time,self::TIMES,true)) Response::error('validation_error','Fecha u horario inválidos.',422);
        $active=array_filter($this->appointments->forVeterinarianDate($item['veterinarian_id'],$date),fn($other)=>$other['id']!==$id); if($this->overlaps($time,(int)($item['duration_minutes']??15),$active)) Response::error('slot_unavailable','Ese horario ya está reservado.',409);
        Response::json(['data'=>$this->appointments->reschedule($id,$date,$time)]);
    }
    public function extend(Request $request, int $id): never {
        $vet=$this->auth->requireRole($request,'veterinarian'); $item=$this->appointments->find($id); if(!$item) Response::error('not_found','Consulta no encontrada.',404); if($item['veterinarian_id']!==$vet->id || !in_array($item['status'],['pending','approved'],true)) Response::error('forbidden','No podés extender esta consulta.',403);
        $minutes=(int)($request->body()['minutes']??0); if($minutes<15 || $minutes%15!==0) Response::error('validation_error','La extensión debe ser múltiplo de 15 minutos.',422);
        $following=array_filter($this->appointments->forVeterinarianDate($vet->id,$item['appointment_date']),fn($other)=>$other['appointment_time']>$item['appointment_time']); $lastEnd=$this->minutes($item['appointment_time'])+(int)($item['duration_minutes']??15)+$minutes; foreach($following as $other) $lastEnd=max($lastEnd,$this->minutes($other['appointment_time'])+(int)($other['duration_minutes']??15)+$minutes); if($lastEnd>18*60) Response::error('schedule_overflow','La extensión desplazaría turnos fuera del horario laboral.',409);
        Response::json(['data'=>$this->appointments->extendAndShift($id,$minutes)]);
    }
    private function validDate(string $date): bool { $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date); return $parsed && $parsed->format('Y-m-d')===$date && $date >= (new DateTimeImmutable('today'))->format('Y-m-d'); }
    private function overlaps(string $start, int $duration, array $appointments): bool { $from=$this->minutes($start); $to=$from+$duration; foreach($appointments as $appointment) { $otherFrom=$this->minutes($appointment['appointment_time']); $otherTo=$otherFrom+(int)($appointment['duration_minutes']??15); if($from<$otherTo && $to>$otherFrom) return true; } return false; }
    private function minutes(string $time): int { return (int)substr($time,0,2)*60+(int)substr($time,3,2); }
}
