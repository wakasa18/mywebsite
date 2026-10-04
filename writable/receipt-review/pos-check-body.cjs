 const checks=[];
 const errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
 for(const variant of ['full','empty']) {
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/pos-${variant}.html`});
  for(let i=0;i<80;i++){if(await evaluate("document.readyState==='complete'&&!!window.PharxmacoModal"))break;await delay(100);}
  for(const [width,height] of [[320,800],[390,844],[768,1024],[1024,768],[1180,820],[1440,900],[844,390]]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
   for(const dark of [false,true]) {
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);
    for(const panel of width<=1100?['products','cart','payment']:['products']) {
     await evaluate(`document.querySelector('[data-pos-target=${panel}]').click()`);
     await delay(90);
     const state=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth+1,panels:[...document.querySelectorAll('[data-pos-panel]')].filter(x=>x.getClientRects().length).length,clipped:[...document.querySelectorAll('.prod-card-name')].filter(x=>x.getClientRects().length).some(x=>x.scrollHeight>x.clientHeight+1),innerOverflow:[...document.querySelectorAll('.prod-scroll,.cart-item-list,.pay-scroll')].filter(x=>x.getClientRects().length).some(x=>x.scrollWidth>x.clientWidth+1)})`);
     assert.equal(state.overflow,false,`${width} ${panel} page overflow`);
     assert.equal(state.innerOverflow,false,`${width} ${panel} panel overflow`);
     assert.equal(state.clipped,false,'Medicine name clipped');
     assert.equal(state.panels,width<=1100?1:3);
     checks.push({variant,width,height,dark,panel});
     if(variant==='full'&&[390,768,1440].includes(width)) {
      await evaluate("window.scrollTo(0,0)");
      const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
      fs.writeFileSync(path.join(dir,`pos-${width}-${panel}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
     }
    }
   }
  }
  await evaluate("document.querySelector('[data-pos-target=products]').click();document.getElementById('availableProductsOnly').click()");
  assert.equal(await evaluate("document.getElementById('prodCount').textContent"),'6');
  await evaluate("document.getElementById('productSearch').value='paracetamol';document.getElementById('productSearch').dispatchEvent(new Event('input'))");await delay(200);
  assert.equal(await evaluate("document.getElementById('prodCount').textContent"),'1');
  await evaluate("document.getElementById('clearProductSearch').click();document.querySelector('.cat-chip[data-cat=vitamins]').click()");
  assert.equal(await evaluate("document.getElementById('prodCount').textContent"),'2');
  await evaluate("document.getElementById('productSearch').value='unmatched';document.getElementById('productSearch').dispatchEvent(new Event('input'))");await delay(200);
  assert.equal(await evaluate("document.getElementById('productFilterEmpty').hidden"),false);
  await evaluate("document.getElementById('resetProductFilters').click()");
  assert.equal(await evaluate("document.getElementById('prodCount').textContent"),'8');
  await evaluate("document.querySelector('.prod-card.disabled').click()");
  assert.equal(await evaluate("document.getElementById('quickAddBar').classList.contains('show')"),false);
  await evaluate("document.querySelector('.prod-card:not(.disabled)').click();document.querySelector('[data-quickadd-step=\"1\"]').click()");
  assert.equal(await evaluate("document.getElementById('quickAddQtyHidden').value"),'2');
  await evaluate("document.getElementById('quickAddQty').value=20;document.querySelector('[data-quickadd-step=\"1\"]').click()");
  assert.equal(await evaluate("document.getElementById('quickAddQtyHidden').value"),'20');
  await evaluate("document.getElementById('quickAddQty').value=1;document.querySelector('[data-quickadd-step=\"-1\"]').click()");
  assert.equal(await evaluate("document.getElementById('quickAddQtyHidden').value"),'1');
  await send('Emulation.setDeviceMetricsOverride',{width:320,height:800,deviceScaleFactor:1,mobile:false});
  assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth+1"),false,'Quick add phone overflow');
  assert.equal(await evaluate("document.getElementById('quickAddBar').scrollWidth>document.getElementById('quickAddBar').clientWidth+1"),false,'Quick add controls overflow');
  if(variant==='full') {await evaluate('window.scrollTo(0,0)');const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(path.join(dir,'pos-320-selected.png'),Buffer.from(shot.data,'base64'));}
  await evaluate("document.getElementById('quickAddCancel').click()");
  assert.equal(await evaluate("document.activeElement===document.querySelector('.prod-card:not(.disabled)')"),true,'Cancel restores product focus');
  await evaluate("document.getElementById('productSearch').focus();for(let i=0;i<10;i++)document.getElementById('productSearch').dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowDown',bubbles:true}))");
  assert.equal(await evaluate("document.querySelector('.prod-card.selected').classList.contains('disabled')"),false,'Arrow navigation skips unavailable products');
  await evaluate("document.getElementById('productSearch').dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}));document.getElementById('quickAddQty').dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}))");
  assert.equal(await evaluate("document.activeElement.classList.contains('prod-card')"),true,'Escape restores product focus');
  await evaluate("document.querySelector('[data-pos-target=payment]').click()");
  if(variant==='full') {
   await evaluate("document.getElementById('discountSelect').value='1';document.getElementById('discountSelect').dispatchEvent(new Event('change'));document.querySelector('[data-tender=exact]').click()");
   assert.equal(await evaluate("document.getElementById('amountPaidInput').value"),'191.70');
   await evaluate("document.querySelector('[data-method=gcash]').click()");
   assert.equal(await evaluate("document.getElementById('paymentMethodInput').value"),'gcash');
   assert.equal(await evaluate("document.getElementById('referenceNoInput').placeholder"),'Optional GCash reference');
   assert.equal(await evaluate("document.querySelector('[data-tender=\"100\"]').getClientRects().length"),0);
   await evaluate("document.querySelector('[data-method=cash]').click()");
   assert.equal(await evaluate("document.getElementById('referenceNoInput').required"),false);
  } else assert.equal(await evaluate("document.getElementById('completeSaleBtn').disabled"),true);
 }
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'pos-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} POS layouts plus filtering, selection, quantity bounds, discounts and tender controls. No forms submitted.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
