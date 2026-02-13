/**
 * Извлекает HTML и список URL запросов из HAR
 * node extract-from-har.js
 */
import { readFileSync, writeFileSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const harPath = join(__dirname, '../capture-output/checkerboard.har');
const outDir = join(__dirname, '../capture-output');

const har = JSON.parse(readFileSync(harPath, 'utf8'));
const entries = har.log?.entries || [];
const requests = [];

for (const e of entries) {
  const url = e.request?.url || '';
  const method = e.request?.method || 'GET';
  const status = e.response?.status;
  const mime = e.response?.content?.mimeType || '';
  requests.push({ method, status, mime, url });

  // Извлекаем первый HTML-ответ
  if (mime.includes('text/html') && e.response?.content?.text) {
    const htmlPath = join(outDir, 'initial-page.html');
    writeFileSync(htmlPath, e.response.content.text, 'utf8');
    console.log('Сохранён initial-page.html (скелет SPA)');
  }

  // styles.css
  if (url.endsWith('/styles.css') && e.response?.content?.text) {
    writeFileSync(join(outDir, 'styles-from-har.css'), e.response.content.text, 'utf8');
    console.log('Сохранён styles-from-har.css');
  }
}

// Список запросов
const list = requests.map((r) => `${r.method} ${r.status} ${r.mime?.slice(0, 30) || ''} ${r.url}`).join('\n');
writeFileSync(join(outDir, 'requests-list.txt'), list, 'utf8');
console.log('Сохранён requests-list.txt');
console.log(`Всего запросов: ${entries.length}`);
