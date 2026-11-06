import { useEffect, useRef } from 'react';

/**
 * Atalhos com Ctrl ou Cmd, por letra. Com a tecla modificadora, não atrapalham
 * quem digita nem exigem opção para desligar (WCAG 2.1.4).
 */
export function useShortcuts(shortcuts) {
    const current = useRef(shortcuts);

    useEffect(() => {
        current.current = shortcuts;
    });

    useEffect(() => {
        const press = (event) => {
            const run = current.current[event.key.toLowerCase()];

            if (run && (event.ctrlKey || event.metaKey) && !event.altKey) {
                event.preventDefault();
                run();
            }
        };

        window.addEventListener('keydown', press);

        return () => window.removeEventListener('keydown', press);
    }, []);
}
