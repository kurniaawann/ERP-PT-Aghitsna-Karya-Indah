<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratMenyuratTest extends TestCase
{
    public function testRedirectsGoToTabs(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::orderBy('id')->first();
        $this->actingAs($user);

        $cases = [
            '/document-receipt' => 'document-receipt',
            '/kwintansi'        => 'kwintansi',
            '/nota-administrasi' => 'nota',
            '/delivery-note'    => 'surat-jalan',
            '/surat-perintah-kerja' => 'spk',
        ];

        foreach ($cases as $path => $tab) {
            $resp = $this->get($path);
            $resp->assertStatus(302);
            $resp->assertLocation(route('surat-menyurat.index', ['tab' => $tab]));
        }
    }

    public function testTabbedPageRendersAllTabs(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::orderBy('id')->first();
        $this->actingAs($user);

        foreach (['document-receipt', 'kwintansi', 'nota', 'surat-jalan', 'spk'] as $tab) {
            $this->get(route('surat-menyurat.index', ['tab' => $tab]))
                ->assertOk();
        }
    }

    public function testInvalidTabRedirectsToFirst(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::orderBy('id')->first();
        $this->actingAs($user);

        $this->get(route('surat-menyurat.index', ['tab' => 'bogus']))
            ->assertRedirect(route('surat-menyurat.index', ['tab' => 'document-receipt']));
    }
}