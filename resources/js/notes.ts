import { Editor } from '@tiptap/core';
import Highlight from '@tiptap/extension-highlight';
import StarterKit from '@tiptap/starter-kit';

const editors = new Map<HTMLElement, Editor>();

const initializeEditors = (root: ParentNode = document) => {
    root.querySelectorAll<HTMLElement>('[data-note-editor]').forEach((element) => {
        if (editors.has(element)) {
            return;
        }

        const textarea =
            element.querySelector<HTMLTextAreaElement>('[data-note-input]');
        const surface = element.querySelector<HTMLElement>(
            '[data-note-surface]',
        );

        if (!textarea || !surface) {
            return;
        }

        const editor = new Editor({
            element: surface,
            content: textarea.value,
            extensions: [
                StarterKit.configure({
                    link: {
                        autolink: true,
                        openOnClick: false,
                        HTMLAttributes: {
                            target: '_blank',
                            rel: 'noopener noreferrer',
                        },
                    },
                }),
                Highlight,
            ],
            editorProps: {
                attributes: {
                    class: 'note-editor-surface',
                },
            },
            onUpdate: ({ editor: updatedEditor }) => {
                textarea.value = updatedEditor.getHTML();
            },
        });

        editors.set(element, editor);

        element
            .querySelectorAll<HTMLButtonElement>('[data-note-command]')
            .forEach((button) => {
                button.addEventListener('click', () => {
                    const chain = editor.chain().focus();

                    switch (button.dataset.noteCommand) {
                        case 'bold':
                            chain.toggleBold().run();
                            break;
                        case 'italic':
                            chain.toggleItalic().run();
                            break;
                        case 'underline':
                            chain.toggleUnderline().run();
                            break;
                        case 'highlight':
                            chain.toggleHighlight().run();
                            break;
                        case 'heading':
                            chain.toggleHeading({ level: 3 }).run();
                            break;
                        case 'bulletList':
                            chain.toggleBulletList().run();
                            break;
                        case 'orderedList':
                            chain.toggleOrderedList().run();
                            break;
                        case 'blockquote':
                            chain.toggleBlockquote().run();
                            break;
                        case 'codeBlock':
                            chain.toggleCodeBlock().run();
                            break;
                        case 'link': {
                            const previousUrl = editor.getAttributes('link')
                                .href as string | undefined;
                            const url = window.prompt(
                                'Link URL',
                                previousUrl ?? 'https://',
                            );

                            if (url === null) {
                                break;
                            }

                            if (url === '') {
                                chain.unsetLink().run();
                            } else {
                                chain
                                    .extendMarkRange('link')
                                    .setLink({ href: url })
                                    .run();
                            }

                            break;
                        }
                        case 'undo':
                            chain.undo().run();
                            break;
                        case 'redo':
                            chain.redo().run();
                            break;
                    }
                });
            });

        textarea.form?.addEventListener('submit', () => {
            textarea.value = editor.getHTML();
        });
    });
};

initializeEditors();

document.addEventListener('todo:modal-will-refresh', (event) => {
    const modal = (event as CustomEvent<HTMLElement>).detail;

    modal.querySelectorAll<HTMLElement>('[data-note-editor]').forEach((element) => {
        editors.get(element)?.destroy();
        editors.delete(element);
    });
});

document.addEventListener('todo:modal-refreshed', (event) => {
    initializeEditors((event as CustomEvent<HTMLElement>).detail);
});
