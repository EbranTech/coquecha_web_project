
@extends('layouts.app')

@section('content')

@php
    $totalUsers = $users->count();
    $activeUsers = $users->where('is_active', true)->count();
    $inactiveUsers = $users->where('is_active', false)->count();
    $coordinatorUsers = $users->filter(
        fn ($user) => $user->role?->name === 'coordinator'
    )->count();

    $isEditing = !empty($editingId);
    $hasErrors = $errors->any();

    $selectedRole = (string) old('roleName', $roleName);

    $inputClass = 'mt-2 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100';
    $labelClass = 'block text-sm font-semibold text-slate-700';
@endphp

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <p class="text-sm font-semibold text-sky-700">
                Acceso y responsabilidades
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Usuarios
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Administra las cuentas, roles y permisos de los usuarios.
            </p>
        </div>

        @if ($isEditing)
            <a href="{{ route('admin.users') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">
                + Nuevo usuario
            </a>
        @else
            <button type="button"
                    onclick="openUserPanel()"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">
                + Nuevo usuario
            </button>
        @endif

    </div>

    @if (session('success'))
        <div role="status"
             class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div role="alert"
             class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Total usuarios
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalUsers }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Cuentas activas
            </p>

            <p class="mt-3 text-3xl font-bold text-emerald-600">
                {{ $activeUsers }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Cuentas inactivas
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-500">
                {{ $inactiveUsers }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Coordinadores
            </p>

            <p class="mt-3 text-3xl font-bold text-sky-600">
                {{ $coordinatorUsers }}
            </p>
        </div>

    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Usuarios registrados
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Consulta y administra los accesos al sistema.
                </p>
            </div>

            <input id="userSearch"
                   type="search"
                   placeholder="Buscar usuario..."
                   aria-label="Buscar usuario"
                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 sm:w-64">
        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full text-left text-sm">

                <thead class="border-b border-slate-200 bg-slate-50">

                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Usuario</th>
                        <th class="px-6 py-4">Rol</th>
                        <th class="px-6 py-4">Sectores</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($users as $user)

                        <tr class="user-row transition hover:bg-slate-50">

                            <td class="px-6 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sm font-bold text-sky-700">
                                        {{ strtoupper(substr($user->display_name, 0, 1)) }}
                                    </div>

                                    <div>
                                        <p class="font-semibold text-slate-900">
                                            {{ $user->display_name }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ '@' . $user->username }}
                                        </p>
                                    </div>

                                </div>

                            </td>

                            <td class="px-6 py-4">

                                @php
                                    $roleName = $user->role?->name;
                                @endphp

                                @if ($roleName === 'admin')

                                    <span class="inline-flex rounded-full bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700">
                                        Administrador
                                    </span>

                                @elseif ($roleName === 'coordinator')

                                    <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                        Coordinador
                                    </span>

                                @else

                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        Vecino
                                    </span>

                                @endif

                            </td>
                            <td class="px-6 py-4">

                                @forelse ($user->managedSectors as $sector)

                                    <span class="mb-1 mr-1 inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        {{ $sector->name }}
                                    </span>

                                @empty

                                    <span class="text-xs text-slate-400">
                                        Sin sectores
                                    </span>

                                @endforelse

                            </td>

                            <td class="px-6 py-4">

                                @if ($user->is_active)

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">

                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                                        Activo
                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">

                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>

                                        Inactivo
                                    </span>

                                @endif

                            </td>

                            <td class="px-6 py-4 text-right">

                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:border-sky-200 hover:bg-sky-50">
                                    Editar
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">

                                    <svg class="h-7 w-7 text-slate-400"
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24"
                                         stroke-width="1.5">

                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m14-14a4 4 0 110 8m6 6v-2a4 4 0 00-3-3.87M9 11a4 4 0 100-8 4 4 0 000 8z"/>

                                    </svg>

                                </div>

                                <h3 class="mt-4 text-base font-semibold text-slate-800">
                                    No hay usuarios registrados
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Comienza creando una cuenta de usuario.
                                </p>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
            <p class="text-xs text-slate-500">
                Mostrando {{ $totalUsers }} usuarios
            </p>
        </div>

    </div>

</div>

<div id="userPanel"
     class="fixed inset-0 z-[100] hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="userPanelTitle">

    <div onclick="closeUserPanel()"
         class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm">
    </div>

    <aside class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl">

        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">

            <div>
                <h2 id="userPanelTitle" class="text-xl font-bold text-slate-900">
                    {{ $isEditing ? 'Editar usuario' : 'Nuevo usuario' }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configura la información y permisos de la cuenta.
                </p>
            </div>

            <button type="button"
                    onclick="closeUserPanel()"
                    aria-label="Cerrar formulario"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                ✕
            </button>

        </div>

        <form id="userForm"
              method="POST"
              action="{{ $isEditing ? route('admin.users.update', $editingId) : route('admin.users.store') }}"
              class="flex min-h-0 flex-1 flex-col">

            @csrf

            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <div>
                        <label for="firstName" class="{{ $labelClass }}">
                            Nombres
                        </label>

                        <input id="firstName"
                               type="text"
                               name="firstName"
                               required
                               value="{{ old('firstName', $firstName) }}"
                               placeholder="Nombres"
                               class="{{ $inputClass }}">

                        @error('firstName')
                            <p class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="lastName" class="{{ $labelClass }}">
                            Apellidos
                        </label>

                        <input id="lastName"
                               type="text"
                               name="lastName"
                               required
                               value="{{ old('lastName', $lastName) }}"
                               placeholder="Apellidos"
                               class="{{ $inputClass }}">

                        @error('lastName')
                            <p class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

                <div>
                    <label for="documentNumber" class="{{ $labelClass }}">
                        Documento de identificación
                    </label>

                    <input id="documentNumber"
                           type="text"
                           inputmode="numeric"
                           maxlength="13"
                           pattern="[0-9]{13}"
                           title="Debe contener 13 dígitos numéricos"
                           oninput="this.value = this.value.replace(/\D/g, '').slice(0, 13)"
                           name="documentNumber"
                           value="{{ old('documentNumber', $documentNumber) }}"
                           placeholder="DPI de 13 dígitos"
                           class="{{ $inputClass }}">

                    <p class="mt-1 text-xs text-slate-500">
                        Ingresa los 13 dígitos sin espacios ni guiones.
                    </p>

                    @error('documentNumber')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="{{ $labelClass }}">
                        Teléfono
                    </label>

                    <input id="phone"
                           type="text"
                           inputmode="numeric"
                           maxlength="8"
                           pattern="[0-9]{8}"
                           title="Debe contener 8 dígitos numéricos"
                           oninput="this.value = this.value.replace(/\D/g, '').slice(0, 8)"
                           name="phone"
                           value="{{ old('phone', $phone) }}"
                           placeholder="Teléfono de 8 dígitos"
                           class="{{ $inputClass }}">

                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="{{ $labelClass }}">
                        Correo electrónico
                    </label>

                    <input id="email"
                           type="email"
                           name="email"
                           value="{{ old('email', $email) }}"
                           placeholder="correo@ejemplo.com"
                           class="{{ $inputClass }}">

                    @error('email')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="username" class="{{ $labelClass }}">
                        Nombre de usuario
                    </label>

                    <input id="username"
                           type="text"
                           name="username"
                           required
                           autocomplete="off"
                           value="{{ old('username', $username) }}"
                           placeholder="Ej. juan.perez"
                           class="{{ $inputClass }}">

                    @error('username')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="{{ $labelClass }}">
                        {{ $isEditing ? 'Nueva contraseña' : 'Contraseña' }}
                    </label>

                    <input id="password"
                           type="password"
                           name="password"
                           autocomplete="new-password"
                           @if (!$isEditing) required minlength="8" @endif
                           placeholder="{{ $isEditing ? 'Dejar vacío para conservar la actual' : 'Mínimo 8 caracteres' }}"
                           class="{{ $inputClass }}">

                    <p class="mt-1 text-xs text-slate-500">
                        @if ($isEditing)
                            Déjala vacía si no deseas cambiar la contraseña.
                        @else
                            Utiliza una contraseña segura de al menos 8 caracteres.
                        @endif
                    </p>

                    @error('password')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="roleNameSelect" class="{{ $labelClass }}">
                        Rol del usuario
                    </label>

                    <select id="roleNameSelect"
                            name="roleName"
                            class="{{ $inputClass }}">

                        <option value="neighbor"
                            @selected($selectedRole === 'neighbor')>
                            Vecino
                        </option>

                        <option value="coordinator"
                            @selected($selectedRole === 'coordinator')>
                            Coordinador
                        </option>

                        <option value="admin"
                            @selected($selectedRole === 'admin')>
                            Administrador
                        </option>

                    </select>

                    @error('roleName')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div id="sectorsSection"
                     class="{{ $selectedRole === 'coordinator' ? '' : 'hidden' }}">

                    <label class="{{ $labelClass }}">
                        Sectores asignados
                    </label>

                    <p class="mt-1 text-xs text-slate-500">
                        Selecciona los sectores que administrará este coordinador.
                    </p>

                    <div class="mt-3 max-h-52 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3">

                        @forelse ($sectors as $sector)

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-white p-3 transition hover:bg-sky-50">

                                <input type="checkbox"
                                       name="sectorIds[]"
                                       value="{{ $sector->id }}"
                                       @checked(in_array($sector->id, (array) old('sectorIds', $sectorIds)))
                                       class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-500">

                                <span class="text-sm font-medium text-slate-700">
                                    {{ $sector->name }}
                                </span>

                            </label>

                        @empty

                            <p class="py-3 text-center text-sm text-slate-500">
                                No hay sectores registrados.
                            </p>

                        @endforelse

                    </div>

                    @error('sectorIds')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('sectorIds.*')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                    <label class="flex cursor-pointer items-center justify-between gap-4">

                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Cuenta activa
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Permite que el usuario tenga acceso al sistema.
                            </p>
                        </div>

                        <input type="hidden" name="isActive" value="0">

                        <input id="isActive"
                               type="checkbox"
                               name="isActive"
                               value="1"
                               @checked(old('isActive', $isActive))
                               class="h-5 w-5 rounded border-slate-300 text-sky-700 focus:ring-sky-500">

                    </label>

                    @error('isActive')
                        <p class="mt-2 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>
            <div class="flex gap-3 border-t border-slate-200 bg-white px-6 py-5">

                @if ($isEditing)

                    <a href="{{ route('admin.users') }}"
                       class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>

                @else

                    <button type="button"
                            onclick="closeUserPanel()"
                            class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>

                @endif

                <button type="submit"
                        class="flex-1 rounded-xl bg-sky-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-sky-800">
                    {{ $isEditing ? 'Guardar cambios' : 'Crear usuario' }}
                </button>

            </div>

        </form>

    </aside>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const userPanel = document.getElementById('userPanel');
    const userForm = document.getElementById('userForm');
    const userSearch = document.getElementById('userSearch');

    const roleSelect = document.getElementById('roleNameSelect');
    const sectorsSection = document.getElementById('sectorsSection');

    const isEditing = @json($isEditing);
    const indexUrl = @json(route('admin.users'));
    const hasErrors = @json($hasErrors);

    let previousFocus = null;

    function toggleSectors() {

        const isCoordinator = roleSelect.value === 'coordinator';

        sectorsSection.classList.toggle('hidden', !isCoordinator);
        sectorsSection.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.disabled = !isCoordinator;
        });
    }

    roleSelect.addEventListener('change', toggleSectors);

    window.openUserPanel = function () {

        previousFocus = document.activeElement;

        userPanel.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        toggleSectors();

        document.getElementById('firstName')?.focus();
    };

    window.closeUserPanel = function () {

        if (isEditing) {
            window.location.href = indexUrl;
            return;
        }

        userPanel.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        userForm.reset();

        userForm.querySelectorAll(
            'input:not([type="hidden"]):not([type="checkbox"]), textarea'
        ).forEach(input => {
            input.value = '';
        });

        userForm.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.checked = false;
        });

        roleSelect.value = 'neighbor';

        const activeCheckbox = document.getElementById('isActive');
        activeCheckbox.checked = true;

        toggleSectors();

        previousFocus?.focus();
    };

    document.addEventListener('keydown', function (event) {

        if (
            event.key === 'Escape' &&
            !userPanel.classList.contains('hidden')
        ) {
            closeUserPanel();
        }
    });

    userSearch?.addEventListener('input', function () {

        const query = this.value.toLowerCase().trim();

        document.querySelectorAll('.user-row').forEach(row => {

            const content = row.textContent.toLowerCase();

            row.classList.toggle(
                'hidden',
                !content.includes(query)
            );
        });
    });
    toggleSectors();
    if (isEditing || hasErrors) {
        openUserPanel();
    }

});
</script>

@endsection
