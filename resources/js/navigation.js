import BadgeOutlinedIcon from '@mui/icons-material/BadgeOutlined';
import HomeOutlinedIcon from '@mui/icons-material/HomeOutlined';
import PeopleOutlinedIcon from '@mui/icons-material/PeopleOutlined';
import TuneIcon from '@mui/icons-material/Tune';
import VpnKeyOutlinedIcon from '@mui/icons-material/VpnKeyOutlined';

export const CONFIG_GROUP = 'config';

/**
 * Módulos dos menus, na ordem de exibição. `route` é o prefixo que destaca o item,
 * e `index` é a rota aberta por ele. Sem `permission`, todos veem. Com
 * `group: CONFIG_GROUP`, o item vai para Config, no rodapé do menu.
 */
export const navigation = [
    {
        key: 'dashboard',
        label: 'Dashboard',
        route: 'dashboard',
        index: 'dashboard',
        permission: null,
        icon: HomeOutlinedIcon,
    },
    {
        key: 'users',
        label: 'Users',
        route: 'admin.users',
        index: 'admin.users.index',
        permission: 'visualizar_usuario',
        icon: PeopleOutlinedIcon,
        group: CONFIG_GROUP,
    },
    {
        key: 'profiles',
        label: 'Profiles',
        route: 'admin.profiles',
        index: 'admin.profiles.index',
        permission: 'visualizar_perfil',
        icon: BadgeOutlinedIcon,
        group: CONFIG_GROUP,
    },
    {
        key: 'permissions',
        label: 'Permissions',
        route: 'admin.permissions',
        index: 'admin.permissions.index',
        permission: 'visualizar_permissao',
        icon: VpnKeyOutlinedIcon,
        group: CONFIG_GROUP,
    },
    {
        key: 'environment',
        label: 'Environment',
        route: 'admin.environment',
        index: 'admin.environment.index',
        permission: 'visualizar_ambiente',
        icon: TuneIcon,
        group: CONFIG_GROUP,
    },
];
