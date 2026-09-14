<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function teachers(): Collection
    {
        return User::query()
            ->where('role', User::ROLE_TEACHER)
            ->withCount('coursesTaught')
            ->orderBy('name')
            ->get();
    }

    public function students(array $filters): Collection
    {
        return User::query()
            ->where('role', User::ROLE_STUDENT)
            ->when($filters['faculty'] ?? null, fn ($query, string $faculty) => $query->where('faculty', $faculty))
            ->when($filters['semester'] ?? null, fn ($query, int $semester) => $query->where('semester', $semester))
            ->withCount('enrolledCourses')
            ->orderBy('name')
            ->get();
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function setDisabled(User $user, bool $disabled): User
    {
        $user->forceFill(['disabled_at' => $disabled ? now() : null])->save();

        return $user;
    }
}
