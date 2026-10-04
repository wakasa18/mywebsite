const fs = require('fs');
const path = require('path');
const http = require('http');
const assert = require('assert/strict');
const {spawn} = require('child_process');
const root = process.cwd(), dir = __dirname;
const profile = path.join(dir, 'chrome-profile-' + Date.now());
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
let chrome, ws, server;
(async () => {
 server = http.createServer((req,res) => {
  const url = new URL(req.url, 'http://localhost');
  const base = url.pathname.startsWith('/assets/') ? path.join(root,'public') : dir;
  const file = path.resolve(base,'.' + decodeURIComponent(url.pathname));
  if (!file.startsWith(base + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) {res.writeHead(404);res.end();return;}
  res.setHeader('Content-Type', file.endsWith('.css')?'text/css':file.endsWith('.js')?'text/javascript':'text/html; charset=utf-8');
  res.end(fs.readFileSync(file));
 });
 await new Promise(resolve => server.listen(0,'127.0.0.1',resolve));
 const port=server.address().port;
 fs.mkdirSync(profile,{recursive:true});
 chrome=spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',[
  '--headless=new','--disable-gpu','--hide-scrollbars','--no-first-run','--no-default-browser-check','--disable-extensions',
  '--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'
 ],{stdio:'ignore',windowsHide:true});
 let debugPort;
 for(let i=0;i<100;i++) {
  const portFile=path.join(profile,'DevToolsActivePort');
  if(fs.existsSync(portFile)){debugPort=Number(fs.readFileSync(portFile,'utf8').split('\n')[0]);break;}
  await delay(100);
 }
 if(!debugPort)throw new Error('Headless Chrome did not start');
 const tabs=await (await fetch('http://127.0.0.1:'+debugPort+'/json', {signal:AbortSignal.timeout(5000)})).json();
 ws=new WebSocket(tabs.find(tab=>tab.type==='page').webSocketDebuggerUrl);
 await new Promise((resolve,reject)=>{const timer=setTimeout(()=>reject(Error('Browser connection timed out')),10000);ws.addEventListener('open',()=>{clearTimeout(timer);resolve();},{once:true});ws.addEventListener('error',()=>{clearTimeout(timer);reject(Error('Browser connection failed'));},{once:true});});
 let serial=0; const pending=new Map();
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(pending.has(m.id)){const p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(Error(m.error.message)):p.resolve(m.result);}});
 const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++serial;const timer=setTimeout(()=>{pending.delete(id);reject(Error('Browser timed out: '+method));},15000);pending.set(id,{resolve:r=>{clearTimeout(timer);resolve(r);},reject:e=>{clearTimeout(timer);reject(e);}});ws.send(JSON.stringify({id,method,params}));});
 const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
 await send('Page.enable');
 const checks=[];
 const key=async(key,code=key,modifiers=0)=>{await send('Input.dispatchKeyEvent',{type:'keyDown',key,code,modifiers});await send('Input.dispatchKeyEvent',{type:'keyUp',key,code,modifiers});};
 for(const role of ['admin','cashier']) {
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/sidebar-${role}.html`});
  for(let i=0;i<80;i++){if(await evaluate("document.readyState==='complete' && !!document.querySelector('#sidebarSearch')"))break;await delay(100);}
  assert.equal(await evaluate("document.querySelector('#sidebar').inert"),true);
  for(const [width,height] of [[320,740],[390,844],[768,1024],[1440,900],[844,390]]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
   for(const dark of [false,true]) {
    await evaluate(`document.documentElement.classList.toggle('dark',${dark});document.documentElement.dataset.accent='purple';document.querySelector('#sidebarToggle').click()`);
    await delay(280);
    const state=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth,sidebarInert:document.querySelector('#sidebar').inert,mainInert:document.querySelector('#mainContent').inert,active:[...document.querySelectorAll('#sidebar [aria-current=page]')].map(x=>x.textContent.trim()),width:document.querySelector('#sidebar').getBoundingClientRect().width,scroll:document.querySelector('.nav-section').clientHeight,signout:document.querySelector('.logout-item').getBoundingClientRect().toJSON(),buttons:[...document.querySelectorAll('#sidebar a,#sidebar button,#sidebar input')].filter(x=>x.getClientRects().length).map(x=>x.getBoundingClientRect().height),bg:getComputedStyle(document.querySelector('#sidebar')).backgroundColor})`);
    assert.equal(state.overflow,false);
    assert.equal(state.sidebarInert,false);
    assert.equal(state.mainInert,true);
    assert.ok(state.width<=width-24);
    assert.ok(state.scroll>=70);
    assert.ok(state.signout.bottom<=height+1);
    assert.ok(state.buttons.every(x=>x>=44),'touch targets');
    assert.deepEqual(state.active,[role==='admin'?'Reports':'Sales History']);
    checks.push({role,width,height,dark,bg:state.bg});
    if([390,1440].includes(width)) {
     const shot=await send('Page.captureScreenshot',{format:'png'});
     fs.writeFileSync(path.join(dir,`sidebar-${role}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
    await evaluate("document.querySelector('#sidebarSearch').value='inventory';document.querySelector('#sidebarSearch').dispatchEvent(new Event('input'))");
    assert.equal(await evaluate("[...document.querySelectorAll('[data-nav-group]')].filter(x=>!x.hidden).length"),1);
    await evaluate("document.querySelector('#sidebarSearch').value='zzzz';document.querySelector('#sidebarSearch').dispatchEvent(new Event('input'))");
    assert.equal(await evaluate("document.querySelector('#sidebarEmpty').hidden"),false);
    await evaluate("document.querySelector('.logout-item').focus()");
    await key('Tab');
    assert.equal(await evaluate('document.activeElement.id'),'sidebarClose');
    await key('Tab','Tab',8);
    assert.equal(await evaluate("document.activeElement.classList.contains('logout-item')"),true);
    await evaluate("document.querySelector('#sidebarSearchClear').click()");
    assert.equal(await evaluate('document.activeElement.id'),'sidebarSearch');
    assert.equal(await evaluate("document.querySelector('#sidebarEmpty').hidden"),true);
    await key('Escape');
    assert.equal(await evaluate("document.querySelector('#mainContent').inert"),false);
    assert.equal(await evaluate('document.activeElement.id'),'sidebarToggle');
    assert.equal(await evaluate("document.querySelector('#sidebar').inert"),true);
    await delay(280);
    const bar = await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth,title:document.querySelector('#topbarTitle').getBoundingClientRect().width,buttons:['sidebarToggle','notificationButton','themeToggle','accountButton'].map(id=>{const r=document.getElementById(id).getBoundingClientRect();return {id,width:r.width,height:r.height,left:r.left,right:r.right}})})`);
    assert.equal(bar.overflow,false);
    assert.ok(bar.title>25);
    assert.ok(bar.buttons.every(x=>x.height>=44 && x.width>=44 && x.left>=0 && x.right<=width));
    await evaluate("document.querySelector('#accountButton').click()");
    assert.equal(await evaluate("document.querySelector('#accountPanel').hidden"),false);
    assert.ok(await evaluate("(()=>{const r=document.querySelector('#accountPanel').getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.bottom<=innerHeight})()"));
    if([390,1440].includes(width)) {
     const shot=await send('Page.captureScreenshot',{format:'png'});
     fs.writeFileSync(path.join(dir,`topbar-${role}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
    await evaluate("document.querySelector('#accountButton').focus()");
    await key('ArrowDown');
    assert.equal(await evaluate("document.activeElement.closest('#accountPanel')!==null"),true);
    await key('Escape');
    assert.equal(await evaluate('document.activeElement.id'),'accountButton');
    assert.equal(await evaluate("document.querySelector('#accountPanel').hidden"),true);
    await evaluate("document.querySelector('#accountButton').click();document.querySelector('#notificationButton').click()");
    assert.equal(await evaluate("document.querySelector('#accountPanel').hidden"),true);
    assert.equal(await evaluate("document.querySelector('#notificationMenu').classList.contains('open')"),true);
    assert.ok(await evaluate("(()=>{const r=document.querySelector('#notificationMenu').getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.bottom<=innerHeight})()"));
    await key('Escape');
    assert.equal(await evaluate('document.activeElement.id'),'notificationButton');
    await evaluate("document.querySelector('#themeToggle').click()");
    assert.equal(await evaluate("document.querySelector('#themeToggle').getAttribute('aria-pressed')"),String(!dark));
    await evaluate("document.querySelector('#themeToggle').click()");
   }
  }
 }
 await send('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:false});
 await evaluate("document.documentElement.dataset.textSize='extra-large';document.documentElement.classList.add('reduce-motion');document.querySelector('#sidebarToggle').click()");
 await delay(100);
 assert.equal(await evaluate('document.documentElement.scrollWidth>innerWidth'),false);
 assert.ok(await evaluate("parseFloat(getComputedStyle(document.querySelector('#sidebar')).transitionDuration)<=0.001"));
 await evaluate("document.querySelector('#overlay').click()");
 assert.equal(await evaluate("document.querySelector('#mainContent').inert"),false);
 fs.writeFileSync(path.join(dir,'sidebar-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} sidebar/topbar layouts: search, roles, focus, account/notification panels, theme switching, large text and reduced motion.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
