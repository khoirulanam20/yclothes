import { createUsePuck } from '@measured/puck';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Redo2, Settings2, Undo2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const usePuck = createUsePuck();

type Props = {
    title: string;
    status: string;
    backHref: string;
    onOpenSettings: () => void;
    onSave: () => void;
    previewHref?: string;
    saving: boolean;
};

function PuckHistoryButtons() {
    const back = usePuck((s) => s.history.back);
    const forward = usePuck((s) => s.history.forward);
    const hasPast = usePuck((s) => s.history.hasPast);
    const hasFuture = usePuck((s) => s.history.hasFuture);

    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="icon"
                className="h-8 w-8"
                disabled={!hasPast}
                onClick={back}
                aria-label="Undo"
            >
                <Undo2 className="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="outline"
                size="icon"
                className="h-8 w-8"
                disabled={!hasFuture}
                onClick={forward}
                aria-label="Redo"
            >
                <Redo2 className="h-4 w-4" />
            </Button>
        </>
    );
}

export function CmsBuilderToolbar({
    title,
    status,
    backHref,
    onOpenSettings,
    onSave,
    previewHref,
    saving,
}: Props) {
    return (
        <div className="cms-builder-toolbar w-full shrink-0 border-b bg-background" data-tour="cms-builder-toolbar">
            <div className="flex w-full items-center justify-between gap-3 px-3 py-2">
                <div className="flex min-w-0 items-center gap-2">
                    <Button variant="outline" size="icon" className="h-8 w-8 shrink-0" asChild>
                        <Link href={backHref} aria-label="Kembali">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>

                    <h1 className="truncate text-sm font-semibold sm:text-base">{title || 'Halaman Baru'}</h1>

                    <Badge
                        variant={status === 'published' ? 'default' : 'secondary'}
                        className="shrink-0"
                    >
                        {status === 'published' ? 'Terbit' : 'Draft'}
                    </Badge>
                </div>

                <div className="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    <PuckHistoryButtons />

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-8 px-2 sm:px-3"
                        onClick={onOpenSettings}
                    >
                        <Settings2 className="h-4 w-4 sm:mr-1.5" />
                        <span className="hidden sm:inline">Pengaturan</span>
                    </Button>

                    {previewHref && (
                        <Button variant="outline" size="sm" className="h-8" asChild>
                            <Link href={previewHref} target="_blank">
                                Preview
                            </Link>
                        </Button>
                    )}

                    <Button type="button" size="sm" className="h-8 min-w-[4.5rem]" onClick={onSave} disabled={saving}>
                        {saving ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
