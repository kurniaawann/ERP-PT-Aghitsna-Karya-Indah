<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RekapTest extends TestCase
{
    private function user(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    public function testSuperAdminCanViewRekapTabs(): void
    {
        $this->actingAs($this->user('superadmin'));

        foreach (['sales', 'aluminium', 'proyek', 'pengeluaran'] as $tab) {
            $this->get(route('rekap.index', ['tab' => $tab]))->assertOk();
        }
    }

    public function testInvalidTabRedirectsToFirst(): void
    {
        $this->actingAs($this->user('superadmin'));

        $this->get(route('rekap.index', ['tab' => 'bogus']))
            ->assertRedirect(route('rekap.index', ['tab' => 'sales']));
    }

    public function testAdminIsForbiddenFromRekapPage(): void
    {
        $this->actingAs($this->user('admin'));

        $this->get(route('rekap.index'))->assertForbidden();
    }

    public function testSuperAdminOldRecapPagesRedirectToTabs(): void
    {
        $this->actingAs($this->user('superadmin'));

        $cases = [
            '/recap-sales'     => 'sales',
            '/recap-alumunium' => 'aluminium',
            '/recap-proyek'    => 'proyek',
            '/recap-expense'   => 'pengeluaran',
        ];

        foreach ($cases as $path => $tab) {
            $this->get($path)->assertRedirect(route('rekap.index', ['tab' => $tab]));
        }
    }

    public function testAdminOldRecapPagesStillRenderStandalone(): void
    {
        $this->actingAs($this->user('admin'));

        foreach (['/recap-proyek', '/recap-expense'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}