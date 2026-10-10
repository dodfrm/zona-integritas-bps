import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

const MESSAGES: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Akses ditolak',
        description:
            'Anda tidak memiliki izin atau data ini berada di luar cakupan organisasi Anda.',
    },
    404: {
        title: 'Halaman tidak ditemukan',
        description: 'Data atau halaman yang Anda cari tidak tersedia.',
    },
    500: {
        title: 'Terjadi kesalahan sistem',
        description:
            'Permintaan tidak dapat diproses. Silakan coba lagi atau hubungi administrator.',
    },
    503: {
        title: 'Layanan sedang dalam pemeliharaan',
        description: 'Silakan coba beberapa saat lagi.',
    },
};

export default function ErrorPage({ status }: { status: number }) {
    const message = MESSAGES[status] ?? MESSAGES[500];

    return (
        <>
            <Head title={message.title} />
            <main className="flex min-h-svh flex-col items-center justify-center gap-4 bg-background p-6 text-center">
                <div className="flex size-12 items-center justify-center rounded-lg bg-sidebar text-white">
                    <AppLogoIcon className="size-7" />
                </div>
                <p className="text-sm font-bold text-muted-foreground">
                    Kode {status}
                </p>
                <h1 className="text-2xl font-bold">{message.title}</h1>
                <p className="max-w-md text-muted-foreground">
                    {message.description}
                </p>
                <div className="flex gap-2">
                    <Button
                        variant="outline"
                        onClick={() => window.history.back()}
                    >
                        Kembali
                    </Button>
                    <Button asChild>
                        <Link href={dashboard()}>Ke dashboard</Link>
                    </Button>
                </div>
            </main>
        </>
    );
}
