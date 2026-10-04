 const checks=[];
 const errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.text+': '+m.params.exceptionDetails.exception?.description);});
 const key=async(key,modifiers=0)=>{await send('Input.dispatchKeyEvent',{type:'keyDown',key,modifiers});await send('Input.dispatchKeyEvent',{type:'keyUp',key,modifiers});};
 for(const [page,modal,trigger,body] of [
  ['held','lowStockModal','reviewLowStockTrigger','.low-stock-list'],
  ['held','heldModalOverlay','heldSalesTrigger','.held-modal-body'],
  ['forecast','forecastModalOverlay','forecastModalTrigger','.forecast-modal-body']
 ]) {
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/modals-${page}.html`});
  for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete' && !!window.PharxmacoModal"))break;await delay(100);}
  assert.equal(await evaluate('!!window.PharxmacoModal'),true);
  const inertBefore=await evaluate("document.querySelectorAll('[inert]').length");
  for(const [width,height] of [[320,740],[390,844],[768,1024],[1024,768],[1440,900],[844,390]]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
   for(const dark of [false,true]) {
    await evaluate(`document.documentElement.classList.toggle('dark',${dark});document.getElementById('${trigger}').click()`);
    await delay(120);
    const state=await evaluate(`(()=>{const m=document.getElementById('${modal}'),d=m.querySelector('[role=dialog]'),b=m.querySelector('${body}');const rect=d.getBoundingClientRect();return {visible:m.getAttribute('aria-hidden')==='false',inert:!!d.closest('[inert]'),focus:m.contains(document.activeElement),overflow:b.scrollWidth>b.clientWidth+2,bodyHeight:b.clientHeight,left:rect.left,right:rect.right,top:rect.top,bottom:rect.bottom,bg:getComputedStyle(d).backgroundColor,backgroundIsolated:document.getElementById('sidebar').inert||!!document.getElementById('sidebar').closest('[inert]'),buttonHeight:m.querySelector('button').getBoundingClientRect().height}})()`);
    assert.equal(state.visible,true,modal+' did not open');
    assert.equal(state.inert,false,modal+' inert dialog');assert.equal(state.focus,true);
    assert.equal(state.overflow,false,`${modal} horizontal overflow ${width}`);
    assert.ok(state.bodyHeight>70,`${modal} scroll area too small ${width}`);
    assert.ok(state.left>=0&&state.right<=width+1&&state.top>=0&&state.bottom<=height+1,`${modal} offscreen ${width}: ${JSON.stringify(state)}`);
    assert.equal(state.backgroundIsolated,true);assert.ok(state.buttonHeight>=44);
    if(modal==='forecastModalOverlay') {
     assert.equal(await evaluate("[...document.querySelectorAll('#forecastModalOverlay .table-wrap')].every(w=>w.getBoundingClientRect().bottom>=w.querySelector('table').getBoundingClientRect().bottom-2)"),true,'Forecast table overlaps following content');
    }
    await key('Tab',8);assert.equal(await evaluate(`document.getElementById('${modal}').contains(document.activeElement)`),true);
    await key('Tab');assert.equal(await evaluate(`document.activeElement===document.getElementById('${modal}').querySelector('button')`),true);
    if(modal==='lowStockModal') {
     await evaluate("document.getElementById('lowStockSearch').value='no match at all';document.getElementById('lowStockSearch').dispatchEvent(new Event('input'))");
     assert.equal(await evaluate("[...document.querySelectorAll('[data-low-stock-row]')].filter(x=>x.getClientRects().length).length"),0);
     assert.equal(await evaluate("document.getElementById('lowStockVisibleCount').textContent"),'0');
     await evaluate("document.getElementById('lowStockSearch').value='';document.getElementById('lowStockSearch').dispatchEvent(new Event('input'))");
     assert.equal(await evaluate("[...document.querySelectorAll('[data-low-stock-row]')].filter(x=>x.getClientRects().length).length"),24);
    }
    if([390,1440].includes(width)) {
     await evaluate(`document.querySelector('#${modal} ${body}').scrollTop=0`);
     const shot=await send('Page.captureScreenshot',{format:'png'});
     fs.writeFileSync(path.join(dir,`${modal}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
    await key('Escape');
    assert.equal(await evaluate(`document.getElementById('${modal}').getAttribute('aria-hidden')`),'true');
    assert.equal(await evaluate(`document.activeElement.id`),trigger);
    assert.equal(await evaluate("document.querySelectorAll('[inert]').length"),inertBefore);
    assert.equal(await evaluate("document.body.style.overflow"),'');
    checks.push({modal,width,height,dark,...state});
   }
  }
  // Backdrop closes, and an existing scroll lock is preserved.
  await evaluate(`document.body.style.overflow='clip';document.getElementById('${trigger}').click();document.getElementById('${modal}').click()`);
  assert.equal(await evaluate("document.body.style.overflow"),'clip');
  await evaluate("document.body.style.overflow=''");
  if(modal==='lowStockModal') {
   await evaluate("document.getElementById('notificationButton').click();document.getElementById('reviewNotificationStock').click()");
   assert.equal(await evaluate("document.getElementById('notificationMenu').classList.contains('open')"),false);
   await evaluate("window.checkoutClicks=0;document.getElementById('completeSaleBtn').disabled=false;document.getElementById('completeSaleBtn').addEventListener('click',e=>{e.preventDefault();e.stopImmediatePropagation();window.checkoutClicks++},true)");
   await key('Enter',2);
   assert.equal(await evaluate('window.checkoutClicks'),0,'Checkout shortcut must be blocked behind a modal');
   await key('Escape');
   assert.equal(await evaluate('document.activeElement.id'),'notificationButton');
   assert.equal(await evaluate("document.getElementById('notificationButton').getAttribute('aria-expanded')"),'false');
  }
 }
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'modal-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} modal layouts, focus trapping/restoration, Escape/backdrop, search, and scroll restoration.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
