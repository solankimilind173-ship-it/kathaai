import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm } from '@inertiajs/react';

function FeatureField({ def, value, onChange, error }) {
    const name = `features.${def.key}`;
    if (def.type === 'boolean') {
        return (
            <div className="flex items-center gap-2">
                <Checkbox
                    name={name}
                    checked={!!value}
                    onChange={(e) => onChange(def.key, e.target.checked ? '1' : '0')}
                    className="rounded border-amber-300 text-amber-600 focus:ring-amber-400"
                />
                <InputLabel value={def.label} className="mb-0" />
                {error && <InputError message={error} className="ml-2" />}
            </div>
        );
    }
    if (def.type === 'select') {
        const options = def.options && typeof def.options === 'object' ? def.options : {};
        const entries = Array.isArray(options) ? options : Object.entries(options);
        const opts = Array.isArray(options)
            ? options.map((o) => (typeof o === 'object' ? { value: o.value ?? o, label: o.label ?? o } : { value: o, label: o }))
            : entries.map(([value, label]) => ({ value, label }));
        return (
            <div>
                <InputLabel value={def.label} />
                <select
                    name={name}
                    value={value ?? ''}
                    onChange={(e) => onChange(def.key, e.target.value)}
                    className="mt-1 block w-full rounded-lg border-amber-200/60 bg-white px-3 py-2 text-stone-800 shadow-sm focus:border-amber-400 focus:ring-1 focus:ring-amber-300"
                >
                    <option value="">—</option>
                    {opts.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
                {error && <InputError message={error} />}
            </div>
        );
    }
    return (
        <div>
            <InputLabel value={def.label} />
            <TextInput
                type={def.type === 'integer' ? 'number' : 'text'}
                name={name}
                value={value ?? ''}
                onChange={(e) => onChange(def.key, e.target.value)}
                className="mt-1 block w-full rounded-lg border-amber-200/60 bg-white px-3 py-2 text-stone-800 shadow-sm focus:border-amber-400 focus:ring-1 focus:ring-amber-300"
                min={def.type === 'integer' ? 0 : undefined}
            />
            {error && <InputError message={error} />}
        </div>
    );
}

export default function AdminSubscriptionsCreate({ featureDefinitions = [] }) {
    const initialFeatures = featureDefinitions.reduce((acc, d) => ({ ...acc, [d.key]: '' }), {});
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        price: '',
        yearly_price: '',
        monthly_credits: '',
        credit_rollover: false,
        is_active: true,
        features: initialFeatures,
    });

    const setFeature = (key, value) => {
        setData('features', { ...data.features, [key]: value });
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.subscriptions.store'));
    };

    const slugFromName = (name) => (name ? name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '') : '');

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-stone-800">
                    Create plan
                </h2>
            }
        >
            <Head title="Admin – Create plan" />

            <Card className="max-w-2xl border-amber-200/20">
                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Plan name" />
                            <TextInput
                                className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2"
                                value={data.name}
                                onChange={(e) => {
                                    setData('name', e.target.value);
                                    if (!data.slug || data.slug === slugFromName(data.name)) {
                                        setData('slug', slugFromName(e.target.value));
                                    }
                                }}
                                placeholder="e.g. Pro"
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div>
                            <InputLabel value="Slug" />
                            <TextInput
                                className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                                placeholder="e.g. pro"
                            />
                            <InputError message={errors.slug} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Monthly price (₹ INR)" />
                            <TextInput
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2"
                                value={data.price}
                                onChange={(e) => setData('price', e.target.value)}
                            />
                            <InputError message={errors.price} />
                        </div>
                        <div>
                            <InputLabel value="Yearly price (₹ INR) – optional" />
                            <TextInput
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2"
                                value={data.yearly_price}
                                onChange={(e) => setData('yearly_price', e.target.value)}
                            />
                            <InputError message={errors.yearly_price} />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Credits per month" />
                        <TextInput
                            type="number"
                            min="0"
                            className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2"
                            value={data.monthly_credits}
                            onChange={(e) => setData('monthly_credits', e.target.value)}
                        />
                        <InputError message={errors.monthly_credits} />
                    </div>

                    <div className="flex flex-wrap gap-6">
                        <label className="flex items-center gap-2">
                            <Checkbox
                                checked={data.credit_rollover}
                                onChange={(e) => setData('credit_rollover', e.target.checked)}
                                className="rounded border-amber-300 text-amber-600 focus:ring-amber-400"
                            />
                            <span className="text-sm font-medium text-stone-700">Credit rollover</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <Checkbox
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded border-amber-300 text-amber-600 focus:ring-amber-400"
                            />
                            <span className="text-sm font-medium text-stone-700">Active</span>
                        </label>
                    </div>

                    <div className="border-t border-amber-200/40 pt-6">
                        <h3 className="font-display mb-4 text-lg font-semibold text-stone-800">
                            Features & benefits
                        </h3>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {featureDefinitions.map((def) => (
                                <FeatureField
                                    key={def.id}
                                    def={def}
                                    value={data.features?.[def.key]}
                                    onChange={setFeature}
                                    error={errors[`features.${def.key}`]}
                                />
                            ))}
                        </div>
                    </div>

                    <div className="flex gap-3">
                        <PrimaryButton type="submit" disabled={processing}>
                            Create plan
                        </PrimaryButton>
                        <a
                            href={route('admin.subscriptions.index')}
                            className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </Card>
        </AdminLayout>
    );
}
