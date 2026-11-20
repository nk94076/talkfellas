const $ = s => document.querySelector(s);
// ---------- Uploads ----------
function upload(done) {
  const i = document.createElement('input'); i.type = 'file'; i.accept = 'image/*';
  i.onchange = async () => { const f = new FormData(); f.append('file', i.files[0]); f.append('csrf', window.CSRF);
    try { const r = await (await fetch('/admin/?a=upload', { method: 'POST', body: f })).json(); r.url ? done(r.url) : alert(r.error); } catch (e) { alert('Upload failed'); } };
  i.click();
}
function pick(btn) { const w = btn.closest('.imgf'); upload(url => { w.querySelector('input').value = url; w.querySelector('.prev').src = url; }); }
document.addEventListener('input', e => { const w = e.target.closest('.imgf'); if (w && e.target.matches('input')) w.querySelector('.prev').src = e.target.value; });

// ---------- Rich text (falls back to HTML textarea if editor lib can't load) ----------
const editors = [];
document.querySelectorAll('.rte').forEach(el => {
  if (window.Quill) {
    const q = new Quill(el, { theme: 'snow', modules: { toolbar: [[{ header: [2, 3, 4, false] }], ['bold', 'italic', 'underline', 'strike'], [{ list: 'ordered' }, { list: 'bullet' }], ['link', 'image', 'video', 'blockquote', 'code-block'], [{ align: [] }], ['clean']] } });
    q.getModule('toolbar').addHandler('image', () => upload(url => { const r = q.getSelection(true); q.insertEmbed(r.index, 'image', url); }));
    editors.push([el.dataset.name, () => q.root.innerHTML === '<p><br></p>' ? '' : q.root.innerHTML]);
    q.on('text-change', seo);
  } else {
    const t = document.createElement('textarea'); t.rows = 12; t.className = 'mono'; t.value = el.innerHTML.trim(); t.placeholder = 'HTML: <h2>, <p>, <ul>, <a>, <img>…'; el.replaceWith(t);
    t.addEventListener('input', seo); editors.push([el.dataset.name, () => t.value]);
  }
});

// ---------- Products repeater ----------
const box = $('#products'), tpl = $('#ptpl');
function renum() { if (!box) return; box.querySelectorAll('.prod').forEach((p, i) => { p.querySelector('.num').textContent = '#' + (i + 1); p.querySelectorAll('[data-k]').forEach(f => f.name = `pr[${i}][${f.dataset.k}]`); }); }
function addProduct(d = {}) { const n = tpl.content.firstElementChild.cloneNode(true); n.querySelectorAll('[data-k]').forEach(f => f.value = d[f.dataset.k] ?? ''); n.querySelector('.prev').src = d.image || ''; box.appendChild(n); renum(); }
if (box && window.PRODUCTS) {
  window.PRODUCTS.forEach(addProduct);
  let drag; box.addEventListener('dragstart', e => drag = e.target.closest('.prod'));
  box.addEventListener('dragover', e => { e.preventDefault(); const t = e.target.closest('.prod'); if (t && drag && t !== drag) { const r = t.getBoundingClientRect(); box.insertBefore(drag, e.clientY < r.top + r.height / 2 ? t : t.nextSibling); } });
  box.addEventListener('dragend', renum);
  box.addEventListener('mousedown', e => { const p = e.target.closest('.prod'); if (p) p.draggable = !e.target.matches('input,textarea'); });
}

// ---------- SEO preview & checks ----------
const slugify = s => s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
function seo() {
  if (!$('#f-title')) return;
  const title = $('#f-title').value, slug = $('#f-slug').value || slugify(title), mt = $('#f-mt').value || title, md = $('#f-md').value || ($('#f-excerpt') ? $('#f-excerpt').value : '');
  const full = mt + ' ' + window.SEP + ' ' + window.SITE;
  $('#slug-prev').textContent = slug; $('#serp-slug').textContent = slug;
  $('#serp-title').textContent = full.length > 60 ? full.slice(0, 58) + '…' : full;
  $('#serp-desc').textContent = md ? (md.length > 158 ? md.slice(0, 155) + '…' : md) : 'Add a meta description or excerpt…';
  $('#mt-count').textContent = `(${full.length}/60)`; $('#md-count').textContent = `(${md.length}/160)`;
  const kw = ($('[name=focus_kw]').value || '').toLowerCase().trim();
  const body = editors.map(([, g]) => g()).join(' '), text = body.replace(/<[^>]+>/g, ' '), words = text.split(/\s+/).filter(Boolean).length;
  const img = $('[name=image]').value, alt = $('[name=image_alt]').value;
  const c = [
    [full.length >= 30 && full.length <= 65, `SEO title length ${full.length} (aim 30–65)`],
    [md.length >= 70 && md.length <= 160, `Meta description length ${md.length} (aim 70–160)`],
    [words >= 300, `Content length: ${words} words (aim 300+)`],
    [/<h2/i.test(body), 'Uses H2 sub-headings'],
    [!!img, 'Has a featured image'], [!img || !!alt, 'Featured image has alt text'],
    [/<a [^>]*href=["'](\/|https?:\/\/(www\.)?talkfellas)/i.test(body), 'Has an internal link'],
  ];
  if (kw) c.unshift([mt.toLowerCase().includes(kw), 'Focus keyword in SEO title'], [slug.includes(slugify(kw)), 'Focus keyword in URL'], [md.toLowerCase().includes(kw), 'Focus keyword in meta description'], [text.toLowerCase().slice(0, 1000).includes(kw), 'Focus keyword in first paragraph']);
  $('#seo-checks').innerHTML = c.map(([ok, t]) => `<li class="${ok ? 'ok' : 'bad'}">${ok ? '✓' : '✗'} ${t}</li>`).join('');
}
document.addEventListener('input', e => { if (e.target.closest('#editor')) seo(); }); seo();

// ---------- Save ----------
const form = $('#editor');
if (form) form.addEventListener('submit', () => { renum(); editors.forEach(([n, get]) => { const h = form.querySelector(`input[name=${n}]`); if (h) h.value = get(); }); });
