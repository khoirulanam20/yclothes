import { Pencil, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { RichTextEditor } from '@/components/admin/RichTextEditor';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    value: string;
    onChange: (value: string) => void;
    dialogTitle: string;
    buttonLabel?: string;
    modalMinHeight?: number;
};

function stripHtml(html: string): string {
    if (!html.trim()) {
        return '';
    }

    return html
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

export function CmsHtmlEditorField({
    value,
    onChange,
    dialogTitle,
    buttonLabel = 'Edit Konten',
    modalMinHeight = 480,
}: Props) {
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState(value);
    const preview = stripHtml(value);

    useEffect(() => {
        if (open) {
            setDraft(value);
        }
    }, [open, value]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    const handleSave = () => {
        onChange(draft);
        setOpen(false);
    };

    const modal = open
        ? createPortal(
              <div className="fixed inset-0 z-[10000] flex items-center justify-center p-4">
                  <button
                      type="button"
                      className="absolute inset-0 bg-black/50"
                      aria-label="Tutup editor"
                      onClick={() => setOpen(false)}
                  />
                  <div
                      role="dialog"
                      aria-modal="true"
                      aria-labelledby="cms-editor-title"
                      className={cn(
                          'relative z-[10001] flex max-h-[92vh] w-full max-w-5xl flex-col',
                          'overflow-hidden rounded-lg border bg-background shadow-lg',
                      )}
                  >
                      <div className="flex items-start justify-between border-b px-6 py-4">
                          <div>
                              <h2 id="cms-editor-title" className="text-lg font-semibold">
                                  {dialogTitle}
                              </h2>
                              <p className="mt-1 text-sm text-muted-foreground">
                                  Edit konten dengan editor lengkap. Klik Simpan untuk menerapkan perubahan.
                              </p>
                          </div>
                          <Button
                              type="button"
                              variant="ghost"
                              size="icon"
                              className="shrink-0"
                              onClick={() => setOpen(false)}
                          >
                              <X className="h-4 w-4" />
                              <span className="sr-only">Tutup</span>
                          </Button>
                      </div>

                      <div className="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                          <RichTextEditor value={draft} onChange={setDraft} minHeight={modalMinHeight} />
                      </div>

                      <div className="flex justify-end gap-2 border-t px-6 py-4">
                          <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                              Batal
                          </Button>
                          <Button type="button" onClick={handleSave}>
                              Simpan
                          </Button>
                      </div>
                  </div>
              </div>,
              document.body,
          )
        : null;

    return (
        <>
            <div className="space-y-2">
                <div className="min-h-16 rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                    {preview ? (
                        <p className="line-clamp-3">{preview}</p>
                    ) : (
                        <p className="italic">Belum ada konten.</p>
                    )}
                </div>
                <Button type="button" variant="outline" size="sm" className="w-full" onClick={() => setOpen(true)}>
                    <Pencil className="mr-2 h-4 w-4" />
                    {buttonLabel}
                </Button>
            </div>
            {modal}
        </>
    );
}
