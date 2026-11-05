<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatsController extends Controller
{
        // File: app/Http/Controllers/Api/StatsController.php
    public function index()
    {
        return response()->json([
            'total_buku' => Book::count(),
            'total_anggota' => Member::count(),
            'peminjaman_aktif' => Loan::where('status', 'dipinjam')->count(),
        ]);
    }
}
