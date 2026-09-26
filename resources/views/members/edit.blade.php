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

        <div style="margin-bottom: 12px;">
            <label for="nim">NIM:</label><br>
            <input type="text" id="nim" name="nim" value="{{ old('nim', $member->nim) }}">
            @error('nim')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 12px;">
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" value="{{ old('email', $member->email) }}">
            @error('email')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 12px;">
            <label for="nomor_telepon">Nomor Telepon:</label><br>
            <input type="text" id="nomor_telepon" name="nomor_telepon" value="{{ old('nomor_telepon', $member->nomor_telepon) }}">
            @error('nomor_telepon')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 12px;">
            <label for="alamat">Alamat:</label><br>
            <textarea id="alamat" name="alamat" rows="3">{{ old('alamat', $member->alamat) }}</textarea>
            @error('alamat')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

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
