import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ permission }) {
    const __ = useTranslations();
    const title = permission ? __('Edit permission') : __('New permission');

    const { data, setData, post, patch, errors, processing } = useForm({
        title: permission?.title ?? '',
        code: permission?.code ?? '',
        description: permission?.description ?? '',
    });

    const submit = (event) => {
        event.preventDefault();

        if (permission) {
            patch(route('admin.permissions.update', permission.id));
        } else {
            post(route('admin.permissions.store'));
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
                        <InputLabel htmlFor="title" value={__('Title')} />
                        <TextInput
                            id="title"
                            className="mt-1 block w-full"
                            value={data.title}
                            maxLength={150}
                            required
                            isFocused
                            onChange={(event) =>
                                setData('title', event.target.value)
                            }
                        />
                        <InputError className="mt-2" message={errors.title} />
                    </div>

                    <div>
                        <InputLabel htmlFor="code" value={__('Code')} />
                        <TextInput
                            id="code"
                            className="mt-1 block w-full read-only:bg-gray-100"
                            value={data.code}
                            maxLength={100}
                            required
                            readOnly={permission !== null}
                            aria-describedby="code-help"
                            onChange={(event) =>
                                setData('code', event.target.value)
                            }
                        />
                        <p id="code-help" className="mt-1 text-sm text-gray-600">
                            {permission
                                ? __('The code cannot be changed after creation.')
                                : __('Lowercase letters without accents, numbers and underscores, starting with a letter.')}
                        </p>
                        <InputError className="mt-2" message={errors.code} />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value={__('Description')} />
                        <TextInput
                            id="description"
                            className="mt-1 block w-full"
                            value={data.description}
                            maxLength={255}
                            required
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                        />
                        <InputError className="mt-2" message={errors.description} />
                    </div>

                    <div className="flex items-center gap-4">
                        <PrimaryButton disabled={processing}>
                            {__('Save')}
                        </PrimaryButton>

                        <Link
                            href={route('admin.permissions.index')}
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
