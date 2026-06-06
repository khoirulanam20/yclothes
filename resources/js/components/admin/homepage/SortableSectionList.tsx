import {
    DndContext,
    DragEndEvent,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

export type LayoutSection = {
    id: string;
    type: string;
    enabled: boolean;
    props: Record<string, unknown>;
};

function SortableSectionRow({
    section,
    label,
    selected,
    onSelect,
    onToggleEnabled,
    onRemove,
}: {
    section: LayoutSection;
    label: string;
    selected: boolean;
    onSelect: () => void;
    onToggleEnabled: (enabled: boolean) => void;
    onRemove: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: section.id });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={`flex items-center gap-2 border rounded-md p-2 cursor-pointer ${selected ? 'border-primary bg-primary/5' : ''} ${isDragging ? 'opacity-70 shadow-md' : ''}`}
            onClick={onSelect}
        >
            <button
                type="button"
                className="touch-none text-muted-foreground hover:text-foreground shrink-0"
                {...attributes}
                {...listeners}
                onClick={(e) => e.stopPropagation()}
            >
                <GripVertical className="h-4 w-4" />
            </button>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium truncate">{label}</p>
                <label className="flex items-center gap-1 text-xs" onClick={(e) => e.stopPropagation()}>
                    <input
                        type="checkbox"
                        checked={section.enabled}
                        onChange={(e) => onToggleEnabled(e.target.checked)}
                    />
                    Aktif
                </label>
            </div>
            <Button type="button" variant="ghost" size="icon" onClick={(e) => { e.stopPropagation(); onRemove(); }}>
                <Trash2 className="h-4 w-4" />
            </Button>
        </div>
    );
}

export function SortableSectionList({
    sections,
    selectedId,
    typeLabel,
    onSelect,
    onReorder,
    onToggleEnabled,
    onRemove,
}: {
    sections: LayoutSection[];
    selectedId: string | null;
    typeLabel: (type: string) => string;
    onSelect: (id: string) => void;
    onReorder: (sections: LayoutSection[]) => void;
    onToggleEnabled: (id: string, enabled: boolean) => void;
    onRemove: (id: string) => void;
}) {
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over || active.id === over.id) return;

        const oldIndex = sections.findIndex((s) => s.id === active.id);
        const newIndex = sections.findIndex((s) => s.id === over.id);
        if (oldIndex === -1 || newIndex === -1) return;

        onReorder(arrayMove(sections, oldIndex, newIndex));
    };

    return (
        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
            <SortableContext items={sections.map((s) => s.id)} strategy={verticalListSortingStrategy}>
                <div className="space-y-2">
                    {sections.map((section) => (
                        <SortableSectionRow
                            key={section.id}
                            section={section}
                            label={typeLabel(section.type)}
                            selected={selectedId === section.id}
                            onSelect={() => onSelect(section.id)}
                            onToggleEnabled={(enabled) => onToggleEnabled(section.id, enabled)}
                            onRemove={() => onRemove(section.id)}
                        />
                    ))}
                    {sections.length === 0 && (
                        <p className="text-sm text-muted-foreground">Belum ada section.</p>
                    )}
                </div>
            </SortableContext>
        </DndContext>
    );
}
