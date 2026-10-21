# Laporan Praktikum - Implementasi CRUD dan Pencarian Data Anggota (Members) pada Laravel

| | |
|---|---|
| **Nama** | ............................................ |
| **NIM** | ............................................ |
| **Kelas** | ............................................ |
| **Mata Kuliah** | ............................................ |
| **Dosen Pengampu** | ............................................ |
| **Tanggal Praktikum** | ............................................ |

---

## 1. Tujuan Praktikum

Praktikum ini bertujuan untuk:

1. Menerapkan pola **CRUD** (*Create, Read, Update, Delete*) pada aplikasi Laravel menggunakan *framework* Eloquent ORM melalui implementasi `create()`, `findOrFail()`, `update()`, dan `delete()`.
2. Mengimplementasikan **validasi data sisi server** menggunakan *Form Request* (`StoreMemberRequest`) beserta pesan validasi berbahasa Indonesia, termasuk aturan **keunikan** (*unique*) pada kolom `nim` dan `email`.
3. Membangun **fitur pencarian data** berdasarkan nama anggota dengan memanfaatkan metode `when()` secara berantai, sehingga query pencarian hanya dijalankan ketika parameter `search` tersedia pada *query string*.
4. Menerapkan **paginasi 10 data per halaman** serta mempertahankan parameter pencarian antar halaman melalui metode `appends()`.
5. Menerapkan arsitektur **MVC** (*Model–View–Controller*) pada antarmuka Blade untuk seluruh proses CRUD data anggota.

---

## 2. Analisis Kebutuhan & Desain

### 2.1 Skema Data Member

Data anggota disimpan pada tabel `members` yang didefinisikan dalam migrasi `2026_09_23_024516_create_members_table.php`:

```php
Schema::create('members', function (Blueprint $table) {
    $table->id();
    $table->string('nama', 100);
    $table->string('nim', 20)->unique();
    $table->string('email', 100)->unique();
    $table->string('nomor_telepon', 15);
    $table->text('alamat');
    $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
    $table->timestamps();
});
```

Tabel tersebut dipetakan ke model `App\Models\Member`:

```php
class Member extends Model
{
    protected $fillable = [
        'nama', 'nim', 'email', 'nomor_telepon', 'alamat', 'status',
    ];
}
```

### 2.2 Analisis Kebutuhan Validasi

Kebutuhan validasi dirancang mengikuti batasan skema database sehingga setiap data yang lolos validasi di sisi aplikasi dijamin dapat disimpan oleh basis data:

| Field | Aturan Validasi | Alasan |
|---|---|---|
| `nama` | `required\|string\|max:100` | Wajib diisi; batas 100 karakter mengikuti kolom `string(100)`. |
| `nim` | `required\|string\|max:20\|unique:members,nim` | Wajib diisi dan unik sebagai identitas akademik mahasiswa. |
| `email` | `required\|email\|max:100\|unique:members,email` | Wajib berformat email valid dan unik pada tabel `members`. |
| `nomor_telepon` | `required\|string\|max:15` | Wajib diisi; batas 15 karakter mengikuti kolom `string(15)`. |
| `alamat` | `required\|string` | Wajib diisi sesuai ketentuan tugas. |
| `status` | `required\|in:aktif,nonaktif` | Wajib dipilih dan dibatasi pada dua nilai enumerasi. |

Pada proses **update**, aturan `unique` harus mengabaikan baris yang sedang diedit agar data milik anggota itu sendiri tidak dianggap duplikat. Digunakan `Rule::unique('members', 'nim')->ignore($member->id)`.

### 2.3 Desain Form Request

Pemisahan aturan validasi ke dalam *Form Request* (`App\Http\Requests\StoreMemberRequest`) mengikuti pola yang telah digunakan pada CRUD kategori (`StoreCategoryRequest`). Keuntungan pendekatan ini:

