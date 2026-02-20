import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('two-factor.challenge'));
    };

    return (
        <GuestLayout>
            <Head title="Two-Factor Authentication" />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <p className="mb-6 text-sm text-stone-600">
                Please enter the code from your authenticator app or a recovery code.
            </p>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="code" value="Code" />
                    <TextInput
                        id="code"
                        type="text"
                        name="code"
                        value={data.code}
                        className="mt-1 block w-full font-mono"
                        autoComplete="one-time-code"
                        autoFocus
                        inputMode="numeric"
                        placeholder="000000"
                        onChange={(e) => setData('code', e.target.value)}
                    />
                    <InputError message={errors.code} className="mt-2" />
                </div>

                <PrimaryButton className="mt-4 w-full" disabled={processing}>
                    Verify
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
