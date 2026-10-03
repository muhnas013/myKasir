<?php

namespace App\Actions\Settings;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdateUser
{
    /**
     * @param  array{name?: string, role?: string, email?: ?string, password?: ?string, pin?: string, is_active?: bool}  $data
     */
    public function handle(User $target, array $data, User $actor): User
    {
        $willDeactivate = array_key_exists('is_active', $data) && ! $data['is_active'] && $target->is_active;
        $willDemote = array_key_exists('role', $data) && $data['role'] !== Role::Owner->value && $target->isOwner();

        if (($willDeactivate || $willDemote) && $target->isOwner() && $this->isLastActiveOwner($target)) {
            throw ValidationException::withMessages([
                'is_active' => 'Minimal satu pemilik aktif harus selalu ada.',
            ]);
        }

        return DB::transaction(function () use ($target, $data, $actor) {
            $old = $target->only(['name', 'role', 'email', 'is_active']);
            $old['role'] = $old['role'] instanceof Role ? $old['role']->value : $old['role'];

            $attributes = [];
            foreach (['name', 'email', 'is_active'] as $field) {
                if (array_key_exists($field, $data)) {
                    $attributes[$field] = $data[$field];
                }
            }
            if (array_key_exists('role', $data)) {
                $attributes['role'] = Role::from($data['role']);
            }
            if (! empty($data['password'])) {
                $attributes['password'] = Hash::make($data['password']);
            }
            if (! empty($data['pin'])) {
                $attributes['pin_hash'] = Hash::make($data['pin']);
            }

            $target->fill($attributes)->save();

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'user.updated',
                'subject_type' => User::class,
                'subject_id' => $target->id,
                'old_values' => $old,
                'new_values' => $target->only(['name', 'role', 'email', 'is_active']) + ['role' => $target->role->value],
                'ip' => request()->ip(),
            ]);

            return $target;
        });
    }

    private function isLastActiveOwner(User $target): bool
    {
        return User::query()
            ->where('role', Role::Owner)
            ->where('is_active', true)
            ->where('id', '!=', $target->id)
            ->doesntExist();
    }
}