1. Aturan validasi terpusat dan dapat digunakan ulang pada method `store()`.
2. Pesan error dapat didefinisikan terpisah dalam bahasa Indonesia.
3. Controller menjadi lebih bersih karena Laravel otomatis melakukan validasi sebelum method `store()` dieksekui dan mengembalikan pengguna ke halaman sebelumnya bila validasi gagal.

### 2.4 Alur Query Pencarian

Alur pencarian nama anggota dirancang sebagai berikut:

```
Pengguna mengisi input search
        │
        ▼
Form GET dikirim ke /members?search=<kata kunci>
        │
        ▼
request('search') bernilai true / null
        │
        ├── bernilai null  ──▶ when() tidak menambahkan klausa
        │                       (seluruh data ditampilkan)
        ▼
Member::when(request('search'),
        fn ($query, $search) =>
            $query->where('nama', 'like', "%{$search}%"))
        ->paginate(10)
        │
        ▼
Query SQL: SELECT * FROM members
            WHERE nama LIKE '%<kata kunci>%'
            LIMIT 11 OFFSET 0
        │
        ▼
View: {{ $members->appends(request()->query())->links() }}
        (parameter search dipertahankan saat berpindah halaman)
```

Beberapa keputusan desain penting:

1. **Operator `LIKE` dengan wildcard di kedua sisi (`%kata%`)** agar pencarian bersifat *partial match* pada posisi mana pun dalam nama.
2. **Metode `when()`** dipilih alih-alih blok `if` agar query tetap terbaca berantai (*chainable*) dan hanya menambahkan klausa `WHERE` ketika parameter pencarian benar-benar ada.
3. **`appends(request()->query())`** pada view memastikan halaman kedua dan seterusnya tetap mempertahankan kata kunci pencarian, sehingga pengguna tidak kehilangan hasil filter saat melakukan navigasi paginasi.

### 2.5 Alur Proses CRUD

```
Route::resource('members', MemberController::class)
   │
   ├── GET  /members          → index()   → view members.index (data + search + paginasi)
   ├── GET  /members/create   → create()  → view members.create (form tambah)
   ├── POST /members          → store()   → StoreMemberRequest → Member::create() → redirect index
   ├── GET  /members/{id}     → show()    → Member::findOrFail() → view members.show
   ├── GET  /members/{id}/edit→ edit()    → Member::findOrFail() → view members.edit
   ├── PUT  /members/{id}     → update()  → Member::findOrFail() → validate → update() → redirect index
   └── DELETE /members/{id}   → destroy() → Member::findOrFail() → delete() → redirect index
```

---

## 3. Implementasi Langkah demi Langkah & Penjelasan Kode

### 3.1 Langkah 1: Membuat Form Request `StoreMemberRequest`

**File:** `app/Http/Requests/StoreMemberRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:100',
            'nim' => 'required|string|max:20|unique:members,nim',
            'email' => 'required|email|max:100|unique:members,email',
            'nomor_telepon' => 'required|string|max:15',
            'alamat' => 'required|string',
            'status' => 'required|in:aktif,nonaktif',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama anggota wajib diisi.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'nomor_telepon.required' => 'Nomor telepon wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'status.required' => 'Status anggota wajib dipilih.',
            'status.in' => 'Status harus aktif atau nonaktif.',
        ];
    }
}
```

**Penjelasan kode:**

- `authorize()` mengembalikan `true` karena seluruh pengguna yang mengakses route diizinkan mengirim data (pembatasan akses lebih lanjut dapat ditambahkan melalui *middleware* autentikasi).
- `rules()` menyimpan seluruh aturan validasi. Aturan `unique:members,nim` dan `unique:members,email` mengecek tabel `members` secara langsung sehingga duplikasi data dicegah sebelum proses penyimpanan.
- `messages()` menyediakan pesan error berbahasa Indonesia yang akan ditampilkan pada view melalui blok `@error`.

### 3.2 Langkah 2: Membangun Logika Controller `MemberController`

**File:** `app/Http/Controllers/MemberController.php`

