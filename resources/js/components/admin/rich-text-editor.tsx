import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import { Bold, Italic, List, ListOrdered, Heading2, Heading3, Undo, Redo } from 'lucide-react';
import { cn } from '@/lib/utils';

interface RichTextEditorProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
}

export function RichTextEditor({ value, onChange, placeholder = 'Write your content here...', className }: RichTextEditorProps) {
    const editor = useEditor({
        extensions: [
            StarterKit,
            Placeholder.configure({ placeholder }),
        ],
        content: value,
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML());
        },
        editorProps: {
            attributes: {
                class: 'prose prose-sm max-w-none min-h-48 px-4 py-3 focus:outline-none text-foreground',
            },
        },
    });

    if (!editor) {
        return null;
    }

    const toolbarButton = (action: () => void, isActive: boolean, icon: React.ReactNode, label: string) => (
        <button
            type="button"
            onClick={action}
            title={label}
            className={cn(
                'flex size-8 items-center justify-center rounded-md text-sm transition-colors hover:bg-accent',
                isActive ? 'bg-accent text-accent-foreground' : 'text-muted-foreground',
            )}
        >
            {icon}
        </button>
    );

    return (
        <div className={cn('rounded-md border border-input bg-background', className)}>
            <div className="flex flex-wrap items-center gap-1 border-b border-input px-2 py-1.5">
                {toolbarButton(
                    () => editor.chain().focus().toggleBold().run(),
                    editor.isActive('bold'),
                    <Bold className="size-4" />,
                    'Bold',
                )}
                {toolbarButton(
                    () => editor.chain().focus().toggleItalic().run(),
                    editor.isActive('italic'),
                    <Italic className="size-4" />,
                    'Italic',
                )}
                <div className="mx-1 h-5 w-px bg-border" />
                {toolbarButton(
                    () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
                    editor.isActive('heading', { level: 2 }),
                    <Heading2 className="size-4" />,
                    'Heading 2',
                )}
                {toolbarButton(
                    () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
                    editor.isActive('heading', { level: 3 }),
                    <Heading3 className="size-4" />,
                    'Heading 3',
                )}
                <div className="mx-1 h-5 w-px bg-border" />
                {toolbarButton(
                    () => editor.chain().focus().toggleBulletList().run(),
                    editor.isActive('bulletList'),
                    <List className="size-4" />,
                    'Bullet List',
                )}
                {toolbarButton(
                    () => editor.chain().focus().toggleOrderedList().run(),
                    editor.isActive('orderedList'),
                    <ListOrdered className="size-4" />,
                    'Ordered List',
                )}
                <div className="mx-1 h-5 w-px bg-border" />
                {toolbarButton(
                    () => editor.chain().focus().undo().run(),
                    false,
                    <Undo className="size-4" />,
                    'Undo',
                )}
                {toolbarButton(
                    () => editor.chain().focus().redo().run(),
                    false,
                    <Redo className="size-4" />,
                    'Redo',
                )}
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}
