import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PageHeading from '@/Components/PageHeading';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, router } from '@inertiajs/react';
import Alert from '@/Components/Alert';
import LanguageSelect from '@/Components/LanguageSelect';
import OnboardingTour from '@/Components/OnboardingTour';
import useOnboarding from '@/Hooks/useOnboarding';
import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const defaultPlan = {
    max_dubbing_languages: 1,
    max_video_minutes: 5,
    max_reels_per_episode: 1,
    allow_4k: false,
    max_episodes_per_day: 1,
};

const defaultEpisodeLimit = { max_per_day: 1, used_today: 0, remaining_today: 1 };

const ACCEPTED_FILE_TYPES = '.pdf,.doc,.docx';
const MAX_FILE_SIZE_MB = 20;

const TAB_STORY = 1;
const TAB_VIDEO_TYPE = 2;

export default function Create({
    languages,
    plan,
    videoFrames = [],
    videoTypes = [],
    episodeLimit = defaultEpisodeLimit,
    books = [],
    sceneGenerationCredits = 50,
    userCredits = 0,
    hasEnoughForSceneGeneration = false,
    demoProjectEligible = false,
}) {
    const { onboarding, startTour, advance, skipTour } = useOnboarding();
    const safePlan = plan ?? defaultPlan;
    const limit = episodeLimit ?? defaultEpisodeLimit;
    const remainingToday = limit.remaining_today ?? 0;
    const atDailyLimit = remainingToday <= 0;
    const [activeTab, setActiveTab] = useState(TAB_STORY);
    const [storyFile, setStoryFile] = useState(null);
    const [showStoryTour, setShowStoryTour] = useState(false);
    const [showVideoTour, setShowVideoTour] = useState(false);
    const fileInputRef = useRef(null);
    const defaultFrame = videoFrames.length > 0 ? videoFrames[0].id : '16:9';
    const defaultVideoType = videoTypes.length > 0 ? videoTypes[0].id : '';
    const { data, setData, post, processing, errors } = useForm({
        source_type: 'uploaded',
        title: '',
        book_id: '',
        story: '',
        language_id: '',
        dub_languages: [],
        video_minutes: 5,
        quality: '1080p',
        video_frame: defaultFrame,
        video_type: defaultVideoType,
        reels_per_episode: 0,
        intro_song: false,
        background_music: false,
        default_video_format: 'youtube',
        default_fps: 24,
        default_subtitle_style: 'default',
    });

    const isLibrary = data.source_type === 'library';
    const hasStoryContent = (data.story?.trim() ?? '').length > 0 || storyFile != null;
    const canGoToTab2 =
        data.title?.trim() &&
        (isLibrary ? data.book_id : hasStoryContent);
    const isFreeDemoProject = demoProjectEligible || onboarding.demo_project_eligible;
    const effectiveSceneGenerationCredits = isFreeDemoProject ? 0 : sceneGenerationCredits;
    const hasEnoughCreditsForCreation = isFreeDemoProject ? true : hasEnoughForSceneGeneration;
    const hasTab2Required =
        data.video_frame &&
        (data.video_type || videoTypes.length === 0);
    const canCreate =
        !atDailyLimit &&
        hasEnoughCreditsForCreation &&
        data.title?.trim() &&
        (isLibrary ? data.book_id : hasStoryContent) &&
        hasTab2Required;

    useEffect(() => {
        if (onboarding.status === 'not_started' && isFreeDemoProject) {
            startTour();
        }
    }, [isFreeDemoProject, onboarding.status, startTour]);

    useEffect(() => {
        const shouldShow =
            onboarding.status === 'in_progress' &&
            isFreeDemoProject &&
            activeTab === TAB_STORY &&
            [
                null,
                'dashboard-create-project',
                'create-source-section',
                'create-story-input',
                'create-next-tab',
            ].includes(onboarding.last_step ?? null);

        setShowStoryTour(shouldShow);
    }, [activeTab, isFreeDemoProject, onboarding.last_step, onboarding.status]);

    useEffect(() => {
        const shouldShow =
            onboarding.status === 'in_progress' &&
            activeTab === TAB_VIDEO_TYPE &&
            ['create-next-tab', 'create-visual-settings', 'create-submit-project'].includes(onboarding.last_step ?? '');

        setShowVideoTour(shouldShow);
    }, [activeTab, onboarding.last_step, onboarding.status]);

    const storyTourSteps = [
        {
            id: 'create-source-section',
            title: 'Choose how you want to start',
            body: 'Pick a library story or paste and upload your own text for the free demo project.',
            target: '[data-tour-id="create-source-section"]',
        },
        {
            id: 'create-story-input',
            title: 'Add the story details',
            body: 'Give the project a title and add the story text or file that KathaAI will turn into episodes.',
            target: '[data-tour-id="create-story-input"]',
        },
        {
            id: 'create-next-tab',
            title: 'Continue to visual settings',
            body: 'Once the story information is ready, move to the next step to choose the frame and visual style.',
            target: '[data-tour-id="create-next-tab"]',
        },
    ];

    const videoTourSteps = [
        {
            id: 'create-visual-settings',
            title: 'Set the cinematic look',
            body: 'Choose the frame, video type, and render defaults that should guide the generated result.',
            target: '[data-tour-id="create-visual-settings"]',
        },
        {
            id: 'create-submit-project',
            title: 'Launch your free demo project',
            body: 'Create the project to start structure generation. Your first demo project will not use scene-generation credits.',
            target: '[data-tour-id="create-submit-project"]',
        },
    ];

    function submit(e) {
        e.preventDefault();
        if (storyFile) {
            const formData = new FormData();
            formData.append('source_type', data.source_type);
            formData.append('title', data.title);
            formData.append('story', data.story);
            formData.append('video_minutes', String(data.video_minutes));
            formData.append('quality', data.quality);
            if (data.video_frame) formData.append('video_frame', data.video_frame);
            if (data.video_type) formData.append('video_type', data.video_type);
            formData.append('reels_per_episode', String(data.reels_per_episode));
            formData.append('intro_song', data.intro_song ? '1' : '0');
            formData.append('background_music', data.background_music ? '1' : '0');
            if (data.default_video_format) formData.append('default_video_format', data.default_video_format);
            if (data.default_fps) formData.append('default_fps', String(data.default_fps));
            if (data.default_subtitle_style) formData.append('default_subtitle_style', data.default_subtitle_style);
            (data.dub_languages || []).forEach((id) => formData.append('dub_languages[]', id));
            formData.append('story_file', storyFile);
            router.post(route('projects.store'), formData, { forceFormData: true });
        } else {
            post(route('projects.store'));
        }
    }

    function handleFileChange(e) {
        const file = e.target.files?.[0];
        if (!file) {
            setStoryFile(null);
            return;
        }
        const ext = file.name.split('.').pop()?.toLowerCase();
        if (!['pdf', 'doc', 'docx'].includes(ext)) {
            setStoryFile(null);
            if (fileInputRef.current) fileInputRef.current.value = '';
            return;
        }
        if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
            setStoryFile(null);
            if (fileInputRef.current) fileInputRef.current.value = '';
            return;
        }
        setStoryFile(file);
    }

    function clearFile() {
        setStoryFile(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    }

    return (
        <AuthenticatedLayout
            tone="projects"
            header={
                <h2 className="font-ui text-xl font-semibold leading-tight text-white">Create Project</h2>
            }
        >
            <Head
                title="Create New Story Project"
                description="Create from a library book or upload your own story. Confirm credits and create."
            />

            <div className="py-6">
                <div className="w-full">
                    <PageHeading
                        title="Create New Story Project"
                        description={
                            <>
                                Choose a library book or paste your own story.
                                <span className="mt-2 block text-sm text-stone-600">
                                    {isFreeDemoProject
                                        ? 'Your first demo project is free, so scene generation credits will not be deducted for this one.'
                                        : 'Credits will be deducted for scene generation.'}{' '}
                                    Your plan: {limit.max_per_day} episode generation{limit.max_per_day !== 1 ? 's' : ''} per day. Used today: {limit.used_today}/{limit.max_per_day}.
                                    {atDailyLimit && (
                                        <span className="text-amber-600 font-medium"> Try again tomorrow or upgrade for more.</span>
                                    )}
                                </span>
                            </>
                        }
                        action={
                            <Link href={route('projects.index')}>
                                <SecondaryButton>← Back to Projects</SecondaryButton>
                            </Link>
                        }
                    />

                    <div className="cinematic-hero-card mb-6 rounded-[1.75rem] p-6 text-white sm:p-8">
                        <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.32em] text-amber-200">Project setup</p>
                                <h2 className="mt-3 font-display text-3xl sm:text-4xl">Shape the story before the first frame is generated.</h2>
                                <p className="mt-4 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                                    Choose the source, define visual format, and set creative defaults so the AI pipeline can build a cinematic result that feels deliberate from scene one.
                                </p>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                                <div className="rounded-[1.25rem] border border-white/10 bg-white/8 px-4 py-4 backdrop-blur-md">
                                    <p className="text-xs uppercase tracking-[0.28em] text-slate-300">Episode limit today</p>
                                    <p className="mt-2 font-display text-2xl text-white">{remainingToday}</p>
                                </div>
                                <div className="rounded-[1.25rem] border border-white/10 bg-white/8 px-4 py-4 backdrop-blur-md">
                                    <p className="text-xs uppercase tracking-[0.28em] text-slate-300">Scene generation cost</p>
                                    <p className="mt-2 font-display text-2xl text-white">{effectiveSceneGenerationCredits}</p>
                                </div>
                                <div className="rounded-[1.25rem] border border-white/10 bg-white/8 px-4 py-4 backdrop-blur-md">
                                    <p className="text-xs uppercase tracking-[0.28em] text-slate-300">Your credits</p>
                                    <p className="mt-2 font-display text-2xl text-white">{userCredits}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {Object.keys(errors).length > 0 && (
                        <Alert variant="error" title="Please fix the errors below." className="mb-6">
                            <ul className="list-disc list-inside text-sm">
                                {Object.entries(errors).map(([key, msg]) => (
                                    <li key={key}>{msg}</li>
                                ))}
                            </ul>
                        </Alert>
                    )}

                    {isFreeDemoProject && (
                        <Card className="mb-6 border-amber-300/40 bg-amber-50/90">
                            <div className="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Free demo project</p>
                                    <h3 className="mt-2 font-display text-2xl text-stone-900">Follow this guided setup once, then watch the AI pipeline work.</h3>
                                    <p className="mt-2 text-sm leading-7 text-stone-600">
                                        This first project is designed to teach the product as you use it. You will not spend scene-generation credits on this demo run.
                                    </p>
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="rounded-2xl border border-amber-200 bg-white/80 px-4 py-4">
                                        <p className="text-sm font-semibold text-stone-900">Step 1</p>
                                        <p className="mt-1 text-sm text-stone-600">Choose the source and give the story a strong title.</p>
                                    </div>
                                    <div className="rounded-2xl border border-amber-200 bg-white/80 px-4 py-4">
                                        <p className="text-sm font-semibold text-stone-900">Step 2</p>
                                        <p className="mt-1 text-sm text-stone-600">Paste the story text or upload a readable PDF or Word file.</p>
                                    </div>
                                    <div className="rounded-2xl border border-amber-200 bg-white/80 px-4 py-4">
                                        <p className="text-sm font-semibold text-stone-900">Step 3</p>
                                        <p className="mt-1 text-sm text-stone-600">Pick the frame and video style you want KathaAI to aim for.</p>
                                    </div>
                                    <div className="rounded-2xl border border-amber-200 bg-white/80 px-4 py-4">
                                        <p className="text-sm font-semibold text-stone-900">Step 4</p>
                                        <p className="mt-1 text-sm text-stone-600">Create the project and continue to the generated episode and scene review.</p>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    )}

                    <Card>
                        <form onSubmit={submit} className="space-y-6 text-stone-900">
                            {/* Tabs */}
                            <div className="flex border-b border-gray-200">
                                <button
                                    type="button"
                                    onClick={() => setActiveTab(TAB_STORY)}
                                    className={`px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors ${
                                        activeTab === TAB_STORY
                                            ? 'border-amber-500 text-amber-700'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    1. Story & details
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveTab(TAB_VIDEO_TYPE)}
                                    className={`px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors ${
                                        activeTab === TAB_VIDEO_TYPE
                                            ? 'border-amber-500 text-amber-700'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    2. Frame & video type
                                </button>
                            </div>

                            {activeTab === TAB_STORY && (
                            <>
                            <div data-tour-id="create-source-section">
                                <InputLabel value="How do you want to create your project?" />
                                <div className="mt-2 flex gap-4">
                                    <label className="inline-flex items-center text-stone-800">
                                        <input
                                            type="radio"
                                            name="source_type"
                                            value="library"
                                            checked={data.source_type === 'library'}
                                            onChange={() => setData('source_type', 'library')}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-stone-800">From Library Book</span>
                                    </label>
                                    <label className="inline-flex items-center text-stone-800">
                                        <input
                                            type="radio"
                                            name="source_type"
                                            value="uploaded"
                                            checked={data.source_type === 'uploaded'}
                                            onChange={() => setData('source_type', 'uploaded')}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-stone-800">From Uploaded Book</span>
                                    </label>
                                </div>
                            </div>

                            <div data-tour-id="create-story-input">
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
                                <>
                                    <div>
                                        <InputLabel value="Upload story file (PDF or Word)" />
                                        <p className="mt-1 text-sm text-stone-600">
                                            Upload a PDF or Word (.doc, .docx) file. Max {MAX_FILE_SIZE_MB} MB. Text will be extracted to create episodes.
                                            {((data.story?.trim() ?? '').length > 0) && (
                                                <span className="mt-1 block text-amber-600">
                                                    Clear the pasted text below to upload a file instead.
                                                </span>
                                            )}
                                        </p>
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept={ACCEPTED_FILE_TYPES}
                                            onChange={handleFileChange}
                                            disabled={((data.story?.trim() ?? '').length > 0)}
                                            className="mt-2 block w-full text-sm text-stone-600 file:mr-4 file:rounded-md file:border-0 file:bg-amber-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-amber-800 hover:file:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-60"
                                        />
                                        {storyFile && (
                                            <div className="mt-2 flex items-center gap-2 text-sm text-stone-700">
                                                <span className="font-medium">{storyFile.name}</span>
                                                <button
                                                    type="button"
                                                    onClick={clearFile}
                                                    className="text-amber-600 hover:text-amber-800 underline"
                                                >
                                                    Remove file
                                                </button>
                                                <span className="text-stone-500">— paste text below instead if you remove it</span>
                                            </div>
                                        )}
                                        <InputError message={errors.story_file} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel value="Or paste story text" />
                                        {storyFile ? (
                                            <p className="mt-1 rounded-md border border-amber-200 bg-amber-50/50 px-3 py-4 text-sm text-stone-600">
                                                Text area is disabled while a file is selected. Click &quot;Remove file&quot; above to paste or edit story text instead.
                                            </p>
                                        ) : (
                                            <>
                                                <p className="mt-1 text-sm text-stone-600">
                                                    Paste your story here, or upload a file above. Use one or the other.
                                                </p>
                                                <textarea
                                                    className="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    rows={14}
                                                    value={data.story}
                                                    onChange={(e) => setData('story', e.target.value)}
                                                    placeholder="Paste your story text here..."
                                                />
                                            </>
                                        )}
                                        <InputError message={errors.story} className="mt-1" />
                                    </div>
                                </>
                            )}

                            <div>
                                <InputLabel value="Dubbing Languages (optional)" />
                                <LanguageSelect
                                    languages={languages}
                                    value={data.dub_languages}
                                    onChange={(val) => {
                                        const next = Array.isArray(val) ? val : (val != null ? [val] : []);
                                        if (next.length <= safePlan.max_dubbing_languages) {
                                            setData('dub_languages', next);
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
                                    Scene generation: <strong>{effectiveSceneGenerationCredits} credits</strong>{' '}
                                    {isFreeDemoProject
                                        ? 'for this guided demo project.'
                                        : 'will be deducted when you create the project.'}
                                </p>
                                <p className="mt-1 text-sm text-gray-600">
                                    Your balance: <strong>{userCredits} credits</strong>
                                </p>
                                {isFreeDemoProject && (
                                    <p className="mt-2 text-sm font-medium text-emerald-700">
                                        Demo unlocked: your first project will be created for free.
                                    </p>
                                )}
                                {!hasEnoughCreditsForCreation && (
                                    <p className="mt-2 text-sm font-medium text-red-600">
                                        Insufficient credits. You need {sceneGenerationCredits} credits to create this project.
                                    </p>
                                )}
                            </div>

                            <div className="flex justify-end">
                                <PrimaryButton
                                    type="button"
                                    onClick={() => {
                                        if (!canGoToTab2) return;
                                        setActiveTab(TAB_VIDEO_TYPE);
                                        if (onboarding.status === 'in_progress') {
                                            advance('create-next-tab');
                                        }
                                    }}
                                    disabled={!canGoToTab2}
                                    data-tour-id="create-next-tab"
                                >
                                    Next
                                </PrimaryButton>
                            </div>
                            </>
                            )}

                            {activeTab === TAB_VIDEO_TYPE && (
                            <div className="space-y-6">
                                {/* Frame selection: small icons with size ratio */}
                                <div data-tour-id="create-visual-settings">
                                    <InputLabel value="Frame (aspect ratio) (required)" />
                                    <p className="mt-1 text-sm text-stone-600">Choose the video size ratio.</p>
                                    <div className="mt-2 flex flex-wrap gap-3">
                                        {videoFrames.map((frame) => (
                                            <button
                                                key={frame.id}
                                                type="button"
                                                onClick={() => setData('video_frame', frame.id)}
                                                className={`flex flex-col items-center gap-1 rounded-lg border-2 p-2 transition-all focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 ${
                                                    data.video_frame === frame.id
                                                        ? 'border-amber-500 bg-amber-50/50'
                                                        : 'border-gray-200 hover:border-gray-300 bg-white'
                                                }`}
                                                title={frame.label}
                                            >
                                                <span
                                                    className={`block rounded bg-gray-300 ${
                                                        frame.id === '9:16'
                                                            ? 'h-10 w-[18px]'
                                                            : 'h-8 w-[36px]'
                                                    }`}
                                                    aria-hidden
                                                />
                                                <span className="text-xs font-medium text-gray-700">{frame.label}</span>
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {/* Video type selection: cinematic, realistic, animated, etc. */}
                                <div>
                                    <InputLabel value="Video type (required)" />
                                    <p className="mt-1 text-sm text-stone-600">Select the visual style for your video.</p>
                                    <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        {videoTypes.map((type) => (
                                            <button
                                                key={type.id}
                                                type="button"
                                                onClick={() => setData('video_type', type.id)}
                                                className={`relative flex items-center gap-3 rounded-lg border-2 p-3 text-left transition-all focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 ${
                                                    data.video_type === type.id
                                                        ? 'border-amber-500 bg-amber-50/50'
                                                        : 'border-gray-200 hover:border-gray-300 bg-white'
                                                }`}
                                            >
                                                {type.sample_image_url ? (
                                                    <img
                                                        src={type.sample_image_url}
                                                        alt=""
                                                        className="h-12 w-20 flex-shrink-0 rounded object-cover"
                                                    />
                                                ) : (
                                                    <div className="h-12 w-20 flex-shrink-0 rounded bg-gray-200" />
                                                )}
                                                <div className="min-w-0 flex-1">
                                                    <p className="font-medium text-gray-900">{type.name}</p>
                                                    {type.description && (
                                                        <p className="text-xs text-gray-500 line-clamp-2">{type.description}</p>
                                                    )}
                                                </div>
                                                {data.video_type === type.id && (
                                                    <span className="absolute top-2 right-2 flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-white">
                                                        <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" /></svg>
                                                    </span>
                                                )}
                                            </button>
                                        ))}
                                    </div>
                                    {videoTypes.length === 0 && (
                                        <p className="mt-2 text-sm text-gray-500">No video types configured. Default will be used.</p>
                                    )}
                                </div>

                                {/* Render defaults (applied to all future videos) */}
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel value="Default resolution for renders" />
                                        <p className="mt-1 text-sm text-stone-600">
                                            This resolution will be used for all videos for this project by default.
                                        </p>
                                        <select
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.quality}
                                            onChange={(e) => setData('quality', e.target.value)}
                                        >
                                            <option value="1080p">1080p</option>
                                            {safePlan.allow_4k && <option value="4k">4K</option>}
                                        </select>
                                        <InputError message={errors.quality} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel value="Default video format" />
                                        <p className="mt-1 text-sm text-stone-600">
                                            Choose where you primarily plan to publish (used for aspect ratio and metadata).
                                        </p>
                                        <select
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.default_video_format}
                                            onChange={(e) => setData('default_video_format', e.target.value)}
                                        >
                                            <option value="youtube">YouTube (16:9)</option>
                                            <option value="instagram_reels">Instagram Reels (9:16)</option>
                                        </select>
                                        <InputError message={errors.default_video_format} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel value="Default FPS" />
                                        <p className="mt-1 text-sm text-stone-600">
                                            Frames per second to use when rendering videos for this project.
                                        </p>
                                        <select
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.default_fps}
                                            onChange={(e) => setData('default_fps', Number(e.target.value))}
                                        >
                                            <option value={24}>24</option>
                                            <option value={30}>30</option>
                                        </select>
                                        <InputError message={errors.default_fps} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel value="Default subtitle style" />
                                        <p className="mt-1 text-sm text-stone-600">
                                            Subtitle appearance applied to all future renders (can be overridden per render if needed).
                                        </p>
                                        <select
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.default_subtitle_style}
                                            onChange={(e) => setData('default_subtitle_style', e.target.value)}
                                        >
                                            <option value="default">Default</option>
                                            <option value="minimal">Minimal</option>
                                            <option value="bold">Bold</option>
                                            <option value="outline">Outline</option>
                                        </select>
                                        <InputError message={errors.default_subtitle_style} className="mt-1" />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <label className="mt-2 inline-flex items-center text-stone-800">
                                            <input
                                                type="checkbox"
                                                checked={data.background_music}
                                                onChange={(e) => setData('background_music', e.target.checked)}
                                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span className="ml-2 text-sm text-stone-700">
                                                Enable background music by default for this project&apos;s videos
                                            </span>
                                        </label>
                                        <InputError message={errors.background_music} className="mt-1" />
                                    </div>
                                </div>
                            </div>
                            )}

                            {activeTab === TAB_VIDEO_TYPE && (
                                <Card.Footer>
                                    <PrimaryButton type="submit" disabled={processing || !canCreate} data-tour-id="create-submit-project">
                                        {processing ? 'Creating...' : 'Create Project'}
                                    </PrimaryButton>
                                </Card.Footer>
                            )}
                        </form>
                    </Card>
                </div>
            </div>

            <OnboardingTour
                open={showStoryTour}
                steps={storyTourSteps}
                onClose={() => {
                    setShowStoryTour(false);
                    skipTour();
                }}
                onFinish={() => setShowStoryTour(false)}
                onAdvance={(stepId) => advance(stepId)}
            />

            <OnboardingTour
                open={showVideoTour}
                steps={videoTourSteps}
                onClose={() => {
                    setShowVideoTour(false);
                    skipTour();
                }}
                onFinish={() => setShowVideoTour(false)}
                onAdvance={(stepId) => advance(stepId)}
            />
        </AuthenticatedLayout>
    );
}
