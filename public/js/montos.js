/**
 * Puntos de mil en los campos numéricos del admin, mientras se escribe.
 * El <input type="number"> original queda escondido con el valor limpio (89000.5),
 * que es el que se envía. Se muestra al lado un campo de texto con el formato 89.000,5.
 * Para excluir un campo: data-sin-mil
 */
(function () {
    const nativo = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

    /** "89000.50" → "89.000,5" (o "89.000" si los decimales son 0) */
    function mostrar(valor, decimales) {
        let s = String(valor ?? '').trim();
        if (s === '' || isNaN(Number(s))) return '';

        const neg = s.startsWith('-');
        if (neg) s = s.slice(1);

        let [ent, dec] = s.split('.');
        ent = (ent || '0').replace(/^0+(?=\d)/, '');
        dec = decimales > 0 && dec ? dec.slice(0, decimales).replace(/0+$/, '') : '';

        return (neg ? '-' : '') + ent.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (dec ? ',' + dec : '');
    }

    function mejorar(orig) {
        if (orig.dataset.mil || orig.type !== 'number' || orig.hasAttribute('data-sin-mil')) return;
        orig.dataset.mil = '1';

        const paso       = orig.getAttribute('step') || '';
        const decimales  = paso.includes('.') ? paso.split('.')[1].length : (paso === 'any' ? 2 : 0);
        const permiteNeg = orig.min === '' || parseFloat(orig.min) < 0;

        // Campo visible
        const vis = document.createElement('input');
        vis.type         = 'text';
        vis.inputMode    = decimales > 0 ? 'decimal' : 'numeric';
        vis.autocomplete = 'off';
        vis.className    = orig.className;
        vis.style.cssText = orig.style.cssText;
        vis.placeholder  = orig.placeholder;
        vis.title        = orig.title;
        vis.required     = orig.required;
        vis.readOnly     = orig.readOnly;
        vis.disabled     = orig.disabled;
        vis.value        = mostrar(nativo.get.call(orig), decimales);

        // El original queda escondido y sin validaciones del navegador
        // (si no, un campo escondido inválido frena el envío sin avisar; el servidor ya valida)
        orig.required = false;
        orig.removeAttribute('min');
        orig.removeAttribute('max');
        orig.setAttribute('step', 'any');
        orig.style.display = 'none';
        orig.parentNode.insertBefore(vis, orig);

        // Si un script cambia el valor del original, se refleja en el visible
        Object.defineProperty(orig, 'value', {
            configurable: true,
            get() { return nativo.get.call(this); },
            set(v) {
                nativo.set.call(this, v);
                vis.value = mostrar(v, decimales);
            },
        });
        orig.focus = () => vis.focus();
        orig.select = () => vis.select();

        // Mientras escribe
        vis.addEventListener('input', () => {
            let v = vis.value;

            // Un punto al final, sin coma todavía: es el decimal (teclados con punto)
            if (decimales > 0 && !v.includes(',') && /\.$/.test(v) && !/\.\d{3}\.$/.test(v)) {
                v = v.slice(0, -1) + ',';
            }

            const neg   = permiteNeg && v.trim().startsWith('-');
            const caret = vis.selectionStart ?? v.length;
            const antes = v.slice(0, caret).replace(/[^\d,]/g, '').length;

            const limpio    = v.replace(/[^\d,]/g, '');
            const tieneComa = decimales > 0 && limpio.includes(',');
            let [ent, ...resto] = limpio.split(',');
            const dec = resto.join('').slice(0, decimales);
            ent = ent.replace(/^0+(?=\d)/, '');

            const texto = (neg ? '-' : '') + ent.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (tieneComa ? ',' + dec : '');
            vis.value = texto;

            // Dejar el cursor donde estaba (contando solo dígitos y coma)
            let pos = 0, cuenta = 0;
            while (pos < texto.length && cuenta < antes) {
                if (/[\d,]/.test(texto[pos])) cuenta++;
                pos++;
            }
            if (neg && pos === 0) pos = 1;
            try { vis.setSelectionRange(pos, pos); } catch (e) {}

            // Valor limpio al original, y avisar a los scripts que lo escuchan
            const numero = ent === '' && !dec ? '' : (neg ? '-' : '') + (ent || '0') + (dec ? '.' + dec : '');
            nativo.set.call(orig, numero);
            orig.dispatchEvent(new Event('input', { bubbles: true }));
        });

        vis.addEventListener('change', () => orig.dispatchEvent(new Event('change', { bubbles: true })));

        // Al salir del campo, prolijar (ej: "1.250," → "1.250")
        vis.addEventListener('blur', () => { vis.value = mostrar(nativo.get.call(orig), decimales); });

        // form.reset()
        if (orig.form) {
            orig.form.addEventListener('reset', () => setTimeout(() => {
                vis.value = mostrar(nativo.get.call(orig), decimales);
            }));
        }
    }

    function mejorarTodo(raiz) {
        (raiz.querySelectorAll ? raiz.querySelectorAll('input[type="number"]') : []).forEach(mejorar);
        if (raiz.matches && raiz.matches('input[type="number"]')) mejorar(raiz);
    }

    document.addEventListener('DOMContentLoaded', () => {
        mejorarTodo(document);

        // Campos que se agregan después (vistas previas, modales armados con JS)
        new MutationObserver(cambios => {
            cambios.forEach(c => c.addedNodes.forEach(n => { if (n.nodeType === 1) mejorarTodo(n); }));
        }).observe(document.body, { childList: true, subtree: true });
    });
})();