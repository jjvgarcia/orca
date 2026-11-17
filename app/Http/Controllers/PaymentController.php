<?php

namespace App\Http\Controllers;

use App\Domain\Exceptions\DomainRuleViolation;
use App\Enums\PaymentMethod;
use App\Models\Receivable;
use App\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PaymentController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'receivable_id' => ['required', 'integer'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ]);

        $receivable = Receivable::findOrFail($data['receivable_id']);

        try {
            $result = $payments->register(
                $receivable,
                (int) $data['amount_cents'],
                PaymentMethod::from($data['method']),
                CarbonImmutable::parse($data['paid_at']),
                $data['idempotency_key'],
            );
        } catch (DomainRuleViolation $violation) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => $violation->status === 409 ? 'conflict' : 'unprocessable',
                        'message' => $violation->getMessage(),
                    ],
                ], $violation->status);
            }

            return back()
                ->withInput()
                ->withErrors(['payment' => $violation->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'replayed' => $result['replayed'],
                'data' => [
                    'id' => $result['payment']->id,
                    'receivable_id' => $result['payment']->receivable_id,
                    'amount_cents' => $result['payment']->amount_cents,
                    'method' => $result['payment']->method->value,
                    'paid_at' => $result['payment']->paid_at->toDateString(),
                ],
            ], $result['replayed'] ? 200 : 201);
        }

        return redirect()
            ->route('orders.show', $result['payment']->receivable->order_id)
            ->with('status', $result['replayed']
                ? 'Pagamento já registrado com esta chave — nada foi duplicado.'
                : 'Pagamento registrado.');
    }
}
