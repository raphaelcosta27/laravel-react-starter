import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ profile, permissions, selected }) {
    const __ = useTranslations();
    const title = profile ? __('Edit profile') : __('New profile');

    const { data, setData, post, patch, errors, processing } = useForm({
        name: profile?.name ?? '',
        description: profile?.description ?? '',
        permissions: selected,
    });

    const toggle = (id, checked) =>
        setData(
            'permissions',
            checked
                ? [...data.permissions, id]
                : data.permissions.filter((item) => item !== id),
        );

    const submit = (event) => {
        event.preventDefault();

        if (profile) {
            patch(route('admin.profiles.update', profile.id));
        } else {
            post(route('admin.profiles.store'));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    {title}
                </h1>
            }
        >
            <Head title={title} />

            <div className="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:px-8">
                <form
                    onSubmit={submit}
                    className="space-y-6 rounded-md bg-white p-6 shadow-sm"
                >
                    <div>
                        <InputLabel htmlFor="name" value={__('Name')} />
                        <TextInput
                            id="name"
                            className="mt-1 block w-full"
                            value={data.name}
                            maxLength={100}
                            required
                            isFocused
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                        />
                        <InputError className="mt-2" message={errors.name} />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value={__('Description')} />
                        <TextInput
                            id="description"
                            className="mt-1 block w-full"
                            value={data.description}
                            maxLength={255}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                        <InputError className="mt-2" message={errors.description} />
                    </div>

                    {profile?.is_administrator ? (
                        <p className="text-sm text-gray-700">
                            {__('This profile has full access to the system.')}
                        </p>
                    ) : (
                        <fieldset>
                            <legend className="text-sm font-medium text-gray-700">
                                {__('Permissions')}
                            </legend>

                            <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                {permissions.map((permission) => (
                                    <label
                                        key={permission.id}
                                        className="flex items-center gap-2 text-sm text-gray-800"
                                    >
                                        <Checkbox
                                            checked={data.permissions.includes(permission.id)}
                                            onChange={(event) =>
                                                toggle(permission.id, event.target.checked)
                                            }
                                        />
                                        {permission.title}
                                    </label>
                                ))}
                            </div>

                            <InputError className="mt-2" message={errors.permissions} />
                        </fieldset>
                    )}

                    <div className="flex items-center gap-4">
                        <PrimaryButton disabled={processing}>
                            {__('Save')}
                        </PrimaryButton>

                        <Link
                            href={route('admin.profiles.index')}
                            className="rounded text-sm text-gray-700 underline focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            {__('Back to list')}
                        </Link>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
