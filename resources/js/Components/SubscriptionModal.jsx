import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import { Link } from '@inertiajs/react';

function CheckIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
        </svg>
    );
}

export default function SubscriptionModal({ show, onClose, plans }) {
    const formatPrice = (price) => {
        if (price == null) return '—';
        const n = Number(price);
        if (n >= 100) return `₹${(n / 100).toFixed(0)}`;
        return `₹${n}`;
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="4xl" closeable>
            <div className="bg-gradient-to-b from-amber-50 to-white px-6 py-8 text-stone-800 sm:p-10">
                <div className="text-center">
                    <h2 className="font-display text-2xl font-bold tracking-wide text-stone-800 sm:text-3xl">
                        All plans
                    </h2>
                    <p className="mt-2 text-sm text-stone-600">
                        Subscribe to unlock story creation, episodes, and more. All plans include monthly credits.
                    </p>
                </div>

                <div className="mt-8 max-h-[60vh] overflow-y-auto">
                    <div className="grid gap-6 sm:grid-cols-2">
                    {plans?.map((plan) => (
                        <div
                            key={plan.id}
                            className="relative overflow-hidden rounded-2xl border border-amber-200 bg-white p-6 shadow-lg transition-all hover:border-amber-300 hover:shadow-xl"
                        >
                            {plan.slug === 'pro' && (
                                <div className="absolute right-0 top-0 rounded-bl-lg bg-amber-500 px-3 py-1 text-xs font-semibold text-white">
                                    Popular
                                </div>
                            )}
                            <div className="flex items-center justify-between">
                                <h3 className="font-display text-xl font-semibold text-stone-800">
                                    {plan.name}
                                </h3>
                                <div className="text-right">
                                    <span className="font-display text-2xl font-bold text-amber-600">
                                        {formatPrice(plan.price)}
                                    </span>
                                    <span className="ml-1 text-sm text-stone-500">/ month</span>
                                </div>
                            </div>
                            <p className="mt-4 text-sm text-stone-600">
                                {plan.monthly_credits} credits per month
                            </p>
                            <ul className="mt-5 space-y-3">
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
                                <Link
                                    href={route('dashboard')}
                                    className="block"
                                >
                                    <PrimaryButton className="w-full justify-center">
                                        Get {plan.name}
                                    </PrimaryButton>
                                </Link>
                            </div>
                        </div>
                    ))}
                    </div>
                </div>

                <div className="mt-6 flex justify-center">
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-sm text-stone-500 underline hover:text-stone-700"
                    >
                        Maybe later
                    </button>
                </div>
            </div>
        </Modal>
    );
}
