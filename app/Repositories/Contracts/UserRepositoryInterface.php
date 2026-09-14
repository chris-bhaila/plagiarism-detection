<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * All teacher accounts, with a courses-taught count preloaded.
     *
     * @return Collection<int, User>
     */
    public function teachers(): Collection;

    /**
     * All student accounts, optionally filtered by faculty and/or semester
     * (combined with AND), with an enrolled-courses count preloaded.
     *
     * @param  array{faculty?: ?string, semester?: ?int}  $filters
     * @return Collection<int, User>
     */
    public function students(array $filters): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    public function setDisabled(User $user, bool $disabled): User;
}
