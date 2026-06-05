import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const rootDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const source = path.join(rootDir, 'node_modules', 'tinymce');
const target = path.join(rootDir, 'public', 'tinymce');

if (!fs.existsSync(source)) {
    console.warn('TinyMCE package not found, skipping copy.');
    process.exit(0);
}

if (fs.existsSync(target)) {
    fs.rmSync(target, { recursive: true, force: true });
}

fs.cpSync(source, target, { recursive: true });

console.log('TinyMCE copied to public/tinymce');
