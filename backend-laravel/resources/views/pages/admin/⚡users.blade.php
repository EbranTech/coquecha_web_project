<?php

use App\Models\Person;
use App\Models\Role;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Administrar usuarios')] class extends Component
{
    public ?int $editingId = null;
    public string $firstName = '';
    public string $lastName = '';
    public string $documentNumber = '';
    public string $phone = '';
    public string $email = '';
    public string $username = '';
    public string $password = '';
    public string $roleName = Role::NEIGHBOR;
    public bool $isActive = true;
    /** @var list<int> */ public array $sectorIds = [];

    public function save(): void
    {
        $this->ensureAdmin();
        $user = $this->editingId ? User::query()->with('person')->findOrFail($this->editingId) : null;
        $personId = $user?->person_id;
        $data = $this->validate([
            'firstName' => ['required', 'string', 'max:80'], 'lastName' => ['required', 'string', 'max:80'],
            'documentNumber' => ['required', 'string', 'max:50', Rule::unique('people', 'document_number')->ignore($personId)],
            'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:120'],
            'username' => ['required', 'string', 'max:60', Rule::unique('user_accounts', 'username')->ignore($this->editingId)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:128'],
            'roleName' => ['required', Rule::in([Role::ADMIN, Role::COORDINATOR, Role::NEIGHBOR])],
            'sectorIds' => ['array'], 'sectorIds.*' => ['integer', Rule::exists('sectors', 'id')],
        ]);
        $person = Person::query()->updateOrCreate(['id' => $personId], [
            'first_name' => $data['firstName'], 'last_name' => $data['lastName'], 'document_number' => $data['documentNumber'],
            'phone' => $data['phone'] ?: null, 'email' => $data['email'] ?: null, 'status' => $this->isActive ? 'active' : 'inactive',
        ]);
        $account = User::query()->updateOrCreate(['id' => $this->editingId], array_filter([
            'person_id' => $person->id, 'role_id' => Role::query()->where('name', $data['roleName'])->value('id'),
            'username' => $data['username'], 'is_active' => $this->isActive,
            'password_hash' => $data['password'] ? Hash::make($data['password']) : null,
        ], fn ($value) => $value !== null));
        $account->managedSectors()->sync($data['roleName'] === Role::COORDINATOR ? $data['sectorIds'] : []);
        session()->flash('success', $this->editingId ? 'Usuario actualizado.' : 'Usuario creado.');
        $this->resetForm();
    }

    public function edit(int $userId): void
    {
        $this->ensureAdmin(); $user = User::query()->with(['person', 'role', 'managedSectors'])->findOrFail($userId);
        $this->editingId = $user->id; $this->firstName = $user->person->first_name; $this->lastName = $user->person->last_name;
        $this->documentNumber = $user->person->document_number; $this->phone = $user->person->phone ?? ''; $this->email = $user->person->email ?? '';
        $this->username = $user->username; $this->roleName = $user->role->name; $this->isActive = $user->is_active;
        $this->sectorIds = $user->managedSectors->pluck('id')->all(); $this->password = '';
    }
    public function cancel(): void { $this->resetForm(); }
    private function resetForm(): void { $this->reset('editingId', 'firstName', 'lastName', 'documentNumber', 'phone', 'email', 'username', 'password', 'sectorIds'); $this->roleName = Role::NEIGHBOR; $this->isActive = true; $this->resetValidation(); }
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole(Role::ADMIN), 403); }
    public function render() { return $this->view(['users' => User::query()->with(['person', 'role', 'managedSectors'])->orderBy('username')->get(), 'sectors' => Sector::query()->where('status', 'active')->orderBy('name')->get()]); }
};
?>

<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_26rem]">
 <section><p class="text-sm font-medium text-sky-700">Acceso y responsabilidades</p><h1 class="mt-1 text-3xl font-bold">Usuarios</h1>@if(session('success'))<p class="mt-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('success') }}</p>@endif
 <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200"><table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Usuario</th><th class="px-4 py-3 text-left">Rol</th><th class="px-4 py-3 text-left">Sectores</th><th></th></tr></thead><tbody>@foreach($users as $user)<tr class="border-t"><td class="px-4 py-3"><strong>{{ $user->display_name }}</strong><br><span class="text-slate-500">{{ $user->username }} · {{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td><td class="px-4 py-3">{{ $user->role->name }}</td><td class="px-4 py-3">{{ $user->managedSectors->pluck('name')->join(', ') ?: '—' }}</td><td class="px-4 py-3"><button class="text-sky-700 hover:underline" wire:click="edit({{ $user->id }})">Editar</button></td></tr>@endforeach</tbody></table></div></section>
 <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h2 class="text-lg font-semibold">{{ $editingId ? 'Editar usuario' : 'Nuevo usuario' }}</h2><form class="mt-4 space-y-3" wire:submit="save"><div class="grid grid-cols-2 gap-2"><input class="rounded border-slate-300" placeholder="Nombres" wire:model="firstName"><input class="rounded border-slate-300" placeholder="Apellidos" wire:model="lastName"></div><input class="w-full rounded border-slate-300" placeholder="Documento" wire:model="documentNumber"><input class="w-full rounded border-slate-300" placeholder="Teléfono" wire:model="phone"><input class="w-full rounded border-slate-300" placeholder="Correo" wire:model="email"><input class="w-full rounded border-slate-300" placeholder="Usuario" wire:model="username"><input class="w-full rounded border-slate-300" type="password" placeholder="{{ $editingId ? 'Nueva contraseña (opcional)' : 'Contraseña (mínimo 8)' }}" wire:model="password"><select class="w-full rounded border-slate-300" wire:model.live="roleName"><option value="neighbor">Vecino</option><option value="coordinator">Coordinador</option><option value="admin">Administrador</option></select>@if($roleName === 'coordinator')<div class="rounded border p-3"><p class="text-sm font-medium">Sectores asignados</p>@foreach($sectors as $sector)<label class="mt-2 flex gap-2 text-sm"><input type="checkbox" value="{{ $sector->id }}" wire:model="sectorIds">{{ $sector->name }}</label>@endforeach</div>@endif<label class="flex gap-2 text-sm"><input type="checkbox" wire:model="isActive">Cuenta activa</label><div class="flex gap-3"><button class="rounded bg-sky-700 px-4 py-2 text-white" type="submit">{{ $editingId ? 'Guardar' : 'Crear usuario' }}</button>@if($editingId)<button type="button" wire:click="cancel">Cancelar</button>@endif</div>@foreach(['firstName','lastName','documentNumber','username','password','roleName','sectorIds'] as $field)@error($field)<p class="text-sm text-red-600">{{ $message }}</p>@enderror@endforeach</form></aside>
</div>
