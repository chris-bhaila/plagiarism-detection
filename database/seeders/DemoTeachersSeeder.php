<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoTeachersSeeder extends Seeder
{
    /**
     * 10 demo teacher accounts (email firstname.lastname@ict.dev, password
     * firstname123), then every course in the system gets a random one of
     * them as its teacher — including the BCA syllabus courses that
     * BcaSyllabusSeeder leaves unassigned. Re-running this reshuffles the
     * assignment each time (only the accounts themselves are idempotent,
     * via updateOrCreate) — that's intentional, not a bug.
     */
    protected array $teachers = [
        ['Ram', 'Sharma'],
        ['Sita', 'Gurung'],
        ['Hari', 'Thapa'],
        ['Gita', 'Karki'],
        ['Bikash', 'Shrestha'],
        ['Anita', 'Rai'],
        ['Suresh', 'Magar'],
        ['Kamala', 'Tamang'],
        ['Deepak', 'Adhikari'],
        ['Sunita', 'Poudel'],
    ];

    public function run(): void
    {
        $created = collect($this->teachers)->map(function (array $name) {
            [$first, $last] = $name;
            $email = Str::lower("{$first}.{$last}@ict.dev");

            return User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "{$first} {$last}",
                    'password' => Hash::make(Str::lower($first).'123'),
                    'role' => User::ROLE_TEACHER,
                    'semester_id' => null,
                ],
            );
        });

        Course::all()->each(function (Course $course) use ($created) {
            $course->update(['teacher_id' => $created->random()->id]);
        });
    }
}
