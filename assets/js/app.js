/* Melhoria progressiva: formulários continuam funcionando sem JavaScript. */
(() => {
  'use strict';
  const normalize = v => v.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  const collator = new Intl.Collator('pt-BR', {numeric:true, sensitivity:'base'});
  function confirmAction(message, destructive) {
    if (typeof HTMLDialogElement === 'undefined') return Promise.resolve(window.confirm(message));
    return new Promise(resolve => {
      const dialog = document.createElement('dialog');
      dialog.className = 'confirmacao';
      dialog.setAttribute('aria-labelledby', 'confirmacaoTitulo');
      dialog.setAttribute('aria-describedby', 'confirmacaoMensagem');
      dialog.innerHTML = '<h2 id="confirmacaoTitulo"></h2><p id="confirmacaoMensagem"></p><div class="confirmacao-acoes"><button type="button" class="btn btn-neutro" autofocus>Cancelar</button><button type="button" class="btn"></button></div>';
      dialog.querySelector('h2').textContent = destructive ? 'Excluir registro?' : 'Registrar saída?';
      dialog.querySelector('p').textContent = message;
      const [cancel, accept] = dialog.querySelectorAll('button');
      accept.textContent = destructive ? 'Excluir' : 'Registrar saída';
      accept.classList.add(destructive ? 'btn-perigo' : 'btn-primario');
      cancel.addEventListener('click', () => dialog.close('cancel'));
      accept.addEventListener('click', () => dialog.close('confirm'));
      dialog.addEventListener('close', () => { const confirmed = dialog.returnValue === 'confirm'; dialog.remove(); resolve(confirmed); }, {once:true});
      document.body.append(dialog);
      dialog.showModal();
    });
  }
  const region = document.createElement('div');
  region.className = 'toasts'; region.setAttribute('aria-label','Notificações'); document.body.append(region);
  function toast(message, error = false, link = null) {
    const item = document.createElement('div'), text = document.createElement('span'), close = document.createElement('button');
    item.className = `toast ${error ? 'toast-erro' : 'toast-sucesso'}`;
    text.setAttribute('role',error ? 'alert' : 'status'); text.textContent = message; item.append(text);
    if (link) { const a = document.createElement('a'); a.href = link; a.textContent = 'Abrir página'; item.append(a); }
    close.type = 'button'; close.textContent = '×'; close.setAttribute('aria-label','Fechar notificação');
    close.addEventListener('click', () => item.remove()); item.append(close); region.append(item);
    if (!error && !link) {
      const timer = setTimeout(() => item.remove(),6500);
      ['mouseenter','focusin'].forEach(e => item.addEventListener(e, () => clearTimeout(timer)));
    }
  }
  function sortValue(cell,type) {
    const v = cell.textContent.trim();
    if (type === 'date') { const m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/); return m ? Number(m[3]+m[2]+m[1]) : null; }
    if (type === 'number') { const m = v.replace(/\./g,'').replace(',','.').match(/-?\d+(?:\.\d+)?/); return m ? Number(m[0]) : null; }
    return v;
  }
  function initialize() {
    document.querySelectorAll('.aviso-erro, [data-toast]').forEach(el => { toast(el.textContent.trim(),el.classList.contains('aviso-erro') || el.dataset.toast === 'erro'); el.remove(); });
    document.querySelectorAll('.barra-progresso > span[data-pct]').forEach(el => el.style.width = `${Math.min(100,Math.max(0,Number(el.dataset.pct)||0))}%`);
    document.querySelectorAll('.tabela-rolagem').forEach(el => {
      el.tabIndex = 0; el.setAttribute('role','region'); el.setAttribute('aria-label','Tabela: deslize para ver todas as colunas');
      const hint = document.createElement('p'); hint.className = 'tabela-dica';
      hint.textContent = 'Deslize a tabela para ver todas as colunas e ações.'; el.after(hint);
      const update = () => hint.hidden = el.scrollWidth <= el.clientWidth + 1;
      if (typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(update); observer.observe(el);
        tableObservers.push(observer);
      }
      update();
    });
    document.querySelectorAll('table.tabela').forEach(table => {
      const heads = [...table.querySelectorAll('thead th')], labels = heads.map(h => h.textContent.trim());
      heads.forEach((head,index) => {
        head.scope = 'col';
        if (!labels[index] || labels[index] === 'Ações') return;
        const type = /Entrada|Saída/.test(labels[index]) ? 'date' : /Capacidade|Volume|Ocupação|Prateleiras/.test(labels[index]) ? 'number' : 'text';
        head.setAttribute('aria-sort','none');
        const button = document.createElement('button'); button.type = 'button'; button.className = 'ordenar';
        button.textContent = labels[index]; button.setAttribute('aria-label',labels[index]); button.title = `Ordenar por ${labels[index]}`; head.replaceChildren(button);
        button.addEventListener('click', () => {
          const asc = head.getAttribute('aria-sort') !== 'ascending';
          heads.filter(h => h.hasAttribute('aria-sort')).forEach(h => h.setAttribute('aria-sort','none'));
          head.setAttribute('aria-sort',asc ? 'ascending' : 'descending');
          const rows = [...table.tBodies[0].rows];
          rows.sort((a,b) => {
            const x = sortValue(a.cells[index],type), y = sortValue(b.cells[index],type);
            if (x === null || y === null) return x === y ? 0 : x === null ? 1 : -1;
            return (typeof x === 'number' ? x-y : collator.compare(x,y)) * (asc ? 1 : -1);
          });
          table.tBodies[0].append(...rows);
        });
      });
      const search = [...document.querySelectorAll('[data-busca]')].find(el => el.dataset.busca === `#${table.id}`);
      if (!search) return;
      const count = search.dataset.contador ? document.querySelector(search.dataset.contador) : null;
      const empty = search.dataset.vazio ? document.querySelector(search.dataset.vazio) : null;
      count?.setAttribute('aria-live','polite');
      const filters = document.createElement('div'); filters.className = 'filtros'; const selections = [];
      labels.forEach((label,index) => {
        if (!['Galpão','Status','Situação'].includes(label) || (label === 'Galpão' && table.id === 'tabelaGalpoes')) return;
        const wrapper = document.createElement('label'), select = document.createElement('select'); wrapper.textContent = label;
        select.name = `filtro-${index}`; select.setAttribute('aria-label',label); select.add(new Option('Todos',''));
        const values = [...new Set([...table.tBodies[0].rows].map(row => row.cells[index].textContent.trim()))].sort(collator.compare);
        values.forEach(v => select.add(new Option(v,normalize(v))));
        if (label === 'Status' && table.id === 'tabelaOcupacoes') select.value = new URLSearchParams(location.search).get('status') || '';
        wrapper.append(select); filters.append(wrapper); selections.push({select,index});
      });
      const clear = document.createElement('button'); clear.type = 'button'; clear.className = 'btn btn-neutro'; clear.textContent = 'Limpar filtros';
      filters.append(clear); table.closest('.tabela-rolagem').before(filters);
      const apply = () => {
        const terms = normalize(search.value).split(/\s+/).filter(Boolean), rows = [...table.tBodies[0].rows]; let visible = 0;
        rows.forEach(row => {
          const text = normalize([...row.cells].slice(0,labels.length-1).map(c=>c.textContent).join(' '));
          row.hidden = !terms.every(term=>text.includes(term)) || !selections.every(({select,index})=>!select.value || normalize(row.cells[index].textContent) === select.value);
          if (!row.hidden) visible++;
        });
        if (count) count.textContent = visible === rows.length ? `${rows.length} ${rows.length === 1 ? 'registro' : 'registros'}` : `${visible} de ${rows.length} registros`;
        empty?.classList.toggle('oculto',visible>0);
      };
      search.addEventListener('input',apply); selections.forEach(({select})=>select.addEventListener('change',apply));
      clear.addEventListener('click',()=>{ search.value=''; selections.forEach(({select})=>select.value=''); apply(); search.focus(); }); apply();
    });
    document.querySelectorAll('form[action^="actions/"]').forEach(form => {
      form.noValidate = true;
      form.querySelectorAll('.campo label').forEach(label => {
        const field = form.querySelector(`#${CSS.escape(label.htmlFor)}`);
        if (!field || (!field.required && field.tagName === 'SELECT')) return;
        const note = document.createElement('span'); note.className = 'campo-obrigatorio';
        note.textContent = field.required ? ' *' : ' (opcional)'; label.append(note);
      });
      form.querySelectorAll('.campo input, .campo select, .campo textarea').forEach((field,index) => {
        const hint = document.createElement('span'); hint.className='campo-erro'; hint.id=`${field.id || 'campo'}-erro-${index}`;
        hint.setAttribute('aria-live','polite'); field.after(hint);
        field.setAttribute('aria-describedby',`${field.getAttribute('aria-describedby') || ''} ${hint.id}`.trim());
        field.validateField = () => {
          field.setCustomValidity('');
          if (field.required && !field.value.trim()) field.setCustomValidity('Preencha este campo.');
          if (['volume_m3','metros_cubicos'].includes(field.name)) {
            const selected = form.querySelector('[name="prateleira_id"], [name="galpao_id"]')?.selectedOptions[0];
            if (selected?.dataset.livre !== undefined && Number(field.value)>Number(selected.dataset.livre)) field.setCustomValidity('O volume excede o espaço disponível.');
          }
          const valid = field.validity.valid; hint.textContent = valid ? '' : field.validationMessage; field.setAttribute('aria-invalid',String(!valid)); return valid;
        };
        ['input','change','blur'].forEach(e=>field.addEventListener(e,field.validateField));
      });
      form.querySelector('[name="prateleira_id"], [name="galpao_id"]')?.addEventListener('change',()=>{const volume=form.querySelector('[name="volume_m3"], [name="metros_cubicos"]'); if(volume?.value) volume.validateField();});
      const capacitySelect = form.querySelector('select[name="prateleira_id"], select[name="galpao_id"]');
      if (capacitySelect?.querySelector('option[data-livre]')) {
        const hint = document.createElement('p'); hint.className = 'capacidade-dica'; hint.setAttribute('aria-live','polite');
        capacitySelect.closest('.campo').append(hint);
        const updateCapacity = () => {
          const available = capacitySelect.selectedOptions[0]?.dataset.livre;
          hint.hidden = available === undefined;
          hint.textContent = available === undefined ? '' : `Espaço disponível: ${Number(available).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})} m³`;
        };
        capacitySelect.addEventListener('change', updateCapacity); updateCapacity();
      }
    });
  }
  async function refresh(url) {
    const samePage = url.href === location.href;
    const filters = samePage ? [...document.querySelectorAll('[data-busca], .filtros select')].map(el => ({key: el.dataset.busca || el.name, value: el.value})) : [];
    const response = await fetch(url,{headers:{Accept:'text/html'}});
    if (!response.ok) throw new Error('refresh');
    const doc = new DOMParser().parseFromString(await response.text(),'text/html');
    if (!doc.querySelector('main.pagina')) throw new Error('refresh');
    tableObservers.forEach(observer => observer.disconnect()); tableObservers.length = 0;
    document.querySelector('main.pagina').replaceWith(doc.querySelector('main.pagina'));
    document.querySelector('.sidebar').replaceWith(doc.querySelector('.sidebar'));
    closeMenu();
    document.querySelector('.header-titulo, .header-esq h1').textContent = doc.querySelector('.header-titulo, .header-esq h1').textContent;
    document.title = doc.title;
    if (url.href !== location.href) history.pushState(null,'',url);
    initialize();
    filters.forEach(({key,value}) => {
      const el = [...document.querySelectorAll('[data-busca], .filtros select')].find(el => (el.dataset.busca || el.name) === key);
      if (el) { el.value = value; el.dispatchEvent(new Event(el.matches('select') ? 'change' : 'input')); }
    });
    const heading = document.querySelector('main h1'); if(heading) {heading.tabIndex=-1; heading.focus({preventScroll:true});}
  }
  const tableObservers = [];
  let sending = false, confirming = false;
  document.addEventListener('submit',async event => {
    const form = event.target.closest('form[action^="actions/"]'); if(!form) return;
    event.preventDefault(); if(sending || confirming) return;
    const fields = [...form.querySelectorAll('.campo input, .campo select, .campo textarea')];
    if (!fields.map(f=>f.validateField()).every(Boolean)) {fields.find(f=>!f.validity.valid)?.focus(); return;}
    const confirmation = form.querySelector('[data-confirmar]');
    if (confirmation) {
      confirming = true;
      let confirmed;
      try { confirmed = await confirmAction(confirmation.dataset.confirmar, form.action.includes('/excluir_')); }
      finally { confirming = false; }
      if (!confirmed) return;
    }
    sending=true; const buttons=[...form.querySelectorAll('button[type="submit"]')], data=new FormData(form);
    buttons.forEach(b=>b.disabled=true); form.setAttribute('aria-busy','true');
    try {
      const response=await fetch(form.action,{method:'POST',body:data,headers:{Accept:'application/json'}});
      if(!response.headers.get('content-type')?.includes('application/json')) throw new Error('response');
      const result=await response.json();
      if(!response.ok || !result.sucesso) {toast(result.mensagem || 'Não foi possível concluir a ação.',true,response.status===401 ? new URL(result.url,form.action) : null); return;}
      const destination=form.classList.contains('acao-inline') ? new URL(location.href) : new URL(result.url,form.action);
      try {await refresh(destination); toast(result.mensagem);}
      catch {buttons.forEach(b=>b.dataset.concluido='true'); toast(`${result.mensagem} Abra a página para ver os dados atualizados.`,false,destination);}
    } catch {toast('Não foi possível confirmar o resultado. Consulte a listagem antes de tentar novamente.',true);}
    finally {sending=false; form.removeAttribute('aria-busy'); buttons.forEach(b=>b.disabled=b.dataset.concluido==='true');}
  });
  window.addEventListener('popstate',()=>location.reload());
  const menu=document.getElementById('btnMenu');
  const compactMenu = window.matchMedia('(max-width: 1024px)');
  function syncSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) sidebar.inert = compactMenu.matches && !sidebar.classList.contains('aberta');
  }
  function closeMenu() {document.querySelector('.sidebar')?.classList.remove('aberta'); document.getElementById('overlay')?.classList.remove('visivel'); menu?.setAttribute('aria-expanded','false'); menu?.setAttribute('aria-label','Abrir menu'); syncSidebar();}
  menu?.addEventListener('click',()=>{const open=document.querySelector('.sidebar').classList.toggle('aberta'); document.getElementById('overlay').classList.toggle('visivel',open); menu.setAttribute('aria-expanded',String(open)); menu.setAttribute('aria-label',open?'Fechar menu':'Abrir menu'); syncSidebar();});
  compactMenu.addEventListener('change', closeMenu); syncSidebar();
  document.getElementById('overlay')?.addEventListener('click',closeMenu);
  const userButton=document.getElementById('btnUsuario'), userMenu=document.getElementById('usuarioMenu');
  function closeUser(){if(userMenu) userMenu.hidden=true; userButton?.setAttribute('aria-expanded','false');}
  userButton?.addEventListener('click',()=>{userMenu.hidden=!userMenu.hidden; userButton.setAttribute('aria-expanded',String(!userMenu.hidden));});
  document.addEventListener('click',e=>{if(!e.target.closest('#menuUsuario')) closeUser();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){
    if (document.querySelector('.sidebar.aberta')) {closeMenu(); menu?.focus();}
    if (userMenu && !userMenu.hidden) {closeUser(); userButton?.focus();}
  }});
  const themeButton=document.getElementById('alternarTema');
  function themeLabel(){const dark=document.documentElement.dataset.tema==='escuro'; themeButton?.setAttribute('aria-pressed',String(dark)); themeButton?.setAttribute('aria-label',dark?'Ativar modo claro':'Ativar modo escuro');}
  themeButton?.addEventListener('click',()=>{const theme=document.documentElement.dataset.tema==='escuro'?'claro':'escuro'; document.documentElement.dataset.tema=theme; try{localStorage.setItem('stocksense-tema',theme);}catch{} themeLabel();});
  const clock=document.getElementById('relogio');
  if(clock){const update=()=>clock.textContent=new Date().toLocaleString('pt-BR',{dateStyle:'short',timeStyle:'short'}); update(); setInterval(update,30000);}
  themeLabel(); initialize();
})();
