import BlockCard from '@/Components/Dashboard/BlockCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, usePage } from '@inertiajs/react';
import Box from '@mui/material/Box';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

const SECTIONS = [
    ['overview', 'Overview'],
    ['details', 'Details'],
    ['account', 'Your account'],
];

const WIDTHS = {
    full: '100%',
    half: 'calc(50% - 8px)',
    third: 'calc(33.333% - 11px)',
};

/**
 * Um arquivo novo nesta pasta já é um bloco: o nome dele é a chave do servidor, e o
 * `layout` exportado diz onde ele fica. Sem `layout`, é um terço da seção Detalhes.
 */
const modules = import.meta.glob('../Components/Dashboard/Blocks/*.jsx', {
    eager: true,
});

const definitions = Object.fromEntries(
    Object.entries(modules).map(([path, module]) => [
        path.split('/').pop().replace('.jsx', ''),
        {
            Component: module.default,
            section: 'details',
            size: 'third',
            plain: false,
            ...module.layout,
        },
    ]),
);

function Block({ block }) {
    const { Component, plain } = definitions[block.key];

    if (plain && !block.failed) {
        return <Component data={block.data} />;
    }

    return (
        <BlockCard title={block.title} failed={block.failed}>
            {!block.failed && <Component data={block.data} />}
        </BlockCard>
    );
}

export default function Dashboard({ blocks = [] }) {
    const __ = useTranslations();
    const { displayName } = usePage().props.auth;

    const known = blocks.filter(({ key }) => definitions[key]);

    return (
        <AuthenticatedLayout title={__('Dashboard')}>
            <Head title={__('Dashboard')} />

            <Stack spacing={4}>
                <Typography component="h2" variant="h5">
                    {__('Hello, :name', { name: displayName })}
                </Typography>

                {SECTIONS.map(([section, label]) => {
                    const items = known.filter(
                        ({ key }) => definitions[key].section === section,
                    );

                    return (
                        items.length > 0 && (
                            <Stack key={section} spacing={2}>
                                <Typography component="h2" variant="h6">
                                    {__(label)}
                                </Typography>

                                {/* Quem sobra na linha cresce: nunca fica um vão. */}
                                <Box
                                    sx={{
                                        display: 'flex',
                                        flexWrap: 'wrap',
                                        gap: 2,
                                    }}
                                >
                                    {items.map((block) => (
                                        <Box
                                            key={block.key}
                                            sx={{
                                                display: 'flex',
                                                minWidth: 0,
                                                flexGrow: 1,
                                                flexBasis: {
                                                    xs: '100%',
                                                    md: WIDTHS[
                                                        definitions[block.key].size
                                                    ],
                                                },
                                                '& > *': { flexGrow: 1, minWidth: 0 },
                                            }}
                                        >
                                            <Block block={block} />
                                        </Box>
                                    ))}
                                </Box>
                            </Stack>
                        )
                    );
                })}
            </Stack>
        </AuthenticatedLayout>
    );
}
