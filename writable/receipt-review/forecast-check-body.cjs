 const checks=[], errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
 for(const width of [390,1440])for(const dark of [false,true]) {
  await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/reports-ui-full.html`});
  for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!window.Chart&&Object.keys(Chart.instances).length===3"))break;await delay(100);}
  await evaluate(`document.documentElement.classList.toggle('dark',${dark});window.reviewSubmits=[];document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',event=>{event.preventDefault();window.reviewSubmits.push({id:form.id,data:Object.fromEntries(new FormData(form)),button:event.submitter?.textContent.trim()})}));window.beforeForecast=JSON.stringify(Chart.getChart('forecastChart').data);window.beforeExport=document.querySelector('[data-report-export]').href`);
  assert.equal(await evaluate("document.getElementById('salesForecastFilters').classList.contains('auto-filter-enabled')"),false);
  assert.equal(await evaluate("document.getElementById('salesReportFilters').classList.contains('auto-filter-enabled')"),true,'Other report filters keep their existing automatic behavior');
  await evaluate("document.getElementById('forecastGrouping').value='weekly';document.getElementById('forecastDateFrom').value='2026-08-02';document.getElementById('forecastDateTo').value='2026-09-22';['forecastGrouping','forecastDateFrom','forecastDateTo'].forEach(id=>{const input=document.getElementById(id);input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true}))})");
  await delay(850);
  assert.equal(await evaluate('window.reviewSubmits.length'),0,'Forecast inputs must not automatically submit');
  assert.equal(await evaluate("JSON.stringify(Chart.getChart('forecastChart').data)===window.beforeForecast"),true);
  assert.equal(await evaluate("document.querySelector('[data-report-export]').href===window.beforeExport"),true);
  assert.equal(await evaluate("document.getElementById('forecastPendingFilters').hidden"),false);
  assert.equal(await evaluate("document.getElementById('salesForecastFilters').checkValidity()"),true);
  await evaluate("document.querySelector('#salesForecastFilters button[type=submit]').click()");
  const submissions=await evaluate('window.reviewSubmits');
  assert.equal(submissions.length,1);
  assert.equal(submissions[0].id,'salesForecastFilters');
  assert.equal(submissions[0].button,'Update Forecast');
  assert.equal(submissions[0].data.forecast_type,'weekly');
  assert.equal(submissions[0].data.forecast_date_from,'2026-08-02');
  assert.equal(submissions[0].data.forecast_date_to,'2026-09-22');
  assert.equal(submissions[0].data.report_date_from,'2026-09-01');
  await evaluate("document.getElementById('salesForecastFilters').reset();document.getElementById('forecastGrouping').dispatchEvent(new Event('change',{bubbles:true}))");
  assert.equal(await evaluate("document.getElementById('forecastPendingFilters').hidden"),true);
  await evaluate("document.getElementById('reportBranch').value='1';document.getElementById('reportBranch').dispatchEvent(new Event('change',{bubbles:true}))");
  await delay(650);
  assert.equal(await evaluate("window.reviewSubmits.filter(x=>x.id==='salesReportFilters').length"),1);
  checks.push({width,dark,automaticForecastSubmits:0,manualForecastSubmits:1});
 }
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'forecast-manual-checks.json'),JSON.stringify(checks,null,2));
 console.log('Passed 4 manual forecast scenarios: no automatic submission, explicit button submits selected dates, chart/export unchanged before submit, other filters preserved.');
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
