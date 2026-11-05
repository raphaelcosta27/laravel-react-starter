import { Link } from '@inertiajs/react';
import { forwardRef } from 'react';

/**
 * Botão que envia um POST. Serve de `component` aos elementos do Material UI,
 * que reservam a propriedade `as`.
 */
const PostLink = forwardRef(function PostLink(props, ref) {
    return <Link ref={ref} as="button" method="post" {...props} />;
});

export default PostLink;
