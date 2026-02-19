import { useForm } from '@inertiajs/react';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head } from '@inertiajs/react';
import Alert from '@/Components/Alert';
import LanguageSelect from '@/Components/LanguageSelect';

export default function Create({ languages, plan }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        story: '',
        pdf: null,
        language_id: '',
        dub_languages: [], // IMPORTANT
    });
    function submit(e) {
        e.preventDefault();
        post(route('projects.store'), {
            forceFormData: true,
        });
    }

    return (
        <div className="mx-auto max-w-3xl p-6">
            <Head title="Create New Story Project" description="Add a title and paste your story to get started." />

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
                        <InputLabel value="Project Title" />
                        <TextInput
                            className="mt-1 block w-full"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="e.g. My First Story"
                        />
                        <InputError message={errors.title} className="mt-1" />
                    </div>

                    <div className="mt-4">
                        <InputLabel value="Original Story Language" />

                        <LanguageSelect
                            languages={languages.filter(
                                lang => lang.id !== data.language_id
                            )}
                            value={data.dub_languages}
                            onChange={(val) => {
                                if (val.length <= plan.max_dubbing_languages) {
                                    setData('dub_languages', val);
                                }
                            }}
                            isMulti={plan.max_dubbing_languages > 1}
                            placeholder="Select dubbing languages"
                        />

                        {errors.language_id && (
                            <div className="text-red-500 text-sm mt-1">
                                {errors.language_id}
                            </div>
                        )}
                    </div>
                    {plan.max_dubbing_languages === 1 && (
                        <p className="text-sm text-yellow-600 mt-1">
                            Your plan allows only 1 dubbing language.
                            <a href="/upgrade" className="underline ml-1">
                                Upgrade Plan
                            </a>
                        </p>
                    )}
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

                    <Card.Footer>
                        <PrimaryButton type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Project'}
                        </PrimaryButton>
                    </Card.Footer>
                </form>
            </Card>
        </div>
    );
}
