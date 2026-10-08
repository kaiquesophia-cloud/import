/* global DLF */
(function () {
	'use strict';

	// Consulta de disponibilidade na página do equipamento.
	document.addEventListener('click', function (ev) {
		var btn = ev.target.closest('.dl-av-btn');
		if (!btn) { return; }
		var box = btn.closest('.dl-detail');
		var id = box.getAttribute('data-equip');
		var start = box.querySelector('.dl-av-start').value;
		var end = box.querySelector('.dl-av-end').value;
		var out = box.querySelector('.dl-av-result');
		if (!start || !end || end < start) {
			out.textContent = 'Confira as datas.';
			return;
		}
		out.textContent = 'Consultando...';
		// DLF.api pode já ter "?" (links permanentes simples usam ?rest_route=).
		var url = DLF.api + 'equipamentos/' + id + '/disponibilidade';
		url += (url.indexOf('?') === -1 ? '?' : '&') + 'inicio=' + encodeURIComponent(start) + '&fim=' + encodeURIComponent(end);
		fetch(url)
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (d.code) { out.textContent = d.message || 'Não foi possível consultar.'; return; }
				var txt = d.disponivel ? '✔ Disponível para ' + d.dias + ' dia(s).' : '✖ Sem disponibilidade nesse período — fale com a gente para alternativas.';
				if (d.disponivel && d.preco_fmt) { txt += ' Valor estimado: ' + d.preco_fmt + ' (' + d.composicao + ').'; }
				out.textContent = txt;
				out.className = 'dl-av-result ' + (d.disponivel ? 'ok' : 'no');
			})
			.catch(function () { out.textContent = 'Não foi possível consultar agora.'; });
	});

	// Formulário de orçamento: adicionar mais equipamentos.
	document.addEventListener('click', function (ev) {
		var add = ev.target.closest('.dl-quote-add');
		if (!add) { return; }
		var wrap = add.parentNode.querySelector('.dl-quote-items');
		var first = wrap.querySelector('.dl-quote-item');
		var clone = first.cloneNode(true);
		clone.querySelector('select').value = '';
		clone.querySelector('select').required = false;
		clone.querySelector('input').value = 1;
		wrap.appendChild(clone);
	});
}());
