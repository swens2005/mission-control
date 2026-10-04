import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes/admin';

export default function Dashboard() {
    return (
        <>
            <Head title="Mission Control" />
            <div className="p-4">
                <h1 className="text-2xl font-semibold">Mission Control</h1>
                <p className="mt-2">Your studio's projects will appear here.</p>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Mission Control', href: dashboard() }],
};
