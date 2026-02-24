import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import { Head, Link, router } from '@inertiajs/react';

export default function RevokeSessions() {
    const revoke = () => {
        router.post(route('settings.sessions.revoke.post'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-stone-800">
                    Revoke other sessions
                </h2>
            }
        >
            <Head title="Revoke sessions" />

            <div className="py-12">
                <div className="w-full">
                    <Card className="mx-auto max-w-xl p-6">
                        <p className="text-stone-600">
                            This will sign you out on all other devices. You will stay signed in on this device.
                        </p>
                        <div className="mt-6 flex gap-3">
                            <DangerButton onClick={revoke}>
                                Revoke other sessions
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
