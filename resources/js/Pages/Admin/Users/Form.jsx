import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ user, profileOptions, canChangeProfile }) {
    const __ = useTranslations();
    const title = user ? __('Edit user') : __('New user');

    const { data, setData, post, patch, errors, processing } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        profile_id: user?.profile_id ? String(user.profile_id) : '',
    });

    const submit = (event) => {
        event.preventDefault();

        if (user) {
            patch(route('admin.users.update', user.id));
        } else {
            post(route('admin.users.store'));
        }
    };

    return (
        <AuthenticatedLayout title={title}>
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
                            maxLength={255}
                            required
                            isFocused
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                        />
                        <InputError className="mt-2" message={errors.name} />
                    </div>

                    <div>
                        <InputLabel htmlFor="email" value={__('Email')} />
                        <TextInput
                            id="email"
                            type="email"
                            className="mt-1 block w-full"
                            value={data.email}
                            maxLength={255}
                            required
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                        />
                        <InputError className="mt-2" message={errors.email} />
                    </div>

                    <div>
                        <InputLabel htmlFor="profile_id" value={__('Profile')} />
                        <select
                            id="profile_id"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                            value={data.profile_id}
                            required
                            disabled={!canChangeProfile}
                            onChange={(event) =>
                                setData('profile_id', event.target.value)
                            }
                        >
                            <option value="">{__('Select')}</option>
                            {profileOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <InputError className="mt-2" message={errors.profile_id} />
                    </div>

                    <div className="flex items-center gap-4">
                        <PrimaryButton disabled={processing}>
                            {__('Save')}
                        </PrimaryButton>

                        <Link
                            href={route('admin.users.index')}
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
