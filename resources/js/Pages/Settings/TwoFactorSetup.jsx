import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function TwoFactorSetup({ qrSvg, secret }) {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('settings.two-factor.confirm'));
    };

    return (
        <AuthenticatedLayout
            tone="settings"
            header={
                <h2 className="font-ui text-xl font-semibold leading-tight text-white">
                    Set up Two-Factor Authentication
                </h2>
            }
        >
            <Head title="Set up 2FA" />

            <div className="py-12">
                <div className="w-full space-y-6">
                    <Card className="mx-auto max-w-xl p-6">
                        <p className="text-sm text-stone-600">
                            Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.), then enter the code below.
                        </p>
                        <div
                            className="mt-4 flex justify-center rounded-lg border border-amber-200/60 bg-white p-4"
                            dangerouslySetInnerHTML={{ __html: qrSvg }}
                        />
                        <p className="mt-4 text-xs text-stone-500">
                            Can&apos;t scan? Enter this key manually: <code className="rounded bg-stone-100 px-1 font-mono">{secret}</code>
                        </p>

                        <form onSubmit={submit} className="mt-6">
                            <InputLabel htmlFor="code" value="Verification code" />
                            <TextInput
                                id="code"
                                type="text"
                                value={data.code}
                                className="mt-1 block w-full font-mono"
                                autoComplete="one-time-code"
                                inputMode="numeric"
                                placeholder="000000"
                                onChange={(e) => setData('code', e.target.value)}
                            />
                            <InputError message={errors.code} className="mt-2" />
                            <div className="mt-4 flex gap-3">
                                <PrimaryButton type="submit" disabled={processing}>
                                    Enable 2FA
                                </PrimaryButton>
                                <Link href={route('settings.two-factor.cancel')}>
                                    <PrimaryButton type="button" className="bg-stone-500 hover:bg-stone-600">
                                        Cancel
                                    </PrimaryButton>
                                </Link>
                            </div>
                        </form>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
