// Extract the supplied self-contained design for integration into CI4.
// The source HTML includes the font licences, which are retained alongside it.
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { gunzipSync } from 'node:zlib';
import { resolve } from 'node:path';

const source = process.argv[2];
if (!source) throw new Error('Usage: node scripts/unpack-fynefield-reference.mjs <reference.html>');
const input = readFileSync(source, 'utf8');
const blocks = {};
for (const match of input.matchAll(/<script type="(__bundler\/[^"]+)">\s*([\s\S]*?)\s*<\/script>/g)) {
  blocks[match[1]] = JSON.parse(match[2]);
}
const manifest = blocks['__bundler/manifest'];
const resources = blocks['__bundler/ext_resources'];
const template = blocks['__bundler/template'];
if (!manifest || !resources || typeof template !== 'string') throw new Error('Incomplete reference bundle');
const folder = resolve('public/assets/redesign');
mkdirSync(folder, { recursive: true });
// Preserve the original self-contained document. Its bootstrap runtime decodes
// and initializes the Design Component output; serving a stripped template
// alone does not initialize repeated/conditional elements.
writeFileSync(resolve(folder, 'fynefield-redesign.html'), input);
const extensions = { 'image/jpeg': 'jpg', 'image/png': 'png', 'font/woff2': 'woff2', 'text/javascript': 'js' };
const paths = {};
for (const [uuid, item] of Object.entries(manifest)) {
  const extension = extensions[item.mime];
  if (!extension) throw new Error(`Unsupported asset type: ${item.mime}`);
  let bytes = Buffer.from(item.data, 'base64');
  if (item.compressed) bytes = gunzipSync(bytes);
  const name = `${uuid}.${extension}`;
  writeFileSync(resolve(folder, name), bytes);
  paths[uuid] = `/assets/redesign/${name}`;
}
writeFileSync(resolve(folder, 'manifest.json'), JSON.stringify({ resources, paths }, null, 2) + '\n');
writeFileSync(resolve(folder, 'font-licences.txt'), input.slice(input.indexOf('<!--') + 4, input.indexOf('-->')).trim() + '\n');
// Keep an isolated preview for side-by-side comparison; it is not a live page.
let preview = template;
for (const [uuid, path] of Object.entries(paths)) preview = preview.split(uuid).join(path);
const resourcePaths = Object.fromEntries(resources.map(({ id, uuid }) => [id, paths[uuid]]));
preview = preview.replace('<head>', `<head><script>window.__resources = ${JSON.stringify(resourcePaths)};</script>`);
mkdirSync(resolve('build/redesign'), { recursive: true });
writeFileSync(resolve('build/redesign/reference-preview.html'), preview);
// The preview is also the production-ready reference implementation. Keeping a
// public copy lets the PHP application serve the supplied design verbatim while
// the Studio continues to manage editorial records and operational forms.
writeFileSync(resolve(folder, 'reference-preview.html'), preview);
console.log(`Extracted ${Object.keys(paths).length} reference assets to ${folder}`);
