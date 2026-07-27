<?php
namespace Database\Factories;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => null,
            'attendance_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'status' => fake()->randomElement(['hadir', 'izin', 'sakit', 'cuti']),
            'overtime_hours' => null,
            'overtime_rate' => null,
            'overtime_total' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function hadir(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'hadir']);
    }

    public function lembur(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lembur',
            'overtime_hours' => fake()->randomElement([1, 2, 3, 4]),
            'overtime_rate' => 50000,
            'overtime_total' => 0,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
