import { useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';

type WilayahOption = { id: string; name: string };

type WilayahValue = {
    provinceCode: string;
    provinceName: string;
    regencyCode: string;
    regencyName: string;
    districtCode: string;
    districtName: string;
    villageCode: string;
    villageName: string;
    postalCode: string;
};

type Props = {
    value: WilayahValue;
    onChange: (value: WilayahValue) => void;
    errors?: Partial<Record<keyof WilayahValue, string>>;
};

async function fetchWilayah(path: string): Promise<WilayahOption[]> {
    const res = await fetch(`/api/wilayah/${path}`);
    if (!res.ok) return [];
    return res.json();
}

export function WilayahSelect({ value, onChange, errors }: Props) {
    const [provinces, setProvinces] = useState<WilayahOption[]>([]);
    const [regencies, setRegencies] = useState<WilayahOption[]>([]);
    const [districts, setDistricts] = useState<WilayahOption[]>([]);
    const [villages, setVillages] = useState<WilayahOption[]>([]);

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

    useEffect(() => {
        if (!value.regencyCode) {
            setDistricts([]);
            return;
        }
        fetchWilayah(`districts/${value.regencyCode}`).then(setDistricts);
    }, [value.regencyCode]);

    useEffect(() => {
        if (!value.districtCode) {
            setVillages([]);
            return;
        }
        fetchWilayah(`villages/${value.districtCode}`).then(setVillages);
    }, [value.districtCode]);

    const update = (patch: Partial<WilayahValue>) => onChange({ ...value, ...patch });

    const selectClass = 'flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm';

    return (
        <div className="grid md:grid-cols-2 gap-3">
            <div>
                <Label className="text-xs">Provinsi</Label>
                <select
                    className={selectClass}
                    value={value.provinceCode}
                    onChange={(e) => {
                        const opt = provinces.find((p) => p.id === e.target.value);
                        update({
                            provinceCode: e.target.value,
                            provinceName: opt?.name ?? '',
                            regencyCode: '', regencyName: '',
                            districtCode: '', districtName: '',
                            villageCode: '', villageName: '',
                        });
                    }}
                    required
                >
                    <option value="">Pilih provinsi</option>
                    {provinces.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                </select>
                {errors?.provinceCode && <p className="text-xs text-destructive mt-1">{errors.provinceCode}</p>}
            </div>
            <div>
                <Label className="text-xs">Kabupaten / Kota</Label>
                <select
                    className={selectClass}
                    value={value.regencyCode}
                    disabled={!value.provinceCode}
                    onChange={(e) => {
                        const opt = regencies.find((r) => r.id === e.target.value);
                        update({
                            regencyCode: e.target.value,
                            regencyName: opt?.name ?? '',
                            districtCode: '', districtName: '',
                            villageCode: '', villageName: '',
                        });
                    }}
                    required
                >
                    <option value="">Pilih kab/kota</option>
                    {regencies.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                </select>
            </div>
            <div>
                <Label className="text-xs">Kecamatan</Label>
                <select
                    className={selectClass}
                    value={value.districtCode}
                    disabled={!value.regencyCode}
                    onChange={(e) => {
                        const opt = districts.find((d) => d.id === e.target.value);
                        update({
                            districtCode: e.target.value,
                            districtName: opt?.name ?? '',
                            villageCode: '', villageName: '',
                        });
                    }}
                    required
                >
                    <option value="">Pilih kecamatan</option>
                    {districts.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                </select>
            </div>
            <div>
                <Label className="text-xs">Kelurahan / Desa</Label>
                <select
                    className={selectClass}
                    value={value.villageCode}
                    disabled={!value.districtCode}
                    onChange={(e) => {
                        const opt = villages.find((v) => v.id === e.target.value);
                        update({ villageCode: e.target.value, villageName: opt?.name ?? '' });
                    }}
                >
                    <option value="">Pilih kelurahan (opsional)</option>
                    {villages.map((v) => <option key={v.id} value={v.id}>{v.name}</option>)}
                </select>
            </div>
            <div>
                <Label className="text-xs">Kode Pos</Label>
                <input
                    className={selectClass}
                    value={value.postalCode}
                    onChange={(e) => update({ postalCode: e.target.value })}
                    placeholder="56211"
                />
            </div>
        </div>
    );
}

export type { WilayahValue };
