import { Form, Head, Link } from '@inertiajs/react';
import { BadgeCheck } from 'lucide-react';
import ClientPaletteController from '@/actions/App/Http/Controllers/Client/PaletteController';
import { focusById } from '@/components/form-field';
import { StyleGuide } from '@/components/palette/style-guide';
import { formatDate, formatDateTime } from '@/lib/format';
import { show } from '@/routes/client/projects';
import type { StyleGuide as Guide } from '@/types';

type Props = {
    project: { id: number; name: string; targetLaunchOn: string | null };
    guide: Guide;
};

export default function ShowPalette({ project, guide }: Props) {
    const approved = guide.approvedAt !== null;

    return (
        <>
            <Head title={`Brand kit: ${project.name}`} />
            <div className="space-y-10">
                <div>
                    <Link
                        href={show(project.id)}
                        className="text-sm font-semibold text-primary underline"
                    >
                        {project.name}
                    </Link>
                    <h1 className="mt-3 text-4xl font-extrabold tracking-tight">
                        Palette Lab
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        Your brand kit · Target launch:{' '}
                        {formatDate(project.targetLaunchOn)}
                    </p>
                </div>

                <section
                    aria-labelledby="approval-heading"
                    className={`lc-card max-w-3xl space-y-3 p-5 ${approved ? 'lc-edge-pass' : 'lc-edge-warn'}`}
                >
                    <h2
                        id="approval-heading"
                        tabIndex={-1}
                        className="text-xl font-bold"
                    >
                        {approved ? 'Approved' : 'Waiting for your approval'}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {approved
                            ? `Approved by ${guide.approvedByName ?? 'you'} on ${formatDateTime(guide.approvedAt)}. The studio builds with these colors and type.`
                            : 'Look through the colors, the readable pairs and the type. When it feels like your brand, approve it so the studio can design with it.'}
                    </p>
                    {!approved && (
                        <Form
                            {...ClientPaletteController.approve.form(
                                guide.kitId,
                            )}
                            options={{ preserveScroll: true }}
                            onBefore={() =>
                                window.confirm(
                                    'Approve this brand kit? The studio will design with it; changes after this need a revision.',
                                )
                            }
                            onSuccess={() => focusById('approval-heading')}
                        >
                            {({ processing }) => (
                                <button
                                    className="lc-pill"
                                    disabled={processing}
                                    data-tour="approve-kit"
                                >
                                    <BadgeCheck
                                        aria-hidden="true"
                                        className="size-5"
                                    />
                                    Approve the brand kit
                                </button>
                            )}
                        </Form>
                    )}
                </section>

                <StyleGuide guide={guide} />
            </div>
        </>
    );
}
