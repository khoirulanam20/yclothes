import { useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { FetchError, fetchJson } from '@/lib/fetchJson';

type WilayahOption = { id: string; name: string };

type Value = {
    provinceCode: string;
    provinceName: string;
    regencyCode: string;
    regencyName: string;
};

type Props = {
    value: Value;
    onChange: (value: Value) => void;
    errors?: Partial<Record<keyof Value, string>>;
    /** Mode filter: pilihan kosong = semua, tanpa atribut required */
    optional?: boolean;
};

async function fetchWilayah(path: string): Promise<WilayahOption[]> {
    try {
        return await fetchJson<WilayahOption[]>(`/api/wilayah/${path}`);
    } catch (error) {
        if (error instanceof FetchError) {
            return [];
        }
        throw error;
    }
}

export function ShippingRegencySelect({ value, onChange, errors, optional = false }: Props) {
    const [provinces, setProvinces] = useState<WilayahOption[]>([]);
    const [regencies, setRegencies] = useState<WilayahOption[]>([]);

    useEffect(() => {
        fetchWilayah('provinces').then(setProvinces);
    }, []);

    useEffect(() => {
        if (!value.provinceCode) {
            setRegencies([]);
            return;
        }
        fetchWilayah(`regencies/${value.provinceCode}`).then(setRegencies);
    }, [value.provinceCode]);

    const selectClass = 'flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm';

    return (
        <div className="grid md:grid-cols-2 gap-3">
            <div>
                <Label>Provinsi</Label>
                <select
                    className={selectClass}
                    value={value.provinceCode}
                    onChange={(e) => {
                        const opt = provinces.find((p) => p.id === e.target.value);
                        onChange({
                            provinceCode: e.target.value,
                            provinceName: opt?.name ?? '',
                            regencyCode: '',
                            regencyName: '',
                        });
                    }}
                    required={!optional}
                >
                    <option value="">{optional ? 'Semua provinsi' : 'Pilih provinsi'}</option>
                    {provinces.map((p) => (
                        <option key={p.id} value={p.id}>{p.name}</option>
                    ))}
                </select>
                {errors?.provinceCode && <p className="text-xs text-destructive mt-1">{errors.provinceCode}</p>}
            </div>
            <div>
                <Label>Kabupaten / Kota</Label>
                <select
                    className={selectClass}
                    value={value.regencyCode}
                    disabled={!value.provinceCode}
                    onChange={(e) => {
                        const opt = regencies.find((r) => r.id === e.target.value);
                        onChange({
                            ...value,
                            regencyCode: e.target.value,
                            regencyName: opt?.name ?? '',
                        });
                    }}
                    required={!optional}
                >
                    <option value="">{optional ? 'Semua kab/kota' : 'Pilih kab/kota'}</option>
                    {regencies.map((r) => (
                        <option key={r.id} value={r.id}>{r.name}</option>
                    ))}
                </select>
                {errors?.regencyCode && <p className="text-xs text-destructive mt-1">{errors.regencyCode}</p>}
            </div>
            {value.regencyCode && (
                <div className="md:col-span-2">
                    <Label>Kode Wilayah (Kemendagri)</Label>
                    <input className={selectClass + ' bg-muted'} value={value.regencyCode} readOnly />
                </div>
            )}
        </div>
    );
}

export type { Value as ShippingRegencyValue };
