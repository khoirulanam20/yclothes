export type CategoryOption = {
    id: number;
    name: string;
    slug?: string;
    depth: number;
};

export function indentCategoryLabel(name: string, depth: number): string {
    return `${'— '.repeat(depth)}${name}`;
}

type CategorySelectProps = {
    id?: string;
    value: number | string;
    options: CategoryOption[];
    onChange: (value: number) => void;
    required?: boolean;
    className?: string;
    placeholder?: string;
};

export function CategorySelect({
    id = 'category_id',
    value,
    options,
    onChange,
    required,
    className = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
    placeholder,
}: CategorySelectProps) {
    return (
        <select
            id={id}
            className={className}
            value={value}
            onChange={(e) => onChange(Number(e.target.value))}
            required={required}
        >
            {placeholder && <option value="">{placeholder}</option>}
            {options.map((option) => (
                <option key={option.id} value={option.id}>
                    {indentCategoryLabel(option.name, option.depth)}
                </option>
            ))}
        </select>
    );
}

type CategoryCheckboxListProps = {
    options: CategoryOption[];
    selectedIds: number[];
    onToggle: (id: number) => void;
};

export function CategoryCheckboxList({ options, selectedIds, onToggle }: CategoryCheckboxListProps) {
    return (
        <div className="flex flex-wrap gap-2 max-h-40 overflow-y-auto border rounded-md p-3">
            {options.map((option) => (
                <label
                    key={option.id}
                    className="flex items-center gap-2 text-sm w-full sm:w-auto"
                    style={{ paddingLeft: `${option.depth * 0.75}rem` }}
                >
                    <input
                        type="checkbox"
                        checked={selectedIds.includes(option.id)}
                        onChange={() => onToggle(option.id)}
                    />
                    {option.name}
                </label>
            ))}
        </div>
    );
}
