<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoanController extends Controller
{
        // File: app/Http/Controllers/Api/LoanController.php
    public function index()
    {
        $loans = Loan::with(['member', 'user', 'loanItems.book'])->paginate(10);

        return LoanResource::collection($loans);
    }
        // File: app/Http/Controllers/Api/LoanController.php
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|integer|exists:members,id',
            'user_id' => 'required|integer|exists:users,id',
            'tanggal_pinjam' => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_pinjam',
            'book_ids' => 'required|array|min:1',
            'book_ids.*' => 'integer|exists:books,id',
        ], [
            'member_id.required' => 'Anggota wajib dipilih.',
            'user_id.required' => 'Petugas (user_id) wajib diisi.',
            'user_id.exists' => 'Petugas yang dipilih tidak valid.',
            'tanggal_pinjam.required' => 'Tanggal pinjam wajib diisi.',
            'tanggal_kembali.required' => 'Tanggal kembali wajib diisi.',
            'tanggal_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
            'book_ids.required' => 'Pilih minimal satu buku.',
        ]);

        $loan = Loan::create([
            'member_id' => $validated['member_id'],
            'user_id' => $validated['user_id'],
            'tanggal_pinjam' => $validated['tanggal_pinjam'],
            'tanggal_kembali' => $validated['tanggal_kembali'],
        ]);

        foreach ($validated['book_ids'] as $bookId) {
            $loan->loanItems()->create(['book_id' => $bookId]);
        }

        $loan->refresh()->load(['member', 'user', 'loanItems.book']);

        return (new LoanResource($loan))->response()->setStatusCode(201);
    }
        // File: app/Http/Controllers/Api/LoanController.php
    public function kembalikan(string $id)
    {
        $loan = Loan::with(['member', 'user', 'loanItems.book'])->findOrFail($id);

        $loan->update([
            'status' => 'dikembalikan',
            'tanggal_dikembalikan' => now()->toDateString(),
        ]);

        return new LoanResource($loan);
    }
}
