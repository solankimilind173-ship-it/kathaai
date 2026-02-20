import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import PaginationLinks from '@/Components/PaginationLinks';
import { Head, Link } from '@inertiajs/react';

function formatPrice(price) {
    if (price == null) return '—';
    const n = Number(price);
    if (n >= 100) return `₹${(n / 100).toFixed(0)}`;
    return `₹${n}`;
}

function formatInvoiceTotal(cents) {
    if (cents == null) return '—';
    return `₹${(Number(cents) / 100).toFixed(2)}`;
}

export default function BillingIndex({
    plan,
    creditsBalance,
    creditsAllowance,
    paymentMethod,
    invoices = [],
    creditTransactions,
}) {
    const hasPlan = !!plan;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-stone-800">
                    Billing &amp; usage
                </h2>
            }
        >
            <Head title="Billing &amp; usage" />

            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-8 sm:px-6 lg:px-8">
                    {/* Current plan & credits */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="font-display text-lg font-semibold text-stone-900">Current plan &amp; credits</h3>
                        <div className="mt-4 flex flex-wrap items-center gap-6">
                            {hasPlan ? (
                                <>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wider text-amber-600">Plan</p>
                                        <p className="font-semibold text-stone-900">{plan.name}</p>
                                        <p className="text-sm text-stone-500">
                                            {formatPrice(plan.price)}/mo · {plan.monthly_credits} credits/month
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wider text-amber-600">Credits</p>
                                        <p className="font-semibold text-stone-900">
                                            {creditsBalance} <span className="text-stone-500">/ {creditsAllowance}</span>
                                        </p>
                                        <p className="text-sm text-stone-500">balance / monthly allowance</p>
                                    </div>
                                    <Link href={route('upgrade')}>
                                        <PrimaryButton>Change plan</PrimaryButton>
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <div>
                                        <p className="text-sm text-stone-600">You don&apos;t have an active plan.</p>
                                        <p className="mt-1 text-sm text-stone-500">Subscribe to get monthly credits and unlock features.</p>
                                    </div>
                                    <Link href={route('upgrade')}>
                                        <PrimaryButton>View plans</PrimaryButton>
                                    </Link>
                                </>
                            )}
                        </div>
                    </Card>

                    {/* Payment method */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="font-display text-lg font-semibold text-stone-900">Payment method</h3>
                        <p className="mt-1 text-sm text-stone-600">
                            The payment method used for your subscription. Updated when you checkout.
                        </p>
                        <div className="mt-4">
                            {paymentMethod ? (
                                <p className="rounded-lg border border-amber-200/40 bg-amber-50/50 px-4 py-3 text-sm text-stone-700">
                                    <span className="capitalize font-medium">{paymentMethod.brand}</span> ending in {paymentMethod.last4}
                                </p>
                            ) : (
                                <p className="text-sm text-stone-500">No payment method on file. Add one when you subscribe via the Upgrade page.</p>
                            )}
                        </div>
                    </Card>

                    {/* Invoices */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="font-display text-lg font-semibold text-stone-900">Invoice history</h3>
                        <p className="mt-1 text-sm text-stone-600">
                            Past invoices from your subscription.
                        </p>
                        {invoices.length === 0 ? (
                            <p className="mt-4 text-sm text-stone-500">No invoices yet.</p>
                        ) : (
                            <ul className="mt-4 space-y-2">
                                {invoices.map((inv) => (
                                    <li
                                        key={inv.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200/40 bg-white px-4 py-3"
                                    >
                                        <div>
                                            <span className="font-medium text-stone-800">{inv.date}</span>
                                            <span className="ml-2 text-sm text-stone-500">{formatInvoiceTotal(inv.total)}</span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {inv.hosted_invoice_url && (
                                                <a
                                                    href={inv.hosted_invoice_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-sm font-medium text-amber-600 hover:text-amber-700"
                                                >
                                                    View
                                                </a>
                                            )}
                                            {inv.invoice_pdf && (
                                                <a
                                                    href={inv.invoice_pdf}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-sm font-medium text-amber-600 hover:text-amber-700"
                                                >
                                                    PDF
                                                </a>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    {/* Credit usage history */}
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="font-display text-lg font-semibold text-stone-900">Credit usage history</h3>
                        <p className="mt-1 text-sm text-stone-600">
                            Recent credit transactions: usage (deductions) and grants.
                        </p>
                        {!creditTransactions?.data?.length ? (
                            <p className="mt-4 text-sm text-stone-500">No transactions yet.</p>
                        ) : (
                            <>
                                <ul className="mt-4 space-y-2">
                                    {creditTransactions.data.map((t) => (
                                        <li
                                            key={t.id}
                                            className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-stone-200/60 bg-stone-50/50 px-4 py-3 text-sm"
                                        >
                                            <div>
                                                <span className="font-medium text-stone-800">
                                                    {t.description || t.action_type || t.type}
                                                </span>
                                                {t.project_title && (
                                                    <span className="ml-2 text-stone-500">· {t.project_title}</span>
                                                )}
                                            </div>
                                            <span className={t.amount < 0 ? 'text-red-600' : 'text-green-600'}>
                                                {t.amount > 0 ? '+' : ''}{t.amount}
                                            </span>
                                            <span className="w-full text-xs text-stone-400 sm:w-auto">
                                                {t.created_at ? new Date(t.created_at).toLocaleString() : ''}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                                {creditTransactions.links && (
                                    <PaginationLinks links={creditTransactions.links} wrapperClass="mt-4 flex flex-wrap gap-2" />
                                )}
                            </>
                        )}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
