<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    private array $members = [
        ['id' => 1, 'nama' => 'Ahmad Fauzi', 'nim' => '3122600001', 'email' => 'fauzi@example.com', 'nomor_telepon' => '081234567890', 'alamat' => 'Surabaya', 'status' => 'aktif'],
        ['id' => 2, 'nama' => 'Siti Nurhaliza', 'nim' => '3122600002', 'email' => 'siti@example.com', 'nomor_telepon' => '081298765432', 'alamat' => 'Sidoarjo', 'status' => 'aktif'],
        ['id' => 3, 'nama' => 'Budi Santoso', 'nim' => '3122600003', 'email' => 'budi@example.com', 'nomor_telepon' => '081345678901', 'alamat' => 'Gresik', 'status' => 'nonaktif'],
    ];

    public function index()
    {
        $members = $this->members;

        return view('members.index', compact('members'));
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(StoreMemberRequest $request)
    {
        $validated = $request->validated();

        return redirect()->route('members.index')
            ->with('success', "Anggota \"{$validated['nama']}\" berhasil ditambahkan (data dummy, belum tersimpan ke database).");
    }

    public function show(string $id)
    {
        return "MemberController@show, id: {$id}";
    }

    public function edit(string $id)
    {
        return "MemberController@edit, id: {$id}";
    }

    public function update(Request $request, string $id)
    {
        return "MemberController@update, id: {$id}";
    }

    public function destroy(string $id)
    {
        return "MemberController@destroy, id: {$id}";
    }
}