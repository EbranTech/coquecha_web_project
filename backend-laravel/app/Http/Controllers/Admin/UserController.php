<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveUserRequest;
use App\Models\Person;
use App\Models\Role;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return $this->form();
    }

    public function edit(User $user): View
    {
        return $this->form($user);
    }

    private function form(?User $record = null): View
    {
        return view('pages.admin.users', [
            'users' => User::query()->with(['person', 'role', 'managedSectors'])->orderBy('username')->get(),
            'sectors' => Sector::query()->where('status', 'active')->orderBy('name')->get(),
            'title' => 'Administrar usuarios',
            'editingId' => $record?->id,
            'firstName' => $record?->person?->first_name ?? '',
            'lastName' => $record?->person?->last_name ?? '',
            'documentNumber' => $record?->person?->document_number ?? '',
            'phone' => $record?->person?->phone ?? '',
            'email' => $record?->person?->email ?? '',
            'username' => $record?->username ?? '',
            'password' => '',
            'roleName' => $record?->role?->name ?? Role::NEIGHBOR,
            'isActive' => $record?->is_active ?? true,
            'sectorIds' => $record?->managedSectors()->pluck('sectors.id')->all() ?? [],
        ]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        return $this->persist($request, $user);
    }

    private function persist(SaveUserRequest $request, ?User $record = null): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $record): void {
            $personId = $record?->person_id;
            $person = Person::query()->updateOrCreate(['id' => $personId], [
                'first_name' => $data['firstName'], 'last_name' => $data['lastName'], 'document_number' => $data['documentNumber'],
                'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null, 'status' => (bool) $data['isActive'] ? 'active' : 'inactive',
            ]);
            $account = User::query()->updateOrCreate(['id' => $record?->id], array_filter([
                'person_id' => $person->id, 'role_id' => Role::query()->where('name', $data['roleName'])->value('id'),
                'username' => $data['username'], 'is_active' => (bool) $data['isActive'],
                'password_hash' => ! empty($data['password']) ? Hash::make($data['password']) : null,
            ], fn ($value) => $value !== null));
            $account->managedSectors()->sync($data['roleName'] === Role::COORDINATOR ? ($data['sectorIds'] ?? []) : []);
        });

        return to_route('admin.users')->with('success', $record ? 'Usuario actualizado.' : 'Usuario creado.');
    }
}
