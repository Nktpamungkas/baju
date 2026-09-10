<?php

namespace App\Domains\Review\Controller;

use App\Domains\Review\Service\ReviewService;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ReviewController
{
    public function __construct(private ReviewService $reviews)
    {
    }

    // Publik — buyer kasih ulasan dari halaman tracking (/pesanan/{token}). Dipanggil
    // lewat axios langsung (bukan Inertia router.post) makanya respon JSON, bukan redirect.
    public function store(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'photo'   => 'nullable|image|max:10240',
        ]);

        try {
            $this->reviews->submit($order, $data, $request->file('photo'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok']);
    }

    // Admin — moderasi (approve, pilih tampil di beranda, hapus).
    public function index(): Response
    {
        return Inertia::render('Admin/Reviews', ['reviews' => $this->reviews->all()]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $this->reviews->approve($review);

        return back();
    }

    public function toggleFeatured(Review $review): RedirectResponse
    {
        $this->reviews->toggleFeatured($review);

        return back();
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->reviews->delete($review);

        return back();
    }
}
