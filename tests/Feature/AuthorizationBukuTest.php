<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationBukuTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_melihat_daftar_dan_membuka_form_tambah_buku(): void
    {
        $this->buatDataBukuUji();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('buku.index'))
            ->assertOk()
            ->assertSee('Tambah Buku')
            ->assertSee('Aksi');

        $this->actingAs($admin)
            ->get(route('buku.create'))
            ->assertOk();
    }

    public function test_pembaca_hanya_dapat_melihat_daftar_tanpa_fungsi_tulis(): void
    {
        ['buku' => $buku] = $this->buatDataBukuUji();
        $pembaca = User::factory()->create(['role' => 'pembaca']);

        $this->actingAs($pembaca)
            ->get(route('buku.index'))
            ->assertOk()
            ->assertDontSee('Tambah Buku')
            ->assertDontSee('<th>Aksi</th>', false);

        $this->actingAs($pembaca)
            ->get(route('buku.create'))
            ->assertForbidden();

        $this->actingAs($pembaca)
            ->get(route('buku.edit', $buku))
            ->assertForbidden();
    }

    public function test_admin_dapat_mengubah_dan_menghapus_buku(): void
    {
        ['kategori' => $kategori, 'buku' => $buku] = $this->buatDataBukuUji();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('buku.edit', $buku))
            ->assertOk()
            ->assertSee('Ubah Buku');

        $this->actingAs($admin)
            ->put(route('buku.update', $buku), [
                'kode' => 'BK001',
                'judul' => 'Laravel Aman',
                'kategori_id' => $kategori->id,
            ])
            ->assertRedirect(route('buku.index'));

        $this->assertDatabaseHas('bukus', [
            'id' => $buku->id,
            'judul' => 'Laravel Aman',
        ]);

        $this->actingAs($admin)
            ->delete(route('buku.destroy', $buku))
            ->assertRedirect(route('buku.index'));

        $this->assertDatabaseMissing('bukus', ['id' => $buku->id]);
    }

    public function test_pembaca_tidak_dapat_menambah_mengubah_atau_menghapus_buku(): void
    {
        ['kategori' => $kategori, 'buku' => $buku] = $this->buatDataBukuUji();
        $pembaca = User::factory()->create(['role' => 'pembaca']);

        $this->actingAs($pembaca)
            ->post(route('buku.store'), [
                'kode' => 'BK002',
                'judul' => 'Buku Baru',
                'kategori_id' => $kategori->id,
            ])
            ->assertForbidden();

        $this->actingAs($pembaca)
            ->put(route('buku.update', $buku), [
                'kode' => 'BK001',
                'judul' => 'Judul Diubah',
                'kategori_id' => $kategori->id,
            ])
            ->assertForbidden();

        $this->actingAs($pembaca)
            ->delete(route('buku.destroy', $buku))
            ->assertForbidden();

        $this->assertDatabaseCount('bukus', 1);
        $this->assertSame('Laravel Dasar', $buku->fresh()->judul);
    }

    public function test_admin_dapat_menambah_buku_dengan_data_valid(): void
    {
        ['kategori' => $kategori] = $this->buatDataBukuUji();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('buku.store'), [
                'kode' => 'BK002',
                'judul' => 'Keamanan Aplikasi Web',
                'kategori_id' => $kategori->id,
            ])
            ->assertRedirect(route('buku.index'));

        $this->assertDatabaseHas('bukus', [
            'kode' => 'BK002',
            'judul' => 'Keamanan Aplikasi Web',
        ]);
    }

    public function test_kode_buku_duplikat_ditolak_oleh_validasi(): void
    {
        ['kategori' => $kategori] = $this->buatDataBukuUji();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('buku.create'))
            ->post(route('buku.store'), [
                'kode' => 'BK001',
                'judul' => 'Kode Duplikat',
                'kategori_id' => $kategori->id,
            ])
            ->assertRedirect(route('buku.create'))
            ->assertInvalid(['kode']);

        $this->assertDatabaseCount('bukus', 1);
    }

    public function test_judul_berisi_html_ditampilkan_sebagai_teks_bukan_elemen_html(): void
    {
        ['buku' => $buku] = $this->buatDataBukuUji();
        $buku->update(['judul' => '<b>Uji</b>']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('buku.index'))
            ->assertOk()
            ->assertSee('&lt;b&gt;Uji&lt;/b&gt;', false)
            ->assertDontSee('<b>Uji</b>', false);
    }

    /**
     * @return array{kategori: Kategori, buku: Buku}
     */
    private function buatDataBukuUji(): array
    {
        $kategori = Kategori::create(['nama' => 'Pemrograman']);
        $buku = Buku::create([
            'kode' => 'BK001',
            'judul' => 'Laravel Dasar',
            'kategori_id' => $kategori->id,
        ]);

        return compact('kategori', 'buku');
    }
}
