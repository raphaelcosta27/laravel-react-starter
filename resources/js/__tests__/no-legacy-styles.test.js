import { readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { describe, expect, test } from 'vitest';

const ROOT = join(process.cwd(), 'resources/js');

const LEGACY = /className=|@headlessui/;

/** Telas ainda não migradas. A lista DEVE ficar vazia ao fim da spec 003. */
const PENDING = [
    'Components/Checkbox.jsx',
    'Components/DangerButton.jsx',
    'Components/DataTable/RowActions.jsx',
    'Components/Dropdown.jsx',
    'Components/InputError.jsx',
    'Components/InputLabel.jsx',
    'Components/Modal.jsx',
    'Components/NavLink.jsx',
    'Components/PrimaryButton.jsx',
    'Components/ResponsiveNavLink.jsx',
    'Components/SecondaryButton.jsx',
    'Components/TextInput.jsx',
    'Layouts/AuthenticatedLayout.jsx',
    'Layouts/GuestLayout.jsx',
    'Pages/Admin/Permissions/Form.jsx',
    'Pages/Admin/Permissions/Index.jsx',
    'Pages/Admin/Profiles/Form.jsx',
    'Pages/Admin/Profiles/Index.jsx',
    'Pages/Admin/Profiles/Users.jsx',
    'Pages/Admin/Users/Form.jsx',
    'Pages/Admin/Users/Index.jsx',
    'Pages/Admin/Users/Permissions.jsx',
    'Pages/Auth/ChangePassword.jsx',
    'Pages/Auth/ConfirmPassword.jsx',
    'Pages/Auth/ForgotPassword.jsx',
    'Pages/Auth/Login.jsx',
    'Pages/Auth/Register.jsx',
    'Pages/Auth/RequestAccess.jsx',
    'Pages/Auth/ResetPassword.jsx',
    'Pages/Auth/VerifyEmail.jsx',
    'Pages/Dashboard.jsx',
    'Pages/Error.jsx',
    'Pages/Profile/Edit.jsx',
    'Pages/Profile/Partials/DeleteUserForm.jsx',
    'Pages/Profile/Partials/UpdatePasswordForm.jsx',
    'Pages/Profile/Partials/UpdateProfileInformationForm.jsx',
    'Pages/Welcome.jsx',
];

const sources = (directory) =>
    readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = join(directory, entry.name);

        if (entry.isDirectory()) {
            return entry.name === '__tests__' ? [] : sources(path);
        }

        return /\.jsx?$/.test(entry.name) ? [path] : [];
    });

const legacy = sources(ROOT)
    .filter((path) => LEGACY.test(readFileSync(path, 'utf8')))
    .map((path) => relative(ROOT, path).replaceAll('\\', '/'))
    .sort();

describe('estilos anteriores', () => {
    test('nenhum arquivo fora da lista usa Tailwind ou Headless UI', () => {
        expect(legacy.filter((path) => !PENDING.includes(path))).toEqual([]);
    });

    test('a lista só traz arquivos que ainda precisam de migração', () => {
        expect(PENDING.filter((path) => !legacy.includes(path))).toEqual([]);
    });

    test('a aplicação não carrega fonte de serviço externo', () => {
        const view = readFileSync(
            join(process.cwd(), 'resources/views/app.blade.php'),
            'utf8',
        );

        expect(view).not.toMatch(/<link[^>]+https?:/);
    });
});
