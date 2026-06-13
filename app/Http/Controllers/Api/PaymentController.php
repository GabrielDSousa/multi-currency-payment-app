<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    private const PER_PAGE = 15;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        $request->validate([
            'status'      => ['sometimes', 'string', 'in:pending,approved,expired,rejected'],
            'currency'    => ['sometimes', 'string', 'size:3'],
            'date_from'   => ['sometimes', 'date'],
            'date_to'     => ['sometimes', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        $query = Payment::query();

        if ($request->user()->department !== 'finance') {
            // Employees only see their own requests; employee_id filter ignored.
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('employee_id')) {
            $query->where('user_id', $request->integer('employee_id'));
        }

        $query->when($request->filled('status'), function ($q) use ($request) {
            match ($request->status) {
                'pending'  => $q->where('pending', true)
                    ->whereNull('approved_at')
                    ->whereNull('expired_at'),
                'approved' => $q->where('pending', false)
                    ->whereNotNull('approved_at'),
                'expired'  => $q->whereNotNull('expired_at'),
                'rejected' => $q->where('pending', false)
                    ->whereNull('approved_at')
                    ->whereNull('expired_at'),
                default    => null,
            };
        });

        $query->when(
            $request->filled('currency'),
            fn($q) => $q->where('currency_code', strtoupper($request->currency))
        );

        $query->when(
            $request->filled('date_from'),
            fn($q) => $q->whereDate('created_at', '>=', $request->date_from)
        );

        $query->when(
            $request->filled('date_to'),
            fn($q) => $q->whereDate('created_at', '<=', $request->date_to)
        );

        return PaymentResource::collection(
            $query->latest()->paginate(self::PER_PAGE)
        );
    }

    public function show(Request $request, Payment $payment): PaymentResource
    {
        $this->authorize('view', $payment);

        return new PaymentResource($payment);
    }

    public function approve(Request $request, Payment $payment): JsonResponse|PaymentResource
    {
        $this->authorize('approve', $payment);

        if (! $this->isPending($payment)) {
            abort(400, 'Only pending requests can be approved.');
        }

        $payment->update([
            'pending'     => false,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return new PaymentResource($payment->fresh());
    }

    public function reject(Request $request, Payment $payment): JsonResponse|PaymentResource
    {
        $this->authorize('reject', $payment);

        if (! $this->isPending($payment)) {
            abort(400, 'Only pending requests can be rejected.');
        }

        $payment->update([
            'pending'     => false,
            'approved_by' => $request->user()->id,
        ]);

        return new PaymentResource($payment->fresh());
    }

    private function isPending(Payment $payment): bool
    {
        return (bool) $payment->pending
            && $payment->approved_at === null
            && $payment->expired_at  === null;
    }
}
