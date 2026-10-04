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
 const checks=[], errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
 const defaults={theme:'system',accent:'blue',textSize:'normal',density:'comfortable',largeControls:false,reduceMotion:false,highContrast:false,stickyHeaders:false};
 const ready=async()=>{for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!document.getElementById('interfacePreview')?.dataset.theme"))return;await delay(100);}throw Error('Settings did not load');};
 if(process.argv.includes('--keyboard')) {
  await send('Emulation.setDeviceMetricsOverride',{width:390,height:1000,deviceScaleFactor:1,mobile:false});
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/settings-admin.html`});await ready();
  await evaluate("document.querySelector('input[name=largeControls]').focus()");
  await send('Input.dispatchKeyEvent',{type:'keyDown',key:' ',code:'Space',windowsVirtualKeyCode:32,text:' '});
  await send('Input.dispatchKeyEvent',{type:'keyUp',key:' ',code:'Space',windowsVirtualKeyCode:32});
  assert.equal(await evaluate("document.querySelector('input[name=largeControls]').checked"),true);
  assert.equal(await evaluate("document.getElementById('settingsStatus').textContent"),'1 unsaved change');
  await evaluate("document.querySelector('input[name=accent][value=blue]').focus()");
  await send('Input.dispatchKeyEvent',{type:'keyDown',key:'ArrowRight',code:'ArrowRight',windowsVirtualKeyCode:39});
  await send('Input.dispatchKeyEvent',{type:'keyUp',key:'ArrowRight',code:'ArrowRight',windowsVirtualKeyCode:39});
  assert.equal(await evaluate("document.querySelector('input[name=accent]:checked').value"),'green');
  assert.equal(await evaluate("getComputedStyle(document.activeElement.nextElementSibling).outlineStyle"),'solid');
  await evaluate("document.querySelector('input[name=textSize][value=extra-large]').click();document.querySelector('input[name=density][value=spacious]').click();document.querySelector('input[name=theme][value=dark]').click();document.getElementById('settingsPreviewDetails').open=true;document.querySelector('.settings-preview').scrollIntoView({behavior:'instant'});window.scrollBy(0,-125)");
  const previewShot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,'settings-preview-mobile.png'),Buffer.from(previewShot.data,'base64'));
  await evaluate("document.querySelector('[aria-labelledby=comfortTitle]').scrollIntoView({behavior:'instant'});window.scrollBy(0,-125)");
  const comfortShot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,'settings-accessibility-mobile.png'),Buffer.from(comfortShot.data,'base64'));
  // A trusted keyboard interaction makes the unsaved-navigation prompt eligible.
  const dialog=new Promise(resolve=>{ws.addEventListener('message',function listener(event){const message=JSON.parse(event.data);if(message.method==='Page.javascriptDialogOpening'){ws.removeEventListener('message',listener);resolve(message.params);}})});
  const navigation=send('Page.navigate',{url:`http://127.0.0.1:${port}/settings-cashier.html`});
  const prompt=await Promise.race([dialog,delay(4000).then(()=>{throw Error('No unsaved navigation warning');})]);
  assert.equal(prompt.type,'beforeunload');await send('Page.handleJavaScriptDialog',{accept:false});await navigation;
  assert.equal(await evaluate("document.querySelector('input[name=accent]:checked').value"),'green');
  await evaluate("document.getElementById('discardUiSettings').click()");
  assert.equal(await evaluate("document.getElementById('saveUiSettings').disabled"),true);
  assert.deepEqual(errors,[]);
  console.log('Keyboard radio/switch navigation, focus ring, unsaved-navigation warning and discard checks passed.');
  return;
 }
 for(const role of ['admin','cashier'])for(const width of [320,390,768,1024,1440])for(const dark of [false,true]) {
  const initial={...defaults,theme:dark?'dark':'light'};
  const seed=await send('Page.addScriptToEvaluateOnNewDocument',{source:`localStorage.setItem('pharxmaco-ui-settings',${JSON.stringify(JSON.stringify(initial))})`});
  await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/settings-${role}.html`});
  await ready();await send('Page.removeScriptToEvaluateOnNewDocument',{identifier:seed.identifier});
  assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true,`Initial overflow ${role} ${width}`);
  assert.equal(await evaluate("document.getElementById('uiSettingsForm').classList.contains('auto-filter-enabled')"),false);
  assert.equal(await evaluate("document.getElementById('saveUiSettings').disabled"),true);
  assert.equal(await evaluate("document.getElementById('settingsPreviewDetails').open"),width>980);
  if(role==='admin'&&[390,1440].includes(width)){
   const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`settings-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
  }
  await evaluate("document.querySelector('input[name=accent][value=green]').click()");
  await delay(650);
  assert.equal(await evaluate("JSON.parse(localStorage.getItem('pharxmaco-ui-settings')).accent"),'blue','No automatic save');
  assert.equal(await evaluate("document.documentElement.dataset.accent"),'blue','Workspace stays unchanged during preview');
  assert.equal(await evaluate("document.getElementById('interfacePreview').dataset.accent"),'green');
  await evaluate("document.querySelector('input[name=accent][value=blue]').click()");
  assert.equal(await evaluate("document.getElementById('saveUiSettings').disabled"),true,'Reverting input clears dirty state');
  await evaluate("document.getElementById('settingsPreviewDetails').open=true;document.querySelector('input[name=accent][value=purple]').click();document.querySelector('input[name=textSize][value=extra-large]').click();document.querySelector('input[name=density][value=spacious]').click();['largeControls','reduceMotion','highContrast','stickyHeaders'].forEach(name=>document.querySelector('input[name='+name+']').click())");
  assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true,'Expanded preview overflow');
  assert.equal(await evaluate("getComputedStyle(document.querySelector('.sample-table th')).position"),'sticky');
  assert.equal(await evaluate("getComputedStyle(document.querySelector('.sample-table')).fontSize"),'16px');
  await evaluate("document.getElementById('saveUiSettings').click()");
  assert.equal(await evaluate("document.documentElement.dataset.accent"),'purple');
  assert.equal(await evaluate("document.documentElement.dataset.textSize"),'extra-large');
  assert.equal(await evaluate("document.getElementById('saveUiSettings').disabled"),true);
  assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true,'Saved large settings overflow');
  await send('Page.reload');await delay(150);await ready();
  assert.equal(await evaluate("document.querySelector('input[name=accent]:checked').value"),'purple');
  assert.equal(await evaluate("document.querySelector('input[name=highContrast]').checked"),true);
  await evaluate("document.getElementById('resetUiSettings').click()");
  assert.equal(await evaluate("JSON.parse(localStorage.getItem('pharxmaco-ui-settings')).accent"),'purple','Defaults wait for save');
  await evaluate("document.getElementById('discardUiSettings').click()");
  assert.equal(await evaluate("document.querySelector('input[name=accent]:checked').value"),'purple');
  await evaluate("document.getElementById('resetUiSettings').click();document.getElementById('saveUiSettings').click()");
  assert.deepEqual(await evaluate("JSON.parse(localStorage.getItem('pharxmaco-ui-settings'))"),defaults);
  checks.push({role,width,dark});
 }
 // Failed storage must retain the draft and must not apply it to the workspace.
 await evaluate("document.querySelector('input[name=accent][value=orange]').click();window.savedSetItem=Storage.prototype.setItem;Storage.prototype.setItem=function(key,value){if(key==='pharxmaco-ui-settings')throw new DOMException('Blocked','SecurityError');return window.savedSetItem.call(this,key,value)};document.getElementById('saveUiSettings').click()");
 assert.equal(await evaluate("document.getElementById('settingsError').hidden"),false);
 assert.equal(await evaluate("document.documentElement.dataset.accent"),'blue');
 assert.equal(await evaluate("document.querySelector('input[name=accent]:checked').value"),'orange');
 await evaluate("Storage.prototype.setItem=window.savedSetItem;document.getElementById('saveUiSettings').click()");
 assert.equal(await evaluate("document.documentElement.dataset.accent"),'orange');
 // Topbar updates theme without destroying an unsaved accent choice.
 await evaluate("document.querySelector('input[name=accent][value=green]').click();document.getElementById('themeToggle').click()");
 assert.equal(await evaluate("document.querySelector('input[name=accent]:checked').value"),'green');
 assert.equal(await evaluate("document.querySelector('input[name=theme]:checked').value===JSON.parse(localStorage.getItem('pharxmaco-ui-settings')).theme"),true);
 assert.equal(await evaluate("document.getElementById('themeToggle').getAttribute('aria-pressed')===String(document.documentElement.classList.contains('dark'))"),true);
 // Simulate a storage event from another tab with both a clean and a dirty field.
 await evaluate("const next={...JSON.parse(localStorage.getItem('pharxmaco-ui-settings')),accent:'purple',density:'compact'};localStorage.setItem('pharxmaco-ui-settings',JSON.stringify(next));window.dispatchEvent(new StorageEvent('storage',{key:'pharxmaco-ui-settings',newValue:JSON.stringify(next),storageArea:localStorage}))");
 assert.equal(await evaluate("document.querySelector('input[name=accent]:checked').value"),'green');
 assert.equal(await evaluate("document.querySelector('input[name=density]:checked').value"),'compact');
 await evaluate("document.getElementById('discardUiSettings').click()");
 assert.equal(await evaluate("document.querySelector('input[name=accent]:checked').value"),'purple');
 await evaluate("document.getElementById('resetUiSettings').click();document.getElementById('saveUiSettings').click()");
 await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-color-scheme',value:'dark'}]});await delay(200);
 assert.equal(await evaluate("document.documentElement.classList.contains('dark')"),true);
 assert.equal(await evaluate("document.getElementById('interfacePreview').dataset.theme"),'dark');
 await send('Emulation.setEmulatedMedia',{features:[]});
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'settings-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} settings layouts and workflows, plus storage failure, topbar synchronization, external updates and device theme.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});