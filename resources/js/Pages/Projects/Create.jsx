import { useForm } from '@inertiajs/react';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import PageHeading from '@/Components/PageHeading';
import Alert from '@/Components/Alert';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        story: '',
        language: 'hindi',
    });

    function submit(e) {
        e.preventDefault();
        post(route('projects.store'));
    }

    return (
        <div className="mx-auto max-w-3xl p-6">
            <PageHeading
                title="Create New Story Project"
                description="Add a title and paste your story to get started."
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
                        <InputLabel value="Project Title" />
                        <TextInput
                            className="mt-1 block w-full"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="e.g. My First Story"
                        />
                        <InputError message={errors.title} className="mt-1" />
                    </div>

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
