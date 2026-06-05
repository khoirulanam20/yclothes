import { Check, Copy } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { guestToast } from '@/lib/guestToast';
import { formatRupiah } from '@/lib/utils';

type Props = {
    amount: number;
    label?: string;
    className?: string;
};

async function copyText(text: string): Promise<boolean> {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {
        // fallback below
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        return ok;
    } catch {
        return false;
    }
}

export function CopyAmount({ amount, label, className }: Props) {
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        const ok = await copyText(String(amount));
        if (!ok) {
            guestToast.error('Gagal menyalin nominal.');
            return;
        }
        setCopied(true);
        guestToast.success('Nominal disalin.');
        window.setTimeout(() => setCopied(false), 2000);
    };

    return (
        <span className={`inline-flex items-center gap-1.5 ${className ?? ''}`}>
            <span>{label ?? formatRupiah(amount)}</span>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="h-7 w-7 shrink-0"
                onClick={handleCopy}
                aria-label="Salin nominal"
                title="Salin"
            >
                {copied ? <Check className="h-4 w-4 text-green-600" /> : <Copy className="h-4 w-4" />}
            </Button>
        </span>
    );
}
