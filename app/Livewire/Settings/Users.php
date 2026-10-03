<?php

namespace App\Livewire\Settings;

use App\Actions\Settings\CreateUser;
use App\Actions\Settings\UpdateUser;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Users extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $role = 'cashier';

    public string $email = '';

    public string $password = '';

    public string $pin = '';

    public bool $is_active = true;

    public function render()
    {
        return view('livewire.settings.users', [
            'users' => User::query()->orderBy('role')->orderBy('name')->get(),
        ]);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->role = $user->role->value;
        $this->email = (string) $user->email;
        $this->password = '';
        $this->pin = '';
        $this->is_active = $user->is_active;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        $isAdminOrOwner = $this->role !== Role::Cashier->value;

        $rules = [
            'name' => 'required|string|max:100',
            'role' => ['required', Rule::in(['admin', 'cashier'])],
            'email' => $isAdminOrOwner
                ? ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)]
                : ['nullable'],
            'pin' => [$this->editingId ? 'nullable' : 'required', 'digits:6', function ($attribute, $value, $fail) {
                if ($value && $this->isWeakPin($value)) {
                    $fail('PIN tidak boleh berurutan atau berulang.');
                }
            }],
            'password' => [$isAdminOrOwner && ! $this->editingId ? 'required' : 'nullable', 'string', 'min:8'],
        ];

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'role' => $this->role,
            'email' => $isAdminOrOwner ? $this->email : null,
        ];

        if ($this->pin !== '') {
            $data['pin'] = $this->pin;
        }

        if ($this->password !== '') {
            $data['password'] = $this->password;
        }

        if ($this->editingId) {
            $data['is_active'] = $this->is_active;

            try {
                app(UpdateUser::class)->handle(User::findOrFail($this->editingId), $data, Auth::user());
            } catch (ValidationException $e) {
                $this->addError('is_active', $e->validator->errors()->first());

                return;
            }
        } else {
            if (! isset($data['pin'])) {
                $this->addError('pin', 'PIN wajib diisi.');

                return;
            }

            app(CreateUser::class)->handle($data);
        }

        $this->dispatch('toast', type: 'success', message: 'Pengguna disimpan.');
        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        try {
            app(UpdateUser::class)->handle($user, ['is_active' => ! $user->is_active], Auth::user());
            $this->dispatch('toast', type: 'success', message: 'Status pengguna diperbarui.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());
        }
    }

    private function isWeakPin(string $pin): bool
    {
        if (preg_match('/^(\d)\1{5}$/', $pin)) {
            return true;
        }

        $ascending = '0123456789';
        $descending = '9876543210';

        return str_contains($ascending, $pin) || str_contains($descending, $pin);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->role = 'cashier';
        $this->email = '';
        $this->password = '';
        $this->pin = '';
        $this->is_active = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }
}
