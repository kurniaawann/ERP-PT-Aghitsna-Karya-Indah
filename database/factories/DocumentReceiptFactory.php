<?php
namespace Database\Factories;
use App\Models\Administrasi\DocumentReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentReceiptFactory extends Factory
{
    protected $model = DocumentReceipt::class;

    public function definition(): array
    {
        return [
            'id_document' => null,
            'received_from' => fake()->company(),
            'regarding' => fake()->sentence(),
            'form_of' => fake()->randomElement(['Surat', 'Dokumen', ' Berkas', 'Formulir', 'Laporan']),
            'receipt_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'receipt_time' => fake()->time('H:i'),
            'location' => fake()->randomElement(['Depok', 'Jakarta', 'Bandung']),
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
