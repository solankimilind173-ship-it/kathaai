import { useState } from 'react';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const googleUrl = route('auth.socialite.redirect', { provider: 'google' });

    return (
        <GuestLayout tone="auth">
            <Head title="Log in" />

            <div>
                <p className="text-xs font-semibold uppercase tracking-[0.32em] text-amber-600">
                    Welcome back
                </p>
                <h1 className="mt-3 font-display text-4xl text-stone-900">
                    Enter the studio
                </h1>
                <p className="mt-3 text-sm leading-7 text-stone-600">
                    Sign in to continue building cinematic videos from your written stories.
                </p>
            </div>

            {status && (
                <div className="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}

            <div className="mt-8 space-y-4">
                <a
                    href={googleUrl}
                    className="inline-flex w-full items-center justify-center gap-3 rounded-full border border-stone-200 bg-white px-4 py-3 text-sm font-medium text-stone-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2"
                >
                    <svg className="h-5 w-5" viewBox="0 0 24 24">
                        <path
                            fill="#4285F4"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                            fill="#34A853"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                            fill="#FBBC05"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        />
                        <path
                            fill="#EA4335"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        />
                    </svg>
                    Continue with Google
                </a>

                <div className="relative">
                    <div className="absolute inset-0 flex items-center">
                        <div className="w-full border-t border-stone-200" />
                    </div>
                    <div className="relative flex justify-center text-sm">
                        <span className="bg-white px-3 text-stone-500">Or continue with email</span>
                    </div>
                </div>
            </div>

            <form onSubmit={submit} className="mt-8 space-y-5">
                <div>
                    <InputLabel htmlFor="email" value="Email" className="text-stone-700" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-2 block w-full rounded-2xl border border-stone-200 bg-white/90 px-4 py-3 text-stone-900 shadow-sm focus:border-amber-400 focus:ring-amber-400"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Password" className="text-stone-700" />
                    <div className="relative mt-2">
                        <TextInput
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            className="block w-full rounded-2xl border border-stone-200 bg-white/90 px-4 py-3 pr-12 text-stone-900 shadow-sm focus:border-amber-400 focus:ring-amber-400"
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword((v) => !v)}
                            className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-stone-200 bg-white p-2 text-stone-500 hover:bg-stone-50 hover:text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-400"
                            tabIndex={-1}
                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                        >
                            {showPassword ? (
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            ) : (
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            )}
                        </button>
                    </div>
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="flex items-center justify-between gap-3">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        <span className="ms-2 text-sm text-stone-600">Remember me</span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-sm font-medium text-amber-700 hover:text-amber-800"
                        >
                            Forgot password?
                        </Link>
                    )}
                </div>

                <PrimaryButton disabled={processing} className="w-full">
                    Log in
                </PrimaryButton>

                <div className="text-center text-sm text-stone-600">
                    New to KathaAI?{' '}
                    <Link href={route('register')} className="font-medium text-amber-700 hover:text-amber-800">
                        Create an account
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
