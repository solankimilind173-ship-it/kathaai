import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import Checkbox from '@/Components/Checkbox';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const EVENT_LABELS = {
    login: 'Login',
    logout: 'Logout',
    password_changed: 'Password changed',
    two_factor_enabled: 'Two-factor enabled',
    two_factor_disabled: 'Two-factor disabled',
    email_changed: 'Email changed',
    other_sessions_revoked: 'Sessions revoked',
    data_export_requested: 'Data export',
};

function formatSessionDate(timestamp) {
    if (!timestamp) return '—';
    const d = new Date(timestamp * 1000);
    return d.toLocaleString();
}

function parseUserAgent(ua) {
    if (!ua) return 'Unknown device';
    if (ua.length > 60) return ua.slice(0, 57) + '...';
    return ua;
}

export default function Index({
    twoFactorEnabled,
    sessions = [],
    securityEvents = [],
    preferences = {},
    recoveryCodes = null,
    status,
}) {
    const [prefs, setPrefs] = useState({
        email_welcome: preferences.email_welcome ?? true,
        email_password_changed: preferences.email_password_changed ?? true,
        email_project_step: preferences.email_project_step ?? true,
    });
    const [showRecoveryCodes, setShowRecoveryCodes] = useState(!!recoveryCodes?.length);

    const savePreferences = (e) => {
        e.preventDefault();
        router.post(route('settings.preferences.update'), prefs, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-stone-800">
                    Settings
                </h2>
            }
        >
            <Head title="Settings" />

            <div className="py-8">
                <div className="w-full space-y-8">
                    {status && (
                        <div className="rounded-lg border border-amber-200/60 bg-amber-50/80 px-4 py-3 text-sm text-amber-800">
                            {status}
                        </div>
                    )}

                    {/* Recovery codes modal */}
                    {recoveryCodes?.length && showRecoveryCodes && (
                        <Card className="border-amber-200/40 bg-amber-50/50 p-6">
                            <h3 className="text-lg font-semibold text-stone-900">Save your recovery codes</h3>
                            <p className="mt-2 text-sm text-stone-600">
                                Store these codes in a safe place. Each can be used once if you lose access to your authenticator app.
                            </p>
                            <ul className="mt-4 grid grid-cols-2 gap-2 font-mono text-sm">
                                {recoveryCodes.map((code, i) => (
                                    <li key={i} className="rounded bg-white/80 px-2 py-1">{code}</li>
                                ))}
                            </ul>
                            <PrimaryButton className="mt-4" onClick={() => setShowRecoveryCodes(false)}>
                                I’ve saved these
                            </PrimaryButton>
                        </Card>
                    )}

                    {/* Two-Factor Authentication */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="text-lg font-semibold text-stone-900">Two-Factor Authentication</h3>
                        <p className="mt-1 text-sm text-stone-600">
                            Add an extra layer of security by requiring a code from your phone when signing in.
                        </p>
                        <div className="mt-4 flex items-center gap-4">
                            {twoFactorEnabled ? (
                                <>
                                    <span className="rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Enabled</span>
                                    <Link href={route('settings.two-factor.disable')}>
                                        <DangerButton type="button">Disable 2FA</DangerButton>
                                    </Link>
                                </>
                            ) : (
                                <Link href={route('settings.two-factor.setup')}>
                                    <PrimaryButton>Enable 2FA</PrimaryButton>
                                </Link>
                            )}
                        </div>
                        {twoFactorEnabled && (
                            <p className="mt-2 text-xs text-stone-500">
                                Disabling requires password confirmation.
                            </p>
                        )}
                    </Card>

                    {/* Sessions */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="text-lg font-semibold text-stone-900">Active sessions</h3>
                        <p className="mt-1 text-sm text-stone-600">
                            Devices where you’re currently signed in. Revoke all others if you see something unfamiliar.
                        </p>
                        <ul className="mt-4 space-y-3">
                            {sessions.map((s) => (
                                <li
                                    key={s.id}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200/40 bg-white px-4 py-3"
                                >
                                    <div>
                                        <p className="font-medium text-stone-800">{parseUserAgent(s.user_agent)}</p>
                                        <p className="text-xs text-stone-500">
                                            {s.ip_address} · Last active {formatSessionDate(s.last_activity)}
                                            {s.is_current && ' · This device'}
                                        </p>
                                    </div>
                                    {s.is_current && (
                                        <span className="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Current</span>
                                    )}
                                </li>
                            ))}
                        </ul>
                        {sessions.filter((s) => !s.is_current).length > 0 && (
                            <div className="mt-4">
                                <Link href={route('settings.sessions.revoke')}>
                                    <PrimaryButton type="button">Revoke other sessions</PrimaryButton>
                                </Link>
                                <p className="mt-1 text-xs text-stone-500">Requires password confirmation.</p>
                            </div>
                        )}
                    </Card>

                    {/* Security history */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="text-lg font-semibold text-stone-900">Security activity</h3>
                        <p className="mt-1 text-sm text-stone-600">
                            Recent sign-ins and security-related events.
                        </p>
                        <ul className="mt-4 space-y-2">
                            {securityEvents.length === 0 ? (
                                <li className="text-sm text-stone-500">No events yet.</li>
                            ) : (
                                securityEvents.map((ev) => (
                                    <li
                                        key={ev.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded border border-stone-200/60 bg-stone-50/50 px-3 py-2 text-sm"
                                    >
                                        <span className="font-medium text-stone-700">
                                            {EVENT_LABELS[ev.event_type] ?? ev.event_type}
                                        </span>
                                        <span className="text-xs text-stone-500">
                                            {ev.ip_address} · {ev.created_at ? new Date(ev.created_at).toLocaleString() : '—'}
                                        </span>
                                    </li>
                                ))
                            )}
                        </ul>
                    </Card>

                    {/* Email preferences */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="text-lg font-semibold text-stone-900">Email notifications</h3>
                        <p className="mt-1 text-sm text-stone-600">
                            Choose which emails you want to receive.
                        </p>
                        <form onSubmit={savePreferences} className="mt-4 space-y-3">
                            <label className="flex items-center gap-3">
                                <Checkbox
                                    checked={prefs.email_welcome}
                                    onChange={(e) => setPrefs((p) => ({ ...p, email_welcome: e.target.checked }))}
                                />
                                <span className="text-sm text-stone-700">Welcome and account emails</span>
                            </label>
                            <label className="flex items-center gap-3">
                                <Checkbox
                                    checked={prefs.email_password_changed}
                                    onChange={(e) => setPrefs((p) => ({ ...p, email_password_changed: e.target.checked }))}
                                />
                                <span className="text-sm text-stone-700">Password change notifications</span>
                            </label>
                            <label className="flex items-center gap-3">
                                <Checkbox
                                    checked={prefs.email_project_step}
                                    onChange={(e) => setPrefs((p) => ({ ...p, email_project_step: e.target.checked }))}
                                />
                                <span className="text-sm text-stone-700">Project step completed</span>
                            </label>
                            <PrimaryButton type="submit">Save preferences</PrimaryButton>
                        </form>
                    </Card>

                    {/* Data export */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="text-lg font-semibold text-stone-900">Download your data</h3>
                        <p className="mt-1 text-sm text-stone-600">
                            Export your account and project data as JSON. Requires password confirmation.
                        </p>
                        <div className="mt-4">
                            <a href={route('settings.export')}>
                                <PrimaryButton type="button">Export my data</PrimaryButton>
                            </a>
                        </div>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