#### a. Method `index()` — pencarian dan paginasi

```php
public function index()
{
    $members = Member::when(request('search'), fn ($query, $search) => $query->where('nama', 'like', "%{$search}%"))
        ->paginate(10);

    return view('members.index', compact('members'));
}
```

**Penjelasan kode:**

- `request('search')` membaca nilai parameter `search` dari URL (`/members?search=siti`).
- `Member::when(kondisi, callback)` hanya mengeksekusi `callback` bila kondisi bernilai *truthy*. Pada callback, `$query` adalah objek query builder dan `$search` adalah nilai pencarian yang diterima.
- Klausa `where('nama', 'like', "%{$search}%")` melakukan pencarian sebagian pada kolom nama.
- `paginate(10)` membatasi hasil menjadi 10 baris per halaman sekaligus menghasilkan objek paginator yang menyediakan metode `links()` dan `appends()`.
- Hasil dikirim ke view `members.index` melalui `compact('members')`.

#### b. Method `create()` dan `store()` — menambah data

```php
public function create()
{
    return view('members.create');
}

public function store(StoreMemberRequest $request)
{
    $validated = $request->validated();
    Member::create($validated);

    return redirect()->route('members.index')
        ->with('success', "Anggota \"{$validated['nama']}\" berhasil ditambahkan.");
}
```

**Penjelasan kode:**

- `create()` hanya menampilkan form, tanpa logika data.
- Karena `store()` memiliki *type-hint* `StoreMemberRequest`, Laravel otomatis menjalankan validasi. Jika gagal, pengguna dikembalikan ke halaman sebelumnya beserta pesan error; jika lolos, `store()` baru dieksekui.
- `$request->validated()` mengembalikan hanya input yang lolos validasi, sehingga aman dari *mass assignment* kolom di luar daftar `fillable`.
- `Member::create($validated)` menyimpan data baru ke tabel `members`.
- `redirect()->route('members.index')->with('success', ...)` mengarahkan pengguna ke daftar anggota dan menampilkan pesan sukses melalui *partial* `partials/alert.blade.php`.

#### c. Method `show()`, `edit()`, dan `update()` — membaca & memperbarui data

```php
public function show(string $id)
{
    $member = Member::findOrFail($id);
    return view('members.show', compact('member'));
}

public function edit(string $id)
{
    $member = Member::findOrFail($id);
    return view('members.edit', compact('member'));
}

public function update(Request $request, string $id)
{
    $member = Member::findOrFail($id);

    $validated = $request->validate([
        'nama' => 'required|string|max:100',
        'nim' => ['required', 'string', 'max:20', Rule::unique('members', 'nim')->ignore($member->id)],
        'email' => ['required', 'email', 'max:100', Rule::unique('members', 'email')->ignore($member->id)],
        'nomor_telepon' => 'required|string|max:15',
        'alamat' => 'required|string',
        'status' => 'required|in:aktif,nonaktif',
    ]);

    $member->update($validated);

    return redirect()->route('members.index')
        ->with('success', "Anggota \"{$validated['nama']}\" berhasil diperbarui.");
}
```

**Penjelasan kode:**

- `findOrFail($id)` mencari baris berdasarkan *primary key*; bila tidak ditemukan, framework otomatis melempar respons HTTP **404 Not Found**, sehingga penanganan error tidak perlu ditulis manual.
- Metode `update()` memvalidasi data secara internal (mengikuti pola `CategoryController`) dengan tambahan `Rule::unique(...)->ignore($member->id)`. Aturan ini mengecek keunikan terhadap seluruh baris **kecuali** baris yang sedang diedit, sehingga anggota dapat menyimpan datanya tanpa dianggap duplikat terhadap dirinya sendiri.
- `$member->update($validated)` hanya menulis kolom yang ada pada `$fillable` pada model `Member`.

#### d. Method `destroy()` — menghapus data

