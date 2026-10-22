const fs = require('node:fs');
const assert = require('node:assert/strict');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const source = fs.readFileSync('public/js/ats-app-install.js', 'utf8');
const html = '<button data-ats-install>Install ATS app</button><p data-ats-install-status role="status"></p>';

(async () => {
    const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'ats-app-browser-'));
    const browser = await chromium.launchPersistentContext(profile, {headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined});
    let server;
    try {
        if (process.argv[3]) {
            const fixture = JSON.parse(fs.readFileSync(process.argv[3], 'utf8'));
            server = http.createServer((request, response) => {
                const origin = 'http://' + request.headers.host;
                const local = value => value.replaceAll('https://ats.example.test', origin);
                if (request.url.endsWith('/manifest.webmanifest')) {
                    response.writeHead(200, {'Content-Type': 'application/manifest+json'});
                    response.end(local(JSON.stringify(fixture.manifest)));
                } else if (/^\/pwa\/icon-(192|512)\.png$/.test(request.url)) {
                    response.writeHead(200, {'Content-Type': 'image/png'});
                    response.end(fs.readFileSync('public' + request.url));
                } else if (request.url === '/js/ats-app-install.js') {
                    response.writeHead(200, {'Content-Type': 'application/javascript'});
                    response.end(source);
                } else {
                    response.writeHead(200, {'Content-Type': 'text/html'});
                    response.end('<!doctype html><head>' + local(fixture.head) + '</head><body>' + html + '</body>');
                }
            });
            await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
            const installedPage = await browser.newPage();
            await installedPage.goto('http://127.0.0.1:' + server.address().port);
            const client = await installedPage.context().newCDPSession(installedPage);
            const manifest = await client.send('Page.getAppManifest');
            assert.deepEqual(manifest.errors, [], 'Chrome parses the real manifest and linked metadata');
            assert.equal(JSON.parse(manifest.data).name, fixture.manifest.name, 'Chrome sees the client-specific app name');
            const installability = await client.send('Page.getInstallabilityErrors');
            assert.deepEqual(installability.installabilityErrors, [], 'Chrome accepts the manifest, start URL and actual app icons: ' + JSON.stringify(installability.installabilityErrors));
            await installedPage.close();
        }
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent(html);
        await page.addScriptTag({content: source});
        const button = page.locator('[data-ats-install]');
        const status = page.locator('[data-ats-install-status]');
        await button.click();
        assert.match(await status.textContent(), /install icon|Install page as app|Install this site as an app/, 'Fallback gives browser installation instructions');
        await page.evaluate(() => {
            window.promptCount = 0;
            const event = new Event('beforeinstallprompt', {cancelable: true});
            event.prompt = async () => { window.promptCount++; };
            event.userChoice = Promise.resolve({outcome: 'accepted'});
            window.dispatchEvent(event);
            window.installEventPrevented = event.defaultPrevented;
        });
        await button.click();
        await page.waitForFunction(() => document.querySelector('[data-ats-install-status]').textContent.includes('Finish the browser'));
        assert.equal(await page.evaluate(() => window.promptCount), 1, 'User click invokes native installation once');
        assert.equal(await page.evaluate(() => window.installEventPrevented), true, 'Native prompt is deferred until the user selects Install');
        await button.click();
        assert.equal(await page.evaluate(() => window.promptCount), 1, 'A consumed prompt is not reused');
        await page.evaluate(() => {
            const event = new Event('beforeinstallprompt');
            event.prompt = async () => {};
            event.userChoice = Promise.resolve({outcome: 'dismissed'});
            window.dispatchEvent(event);
        });
        await button.click();
        await page.waitForFunction(() => document.querySelector('[data-ats-install-status]').textContent.includes('later'));
        assert.equal(await button.isEnabled(), true, 'Dismissing an installation leaves the button usable');
        await page.evaluate(() => {
            const event = new Event('beforeinstallprompt');
            event.prompt = async () => { throw new Error('Prompt unavailable'); };
            window.dispatchEvent(event);
        });
        await button.click();
        await page.waitForFunction(() => !document.querySelector('[data-ats-install]').disabled);
        assert.match(await status.textContent(), /install icon|Install page as app|Install this site as an app/, 'Unavailable prompt falls back cleanly');
        await page.evaluate(() => window.dispatchEvent(new Event('appinstalled')));
        assert.equal(await button.isVisible(), false, 'Successful installation hides the install button');
        assert.match(await status.textContent(), /ATS app installed/);
        const standalone = await browser.newPage();
        await standalone.setContent(html);
        await standalone.evaluate(() => {
            const original = window.matchMedia;
            window.matchMedia = query => query === '(display-mode: standalone)'
                ? {matches: true, addEventListener() {}} : original.call(window, query);
        });
        await standalone.addScriptTag({content: source});
        assert.equal(await standalone.locator('[data-ats-install]').isVisible(), false, 'App windows do not offer to install again');
        assert.deepEqual(errors, [], 'Installation UI has no unhandled browser errors');
        console.log('ATS app browser checks passed: browser instructions, native prompt, acceptance, dismissal, errors, installed state and app window.' + (server ? ' Chrome manifest installability also passed.' : ''));
    } finally {
        await browser.close();
        if (server) await new Promise(resolve => server.close(resolve));
        const profilePath = fs.realpathSync(profile);
        if (profilePath.startsWith(path.resolve(os.tmpdir()) + path.sep) && path.basename(profilePath).startsWith('ats-app-browser-')) {
            fs.rmSync(profilePath, {recursive: true, force: true});
        }
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
