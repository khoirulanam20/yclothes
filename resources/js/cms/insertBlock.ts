import type { PuckAction } from '@measured/puck';
import type { CmsBlockDefinition } from '@/cms/registry';
import type { PuckData } from '@/cms/puckConfig';

function createBlockId(type: string): string {
    return `${type}-${Math.random().toString(36).slice(2, 9)}`;
}

export function insertBlockAtEnd(
    dispatch: (action: PuckAction) => void,
    currentData: PuckData,
    block: CmsBlockDefinition,
): PuckData {
    const newItem = {
        type: block.type,
        props: {
            ...block.defaultProps,
            id: createBlockId(block.type),
        },
    };

    const newData: PuckData = {
        ...currentData,
        content: [...(currentData.content ?? []), newItem],
    };

    dispatch({
        type: 'setData',
        data: newData,
    });

    return newData;
}
