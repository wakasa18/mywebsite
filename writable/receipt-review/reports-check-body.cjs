 const checks=[],errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
 for(const variant of ['full','empty','mixed']) {
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/reports-ui-${variant}.html`});
  for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!document.querySelector('.mobile-record-ready')"))break;await delay(100);}
  assert.equal(await evaluate("typeof Chart!=='undefined' && Object.keys(Chart.instances).length===3"),true,'Charts must render');
  assert.equal(await evaluate("document.getElementById('reportPendingFilters').hidden"),true);
  for(const width of [320,390,600,768,1024,1440]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
   for(const dark of [false,true]) {
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);await delay(120);
    const state=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth+1,cardOverflow:[...document.querySelectorAll('.reports-workspace .card,.reports-workspace .kpi')].filter(x=>x.getClientRects().length).some(x=>x.getBoundingClientRect().right>innerWidth+1),smallButtons:[...document.querySelectorAll('.rp-date-presets button,.rp-section-nav a,.rp-actions-row a')].some(x=>x.getBoundingClientRect().height<44),fieldOverflow:[...document.querySelectorAll('#salesReportFilters .input')].some(x=>x.getBoundingClientRect().right>x.closest('.card').getBoundingClientRect().right),emptyCharts:document.querySelectorAll('.rp-chart-empty').length})`);
    assert.equal(state.overflow,false,`${width} report overflow`);assert.equal(state.cardOverflow,false,`${width} card overflow`);assert.equal(state.fieldOverflow,false);assert.equal(state.smallButtons,false);
    assert.equal(state.emptyCharts,variant==='empty'?3:variant==='mixed'?1:0);
    assert.equal(await evaluate("[...document.querySelectorAll('canvas[hidden]')].every(c=>c.getClientRects().length===0)"),true,'Empty chart canvas still takes up space');
    assert.equal(await evaluate("[...document.querySelectorAll('.rp-chart-empty')].every(e=>{const r=e.getBoundingClientRect(),p=e.parentElement.getBoundingClientRect();return r.top>=p.top-1&&r.bottom<=p.bottom+1})"),true,'Empty chart message spills outside its chart');
    if(width<=700)assert.equal(await evaluate("[...document.querySelectorAll('.reports-workspace .mobile-record-ready')].every(t=>t.scrollWidth<=t.clientWidth+2)"),true,'Mobile report table overflow');
    checks.push({variant,width,dark,...state});
    if(variant==='mixed'&&[390,1440].includes(width)) {
     await evaluate("document.getElementById('reportTrends').scrollIntoView({block:'start'})");
     const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`reports-empty-chart-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
    if(variant==='full'&&[390,1440].includes(width)) {
     for(const section of ['reportFilters','reportSummary','reportTrends','reportExportHistory']) {
      await evaluate(`document.getElementById('${section}').scrollIntoView({block:'start'})`);await delay(60);
      const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`reports-${section}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
     }
    }
   }
  }
  const href=await evaluate("document.querySelector('[data-report-export]').href");
  for(const [range,days] of [['today',0],['7',6],['30',29],['month',null]]) {
   await evaluate(`document.querySelector('[data-report-range="${range}"]').click()`);
   const dates=await evaluate("({from:document.getElementById('reportDateFrom').value,to:document.getElementById('reportDateTo').value,today:document.querySelector('[data-today]').dataset.today,valid:document.getElementById('salesReportFilters').checkValidity()})");
   assert.equal(dates.to,dates.today);assert.equal(dates.valid,true);
   if(days!==null)assert.equal((Date.parse(dates.to)-Date.parse(dates.from))/86400000,days);else assert.ok(dates.from.endsWith('-01'));
   assert.equal(await evaluate("document.getElementById('reportPendingFilters').hidden"),dates.from==='2026-09-01'&&dates.to==='2026-09-23');
   assert.equal(await evaluate("document.querySelector('[data-report-export]').href"),href,'Export dates remain applied values');
  }
  await evaluate("document.getElementById('salesReportFilters').reset();document.getElementById('reportDateFrom').dispatchEvent(new Event('change',{bubbles:true}))");
  assert.equal(await evaluate("document.getElementById('reportPendingFilters').hidden"),true);
  await evaluate("document.getElementById('forecastGrouping').value='weekly';document.getElementById('forecastGrouping').dispatchEvent(new Event('change',{bubbles:true}))");
  assert.equal(await evaluate("document.getElementById('reportPendingFilters').hidden"),false);
  await evaluate("document.getElementById('forecastModalTrigger').click()");
  assert.equal(await evaluate("document.getElementById('forecastModalOverlay').getAttribute('aria-hidden')"),'false');
  await evaluate("document.getElementById('forecastModalClose').click()");
 }
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'reports-ui-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} report layouts, charts, empty states, date presets, pending-filter notice, export URLs and restock modal. No exports or forms submitted.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
