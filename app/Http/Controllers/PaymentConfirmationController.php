<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\PaymentConfirmation;
use App\Services\OrderWorkflowService;
use App\Services\PaymentMethodService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentConfirmationController extends Controller
{
    public function __construct(private OrderWorkflowService $orderWorkflow) {}

    public function create(Order $order)
    {
        if (! $order->canSubmitPaymentConfirmation()) {
            return redirect()->to($this->redirectUrl($order))
                ->with('info', 'Konfirmasi pembayaran tidak diperlukan untuk pesanan ini.');
        }

        if (app(PaymentMethodService::class)->isCod($order->payment_method)) {
            return redirect()->to($this->redirectUrl($order))
                ->with('info', 'Pesanan COD dibayar saat barang diterima.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()->to($this->redirectUrl($order))
                ->with('info', 'Pembayaran sudah dikonfirmasi.');
        }

        if ($this->hasPendingConfirmation($order)) {
            return redirect()->to($this->redirectUrl($order))
                ->with('info', 'Konfirmasi pembayaran sudah diajukan.');
        }

        $banks = PaymentBank::where('is_active', true)->get();

        return Inertia::render('Guest/Order/ConfirmPayment', [
            'order' => ModelSerializer::order($order),
            'banks' => ModelSerializer::collection($banks, [ModelSerializer::class, 'paymentBank']),
            'isQris' => $order->payment_method === 'qris',
            'qris' => $order->payment_method === 'qris' ? app(PaymentMethodService::class)->qrisSettings() : null,
        ]);
    }

    public function store(Request $request, Order $order)
    {
        if (! $order->canSubmitPaymentConfirmation()) {
            return back()->with('error', 'Konfirmasi pembayaran tidak diperlukan karena pesanan sudah diproses.');
        }

        if (app(PaymentMethodService::class)->isCod($order->payment_method)) {
            return back()->with('error', 'Pesanan COD tidak memerlukan konfirmasi pembayaran di awal.');
        }

        if ($order->payment_status === 'paid') {
            return back()->with('error', 'Pembayaran sudah dikonfirmasi.');
        }

        if ($this->hasPendingConfirmation($order)) {
            return back()->with('error', 'Konfirmasi pembayaran sudah diajukan.');
        }

        $maxAttempts = max(1, (int) setting('max_payment_confirmation_attempts', 3));
        if ($order->paymentConfirmations()->count() >= $maxAttempts) {
            return back()->with('error', 'Batas konfirmasi pembayaran telah tercapai. Hubungi penjual.');
        }

        $isQris = $order->payment_method === 'qris';

        $validated = $request->validate([
            'payment_bank_id' => [$isQris ? 'nullable' : 'required', 'exists:payment_banks,id'],
            'amount_claimed' => 'required|integer|min:1',
            'transfer_date' => 'required|date',
            'sender_name' => 'required|string|max:255',
            'proof_image' => 'nullable|image|max:5120',
        ]);

        if ($order->unique_payment_amount && (int) $validated['amount_claimed'] !== (int) $order->unique_payment_amount) {
            return back()->withErrors([
                'amount_claimed' => 'Nominal harus sesuai instruksi transfer (Rp '.number_format($order->unique_payment_amount, 0, ',', '.').').',
            ]);
        }

        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('payment-proofs', 'public');
        }

        $customer = Auth::guard('customer')->user();

        PaymentConfirmation::create([
            'order_id' => $order->id,
            'customer_id' => $customer?->id,
            'payment_bank_id' => $validated['payment_bank_id'] ?? null,
            'amount_claimed' => $validated['amount_claimed'],
            'transfer_date' => $validated['transfer_date'],
            'sender_name' => $validated['sender_name'],
            'proof_image' => $proofPath,
            'status' => 'pending',
        ]);

        $order->update(['payment_confirmation_status' => 'pending']);

        if ($order->order_status === 'pending') {
            $this->orderWorkflow->transition(
                $order,
                'awaiting_verification',
                'Pembeli mengajukan konfirmasi pembayaran',
                $customer ? 'customer' : 'guest',
                $customer?->id,
            );
        }

        $this->orderWorkflow->notifyAdminPaymentSubmitted($order->fresh());

        return redirect()->to($this->redirectUrl($order))
            ->with('success', 'Konfirmasi pembayaran berhasil diajukan. Menunggu verifikasi penjual.');
    }

    private function hasPendingConfirmation(Order $order): bool
    {
        if (in_array($order->payment_confirmation_status, ['pending', 'approved'], true)) {
            return true;
        }

        return $order->paymentConfirmations()->where('status', 'pending')->exists();
    }

    private function redirectUrl(Order $order): string
    {
        $customer = Auth::guard('customer')->user();

        if ($customer && $order->customer_id === $customer->id) {
            return route('customer.orders.show', $order);
        }

        return order_public_url('order.show', $order);
    }
}