```php
public function destroy(string $id)
{
    $member = Member::findOrFail($id);
    $member->delete();

    return redirect()->route('members.index')
        ->with('success', 'Anggota berhasil dihapus.');
}
```

**Penjelasan kode:**

- Baris dicari dengan `findOrFail()`, dihapus dengan `delete()`, lalu pengguna diarahkan kembali ke halaman daftar dengan pesan sukses.

### 3.3 Langkah 3: Desain Antarmuka Blade

Seluruh view anggota menggunakan template bersama `layouts/app.blade.php` yang menyediakan navbar, area notifikasi `@include('partials.alert')`, serta gaya tabel dan tombol.

#### a. `resources/views/members/create.blade.php` (Form Tambah)

```blade
@extends('layouts.app')

@section('title', 'Tambah Anggota')

@section('content')
    <p><a href="{{ route('members.index') }}">← Kembali ke daftar</a></p>

    <h1>Tambah Anggota</h1>

    <form action="{{ route('members.store') }}" method="POST">
        @csrf

        <div style="margin-bottom: 12px;">
            <label for="nama">Nama Lengkap:</label><br>
            <input type="text" id="nama" name="nama" value="{{ old('nama') }}">
            @error('nama')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        {{-- field nim, email, nomor_telepon, alamat dengan pola yang sama --}}

        <div style="margin-bottom: 12px;">
            <label for="status">Status:</label><br>
            <select id="status" name="status">
                <option value="">-- Pilih Status --</option>
                <option value="aktif" @selected(old('status') == 'aktif')>Aktif</option>
                <option value="nonaktif" @selected(old('status') == 'nonaktif')>Nonaktif</option>
            </select>
            @error('status')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn">Simpan</button>
    </form>
@endsection
```

**Penjelasan kode:**

- `@extends('layouts.app')` dan `@section('content')` menyematkan halaman ke dalam template utama agar tampilan konsisten dengan halaman lain.
- `@csrf` menyertakan *token* keamanan Laravel pada setiap form POST.
- `old('nama')` mengembalikan nilai input sebelumnya ketika validasi gagal, sehingga pengguna tidak perlu mengisi ulang seluruh form.
- `@error('nama') ... @enderror` menampilkan pesan validasi berasal dari `StoreMemberRequest`.
- `@selected(old('status') == 'aktif')` mempertahankan pilihan status yang sudah dibuat pengguna.

#### b. `resources/views/members/edit.blade.php` (Form Ubah)

```blade
@extends('layouts.app')

@section('title', 'Edit Anggota')

@section('content')
    <p><a href="{{ route('members.index') }}">← Kembali ke daftar</a></p>

    <h1>Edit Anggota</h1>

    <form action="{{ route('members.update', $member->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 12px;">
            <label for="nama">Nama Lengkap:</label><br>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $member->nama) }}">
            @error('nama')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        {{-- field nim, email, nomor_telepon, alamat dengan pola yang sama --}}

        <div style="margin-bottom: 12px;">
            <label for="status">Status:</label><br>
            <select id="status" name="status">
                <option value="">-- Pilih Status --</option>
                <option value="aktif" @selected(old('status', $member->status) == 'aktif')>Aktif</option>
                <option value="nonaktif" @selected(old('status', $member->status) == 'nonaktif')>Nonaktif</option>
            </select>
            @error('status')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn">Perbarui</button>
    </form>
@endsection
```

**Penjelasan kode:**

- `@method('PUT')` mensimulasikan HTTP PUT melalui form POST karena HTML tidak mendukung metode PUT secara langsung; Laravel mengonversinya kembali melalui *middleware* `MethodOverride`.
- `value="{{ old('nama', $member->nama) }}"` menampilkan nilai terbaru dari database sebagai nilai default, namun tetap mengutamakan input pengguna bila terjadi kegagalan validasi.

#### c. `resources/views/members/show.blade.php` (Detail Anggota)

