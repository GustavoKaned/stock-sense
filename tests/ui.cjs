// Testes de interface com respostas HTTP simuladas; não substituem integração MySQL.
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const rows = [
  ['Peça A','A1','Galpão Norte','Usinagem','1.200,00 m³','31/01/2026','—','Armazenado'],
  ['Peça B','A2','Galpão Sul','Montagem','20,00 m³','02/02/2026','—','Armazenado'],
  ['Motor','A10','Galpão Norte','Usinagem','3,00 m³','15/12/2025','01/01/2026','Retirado'],
];
function fixture() {
  return `<!doctype html><html lang="pt-BR"><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ocupações</title>
  <script src="assets/js/tema.js"></script><link rel="stylesheet" href="assets/css/base.css"></head><body>
  <div class="container"><aside class="sidebar">Menu</aside><div id="overlay" class="overlay"></div><div class="conteudo">
  <header><div class="header-esq"><button id="btnMenu">Menu</button><h1>Ocupações</h1></div><button id="alternarTema">Tema</button></header>
  <main class="pagina"><h1>Ocupações</h1><section class="card"><div class="card-topo"><span id="count"></span><div class="busca"><input type="search" aria-label="Buscar" data-busca="#tabelaOcupacoes" data-contador="#count" data-vazio="#empty"></div></div>
  <div class="tabela-rolagem"><table id="tabelaOcupacoes" class="tabela"><thead><tr>${['Produto','Prateleira','Galpão','Setor','Volume','Entrada','Saída','Status',''].map(t=>`<th>${t}</th>`).join('')}</tr></thead>
  <tbody>${rows.map((row,i)=>`<tr>${row.map(t=>`<td>${t}</td>`).join('')}<td><form class="acao-inline" action="actions/excluir_ocupacao.php" method="POST"><input name="id" type="hidden" value="${i+1}"><input name="csrf_token" type="hidden" value="test"><button type="submit" data-confirmar="Excluir?">Excluir</button></form></td></tr>`).join('')}</tbody></table></div><div id="empty" class="oculto">Nenhum resultado</div></section>
  <form id="edit" action="actions/salvar_galpao.php" method="POST" class="card"><input name="csrf_token" type="hidden" value="test"><div class="form"><div class="form-grade"><div class="campo"><label for="nome">Nome</label><input id="nome" name="nome" required maxlength="80"></div><div class="campo"><label for="metros_cubicos">Capacidade</label><input id="metros_cubicos" name="metros_cubicos" type="number" min="0.01" step="0.01" required></div></div><button type="submit">Salvar</button></div></form>
  </main></div></div><script src="assets/js/app.js"></script></body></html>`;
}
(async()=>{
  const browser = await chromium.launch({headless:true, channel:process.env.BROWSER_CHANNEL || 'msedge'});
  try {
    const page = await browser.newPage();
    page.setDefaultTimeout(8000);
    const errors = []; page.on('pageerror', e=>errors.push(e.message));
    let mode='error', posts=0;
    await page.route('**/*',async route=>{
      const url = new URL(route.request().url());
      if(url.hostname !== 'stocksense.test') return route.abort();
      if(url.pathname.includes('/assets/')) {
        const file=path.join(root,url.pathname.slice(1));
        return route.fulfill({contentType:file.endsWith('.js')?'text/javascript; charset=utf-8':'text/css; charset=utf-8',body:fs.readFileSync(file)});
      }
      if(url.pathname.includes('/actions/')) {
        posts++; assert.equal(route.request().method(),'POST'); assert.match(route.request().postData(),/csrf_token/);
        await new Promise(resolve=>setTimeout(resolve,100));
        return route.fulfill({status:mode==='error'?422:200,contentType:'application/json',body:JSON.stringify({sucesso:mode!=='error',mensagem:mode==='error'?'Capacidade indisponível.':'Registro salvo.',url:'../index.php?pagina=ocupacoes'})});
      }
      return route.fulfill({contentType:'text/html; charset=utf-8',body:fixture()});
    });
    await page.goto('http://stocksense.test/index.php?pagina=ocupacoes');
    const visible = ()=>page.locator('tbody tr:not([hidden])').count();
    await page.getByLabel('Buscar').fill('peca norte'); assert.equal(await visible(),1);
    await page.getByRole('combobox',{name:'Galpão',exact:true}).selectOption('galpao sul'); assert.equal(await visible(),0);
    assert.equal(await page.locator('#empty').isVisible(),true);
    await page.getByRole('button',{name:'Limpar filtros'}).click(); assert.equal(await visible(),3);
    await page.getByRole('button',{name:'Volume',exact:true}).click(); assert.match(await page.locator('tbody tr').first().innerText(),/Motor/);
    await page.getByRole('button',{name:'Volume',exact:true}).click(); assert.match(await page.locator('tbody tr').first().innerText(),/Peça A/);
    await page.getByRole('button',{name:'Entrada',exact:true}).click(); assert.match(await page.locator('tbody tr').first().innerText(),/Motor/);
    await page.getByRole('button',{name:'Prateleira',exact:true}).click(); assert.match(await page.locator('tbody tr').last().innerText(),/Motor/);
    await page.locator('#nome').fill('   '); await page.locator('#metros_cubicos').fill('0');
    await page.getByRole('button',{name:'Salvar',exact:true}).click(); assert.equal(posts,0);
    assert.equal(await page.locator('#nome').getAttribute('aria-invalid'),'true');
    await page.locator('#nome').fill('Galpão novo'); await page.locator('#metros_cubicos').fill('20');
    await page.getByRole('button',{name:'Salvar',exact:true}).click(); await page.getByRole('alert').filter({hasText:'Capacidade indisponível.'}).waitFor();
    assert.equal(await page.locator('#nome').inputValue(),'Galpão novo'); assert.equal(posts,1);
    mode='success'; await page.getByRole('button',{name:'Salvar',exact:true}).click();
    await page.getByRole('status').filter({hasText:'Registro salvo.'}).waitFor(); assert.equal(posts,2);
    assert.equal(await page.locator('#nome').inputValue(),'');
    await page.getByLabel('Buscar').fill('peca');
    await page.getByRole('button',{name:'Excluir',exact:true}).first().click();
    await page.getByRole('dialog').getByRole('button',{name:'Cancelar'}).click(); assert.equal(posts,2);
    await page.getByRole('button',{name:'Excluir',exact:true}).first().click();
    await page.keyboard.press('Escape'); assert.equal(await page.getByRole('dialog').count(),0); assert.equal(posts,2);
    await page.getByRole('button',{name:'Excluir',exact:true}).first().click();
    await page.getByRole('dialog').getByRole('button',{name:'Excluir',exact:true}).click();
    await page.waitForFunction(()=>document.querySelector('[data-busca]').value==='peca' && !document.querySelector('[aria-busy]'));
    assert.equal(posts,3); assert.equal(await visible(),2);
    await page.locator('#alternarTema').click(); const theme=await page.locator('html').getAttribute('data-tema');
    await page.reload(); assert.equal(await page.locator('html').getAttribute('data-tema'),theme);
    await page.goto('http://stocksense.test/index.php?pagina=ocupacoes&status=armazenado'); assert.equal(await visible(),2);
    for(const width of [360,768,1280]) {
      await page.setViewportSize({width,height:850});
      assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth),true,`overflow na largura ${width}`);
    }
    await page.setViewportSize({width:360,height:850});
    await page.locator('#btnMenu').click(); assert.equal(await page.locator('#btnMenu').getAttribute('aria-expanded'),'true');
    await page.keyboard.press('Escape'); assert.equal(await page.locator('#btnMenu').getAttribute('aria-expanded'),'false');
    assert.deepEqual(errors,[]);
    console.log('OK: filtros combinados, acentos, ordenação numérica/data/natural, validação, POST, erros, sucesso sem recarga, confirmação, tema persistente e telas 360/768/1280.');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
