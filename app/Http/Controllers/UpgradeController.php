<?php

namespace App\Http\Controllers;

use App\Mail\PaymentSuccess;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class UpgradeController extends Controller
{
    /**
     * Show the upgrade / plans page with Stripe checkout.
     */
    public function index(Request $request): Response
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('price')
            ->get();

        $user = $request->user();
        $currentPlan = $user->plan;

        return Inertia::render('Upgrade', [
            'plans' => $plans,
            'currentPlan' => $currentPlan,
            'stripeConfigured' => (bool) config('services.stripe.secret'),
            'yearlyDiscountPercent' => config('services.stripe.yearly_discount_percent', 20),
        ]);
    }

    /**
     * Create a Stripe Checkout session for the selected plan and redirect to Stripe.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = $request->user();

        $priceId = $plan->getStripePriceIdForInterval($request->interval);

        if (empty($priceId)) {
            return redirect()->route('upgrade')
                ->withErrors(['plan' => 'Stripe is not configured for this plan. Please add Stripe Price IDs in .env or plans table.']);
        }

        try {
            return $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('upgrade.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('upgrade'),
                    'metadata' => [
                        'plan_id' => (string) $plan->id,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout error: ' . $e->getMessage());
            return redirect()->route('upgrade')
                ->withErrors(['stripe' => 'Unable to start checkout. Please try again or contact support.']);
        }
    }

    /**
     * Send invoice and billing details email after successful payment.
     */
    private function sendPaymentSuccessEmail($user, Plan $plan, StripeSession $session): void
    {
        try {
            $amountTotal = $session->amount_total ?? 0;
            $currency = strtolower($session->currency ?? 'usd');
            $amountFormatted = number_format($amountTotal / 100, 2);

            $interval = 'monthly';
            $invoiceNumber = null;
            $invoicePdfUrl = null;
            $receiptUrl = null;

            $subscription = $session->subscription ?? null;
            if ($subscription && isset($subscription->latest_invoice)) {
                $invoice = $subscription->latest_invoice;
                if (is_object($invoice)) {
                    $invoiceNumber = $invoice->number ?? null;
                    $invoicePdfUrl = $invoice->invoice_pdf ?? null;
                    $receiptUrl = $invoice->hosted_invoice_url ?? null;
                    if (isset($invoice->charge) && is_object($invoice->charge) && ! empty($invoice->charge->receipt_url)) {
                        $receiptUrl = $invoice->charge->receipt_url;
                    }
                }
                if (isset($subscription->interval)) {
                    $interval = $subscription->interval === 'year' ? 'yearly' : 'monthly';
                }
            }

            Mail::to($user->email)->send(new PaymentSuccess(
                user: $user,
                plan: $plan,
                amountFormatted: $amountFormatted,
                currency: $currency,
                interval: $interval,
                invoiceNumber: $invoiceNumber,
                invoicePdfUrl: $invoicePdfUrl,
                receiptUrl: $receiptUrl
            ));
        } catch (\Throwable $e) {
            Log::error('Payment success email failed: ' . $e->getMessage(), ['user_id' => $user->id]);
        }
    }

    /**
     * Handle return from Stripe Checkout; assign plan from session metadata.
     */
    public function success(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');
        if (empty($sessionId)) {
            return redirect()->route('upgrade')->withErrors(['session' => 'Invalid session.']);
        }

        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            return redirect()->route('upgrade')->withErrors(['session' => 'Stripe is not configured. Subscription could not be verified.']);
        }

        Stripe::setApiKey($secret);

        try {
            $session = StripeSession::retrieve($sessionId, [
                'expand' => ['subscription', 'subscription.latest_invoice', 'subscription.latest_invoice.charge'],
            ]);
        } catch (ApiErrorException $e) {
            Log::warning('Stripe session retrieve failed: ' . $e->getMessage());
            return redirect()->route('upgrade')->withErrors(['session' => 'Could not verify payment.']);
        }

        $subscription = $session->subscription ?? null;
        $isActive = $subscription && in_array($subscription->status ?? '', ['active', 'trialing'], true);

        if (! $isActive) {
            return redirect()->route('upgrade')->withErrors(['session' => 'No active subscription found. Payment may still be processing.']);
        }

        $user = $request->user();
        $planId = $session->metadata->plan_id ?? null;
        if ($planId) {
            $user->update(['plan_id' => (int) $planId]);
        }

        $plan = Plan::find($planId) ?? $user->plan;
        if ($plan) {
            $this->sendPaymentSuccessEmail($user, $plan, $session);
        }

        return redirect()->route('dashboard')->with('success', 'Your plan has been upgraded. Thank you!');
    }
}
