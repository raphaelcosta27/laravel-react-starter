import UserAvatar from '@/Components/UserAvatar';
import { useTranslations } from '@/lib/i18n';
import { router, usePage } from '@inertiajs/react';
import PhotoCameraOutlinedIcon from '@mui/icons-material/PhotoCameraOutlined';
import DeleteOutlinedIcon from '@mui/icons-material/DeleteOutlined';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import { useRef, useState } from 'react';

export default function UpdateAvatarForm({ maxKb = 2048 }) {
    const __ = useTranslations();
    const { auth, errors } = usePage().props;
    const input = useRef(null);

    const [refusal, setRefusal] = useState(null);
    const [processing, setProcessing] = useState(false);

    const size = Math.round((maxKb / 1024) * 10) / 10;
    const error = refusal ?? errors?.avatar;

    const visit = {
        preserveScroll: true,
        preserveState: true,
        onStart: () => setProcessing(true),
        onFinish: () => setProcessing(false),
    };

    const upload = (event) => {
        const file = event.target.files[0];

        event.target.value = '';

        if (!file) {
            return;
        }

        if (file.size > maxKb * 1024) {
            setRefusal(__('The picture must be at most :size MB.', { size }));

            return;
        }

        setRefusal(null);
        router.post(route('profile.avatar.update'), { avatar: file }, visit);
    };

    const remove = () => {
        setRefusal(null);
        router.delete(route('profile.avatar.destroy'), visit);
    };

    return (
        <Stack component="section" spacing={3} sx={{ maxWidth: 576 }}>
            <header>
                <Typography component="h2" variant="h6">
                    {__('Profile picture')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__('JPG, PNG or WebP, up to :size MB.', { size })}
                </Typography>
            </header>

            <Stack
                direction={{ xs: 'column', sm: 'row' }}
                spacing={2}
                sx={{ alignItems: { sm: 'center' } }}
            >
                <UserAvatar size={96} alt={__('Profile picture')} />

                <input
                    ref={input}
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp"
                    aria-label={__('Profile picture file')}
                    hidden
                    onChange={upload}
                />

                <Button
                    startIcon={<PhotoCameraOutlinedIcon />}
                    variant="contained"
                    disabled={processing}
                    onClick={() => input.current.click()}
                >
                    {__('Choose picture')}
                </Button>

                {auth.avatarUrl && (
                    <Button startIcon={<DeleteOutlinedIcon />} color="inherit" disabled={processing} onClick={remove}>
                        {__('Remove picture')}
                    </Button>
                )}
            </Stack>

            {error && (
                <Typography role="alert" variant="body2" color="error">
                    {error}
                </Typography>
            )}
        </Stack>
    );
}