```blade
@extends('layouts.app')

@section('title', 'Detail Anggota')

@section('content')
    <p><a href="{{ route('members.index') }}">← Kembali ke daftar</a></p>

    <h1>Detail Anggota</h1>

    <table>
        <tr>
            <th style="width: 160px; background: #f3f4f6;">ID</th>
            <td>{{ $member->id }}</td>
        </tr>
        <tr>
            <th style="background: #f3f4f6;">Nama</th>
            <td>{{ $member->nama }}</td>
        </tr>
        <tr>
            <th style="background: #f3f4f6;">NIM</th>
            <td>{{ $member->nim }}</td>
        </tr>
        <tr>
            <th style="background: #f3f4f6;">Email</th>
            <td>{{ $member->email }}</td>
        </tr>
        <tr>
            <th style="background: #f3f4f6;">Nomor Telepon</th>
            <td>{{ $member->nomor_telepon }}</td>
        </tr>
        <tr>
            <th style="background: #f3f4f6;">Alamat</th>
            <td>{{ $member->alamat }}</td>
        </tr>
        <tr>
            <th style="background: #f3f4f6;">Status</th>
            <td>{{ ucfirst($member->status) }}</td>
        </tr>
        <tr>
            <th style="background: #f3f4f6;">Dibuat</th>
            <td>{{ $member->created_at }}</td>
        </tr>
    </table>

    <p style="margin-top: 16px;">
        <a href="{{ route('members.edit', $member->id) }}" class="btn">Edit</a>
    </p>
@endsection
```

**Penjelasan kode:**

- View ini menerima `$member` hasil `findOrFail()` dan menampilkan seluruh atribut dalam format tabel.
- `ucfirst($member->status)` menampilkan status dengan huruf awal kapital (*aktif* → *Aktif*).
- Tersedia tautan *Edit* sebagai jalur cepat menuju proses pembaruan data.

#### d. `resources/views/members/index.blade.php` (Daftar + Pencarian + Paginasi)

```blade
@extends('layouts.app')

@section('title', 'Daftar Anggota')

@section('content')
    <h1>Daftar Anggota</h1>

    <p><a href="{{ route('members.create') }}" class="btn">+ Tambah Anggota</a></p>

    <form action="{{ route('members.index') }}" method="GET" style="margin-top: 12px; display: flex; gap: 8px;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama anggota..." style="padding: 6px 10px; flex: 1; max-width: 300px;">
        <button type="submit" class="btn">Cari</button>
        @if (request('search'))
            <a href="{{ route('members.index') }}" style="padding: 6px 10px;">Reset</a>
        @endif
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>NIM</th>
                <th>Email</th>
                <th>No. Telepon</th>
                <th>Alamat</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($members as $member)
                <tr>
                    <td>{{ $member->id }}</td>
                    <td>{{ $member->nama }}</td>
                    <td>{{ $member->nim }}</td>
                    <td>{{ $member->email }}</td>
                    <td>{{ $member->nomor_telepon }}</td>
                    <td>{{ $member->alamat }}</td>
                    <td>{{ ucfirst($member->status) }}</td>
                    <td>
                        <a href="{{ route('members.show', $member->id) }}">Detail</a>
                        |
                        <a href="{{ route('members.edit', $member->id) }}">Edit</a>
                        |
                        <form class="inline" action="{{ route('members.destroy', $member->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada data anggota.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $members->appends(request()->query())->links() }}
@endsection
```

**Penjelasan kode:**

1. Form pencarian menggunakan **metode GET** ke route `members.index` dengan atribut `name="search"`, sehingga kata kunci terbaca oleh `request('search')` di controller.
2. `value="{{ request('search') }}"` mempertahankan kata kunci pada input setelah form dikirim.
3. `@forelse ... @empty ... @endforelse` menampilkan baris data, atau pesan "Belum ada data anggota." bila hasil kosong (termasuk ketika pencarian tidak menemukan kecocokan).
4. Tombol *Hapus* berada dalam `form.inline` dengan `@csrf` dan `@method('DELETE')` karena browser hanya mendukung GET dan POST.
5. `{{ $members->appends(request()->query())->links() }}` merender tautan paginasi **sambil menyertakan seluruh parameter query aktif** (termasuk `search`), sehingga hasil pencarian tetap konsisten ketika pengguna berpindah halaman.

