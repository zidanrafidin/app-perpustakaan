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
            'nama' => 'required|string|max:150',
            'nim' => 'required|string|max:20',
            'email' => 'required|email|max:100',
            'nomor_telepon' => 'required|string|max:15',
            'alamat' => 'nullable|string',
            'status' => 'required|in:aktif,nonaktif',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama anggota wajib diisi.',
            'nim.required' => 'NIM wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'nomor_telepon.required' => 'Nomor telepon wajib diisi.',
            'status.required' => 'Status anggota wajib dipilih.',
            'status.in' => 'Status harus aktif atau nonaktif.',
        ];
    }
}