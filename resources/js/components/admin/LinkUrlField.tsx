import { useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
    fetchLinkTemplateGroups,
    findTemplateIdForUrl,
    type LinkTemplateGroup,
} from '@/lib/link-templates';

type Props = {
    id?: string;
    label?: string;
    value: string;
    onChange: (value: string) => void;
    readOnly?: boolean;
    required?: boolean;
    placeholder?: string;
};

export function LinkUrlField({
    id,
    label = 'Link',
    value,
    onChange,
    readOnly = false,
    required = false,
    placeholder = '/products',
}: Props) {
    const [groups, setGroups] = useState<LinkTemplateGroup[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedTemplate, setSelectedTemplate] = useState('');

    useEffect(() => {
        let active = true;

        fetchLinkTemplateGroups()
            .then((loadedGroups) => {
                if (!active) {
                    return;
                }

                setGroups(loadedGroups);
                setSelectedTemplate(findTemplateIdForUrl(loadedGroups, value));
                setError(null);
            })
            .catch(() => {
                if (active) {
                    setGroups([]);
                    setError('Gagal memuat template. Anda masih bisa mengisi link secara manual.');
                }
            })
            .finally(() => {
                if (active) {
                    setLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, []);

    useEffect(() => {
        if (groups.length === 0) {
            return;
        }

        setSelectedTemplate(findTemplateIdForUrl(groups, value));
    }, [groups, value]);

    const handleTemplateChange = (templateId: string) => {
        setSelectedTemplate(templateId);

        if (!templateId) {
            return;
        }

        for (const group of groups) {
            const option = group.options.find((item) => item.id === templateId);
            if (option) {
                onChange(option.url);
                return;
            }
        }
    };

    const handleUrlChange = (nextValue: string) => {
        onChange(nextValue);
        setSelectedTemplate(findTemplateIdForUrl(groups, nextValue));
    };

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>

            {!readOnly && (
                <div>
                    <select
                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        value={selectedTemplate}
                        onChange={(e) => handleTemplateChange(e.target.value)}
                        disabled={loading || (groups.length === 0 && !error)}
                    >
                        <option value="">
                            {loading ? 'Memuat template...' : 'Pilih template link...'}
                        </option>
                        {groups.map((group) => (
                            <optgroup key={group.label} label={group.label}>
                                {group.options.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.label}
                                    </option>
                                ))}
                            </optgroup>
                        ))}
                    </select>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Pilih template untuk mengisi link otomatis, atau ketik manual di bawah.
                    </p>
                    {error && (
                        <p className="mt-1 text-xs text-destructive">{error}</p>
                    )}
                </div>
            )}

            <Input
                id={id}
                value={value}
                onChange={(e) => handleUrlChange(e.target.value)}
                placeholder={placeholder}
                readOnly={readOnly}
                required={required}
            />
        </div>
    );
}
