import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import { Head, Link, router } from '@inertiajs/react';

export default function TwoFactorDisable() {
    const disable = () => {
        router.post(route('settings.two-factor.disable.post'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-stone-800">
                    Disable Two-Factor Authentication
                </h2>
            }
        >
            <Head title="Disable 2FA" />

            <div className="py-12">
                <div className="mx-auto max-w-xl sm:px-6 lg:px-8">
                    <Card className="p-6">
                        <p className="text-stone-600">
                            Disabling 2FA will make your account less secure. You will only need your password to sign in.
                        </p>
                        <div className="mt-6 flex gap-3">
                            <DangerButton onClick={disable}>
                                Disable 2FA
                            </DangerButton>
                            <Link href={route('settings.index')}>
                                <PrimaryButton type="button">Cancel</PrimaryButton>
                            </Link>
                        </div>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
