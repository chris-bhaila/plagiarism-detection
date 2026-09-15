<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function teachers(?string $search = null): Collection
    {
        return User::query()
            ->where('role', User::ROLE_TEACHER)
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount('coursesTaught')
            ->orderBy('name')
            ->get();
    }

    public function students(array $filters): Collection
    {
        return User::query()
            ->where('role', User::ROLE_STUDENT)
            ->when($filters['faculty_id'] ?? null, fn ($query, int $facultyId) => $query->whereHas(
                'semester', fn ($query) => $query->where('faculty_id', $facultyId)
            ))
            ->when($filters['semester_id'] ?? null, fn ($query, int $semesterId) => $query->where('semester_id', $semesterId))
            ->withCount('enrolledCourses')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
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