---

## 4. Hasil Uji Coba & Bukti Percobaan

### 4.1 Skenario Pengujian

Pengujian dilakukan terhadap aplikasi yang berjalan pada `php artisan serve` (URL dasar `http://127.0.0.1:8000`).

| No | Skenario | Input | Hasil Ekspektasi | Hasil Aktual |
|---|---|---|---|---|
| 1 | Akses halaman daftar | `GET /members` | Respons **200 OK**, tabel dan pagination tampil | **Sesuai (200)** |
| 2 | Tambah anggota valid | Nama, NIM, email, telepon, alamat, status lengkap & unik | Redirect **302** ke `/members`, data tersimpan, pesan sukses tampil | **Sesuai (302)** |
| 3 | Tambah anggota dengan NIM duplikat | NIM sama dengan data existing | Redirect kembali ke `/members/create`, error "NIM sudah terdaftar." | **Sesuai** |
| 4 | Tambah anggota tanpa alamat | `alamat` kosong | Error "Alamat wajib diisi.", data tidak tersimpan | **Sesuai** |
| 5 | Tambah anggota dengan status di luar daftar | `status=suspend` | Error "Status harus aktif atau nonaktif." | **Sesuai** |
| 6 | Lihat detail anggota | `GET /members/{id}` | Respons **200 OK**, seluruh atribut tampil | **Sesuai (200)** |
| 7 | Form edit anggota | `GET /members/{id}/edit` | Respons **200 OK**, form terisi data lama | **Sesuai (200)** |
| 8 | Perbarui anggota (NIM miliknya sendiri) | Ubah nama & status | Tidak muncul error *unique*; redirect **302**, data berubah | **Sesuai (302)** |
| 9 | Pencarian nama yang cocok | `?search=Budi` | Hanya baris dengan nama mengandung "Budi" tampil | **Sesuai** |
| 10 | Pencarian tanpa kecocokan | `?search=xyz` | Pesan "Belum ada data anggota." tampil | **Sesuai** |
| 11 | Hapus anggota | `DELETE /members/{id}` | Redirect **302**, baris terhapus dari tabel | **Sesuai (302)** |
| 12 | Paginasi & pertahankan search | Navigasi halaman 2 pada kondisi search aktif | Parameter `?search=` tetap ada pada URL halaman berikutnya | **Sesuai** |

Selain itu, pemeriksaan otomatis dijalankan dengan perintah berikut dan menghasilkan seluruhnya lulus:

```
$ php -l app/Http/Requests/StoreMemberRequest.php   → No syntax errors detected
$ php -l app/Http/Controllers/MemberController.php  → No syntax errors detected
$ php artisan route:list --path=members              → 7 route terdaftar
$ php artisan test                                   → Tests: 2 passed (2 assertions)
```

### 4.2 Bukti Screenshot

> **[GANTIKAN DENGAN SCREENSHOT — Data Nyata `/members`]**
>
> **Instruksi:**
> 1. Jalankan aplikasi dengan `php artisan serve`.
> 2. Buka `http://127.0.0.1:8000/members/create` dan isi minimal **satu** anggota hingga tersimpan (idealnya isi 11–12 data agar pagination 10 baris terlihat pada halaman 1 dan 2).
> 3. Buka `http://127.0.0.1:8000/members`.
> 4. Sertakan tangkapan layar yang menampilkan: judul "Daftar Anggota", tabel berisi data, tombol **Detail | Edit | Hapus**, serta kontrol pagination di bawah tabel.
> 5. Tempel gambar di blok ini:
>
> `![Screenshot Data Nyata /members](ganti-dengan-nama-file-screenshot-1.png)`

---

