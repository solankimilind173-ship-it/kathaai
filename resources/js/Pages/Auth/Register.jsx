import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Register() {
    const { flash } = usePage().props;
    const showOtpStep = flash?.otp_sent && flash?.pending_email;

    const registrationForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const otpForm = useForm({
        email: '',
        otp: '',
    });

    useEffect(() => {
        if (flash?.pending_email) {
            otpForm.setData('email', flash.pending_email);
        }
    }, [flash?.pending_email]);

    const sendOtp = (e) => {
        e.preventDefault();
        registrationForm.post(route('register.send-otp'), {
            onFinish: () =>
                registrationForm.reset('password', 'password_confirmation'),
        });
    };

    const verifyAndRegister = (e) => {
        e.preventDefault();
        otpForm.post(route('register'));
    };

    if (showOtpStep) {
        return (
            <GuestLayout>
                <Head title="Verify email" />

                <div className="mb-4 text-sm text-gray-600">
                    We sent a 6-digit verification code to{' '}
                    <strong>{flash.pending_email}</strong>. Enter it below.
                </div>

                <form onSubmit={verifyAndRegister}>
                    <div>
                        <InputLabel htmlFor="otp" value="Verification code" />

                        <TextInput
                            id="otp"
                            type="text"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            maxLength={6}
                            placeholder="000000"
                            value={otpForm.data.otp}
                            className="mt-1 block w-full text-center text-lg tracking-[0.5em]"
                            onChange={(e) => {
                                const v = e.target.value.replace(/\D/g, '');
                                otpForm.setData('otp', v.slice(0, 6));
                            }}
                            isFocused={true}
                            required
                        />

                        <InputError
                            message={otpForm.errors.otp}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-4 text-sm text-gray-600">
                        Didn&apos;t receive the code?{' '}
                        <Link
                            href={route('register.resend-otp')}
                            method="post"
                            as="button"
                            className="font-medium text-gray-900 underline hover:no-underline"
                        >
                            Resend code
                        </Link>
                    </div>

                    <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <Link
                            href={route('register')}
                            className="rounded-md text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Use a different email
                        </Link>

                        <PrimaryButton
                            type="submit"
                            disabled={otpForm.processing}
                        >
                            Verify & Register
                        </PrimaryButton>
                    </div>
                </form>
            </GuestLayout>
        );
    }

    const { data, setData, processing, errors, reset } = registrationForm;

    return (
        <GuestLayout>
            <Head title="Register" />

            <form onSubmit={sendOtp}>
                <div>
                    <InputLabel htmlFor="name" value="Name" />

                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />

                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Confirm Password"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <Link
                        href={route('login')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Already registered?
                    </Link>

                    <PrimaryButton
                        type="submit"
                        className="ms-4"
                        disabled={processing}
                    >
                        Send verification code
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
