import { Editor } from '@tinymce/tinymce-react';
import { useId, useRef } from 'react';
import '../../../css/tinymce-editor.css';
import { uploadEditorImage } from '@/lib/uploadEditorImage';

type Props = {
    value: string;
    onChange: (html: string) => void;
    placeholder?: string;
    minHeight?: number;
};

const PLUGINS = [
    'lists',
    'advlist',
    'link',
    'image',
    'table',
    'code',
    'autolink',
    'directionality',
    'visualblocks',
    'charmap',
    'preview',
    'searchreplace',
    'wordcount',
    'quickbars',
];

const TOOLBAR =
    'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | ' +
    'image | alignleft aligncenter alignright alignjustify | link | ' +
    'numlist bullist outdent indent | removeformat | code | table';

export function RichTextEditor({
    value,
    onChange,
    placeholder = 'Tulis konten...',
    minHeight = 160,
}: Props) {
    const editorId = useId().replace(/:/g, '');
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    return (
        <div className="tinymce-editor">
            <Editor
                id={editorId}
                tinymceScriptSrc="/tinymce/tinymce.min.js"
                licenseKey="gpl"
                value={value}
                onEditorChange={(content) => onChangeRef.current(content)}
                init={{
                    base_url: '/tinymce',
                    suffix: '.min',
                    height: minHeight,
                    menubar: false,
                    placeholder,
                    plugins: PLUGINS,
                    toolbar: TOOLBAR,
                    toolbar_mode: 'wrap',
                    branding: true,
                    promotion: false,
                    statusbar: true,
                    resize: true,
                    content_style:
                        'body { font-family: "DM Sans", system-ui, sans-serif; font-size: 14px; line-height: 1.6; margin: 8px; }',
                    images_upload_handler: async (blobInfo) => {
                        const file = new File([blobInfo.blob()], blobInfo.filename(), {
                            type: blobInfo.blob().type,
                        });
                        const result = await uploadEditorImage(file);

                        return result.url;
                    },
                    automatic_uploads: true,
                    file_picker_types: 'image',
                    link_default_target: '_blank',
                    link_assume_external_targets: true,
                    setup: (editor) => {
                        editor.on('init', () => {
                            window.setTimeout(() => {
                                editor.dispatch('ResizeEditor');
                            }, 50);
                        });
                    },
                }}
            />
        </div>
    );
}
