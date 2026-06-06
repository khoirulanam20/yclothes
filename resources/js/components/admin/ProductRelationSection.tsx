import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ProductSearchPicker,
    type SelectedProduct,
} from '@/components/admin/ProductSearchPicker';

type Props = {
    title: string;
    description: string;
    selected: SelectedProduct[];
    onChange: (products: SelectedProduct[]) => void;
    excludeProductId?: number;
};

export function ProductRelationSection({
    title,
    description,
    selected,
    onChange,
    excludeProductId,
}: Props) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0 pb-3">
                <div>
                    <CardTitle className="text-base">{title}</CardTitle>
                    <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                </div>
            </CardHeader>
            <CardContent>
                <ProductSearchPicker
                    selected={selected}
                    onChange={onChange}
                    excludeProductId={excludeProductId}
                />
            </CardContent>
        </Card>
    );
}
