// Rich text editors (falls back to plain HTML textarea if the editor library can't load)
const editors = [];
document.querySelectorAll('.rte').forEach(el => {
  if (window.Quill) {
    const q = new Quill(el, { theme: 'snow', modules: { toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link', 'image', 'blockquote'], ['clean']] } });
    q.getModule('toolbar').addHandler('image', () => upload(url => { const r = q.getSelection(true); q.insertEmbed(r.index, 'image', url); }));
    editors.push([el.dataset.name, () => q.root.innerHTML === '<p><br></p>' ? '' : q.root.innerHTML]);
  } else {
    const t = document.createElement('textarea'); t.rows = 10; t.value = el.innerHTML.trim(); t.placeholder = 'HTML allowed: <h2>, <p>, <ul>, <a>, <img>…'; el.replaceWith(t);
    editors.push([el.dataset.name, () => t.value]);
  }
});
// Products repeater
const box = document.getElementById('products'), tpl = document.getElementById('ptpl');
function renum() { box.querySelectorAll('.prod').forEach((p, i) => { p.querySelector('.num').textContent = '#' + (i + 1); p.querySelectorAll('[data-k]').forEach(f => f.name = `pr[${i}][${f.dataset.k}]`); }); }
function addProduct(d = {}) {
  const n = tpl.content.firstElementChild.cloneNode(true);
  n.querySelectorAll('[data-k]').forEach(f => f.value = d[f.dataset.k] ?? '');
  const img = n.querySelector('[data-k=image]'); n.querySelector('.prev').src = img.value;
  img.addEventListener('input', () => n.querySelector('.prev').src = img.value);
  box.appendChild(n); renum();
}
if (box) { (window.PRODUCTS.length ? window.PRODUCTS : [{}]).forEach(addProduct);
  let drag; box.addEventListener('dragstart', e => { drag = e.target.closest('.prod'); });
  box.addEventListener('dragover', e => { e.preventDefault(); const t = e.target.closest('.prod'); if (t && t !== drag) { const r = t.getBoundingClientRect(); box.insertBefore(drag, e.clientY < r.top + r.height / 2 ? t : t.nextSibling); } });
  box.addEventListener('dragend', renum);
  box.addEventListener('mousedown', e => { const p = e.target.closest('.prod'); if (p) p.draggable = !e.target.matches('input,textarea'); });
}
// Uploads
function upload(done) {
  const i = document.createElement('input'); i.type = 'file'; i.accept = 'image/*';
  i.onchange = async () => { const f = new FormData(); f.append('file', i.files[0]); f.append('csrf', window.CSRF);
    const r = await (await fetch('/admin/?a=upload', { method: 'POST', body: f })).json(); r.url ? done(r.url) : alert(r.error); };
  i.click();
}
function pick(btn) { const w = btn.closest('.imgf'); upload(url => { w.querySelector('input').value = url; w.querySelector('.prev').src = url; }); }
// Save
document.getElementById('editor').addEventListener('submit', () => { renum(); editors.forEach(([n, get]) => document.querySelector(`input[name=${n}]`).value = get()); });
