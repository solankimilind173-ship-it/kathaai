import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PageHeading from '@/Components/PageHeading';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link } from '@inertiajs/react';
import Alert from '@/Components/Alert';
import LanguageSelect from '@/Components/LanguageSelect';
import { useForm } from '@inertiajs/react';

const defaultPlan = {
    max_dubbing_languages: 1,
    max_video_minutes: 5,
    max_reels_per_episode: 1,
    allow_4k: false,
};

export default function Create({
    languages,
    plan,
    books = [],
    sceneGenerationCredits = 50,
    userCredits = 0,
    hasEnoughForSceneGeneration = false,
}) {
    const safePlan = plan ?? defaultPlan;
    const { data, setData, post, processing, errors } = useForm({
        source_type: 'uploaded',
        title: '',
        book_id: '',
        story: '',
        language_id: '',
        dub_languages: [],
        video_minutes: 5,
        quality: '1080p',
        reels_per_episode: 0,
        intro_song: false,
        background_music: false,
    });

    const isLibrary = data.source_type === 'library';
    const canCreate =
        hasEnoughForSceneGeneration &&
        data.title?.trim() &&
        (isLibrary ? data.book_id : (data.story?.trim() ?? '').length > 0);

    function submit(e) {
        e.preventDefault();
        post(route('projects.store'));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">Create Project</h2>
            }
        >
            <Head
                title="Create New Story Project"
                description="Create from a library book or upload your own story. Confirm credits and create."
            />

            <div className="py-6">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <PageHeading
                        title="Create New Story Project"
                        description="Choose a library book or paste your own story. Credits will be deducted for scene generation."
                        action={
                            <Link href={route('projects.index')}>
                                <SecondaryButton>← Back to Projects</SecondaryButton>
                            </Link>
                        }
                    />

                    {Object.keys(errors).length > 0 && (
                        <Alert variant="error" title="Please fix the errors below." className="mb-6">
                            <ul className="list-disc list-inside text-sm">
                                {Object.entries(errors).map(([key, msg]) => (
                                    <li key={key}>{msg}</li>
                                ))}
                            </ul>
                        </Alert>
                    )}

                    <Card>
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel value="How do you want to create your project?" />
                                <div className="mt-2 flex gap-4">
                                    <label className="inline-flex items-center">
                                        <input
                                            type="radio"
                                            name="source_type"
                                            value="library"
                                            checked={data.source_type === 'library'}
                                            onChange={() => setData('source_type', 'library')}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="ml-2">From Library Book</span>
                                    </label>
                                    <label className="inline-flex items-center">
                                        <input
                                            type="radio"
                                            name="source_type"
                                            value="uploaded"
                                            checked={data.source_type === 'uploaded'}
                                            onChange={() => setData('source_type', 'uploaded')}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="ml-2">From Uploaded Book</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Project Title" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="e.g. My First Story"
                                />
                                <InputError message={errors.title} className="mt-1" />
                            </div>

                            {isLibrary ? (
                                <div>
                                    <InputLabel value="Select Book" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={data.book_id}
                                        onChange={(e) => setData('book_id', e.target.value)}
                                    >
                                        <option value="">Choose a book...</option>
                                        {books.map((book) => (
                                            <option key={book.id} value={book.id}>
                                                {book.title}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.book_id} className="mt-1" />
                                </div>
                            ) : (
                                <div>
                                    <InputLabel value="Paste Story" />
                                    <textarea
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows={14}
                                        value={data.story}
                                        onChange={(e) => setData('story', e.target.value)}
                                        placeholder="Paste your story text here..."
                                    />
                                    <InputError message={errors.story} className="mt-1" />
                                </div>
                            )}

                            <div>
                                <InputLabel value="Dubbing Languages (optional)" />
                                <LanguageSelect
                                    languages={languages}
                                    value={data.dub_languages}
                                    onChange={(val) => {
                                        if (val.length <= safePlan.max_dubbing_languages) {
                                            setData('dub_languages', val);
                                        }
                                    }}
                                    isMulti={safePlan.max_dubbing_languages > 1}
                                    placeholder="Select dubbing languages"
                                />
                                <InputError message={errors.dub_languages} className="mt-1" />
                                {safePlan.max_dubbing_languages === 1 && (
                                    <p className="mt-1 text-sm text-amber-600">
                                        Your plan allows only 1 dubbing language.{' '}
                                        <a href="/upgrade" className="underline">Upgrade Plan</a>
                                    </p>
                                )}
                            </div>

                            <div className="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <InputLabel value="Credit estimation" />
                                <p className="mt-1 text-sm text-gray-600">
                                    Scene generation: <strong>{sceneGenerationCredits} credits</strong> will be deducted when you
                                    create the project.
                                </p>
                                <p className="mt-1 text-sm text-gray-600">
                                    Your balance: <strong>{userCredits} credits</strong>
                                </p>
                                {!hasEnoughForSceneGeneration && (
                                    <p className="mt-2 text-sm font-medium text-red-600">
                                        Insufficient credits. You need {sceneGenerationCredits} credits to create this project.
                                    </p>
                                )}
                            </div>

                            <Card.Footer>
                                <PrimaryButton type="submit" disabled={processing || !canCreate}>
                                    {processing ? 'Creating...' : 'Create Project'}
                                </PrimaryButton>
                            </Card.Footer>
                        </form>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
