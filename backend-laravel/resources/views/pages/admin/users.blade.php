@extends('layouts.app')

@section('content')
<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_26rem]">
 <section><p class="text-sm font-medium text-sky-700">Acceso y responsabilidades</p><h1 class="mt-1 text-3xl font-bold">Usuarios</h1>@if(session('success'))<p class="mt-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('success') }}</p>@endif
 <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200"><table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Usuario</th><th class="px-4 py-3 text-left">Rol</th><th class="px-4 py-3 text-left">Sectores</th><th></th></tr></thead><tbody>@foreach($users as $user)<tr class="border-t"><td class="px-4 py-3"><strong>{{ $user->display_name }}</strong><br><span class="text-slate-500">{{ $user->username }} · {{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td><td class="px-4 py-3">{{ $user->role->name }}</td><td class="px-4 py-3">{{ $user->managedSectors->pluck('name')->join(', ') ?: '—' }}</td><td class="px-4 py-3"><a class="text-sky-700 hover:underline" href="{{ route('admin.users.edit', $user) }}">Editar</a></td></tr>@endforeach</tbody></table></div></section>
 <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h2 class="text-lg font-semibold">{{ $editingId ? 'Editar usuario' : 'Nuevo usuario' }}</h2><form class="mt-4 space-y-3" method="POST" action="{{ $editingId ? route('admin.users.update', $editingId) : route('admin.users.store') }}">
      @csrf
@if ($editingId) @method('PUT') @endif

<div class="grid grid-cols-2 gap-2">
    <input class="rounded border-slate-300" placeholder="Nombres" name="firstName" value="{{ old('firstName', $firstName) }}">
    <input class="rounded border-slate-300" placeholder="Apellidos" name="lastName" value="{{ old('lastName', $lastName) }}">
</div>

<input
    class="w-full rounded border-slate-300"
    type="text"
    inputmode="numeric"
    maxlength="13"
    pattern="[0-9]{13}"
    title="Debe contener 13 dígitos numéricos"
    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 13)"
    placeholder="Documento (13 dígitos)"
    name="documentNumber"
    value="{{ old('documentNumber', $documentNumber) }}"
>

<input
    class="w-full rounded border-slate-300"
    type="text"
    inputmode="numeric"
    maxlength="8"
    pattern="[0-9]{8}"
    title="Debe contener 8 dígitos numéricos"
    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 8)"
    placeholder="Teléfono (8 dígitos)"
    name="phone"
    value="{{ old('phone', $phone) }}"
>

<input class="w-full rounded border-slate-300" placeholder="Correo" name="email" value="{{ old('email', $email) }}">
<input class="w-full rounded border-slate-300" placeholder="Usuario" name="username" value="{{ old('username', $username) }}">
<input class="w-full rounded border-slate-300" type="password" placeholder="{{ $editingId ? 'Nueva contraseña (opcional)' : 'Contraseña (mínimo 8)' }}" name="password" value="">

@php
    $selectedRole = (string) old('roleName', $roleName);
@endphp

<select id="roleNameSelect" class="w-full rounded border-slate-300" name="roleName">
    <option value="neighbor" @selected($selectedRole === 'neighbor')>Vecino</option>
    <option value="coordinator" @selected($selectedRole === 'coordinator')>Coordinador</option>
    <option value="admin" @selected($selectedRole === 'admin')>Administrador</option>
</select>

<div id="sectorsSection" class="rounded border p-3 {{ $selectedRole === 'coordinator' ? '' : 'hidden' }}">
    <p class="text-sm font-medium">Sectores asignados</p>
    <p class="text-xs text-slate-500">Se aplican a usuarios con rol de coordinador.</p>
    @foreach($sectors as $sector)
        <label class="mt-2 flex gap-2 text-sm">
            <input type="checkbox" value="{{ $sector->id }}" name="sectorIds[]" @checked(in_array($sector->id, (array) old('sectorIds', $sectorIds)))>
            {{ $sector->name }}
        </label>
    @endforeach
</div>

<label class="flex gap-2 text-sm">
    <input type="hidden" name="isActive" value="0">
    <input type="checkbox" name="isActive" value="1" @checked(old('isActive', $isActive))>
    Cuenta activa
</label>

<div class="flex gap-3">
    <button class="rounded bg-sky-700 px-4 py-2 text-white" type="submit">{{ $editingId ? 'Guardar' : 'Crear usuario' }}</button>
    @if($editingId)
        <a href="{{ route('admin.users') }}">Cancelar</a>
    @endif
</div>

@foreach(['firstName','lastName','documentNumber','phone','email','username','password','roleName','isActive','sectorIds','sectorIds.*'] as $field)
    @error($field)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
@endforeach
</form>
</aside>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('roleNameSelect');
        const sectorsSection = document.getElementById('sectorsSection');

        function toggleSectors() {
            if (roleSelect.value === 'coordinator') {
                sectorsSection.classList.remove('hidden');
            } else {
                sectorsSection.classList.add('hidden');
            }
        }

        roleSelect.addEventListener('change', toggleSectors);
    });
</script>


@endsection
