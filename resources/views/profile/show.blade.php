@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <p><a href="{{ route('books.index') }}">← Kembali ke beranda</a></p>

    <h1>Profil Saya</h1>

    <table>
        <tr>
            <th style="width: 180px;">Nama</th>
            <td>{{ $user->name }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <th>Role</th>
            <td>{{ ucfirst($user->role) }}</td>
        </tr>
    </table>

    <h2 style="margin-top: 32px;">Ganti Password</h2>

    <form action="{{ route('profil.password') }}" method="POST" style="max-width: 420px;">
        @csrf

        <div style="margin-bottom: 12px;">
            <label for="old_password">Password Lama:</label><br>
            <input type="password" id="old_password" name="old_password" required>
            @error('old_password')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 12px;">
            <label for="new_password">Password Baru (minimal 8 karakter):</label><br>
            <input type="password" id="new_password" name="new_password" required>
            @error('new_password')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 12px;">
            <label for="new_password_confirmation">Konfirmasi Password Baru:</label><br>
            <input type="password" id="new_password_confirmation" name="new_password_confirmation" required>
            @error('new_password_confirmation')
                <div style="color: red; font-size: 14px;">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn">Simpan Password Baru</button>
    </form>
@endsection
