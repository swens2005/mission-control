import { Head } from '@inertiajs/react';
import { StyleGuide } from '@/components/palette/style-guide';
import type { StyleGuide as Guide } from '@/types';

/**
 * The public, read-only style guide (story 24). Opened by link, without
 * login; nothing here can be changed.
 */
export default function PublicStyleGuide({
    projectName,
    guide,
}: {
    projectName: string;
    guide: Guide;
}) {
    return (
        <>
            <Head title={`Style guide: ${projectName}`}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <div className="space-y-10">
                <div>
                    <p className="lc-label text-muted-foreground">
                        Brand kit · read only
                    </p>
                    <h1 className="mt-1 text-4xl font-extrabold tracking-tight">
                        {projectName}
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        {guide.approvedAt
                            ? 'The approved colors and type for this website.'
                            : 'The colors and type for this website, waiting for approval.'}
                    </p>
                </div>
                <StyleGuide guide={guide} />
            </div>
        </>
    );
}
