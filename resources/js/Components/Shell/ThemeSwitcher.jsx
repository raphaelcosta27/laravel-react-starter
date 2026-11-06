import { requestJson } from '@/lib/http';
import { useTranslations } from '@/lib/i18n';
import CheckIcon from '@mui/icons-material/Check';
import DarkModeOutlinedIcon from '@mui/icons-material/DarkModeOutlined';
import LightModeOutlinedIcon from '@mui/icons-material/LightModeOutlined';
import SettingsBrightnessOutlinedIcon from '@mui/icons-material/SettingsBrightnessOutlined';
import ListItemIcon from '@mui/material/ListItemIcon';
import ListSubheader from '@mui/material/ListSubheader';
import MenuItem from '@mui/material/MenuItem';
import { useColorScheme } from '@mui/material/styles';

const OPTIONS = [
    { value: 'light', label: 'Light', icon: LightModeOutlinedIcon },
    { value: 'dark', label: 'Dark', icon: DarkModeOutlinedIcon },
    { value: 'system', label: 'System', icon: SettingsBrightnessOutlinedIcon },
];

/**
 * Itens de um menu. A troca vale na hora e fica no aparelho: se a conta não
 * puder guardar agora, a pessoa segue com a escolha feita.
 */
export default function ThemeSwitcher() {
    const __ = useTranslations();
    const { mode, setMode } = useColorScheme();

    const choose = (theme) => {
        setMode(theme);

        requestJson(route('profile.theme.update'), {
            method: 'PATCH',
            body: { theme },
        }).catch(() => {});
    };

    return (
        <>
            <ListSubheader
                disableSticky
                role="presentation"
                sx={{ bgcolor: 'transparent', lineHeight: 2.5 }}
            >
                {__('Theme')}
            </ListSubheader>

            {OPTIONS.map(({ value, label, icon: Icon }) => (
                <MenuItem
                    key={value}
                    role="menuitemradio"
                    aria-checked={mode === value}
                    onClick={() => choose(value)}
                >
                    <ListItemIcon>
                        <Icon fontSize="small" />
                    </ListItemIcon>
                    {__(label)}
                    {mode === value && (
                        <CheckIcon
                            fontSize="small"
                            sx={{ color: 'primary.main', marginInlineStart: 'auto' }}
                        />
                    )}
                </MenuItem>
            ))}
        </>
    );
}
