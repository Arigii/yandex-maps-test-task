// Playwright-скрипт для PlaywrightFetcher (см. app/Services/YandexMaps/Fetchers/PlaywrightFetcher.php).
//
// Ключевая идея: не пытаемся сами посчитать подпись запроса "s" у внутреннего
// эндпоинта yandex.ru/maps/api/business/fetchReviews (она реально проверяется
// сервером — проверено эмпирически, см. README) — вместо этого запускаем
// настоящий браузер, даём ЕГО собственному JS выполнить и подписать запросы
// самому (как это делает обычный пользователь при скролле ленты отзывов), а
// сами просто ПОДСЛУШИВАЕМ сетевые ответы этих запросов через page.on('response').
//
// Запуск: node yandex-scrape.mjs --mode=org|reviews --id=<oid> [--offset=N --limit=N]
// Вывод: один JSON в stdout (см. форматы ниже), совместимый с тем, что ждёт
// OrganizationFetcherInterface на стороне PHP.

import { chromium } from 'playwright';

function parseArgs() {
    const out = {};
    for (const arg of process.argv.slice(2)) {
        const m = arg.match(/^--([^=]+)=(.*)$/);
        if (m) out[m[1]] = m[2];
    }
    return out;
}

const args = parseArgs();
const mode = args.mode;
const orgId = args.id;
const offset = parseInt(args.offset ?? '0', 10);
const limit = parseInt(args.limit ?? '50', 10);

if (!mode || !orgId) {
    console.error('Usage: --mode=org|reviews --id=<oid> [--offset=N --limit=N]');
    process.exit(1);
}

const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

try {
    const context = await browser.newContext({
        locale: 'ru-RU',
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
    });
    const page = await context.newPage();

    // Копим все реальные ответы fetchReviews, которые браузер сам подписал и отправил.
    const capturedBatches = [];
    page.on('response', async (response) => {
        if (!response.url().includes('/maps/api/business/fetchReviews')) return;
        try {
            const json = await response.json();
            if (json && Array.isArray(json.reviews)) capturedBatches.push(json);
        } catch {
            // не JSON / уже закрыт — игнорируем, не фатально
        }
    });

    await page.goto(`https://yandex.ru/maps/org/${orgId}/reviews/`, {
        waitUntil: 'networkidle',
        timeout: 30000,
    });

    // Агрегаты организации лежат в том же встроенном состоянии, что мы уже
    // разобрали статически в HttpJsonFetcher — тут просто читаем его из DOM.
    const orgInfo = await page.evaluate(() => {
        const el = document.querySelector('script.state-view[type="application/json"]');
        if (!el) return null;
        try {
            const state = JSON.parse(el.textContent);
            const item = state?.stack?.[0]?.results?.items?.[0];
            if (!item) return null;
            return {
                name: item.title ?? null,
                address: item.address ?? null,
                rating: item.ratingData?.ratingValue ?? null,
                ratingsCount: item.ratingData?.ratingCount ?? null,
                reviewsCount: item.ratingData?.reviewCount ?? null,
            };
        } catch {
            return null;
        }
    });

    if (mode === 'org') {
        process.stdout.write(JSON.stringify(orgInfo ?? {}));
        await browser.close();
        process.exit(0);
    }

    // mode === 'reviews': скроллим ленту, провоцируя подгрузку следующих
    // порций — сама страница шлёт fetchReviews с корректной подписью, нам
    // остаётся только ждать и собирать то, что пролетело мимо через сеть.
    const targetCount = offset + limit;
    let attempts = 0;
    const maxAttempts = 60; // защита от бесконечного скролла, если лента закончилась раньше

    const countCaptured = () => {
        const ids = new Set();
        for (const b of capturedBatches) for (const r of b.reviews) ids.add(r.reviewId);
        return ids.size;
    };

    while (countCaptured() < targetCount && attempts < maxAttempts) {
        await page.mouse.wheel(0, 2400);
        await page.waitForTimeout(650);
        attempts++;
    }

    const merged = new Map();
    for (const batch of capturedBatches) {
        for (const r of batch.reviews) {
            merged.set(r.reviewId, {
                id: r.reviewId,
                author: r.author?.name ?? null,
                avatarUrl: r.author?.avatarUrl ? String(r.author.avatarUrl).replace('{size}', 'islands-68') : null,
                rating: r.rating ?? null,
                text: r.text ?? null,
                publishedAt: r.updatedTime ?? null,
            });
        }
    }

    const all = Array.from(merged.values());
    const slice = all.slice(offset, offset + limit);
    const total = orgInfo?.reviewsCount ?? all.length;

    process.stdout.write(JSON.stringify({
        items: slice,
        total,
        hasMore: (offset + limit) < all.length,
    }));

    await browser.close();
} catch (err) {
    console.error(String(err?.stack ?? err));
    try { await browser.close(); } catch {}
    process.exit(1);
}