> **[GANTIKAN DENGAN SCREENSHOT — Hasil Pencarian Nama Anggota]**
>
> **Instruksi:**
> 1. Pastikan ada data anggota yang namanya mengandung kata kunci tertentu (misalnya nama depan yang dipakai bersama, atau kata umum seperti "a").
> 2. Buka `http://127.0.0.1:8000/members`.
> 3. Ketik kata kunci pada kolom pencarian (mis. `Siti`), lalu klik **Cari** — atau langsung akses `http://127.0.0.1:8000/members?search=Siti`.
> 4. Sertakan tangkapan layar yang menampilkan **tiga hal sekaligus**: nilai kata kunci pada input pencarian, tabel yang hanya berisi baris dengan nama cocok, serta parameter `?search=Siti` pada *address bar* browser.
> 5. Tempel gambar di blok ini:
>
> `![Screenshot Hasil Pencarian Nama Anggota](ganti-dengan-nama-file-screenshot-2.png)`

---

> **[GANTIKAN DENGAN SCREENSHOT — Opsional: Validasi Gagal]**
>
> **Instruksi:** Buka `http://127.0.0.1:8000/members/create`, isi form dengan NIM yang sudah terpakai lalu klik **Simpan**. Sertakan tangkapan layar pesan merah "NIM sudah terdaftar." dan form yang tetap terisi.
>
> `![Screenshot Validasi Duplikat NIM](ganti-dengan-nama-file-screenshot-3.png)`

---

> **[GANTIKAN DENGAN SCREENSHOT — Opsional: Halaman Detail]**
>
> **Instruksi:** Dari tabel daftar, klik **Detail** pada salah satu baris. Sertakan tangkapan layar halaman "Detail Anggota" yang menampilkan seluruh atribut.
>
> `![Screenshot Detail Anggota](ganti-dengan-nama-file-screenshot-4.png)`

---

## 5. Kesimpulan

1. **CRUD data anggota telah terimplementasi lengkap** pada aplikasi Laravel mengikuti arsitektur MVC. Seluruh operasi — tambah (`create()`), tampil (`findOrFail()` + `paginate()`), ubah (`update()`), dan hapus (`delete()`) — berjalan sesuai harapan dan terhubung dengan route `Route::resource('members', MemberController::class)`.

2. **Validasi berbasis Form Request berhasil diterapkan.** `StoreMemberRequest` menegakkan aturan wajib isi pada seluruh kolom, pembatasan format (`email`), pembatasan nilai (`in:aktif,nonaktif`), serta keunikan `nim` dan `email` terhadap tabel `members`. Pesan error berbahasa Indonesia ditampilkan langsung pada field yang bermasalah, sehingga pengalaman pengguna lebih baik. Pada proses pembaruan, keunikan diperiksa dengan `Rule::unique()->ignore($id)` sehingga data milik anggota yang sama tidak dianggap duplikat.

3. **Fitur pencarian berjalan efisien** berkat penggunaan `Member::when(request('search'), fn ($query, $search) => $query->where('nama', 'like', "%{$search}%"))->paginate(10)`. Klausa `WHERE` hanya ditambahkan ketika parameter pencarian tersedia, pencocokan bersifat sebagian pada kolom nama, dan beban server tetap rendah karena hasil dibatasi 10 baris per halaman.

4. **Paginasi mempertahankan konteks pencarian** melalui `{{ $members->appends(request()->query())->links() }}`, sehingga pengguna tidak kehilangan kata kunci saat berpindah halaman — aspek penting pada aplikasi dengan data dalam jumlah banyak.

Secara keseluruhan, praktikum ini membuktikan bahwa Laravel menyediakan mekanisme bawaan yang terstruktur dan efisien untuk membangun aplikasi CRUD beserta pencarian data, sekaligus memperkuat pemahaman terhadap alur kerja *Model–View–Controller*, *Form Request Validation*, *query builder* Eloquent, dan komponen paginasi Blade.
