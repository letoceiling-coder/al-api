/**
 * Скрипт для захвата страницы checkerboard с spb.trendagent.ru
 * Сохраняет: HAR, HTML, скриншот
 * Запуск: cd scripts && npm install && npm run capture
 * (из корня: cd c:\OSPanel\domains\AL\scripts && npm i && npm run capture)
 */

import { chromium } from 'playwright';
import { writeFileSync, mkdirSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUTPUT_DIR = join(__dirname, '../capture-output');
const URL = 'https://spb.trendagent.ru/object/dom-na-naberezhnoy-st/checkerboard';
const PHONE = '+79045393434';
const PASSWORD = 'nwBvh4q';

async function capture() {
  mkdirSync(OUTPUT_DIR, { recursive: true });
  const browser = await chromium.launch({ headless: false }); // headless: false — видимый браузер

  const context = await browser.newContext({
    recordHar: { path: join(OUTPUT_DIR, 'checkerboard.har'), mode: 'full' },
    viewport: { width: 1920, height: 1080 },
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    locale: 'ru-RU',
  });

  const page = await context.newPage();

  try {
    console.log('Открываю страницу (таймаут 90 сек)...');
    await page.goto(URL, { waitUntil: 'commit', timeout: 90000 });
    await page.waitForLoadState('domcontentloaded', { timeout: 60000 }).catch(() => {});
    await page.waitForTimeout(2000);

    // Проверяем, нужна ли авторизация (форма входа SSO)
    const phoneInput = page.locator('input[name="phone"], input[placeholder*="000 000"]');
    if (await phoneInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      console.log('Найдена форма входа, выполняю авторизацию...');
      await phoneInput.fill(PHONE);
      const passInput = page.locator('input[name="password"], input[type="password"]');
      await passInput.fill(PASSWORD);
      await page.locator('button[type="submit"], button[aria-label="login"]').first().click();
      await page.waitForURL(/checkerboard|object|spb\.trendagent/, { timeout: 30000 }).catch(() => {});
      await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
      await page.waitForTimeout(5000);
      // Если остались на логине — пробуем перейти на checkerboard
      const cur = page.url();
      if (!cur.includes('checkerboard')) {
        console.log('Переход на checkerboard...');
        await page.goto(URL, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
        await page.waitForTimeout(3000);
      }
    }

    // Дополнительно ждём загрузки контента checkerboard (SPA)
    await page.waitForSelector('.checkerboard, .chessboard, [class*="checkerboard"], [class*="chessboard"], #chessboard, table.chessboard, .object-page', { timeout: 20000 }).catch(() => {
      console.log('Чекерборд не найден — сохраняем текущую страницу');
    });
    await page.waitForTimeout(3000);

    // Сохраняем HTML
    const html = await page.evaluate(() => document.documentElement.outerHTML);
    writeFileSync(join(OUTPUT_DIR, 'checkerboard.html'), html, 'utf8');
    console.log('HTML сохранён: capture-output/checkerboard.html');

    // HTML только main/контент (если нужно)
    const mainHtml = await page.evaluate(() => {
      const main = document.querySelector('main, .content, #content, [class*="checkerboard"], [class*="chessboard"]');
      return main ? main.outerHTML : document.body.outerHTML;
    });
    writeFileSync(join(OUTPUT_DIR, 'checkerboard-main.html'), mainHtml, 'utf8');
    console.log('HTML (main) сохранён: capture-output/checkerboard-main.html');

    // Скриншот
    await page.screenshot({ path: join(OUTPUT_DIR, 'checkerboard.png'), fullPage: true });
    console.log('Скриншот сохранён: capture-output/checkerboard.png');

    await context.close();
  } catch (err) {
    console.error('Ошибка:', err.message);
    // Сохраняем что успели
    try {
      const html = await page.evaluate(() => document.documentElement.outerHTML);
      writeFileSync(join(OUTPUT_DIR, 'checkerboard-partial.html'), html, 'utf8');
      await page.screenshot({ path: join(OUTPUT_DIR, 'checkerboard-partial.png'), fullPage: true });
      console.log('Сохранены partial HTML и скриншот');
    } catch (_) {}
    await context.close();
  }

  await browser.close();
  console.log('Готово. Файлы в папке capture-output/');
}

capture();
