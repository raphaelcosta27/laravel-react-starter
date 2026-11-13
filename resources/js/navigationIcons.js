import ArticleOutlinedIcon from '@mui/icons-material/ArticleOutlined';
import CampaignOutlinedIcon from '@mui/icons-material/CampaignOutlined';
import CategoryOutlinedIcon from '@mui/icons-material/CategoryOutlined';
import DescriptionOutlinedIcon from '@mui/icons-material/DescriptionOutlined';
import ExtensionOutlinedIcon from '@mui/icons-material/ExtensionOutlined';
import PaletteOutlinedIcon from '@mui/icons-material/PaletteOutlined';
import SettingsOutlinedIcon from '@mui/icons-material/SettingsOutlined';
import TuneIcon from '@mui/icons-material/Tune';

/**
 * Ícones que os pacotes podem pedir pelo nome, no `menu.icon` do registro. Cada
 * ícone é importado um a um, para o build levar só estes. Para oferecer outro,
 * importe-o aqui e acrescente o nome. Veja docs/guias/estender-template.md.
 */
export const navigationIcons = {
    settings: SettingsOutlinedIcon,
    article: ArticleOutlinedIcon,
    tune: TuneIcon,
    palette: PaletteOutlinedIcon,
    campaign: CampaignOutlinedIcon,
    category: CategoryOutlinedIcon,
    description: DescriptionOutlinedIcon,
    extension: ExtensionOutlinedIcon,
};

/** Nome desconhecido usa o ícone de extensão. */
export function navigationIcon(name) {
    return Object.hasOwn(navigationIcons, name)
        ? navigationIcons[name]
        : navigationIcons.extension;
}
