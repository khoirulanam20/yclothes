<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\ModelSerializer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $reviews = Review::with(['product', 'customer'])
            ->when($status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->latest('created_at')
            ->paginate(20);

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => ModelSerializer::paginated($reviews, [ModelSerializer::class, 'review']),
            'status' => $status,
        ]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => true]);
        Review::recalculateProductRating($review->product_id);

        return back()->with('success', 'Review disetujui.');
    }

    public function reject(Review $review): RedirectResponse
    {
        $productId = $review->product_id;
        $review->delete();
        Review::recalculateProductRating($productId);

        return back()->with('success', 'Review ditolak dan dihapus.');
    }
}
