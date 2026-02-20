import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function CheckIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
        </svg>
    );
}

function formatPrice(price) {
    if (price == null) return '—';
    const n = Number(price);
    if (n >= 100) return `₹${(n / 100).toFixed(0)}`;
    return `₹${n}`;
}

export default function Upgrade({ plans = [], currentPlan = null, stripeConfigured = false, yearlyDiscountPercent = 20 }) {
    const [interval, setInterval] = useState('monthly');
    const { errors } = usePage().props;

    const yearlyDiscountMultiplier = 1 - (Number(yearlyDiscountPercent) || 0) / 100;

    // Use a regular form POST so the browser follows the redirect to Stripe Checkout.
    const checkoutUrl = route('upgrade.checkout');

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Upgrade your plan
                </h2>
            }
        >
            <Head title="Upgrade – Choose a plan" />

            <div className="py-6">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    {(errors?.plan || errors?.stripe) && (
                        <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <InputError message={errors.plan || errors.stripe} />
                        </div>
                    )}
                    {!stripeConfigured && (
                        <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Stripe is not configured yet. Add <code className="rounded bg-amber-100 px-1">STRIPE_KEY</code> and <code className="rounded bg-amber-100 px-1">STRIPE_SECRET</code> to your <code className="rounded bg-amber-100 px-1">.env</code>, and set Stripe Price IDs in the plans table or via <code className="rounded bg-amber-100 px-1">STRIPE_PRICE_*_MONTHLY/YEARLY</code> env variables.
                        </div>
                    )}

                    <div className="text-center">
                        <h1 className="font-display text-2xl font-bold tracking-wide text-stone-800 sm:text-3xl">
                            Choose your plan
                        </h1>
                        <p className="mt-2 text-sm text-stone-600">
                            Subscribe with Stripe to unlock more credits, projects, and features.
                        </p>

                        <div className="mt-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                            <div className="inline-flex rounded-lg border border-amber-200 p-1">
                                <button
                                    type="button"
                                    onClick={() => setInterval('monthly')}
                                    className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                                        interval === 'monthly'
                                            ? 'bg-amber-100 text-amber-900'
                                            : 'text-stone-600 hover:bg-amber-50'
                                    }`}
                                >
                                    Monthly
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setInterval('yearly')}
                                    className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                                        interval === 'yearly'
                                            ? 'bg-amber-100 text-amber-900'
                                            : 'text-stone-600 hover:bg-amber-50'
                                    }`}
                                >
                                    Yearly
                                </button>
                            </div>
                            {yearlyDiscountPercent > 0 && (
                                <span className="rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                    Save {yearlyDiscountPercent}% on yearly
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="mt-8 grid gap-6 sm:grid-cols-3">
                        {plans.map((plan) => {
                            const isYearly = interval === 'yearly';
                            const baseYearly = plan.yearly_price != null ? Number(plan.yearly_price) : null;
                            const discountedYearly = baseYearly != null && yearlyDiscountPercent > 0
                                ? Math.round(baseYearly * yearlyDiscountMultiplier)
                                : baseYearly;
                            const price = isYearly ? (discountedYearly ?? baseYearly) : plan.price;
                            const isCurrentPlan = currentPlan && currentPlan.id === plan.id;
                            const canSubscribe = stripeConfigured && !isCurrentPlan;
                            const showOriginalYearly = isYearly && baseYearly != null && yearlyDiscountPercent > 0 && discountedYearly !== baseYearly;

                            return (
                                <div
                                    key={plan.id}
                                    className="relative flex flex-col overflow-hidden rounded-2xl border border-amber-200 bg-white p-6 shadow-lg transition-all hover:border-amber-300 hover:shadow-xl"
                                >
                                    {plan.slug === 'pro' && (
                                        <div className="absolute right-0 top-0 rounded-bl-lg bg-amber-500 px-3 py-1 text-xs font-semibold text-white">
                                            Popular
                                        </div>
                                    )}
                                    {isCurrentPlan && (
                                        <div className="absolute right-0 top-0 rounded-bl-lg bg-green-600 px-3 py-1 text-xs font-semibold text-white">
                                            Current
                                        </div>
                                    )}
                                    <div className="flex items-center justify-between">
                                        <h3 className="font-display text-xl font-semibold text-stone-800">
                                            {plan.name}
                                        </h3>
                                        <div className="text-right">
                                            {showOriginalYearly && (
                                                <span className="mr-1 text-sm font-medium text-stone-400 line-through">
                                                    {formatPrice(baseYearly)}
                                                </span>
                                            )}
                                            <span className="font-display text-2xl font-bold text-amber-600">
                                                {formatPrice(price)}
                                            </span>
                                            <span className="ml-1 text-sm text-stone-500">
                                                / {isYearly ? 'year' : 'month'}
                                            </span>
                                        </div>
                                    </div>
                                    <p className="mt-4 text-sm text-stone-600">
                                        {plan.monthly_credits} credits per month
                                    </p>
                                    <ul className="mt-5 flex-1 space-y-3">
                                        <li className="flex items-center gap-2 text-sm text-stone-700">
                                            <CheckIcon className="h-4 w-4 shrink-0 text-amber-500" />
                                            Up to {plan.max_projects ?? '∞'} projects
                                        </li>
                                        <li className="flex items-center gap-2 text-sm text-stone-700">
                                            <CheckIcon className="h-4 w-4 shrink-0 text-amber-500" />
                                            {plan.max_video_minutes} min video / project
                                        </li>
                                        <li className="flex items-center gap-2 text-sm text-stone-700">
                                            <CheckIcon className="h-4 w-4 shrink-0 text-amber-500" />
                                            {plan.max_dubbing_languages} dubbing language(s)
                                        </li>
                                        <li className="flex items-center gap-2 text-sm text-stone-700">
                                            <CheckIcon className="h-4 w-4 shrink-0 text-amber-500" />
                                            {plan.max_reels_per_episode} reels per episode
                                        </li>
                                        {plan.allow_4k && (
                                            <li className="flex items-center gap-2 text-sm text-stone-700">
                                                <CheckIcon className="h-4 w-4 shrink-0 text-amber-500" />
                                                4K quality
                                            </li>
                                        )}
                                        {plan.allow_intro_song_generation && (
                                            <li className="flex items-center gap-2 text-sm text-stone-700">
                                                <CheckIcon className="h-4 w-4 shrink-0 text-amber-500" />
                                                Intro song generation
                                            </li>
                                        )}
                                        {plan.allow_background_music && (
                                            <li className="flex items-center gap-2 text-sm text-stone-700">
                                                <CheckIcon className="h-4 w-4 shrink-0 text-amber-500" />
                                                Background music
                                            </li>
                                        )}
                                    </ul>
                                    <div className="mt-6">
                                        {isCurrentPlan ? (
                                            <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-center text-sm font-medium text-green-800">
                                                Your current plan
                                            </div>
                                        ) : (
                                            <form
                                                method="post"
                                                action={checkoutUrl}
                                                className="w-full"
                                                onSubmit={(e) => !canSubscribe && e.preventDefault()}
                                            >
                                                <input type="hidden" name="plan_id" value={plan.id} />
                                                <input type="hidden" name="interval" value={interval} />
                                                <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''} />
                                                <PrimaryButton
                                                    type="submit"
                                                    className="w-full justify-center"
                                                    disabled={!canSubscribe}
                                                >
                                                    Subscribe with Stripe
                                                </PrimaryButton>
                                            </form>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <div className="mt-8 flex justify-center">
                        <Link href={route('dashboard')}>
                            <SecondaryButton>Back to Dashboard</SecondaryButton>
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
