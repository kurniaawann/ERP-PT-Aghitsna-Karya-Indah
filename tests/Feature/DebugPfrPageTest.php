<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class DebugPfrPageTest extends TestCase
{
    public function test_index_page_renders_edit_modal(): void
    {
        $user = User::where('email', 'adminaghitsna1@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->get(route('project-financial-report.index'));
        $response->assertOk();

        $html = $response->getContent();

        // Modal edit gabungan ter-render
        $this->assertStringContainsString('editPfrModal-RP-00001', $html);
        $this->assertStringContainsString('transactionsContainer-RP-00001', $html);
        $this->assertStringContainsString('data-existing-items', $html);
        $this->assertStringContainsString('data-categories', $html);
        $this->assertStringContainsString("addTransactionBlock('transactionsContainer-RP-00001')", $html);

        // JS ter-serve
        $jsAsset = preg_match('/src="([^"]+index-[A-Za-z0-9_-]+\.js)"/', $html, $m) ? $m[1] : null;
        dump('js asset: ' . ($jsAsset ?? 'NOT FOUND'));

        // Cetak potongan modal edit untuk diperiksa
        $start = strpos($html, 'editPfrModal-RP-00001');
        if ($start !== false) {
            $snippet = substr($html, $start, 400);
            dump($snippet);
        }

        // Buat log HTML untuk analisis lebih lanjut
        file_put_contents('/tmp/opencode/pfr-index.html', $html);
    }
}
