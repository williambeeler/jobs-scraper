import { readFile, readdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import { BladeFormatter } from 'blade-formatter';

const projectRoot = process.cwd();
const checkOnly = process.argv.includes('--check');
const config = JSON.parse(
    await readFile(path.join(projectRoot, '.bladeformatterrc.json'), 'utf8'),
);
const formatter = new BladeFormatter(config);
const roots = [
    path.join(projectRoot, 'resources', 'views'),
    path.join(projectRoot, 'packages', 'crm', 'resources', 'views'),
];

async function bladeFiles(directory) {
    const entries = await readdir(directory, { withFileTypes: true });
    const files = await Promise.all(
        entries.map((entry) => {
            const target = path.join(directory, entry.name);

            return entry.isDirectory()
                ? bladeFiles(target)
                : entry.name.endsWith('.blade.php')
                  ? [target]
                  : [];
        }),
    );

    return files.flat();
}

function applyProjectStyle(content) {
    const lines = content.replaceAll('\r\n', '\n').split('\n');
    const attachedBrackets = [];

    for (const line of lines) {
        const trimmed = line.trim();

        if ((trimmed === '>' || trimmed === '/>') && attachedBrackets.length > 0) {
            attachedBrackets[attachedBrackets.length - 1] =
                attachedBrackets[attachedBrackets.length - 1].trimEnd() + trimmed;
            continue;
        }

        attachedBrackets.push(line);
    }

    const compacted = attachedBrackets.filter((line, index, allLines) => {
        if (line !== '') {
            return true;
        }

        const previous = allLines[index - 1] ?? '';
        const next = allLines[index + 1] ?? '';

        return !/^@section\b/.test(previous) && next !== '@endsection';
    });
    const spacedDirectives = [];

    for (const line of compacted) {
        if (
            line.length > 0 &&
            !line.startsWith(' ') &&
            /^@(extends|section)\b/.test(line) &&
            spacedDirectives.length > 0 &&
            spacedDirectives.at(-1) !== ''
        ) {
            spacedDirectives.push('');
        }

        spacedDirectives.push(line);
    }

    return spacedDirectives.join('\n').replace(/\n{3,}/g, '\n\n').trimEnd() + '\n';
}

const files = [...new Set((await Promise.all(roots.map(bladeFiles))).flat())].sort();
const changed = [];

for (const file of files) {
    const original = await readFile(file, 'utf8');
    const formatted = applyProjectStyle(await formatter.format(original, config));

    if (formatted === original) {
        continue;
    }

    changed.push(path.relative(projectRoot, file));

    if (!checkOnly) {
        await writeFile(file, formatted);
    }
}

if (checkOnly && changed.length > 0) {
    console.error('Blade formatting is required for:');
    changed.forEach((file) => console.error(`- ${file}`));
    process.exitCode = 1;
} else if (checkOnly) {
    console.log(`All ${files.length} Blade files are formatted.`);
} else {
    console.log(`Formatted ${changed.length} of ${files.length} Blade files.`);
}
