<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class NoteHtmlSanitizer
{
    public function sanitize(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache/data'));
        $config->set('HTML.DefinitionID', 'job-note-html');
        $config->set('HTML.DefinitionRev', 1);
        $config->set('HTML.Allowed', implode(',', [
            'p',
            'br',
            'h2',
            'h3',
            'h4',
            'strong',
            'b',
            'em',
            'i',
            'u',
            's',
            'strike',
            'mark',
            'ul',
            'ol',
            'li',
            'blockquote',
            'pre',
            'code',
            'a[href|title|target|rel]',
        ]));
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        $definition = $config->maybeGetRawHTMLDefinition();
        $definition?->addElement('mark', 'Inline', 'Inline', 'Common');

        return trim((new HTMLPurifier($config))->purify($html));
    }
}
