import { Head } from '@inertiajs/react';
import { home } from '@/routes/client';

export default function Home() {
    return (
        <>
            <Head title="Launchpad" />
            <div className="p-4">
                <h1 className="text-2xl font-semibold">Launchpad</h1>
                <p className="mt-2">Your projects will appear here.</p>
            </div>
        </>
    );
}

Home.layout = {
    breadcrumbs: [{ title: 'Launchpad', href: home() }],
};
