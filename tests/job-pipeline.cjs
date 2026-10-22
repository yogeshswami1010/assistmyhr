// First generate a fixture with: php tests/job-pipeline.php vendor/autoload.php /tmp/job-pipeline.html
// Then: node tests/job-pipeline.cjs /tmp/job-pipeline.html [path-to-playwright]
const fs = require('node:fs');
const assert = require('node:assert/strict');
const {chromium} = require(process.argv[3] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent(fs.readFileSync(process.argv[2], 'utf8'));
        const rows = page.locator('.job-status-row');
        const order = () => rows.locator('input[type=text]').evaluateAll(inputs => inputs.map(input => input.value));
        const row = async name => rows.nth((await order()).indexOf(name));
        const drag = async (name, target, after) => {
            target = await target;
            const handle = (await row(name)).locator('.js-drag-handle');
            const box = await target.boundingBox();
            await handle.dragTo(target, {targetPosition: {x: 8, y: after ? box.height - 5 : 5}});
        };
        assert.deepEqual(await order(), ['Applied', 'Interview', 'Hired', 'Rejected']);
        await drag('Rejected', row('Interview'), false);
        assert.deepEqual(await order(), ['Applied', 'Rejected', 'Interview', 'Hired'], 'Existing stage can be dragged up');
        await drag('Rejected', row('Hired'), true);
        assert.deepEqual(await order(), ['Applied', 'Interview', 'Hired', 'Rejected'], 'Existing stage can be dragged down');
        await page.locator('#add-job-status-btn').click();
        await rows.last().locator('input[type=text]').fill('Reference check');
        await drag('Reference check', row('Interview'), false);
        assert.deepEqual(await order(), ['Applied', 'Reference check', 'Interview', 'Hired', 'Rejected'], 'Newly added stage is draggable immediately');
        await drag('Reference check', row('Applied'), false);
        assert.equal((await order())[0], 'Applied', 'Cannot drop a stage before Applied');
        const beforeTextDrag = await order();
        await (await row('Interview')).locator('input[type=text]').dragTo(await row('Rejected'));
        assert.deepEqual(await order(), beforeTextDrag, 'Editing a stage name does not start a row drag');
        await page.locator('#save-form').click();
        let submitted = await page.locator('#createForm').evaluate(form => Array.from(new FormData(form).getAll('job_status_name[]')));
        assert.deepEqual(submitted, await order(), 'Saving serializes the visual stage order');
        await (await row('Reference check')).locator('.js-remove-status').click();
        await page.locator('#save-form').click();
        submitted = await page.locator('#createForm').evaluate(form => Array.from(new FormData(form).getAll('job_status_name[]')));
        assert.deepEqual(submitted, ['Applied', 'Interview', 'Hired', 'Rejected'], 'Removing a stage and saving again refreshes submitted rows without duplicates');
        assert.deepEqual(errors, [], 'Pipeline scripts execute without browser errors');
        console.log('Job pipeline browser checks passed: existing and new stage dragging, fixed Applied, text editing, removal and save order.');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
