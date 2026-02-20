import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import { Head, Link } from '@inertiajs/react';

const FAQ = [
    {
        question: 'How do I create my first project?',
        answer: 'Go to Projects in the sidebar and click "Create project". Enter a story or paste your text. KathaAI will split it into episodes and generate scenes, characters, and visuals. You can then edit scenes and render your story as video.',
    },
    {
        question: 'What are credits and how do I get them?',
        answer: 'Credits are used for AI actions: generating scenes, regenerating images or voice, and rendering videos. Subscribe to a plan (Upgrade) to get a monthly credit allowance. Your balance and usage history are on the Billing & usage page.',
    },
    {
        question: 'How do I change my plan or upgrade?',
        answer: 'Use the Upgrade link in the sidebar to see plans and subscribe via Stripe. You can switch between monthly and yearly billing. After subscribing, your plan and credits are updated automatically.',
    },
    {
        question: 'Where can I see my invoices and payment method?',
        answer: 'Open Billing & usage from the sidebar. There you can see your current plan, credit balance, payment method on file, and past invoices (with links to view or download PDFs).',
    },
    {
        question: 'What happens if I run out of credits?',
        answer: 'You won’t be able to run credit-consuming actions (e.g. scene generation, image/voice regeneration, video render) until the next billing cycle or until you upgrade to a higher plan. Your existing projects and media stay saved.',
    },
    {
        question: 'How do I share my project with others?',
        answer: 'Open a project and use the visibility/share option to make it public. You’ll get a share link that you can send. Anyone with the link can view the project (read-only).',
    },
    {
        question: 'Where are my generated images and videos?',
        answer: 'Character and scene images are in Image Gallery. Rendered videos appear in Video Gallery. You can also download or view them from within each project.',
    },
    {
        question: 'How do I manage my account and security?',
        answer: 'Use Profile from the user menu to edit your name and email. Use Settings for two-factor authentication, active sessions, email preferences, and to export your data.',
    },
];

export default function HelpIndex() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-stone-800">
                    Help
                </h2>
            }
        >
            <Head title="Help" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-8 sm:px-6 lg:px-8">
                    <Card className="border-amber-200/20 p-6">
                        <h3 className="font-display text-lg font-semibold text-stone-900">Getting started</h3>
                        <p className="mt-2 text-sm text-stone-600">
                            Create a project, add your story, and let KathaAI generate episodes and scenes. Use credits to regenerate images or voice and to render videos. Check your usage and plan on the{' '}
                            <Link href={route('billing.index')} className="font-medium text-amber-600 hover:text-amber-700">
                                Billing &amp; usage
                            </Link>{' '}
                            page.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-3">
                            <Link
                                href={route('projects.index')}
                                className="inline-flex items-center rounded-lg border border-amber-200/60 bg-amber-50/80 px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100"
                            >
                                Go to Projects
                            </Link>
                            <Link
                                href={route('upgrade')}
                                className="inline-flex items-center rounded-lg border border-amber-200/60 bg-amber-50/80 px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100"
                            >
                                View plans
                            </Link>
                        </div>
                    </Card>

                    <Card className="border-amber-200/20 p-6">
                        <h3 className="font-display text-lg font-semibold text-stone-900">Frequently asked questions</h3>
                        <ul className="mt-4 space-y-6">
                            {FAQ.map((item, i) => (
                                <li key={i} className="border-b border-amber-200/20 pb-6 last:border-0 last:pb-0">
                                    <p className="font-medium text-stone-900">{item.question}</p>
                                    <p className="mt-1 text-sm text-stone-600">{item.answer}</p>
                                </li>
                            ))}
                        </ul>
                    </Card>

                    <p className="text-center text-sm text-stone-500">
                        Need more help? Contact support with your account email for assistance.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
