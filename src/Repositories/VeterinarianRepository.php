<?php
declare(strict_types=1);
namespace MyVet\Repositories;
final class VeterinarianRepository {
    public function __construct(private DemoStore $store) {}
    public function linkedTo(int $ownerId): array { $ids=array_column(array_filter($this->store->all('owner_veterinarians'),fn($link)=>$link['owner_id']===$ownerId),'veterinarian_id'); return array_values(array_map(fn($user)=>['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email']],array_filter($this->store->all('users'),fn($user)=>in_array($user['id'],$ids,true)))); }
    public function isLinked(int $ownerId, int $vetId): bool { return in_array($vetId,array_column($this->linkedTo($ownerId),'id'),true); }
    public function createInvitation(int $vetId): array { $invitations=$this->store->all('invitations'); $code='INV-'.strtoupper(substr(bin2hex(random_bytes(3)),0,6)); $record=['id'=>$this->store->nextId('invitations'),'veterinarian_id'=>$vetId,'code'=>$code,'status'=>'active','used_by_owner_id'=>null]; $invitations[]=$record; $this->store->replace('invitations',$invitations); return $record; }
    public function invitationsFor(int $vetId): array { return array_values(array_filter($this->store->all('invitations'),fn($item)=>$item['veterinarian_id']===$vetId)); }
    public function redeem(int $ownerId, string $code): ?array { $invitations=$this->store->all('invitations'); foreach($invitations as $index=>$item) { if (hash_equals(strtoupper($item['code']),strtoupper($code)) && $item['status']==='active') { if ($this->isLinked($ownerId,$item['veterinarian_id'])) return null; $invitations[$index]['status']='used'; $invitations[$index]['used_by_owner_id']=$ownerId; $links=$this->store->all('owner_veterinarians'); $links[]=['id'=>$this->store->nextId('owner_veterinarians'),'owner_id'=>$ownerId,'veterinarian_id'=>$item['veterinarian_id']]; $this->store->replace('invitations',$invitations); $this->store->replace('owner_veterinarians',$links); return $invitations[$index]; } } return null; }
}
