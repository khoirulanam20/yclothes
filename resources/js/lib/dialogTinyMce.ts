const TINYMCE_FLOATING_SELECTOR =
    '.tox-tinymce-aux, .tox-dialog, .tox-dialog-wrap, .tox-menu, .tox-collection, .tox-pop, .tox-toolbar__overflow, .tox-swatches, .tox-selector';

export function isTinyMceFloatingElement(target: EventTarget | null): boolean {
    if (!(target instanceof Element)) {
        return false;
    }

    return !!target.closest(TINYMCE_FLOATING_SELECTOR);
}

function preventIfTinyMce(event: { preventDefault: () => void; target: EventTarget | null }): void {
    if (isTinyMceFloatingElement(event.target)) {
        event.preventDefault();
    }
}

/** Prevent Radix Dialog from stealing focus / closing when interacting with TinyMCE UI portaled to body. */
export const dialogTinyMceHandlers = {
    onInteractOutside: preventIfTinyMce,
    onFocusOutside: preventIfTinyMce,
    onPointerDownOutside: preventIfTinyMce,
};
