<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    /**
     * Show billing & usage: current plan, payment method, invoices, credit history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->load('plan');

        $plan = $user->plan;
        $creditsBalance = (int) $user->credits;
        $creditsAllowance = $plan?->monthly_credits ?? 0;

        $paymentMethod = null;
        $invoices = [];
        try {
            if ($user->hasStripeId()) {
                if ($user->defaultPaymentMethod()) {
                    $pm = $user->defaultPaymentMethod();
                    $paymentMethod = [
                        'brand' => $pm->card->brand ?? 'card',
                        'last4' => $pm->card->last4 ?? '****',
                    ];
                }
                $invoices = $user->invoices(50)->map(function ($inv) {
                    return [
                        'id' => $inv->id,
                        'date' => $inv->date()->toDateString(),
                        'total' => $inv->total(),
                        'status' => $inv->status ?? 'paid',
                        'invoice_pdf' => $inv->invoice_pdf,
                        'hosted_invoice_url' => $inv->hosted_invoice_url,
                    ];
                })->toArray();
            }
        } catch (\Throwable $e) {
            // Stripe not configured or no customer
        }

        $creditTransactions = CreditTransaction::where('user_id', $user->id)
            ->with(['project:id,title'])
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'transactions_page')
            ->through(function ($t) {
                return [
                    'id' => $t->id,
                    'amount' => $t->amount,
                    'type' => $t->type,
                    'action_type' => $t->action_type,
                    'description' => $t->description ?? $t->feature,
                    'project_title' => $t->project?->title,
                    'created_at' => $t->created_at->toIso8601String(),
                ];
            });

        return Inertia::render('Billing/Index', [
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->price,
                'yearly_price' => $plan->yearly_price,
                'monthly_credits' => $plan->monthly_credits,
            ] : null,
            'creditsBalance' => $creditsBalance,
            'creditsAllowance' => $creditsAllowance,
            'paymentMethod' => $paymentMethod,
            'invoices' => $invoices,
            'creditTransactions' => $creditTransactions,
        ]);
    }
}
