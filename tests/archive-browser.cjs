// Run after npm dependencies + Playwright are available in local/browser-tools.
// KN_URL can point at a deployed installation. No content/settings are changed.
const browsers = require('../local/browser-tools/node_modules/playwright');
const assert = require('node:assert/strict');
const base = process.env.KN_URL || 'http://learnwithkamal.test';
(async () => {
 const engine = process.env.KN_BROWSER || 'chromium';
 const browser = await browsers[engine].launch({headless:true, ...(engine === 'chromium' ? {executablePath:process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'} : {})});
 try {
  for(const width of [320,390,768,1024,1440]) {
   const page = await browser.newPage({viewport:{width,height:900},reducedMotion:width===320?'reduce':'no-preference'});
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   await page.goto(base);
   await page.evaluate(() => window.archiveDocument = 'preserved');
   const links=await page.locator('.topic-tab').evaluateAll(nodes=>nodes.map(n=>n.href));
   const wait=async()=>{await page.waitForFunction(()=>document.querySelector('#stories')?.getAttribute('aria-busy')==='false');};
   await page.locator('.topic-tab').nth(1).click();await page.waitForURL(links[1]);await wait();
   assert.equal(await page.evaluate(()=>window.archiveDocument),'preserved','Topic must preserve document');
   assert.equal(await page.locator('.topic-tab.is-active').getAttribute('href'),links[1]);
   await page.goBack();await wait();
   assert.equal(await page.evaluate(()=>window.archiveDocument),'preserved','Back must preserve document');
   await page.goForward();await wait();assert.equal(page.url(),links[1]);
   // Slow the first request: a newer selection must win even when the old response arrives later.
   await page.route('**/*',async route=>{if(route.request().resourceType()==='fetch' && route.request().url()===links[1].split('#')[0]) await new Promise(r=>setTimeout(r,350));await route.continue().catch(()=>{});});
   await page.locator('.topic-tab').nth(1).click();await page.locator('.topic-tab').nth(2).click();
   await page.waitForURL(links[2]);await wait();await page.waitForTimeout(400);assert.equal(page.url(),links[2]);
   await page.unroute('**/*');
   await page.locator('#story-search').fill('no-such-story-archive-test-938283');await page.locator('#story-search').press('Enter');await wait();
   assert.equal(await page.locator('.empty-state').count(),1);assert.equal(await page.evaluate(()=>window.archiveDocument),'preserved','Search must preserve document');
   await page.locator('.empty-state a').click();await wait();assert.ok(await page.locator('.story-card').count()>0);
   const before=await page.locator('.story-card h3').allTextContents();const url=page.url();
   await page.route('**/*',route=>route.request().resourceType()==='fetch'?route.abort():route.continue());
   await page.locator('.topic-tab').nth(1).click();await wait();
   assert.equal(page.url(),url,'Failed request must preserve URL');assert.deepEqual(await page.locator('.story-card h3').allTextContents(),before);
   assert.ok(await page.locator('.archive-status a').isVisible());await page.unroute('**/*');
   await page.locator('.topic-tab').nth(1).click();await wait();assert.equal(await page.locator('.archive-status a').count(),0);
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'No viewport overflow');assert.deepEqual(errors,[]);
   console.log(`PASS ${width}px: topic, history, race, empty search, reset, failure/retry, overflow`);
   await page.close();
  }
  for(const route of ['/writing/','/category/wordpress/','/?s=wordpress']){
   const page=await browser.newPage();await page.goto(base+route);await page.evaluate(()=>window.archiveDocument=true);
   await page.locator('.topic-tab').nth(1).click();await page.waitForFunction(()=>document.querySelector('#stories')?.getAttribute('aria-busy')==='false');assert.equal(await page.evaluate(()=>window.archiveDocument),true);await page.close();
  }
  const edge=await browser.newPage();await edge.goto(base+'/writing/?topic=wordpress');
  await edge.locator('.topic-tab').first().click();await edge.waitForFunction(()=>document.querySelector('#stories')?.getAttribute('aria-busy')==='false');
  const fresh=await browser.newPage();await fresh.goto(base+'/writing/');
  assert.deepEqual(await edge.locator('.kn-featured h2').allTextContents(),await fresh.locator('.kn-featured h2').allTextContents(),'Clearing direct filter must retain featured articles');await fresh.close();
  if(await edge.locator('[data-featured-next]').count()) {
   const title=await edge.locator('.kn-featured-slide.is-active h2').textContent();
   await edge.locator('[data-featured-next]').click();
   assert.notEqual(await edge.locator('.kn-featured-slide.is-active h2').textContent(),title,'Restored featured carousel must initialize');
  }
  await edge.route('**/*',async route=>{if(route.request().resourceType()==='fetch')await new Promise(r=>setTimeout(r,400));await route.continue().catch(()=>{});});
  await edge.locator('#story-search').fill('wordpress');await edge.locator('#story-search').press('Enter');await edge.locator('#story-search').fill('new draft query');
  await edge.waitForFunction(()=>document.querySelector('#stories')?.getAttribute('aria-busy')==='false');
  assert.equal(await edge.locator('#story-search').inputValue(),'new draft query','Pending response must preserve newer input');await edge.close();
  const plain=await browser.newPage({javaScriptEnabled:false});await plain.goto(base);await plain.locator('.topic-tab').nth(1).click();assert.ok(new URL(plain.url()).searchParams.has('topic'));await plain.close();
  console.log('PASS Writing/category/search entry routes and no-JavaScript fallback');
 } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
