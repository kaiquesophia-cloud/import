/* Sistema Dimaq Locações — aplicação de tela única (Vue 3 sem etapa de build). */
/* global Vue, Chart, DIMAQ */
(function () {
	'use strict';

	const { createApp, reactive, ref, computed, watch, onMounted, onUnmounted, nextTick } = Vue;
	const D = window.DIMAQ;

	/* ================================================================ base */

	const store = reactive({
		route: { name: 'painel', params: {}, query: {} },
		toasts: [],
		modal: null,
		tick: 0,
		sidebar: false,
		counts: Object.assign({ solicitacoes: 0, atrasados: 0 }, D.contagens || {}),
	});

	function toast(message, type) {
		const t = { id: Math.random(), message: message, type: type || 'success' };
		store.toasts.push(t);
		setTimeout(function () { store.toasts.splice(store.toasts.indexOf(t), 1); }, type === 'error' ? 7000 : 4200);
	}

	function qs(params) {
		const p = Object.keys(params || {}).filter(function (k) { return params[k] !== '' && params[k] !== null && params[k] !== undefined; });
		return p.length ? p.map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); }).join('&') : '';
	}

	async function api(method, path, body, query) {
		let url = D.api + path;
		const q = qs(query);
		if (q) { url += (url.indexOf('?') === -1 ? '?' : '&') + q; }
		const res = await fetch(url, {
			method: method,
			credentials: 'same-origin',
			headers: Object.assign({ 'X-WP-Nonce': D.nonce }, body ? { 'Content-Type': 'application/json' } : {}),
			body: body ? JSON.stringify(body) : undefined,
		});
		let data = null;
		try { data = await res.json(); } catch (e) { data = null; }
		if (res.status === 401 || res.status === 403) {
			if (data && data.code === 'rest_cookie_invalid_nonce') { location.reload(); }
		}
		if (!res.ok) {
			throw new Error((data && data.message) || 'Erro de comunicação (' + res.status + ').');
		}
		if (data && data.avisos) {
			data.avisos.forEach(function (a) { toast(a.message, a.type === 'error' ? 'error' : 'warning'); });
		}
		return data;
	}
	const get = function (p, q) { return api('GET', p, null, q); };
	const post = function (p, b) { return api('POST', p, b || {}); };

	/* ---------- formatos ---------- */
	const fmt = {
		money: function (v) { return 'R$ ' + (Number(v) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
		num: function (v, d) { return (Number(v) || 0).toLocaleString('pt-BR', { minimumFractionDigits: d || 0, maximumFractionDigits: d || 0 }); },
		date: function (s) { if (!s || s.slice(0, 4) === '0000') { return ''; } const p = s.slice(0, 10).split('-'); return p[2] + '/' + p[1] + '/' + p[0]; },
		short: function (s) { if (!s) { return ''; } const p = s.slice(0, 10).split('-'); return p[2] + '/' + p[1]; },
	};
	function addDays(s, n) { const d = new Date(s + 'T12:00:00'); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); }
	function daysBetween(a, b) { return Math.round((new Date(b + 'T12:00:00') - new Date(a + 'T12:00:00')) / 86400000); }
	/** Dias cobrados; a Dimaq conta a retirada e a devolução (08/10 a 06/11 = 30 dias). */
	function rentalDays(a, b) { return Math.max(1, daysBetween(a, b) + (D.inclusivo ? 1 : 0)); }
	function endFor(start, days) { return addDays(start, days - (D.inclusivo ? 1 : 0)); }
	function statusLabel(entity, s) { return (D.status[entity] || {})[s] || s; }
	function debounce(fn, ms) { let t; return function () { const a = arguments, self = this; clearTimeout(t); t = setTimeout(function () { fn.apply(self, a); }, ms); }; }
	function greeting() { const h = new Date().getHours(); return h < 12 ? 'Bom dia' : (h < 18 ? 'Boa tarde' : 'Boa noite'); }
	function todayLong() { const t = new Date().toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }); return t.charAt(0).toUpperCase() + t.slice(1); }

	function downloadCsv(name, rows) {
		const esc = function (v) { v = String(v === null || v === undefined ? '' : v); if (/^[=+\-@]/.test(v)) { v = "'" + v; } return /[";\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; };
		const csv = '﻿' + rows.map(function (r) { return r.map(esc).join(';'); }).join('\n');
		const a = document.createElement('a');
		a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
		a.download = name;
		a.click();
		setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
	}

	/* ---------- ícones (traço 2px) ---------- */
	const ICONS = {
		home: 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
		file: 'M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8zM14 3v5h5M9 13h6M9 17h6',
		calendar: 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
		truck: 'M3 6h11v10H3zM14 9h4l3 3v4h-7M7 19a2 2 0 1 0 0-.01M17 19a2 2 0 1 0 0-.01',
		users: 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c0-4 3-6 7-6s7 2 7 6M16 3a4 4 0 0 1 0 8M22 21c0-3-2-5-4-5.6',
		box: 'M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10',
		tool: 'M14.7 6.3a4 4 0 0 0 5 5L12 19a2.8 2.8 0 0 1-4-4l7.7-7.7zM5 21l3-3',
		cart: 'M3 4h2l2.4 11h11l2-8H6.2M9 20a1 1 0 1 0 0-.01M18 20a1 1 0 1 0 0-.01',
		money: 'M3 6h18v12H3zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6 9v.01M18 15v.01',
		chart: 'M4 20V10M10 20V4M16 20v-7M22 20H2',
		receipt: 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6',
		gear: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 7 19.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 14H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.7 7l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 3v0a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.7 1.7 0 0 0 21 10h0a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z',
		search: 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM21 21l-5-5',
		plus: 'M12 5v14M5 12h14',
		menu: 'M4 6h16M4 12h16M4 18h16',
		x: 'M6 6l12 12M18 6L6 18',
		check: 'M5 12l5 5L20 7',
		alert: 'M12 9v4M12 17v.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
		clock: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 3',
		pin: 'M12 21s-7-6.3-7-11a7 7 0 0 1 14 0c0 4.7-7 11-7 11zM12 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
		wa: 'M3 21l1.6-4.8A8.5 8.5 0 1 1 8 19.5zM9 8.5c0 3.5 3 6.5 6.5 6.5l1.2-1.6-2-1-1 .8a5 5 0 0 1-2.4-2.4l.8-1-1-2z',
		back: 'M15 18l-6-6 6-6',
		out: 'M14 4h6v16h-6M10 16l-4-4 4-4M6 12h10',
		in: 'M10 4H4v16h6M14 16l4-4-4-4M18 12H8',
		refresh: 'M20 11a8 8 0 0 0-14.9-3.9L4 8M4 4v4h4M4 13a8 8 0 0 0 14.9 3.9L20 16M20 20v-4h-4',
		grid: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
		list: 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
		board: 'M4 4h4v16H4zM10 4h4v10h-4zM16 4h4v13h-4z',
		print: 'M6 9V3h12v6M6 18H4v-7h16v7h-2M8 14h8v7H8z',
		mail: 'M3 6h18v12H3zM3 6l9 7 9-7',
		copy: 'M9 9h11v11H9zM5 15H4V4h11v1',
		trash: 'M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3',
		edit: 'M4 20h4L19 9l-4-4L4 16zM14 6l4 4',
		down: 'M12 4v12M6 12l6 6 6-6M4 20h16',
		gauge: 'M12 21a9 9 0 1 1 9-9M12 12l4-4',
		star: 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z',
		link: 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1',
		camera: 'M4 8h3l2-3h6l2 3h3v11H4zM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
	};
	const Ic = {
		props: { n: String },
		template: '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="d"/></svg>',
		computed: { d: function () { return ICONS[this.n] || ''; } },
	};

	/* ---------- rotas ---------- */
	const ROUTES = [
		['painel', /^$|^painel$/],
		['contratos-novo', /^contratos\/novo$/],
		['contrato-editar', /^contratos\/(\d+)\/editar$/, ['id']],
		['contrato', /^contratos\/(\d+)$/, ['id']],
		['contratos', /^contratos$/],
		['frota', /^frota$/],
		['relatorios', /^relatorios(?:\/([a-z_]+))?$/, ['key']],
		['configuracoes', /^configuracoes$/],
		['registro-novo', /^([a-z_]+)\/novo$/, ['module']],
		['registro', /^([a-z_]+)\/(\d+)$/, ['module', 'id']],
		['lista', /^([a-z_]+)$/, ['module']],
	];
	function parseRoute() {
		const h = decodeURIComponent(location.hash.replace(/^#\/?/, ''));
		const parts = h.split('?');
		const path = parts[0].replace(/\/$/, '');
		const query = {};
		(parts[1] || '').split('&').filter(Boolean).forEach(function (kv) { const p = kv.split('='); query[p[0]] = p.slice(1).join('='); });
		for (let i = 0; i < ROUTES.length; i++) {
			const m = path.match(ROUTES[i][1]);
			if (m) {
				const params = {};
				(ROUTES[i][2] || []).forEach(function (k, j) { params[k] = m[j + 1]; });
				if (params.module && !D.modulos[params.module]) { break; }
				return { name: ROUTES[i][0], params: params, query: query, path: path };
			}
		}
		return { name: 'painel', params: {}, query: query, path: '' };
	}
	function go(route) { location.hash = '#/' + route; }
	window.addEventListener('hashchange', function () { store.route = parseRoute(); store.sidebar = false; window.scrollTo(0, 0); });
	store.route = parseRoute();

	/* ---------- componentes básicos ---------- */
	const Badge = {
		props: { e: String, s: String },
		template: '<span class="badge" :class="\'b-\' + s">{{ label }}</span>',
		computed: { label: function () { return statusLabel(this.e, this.s); } },
	};

	const Modal = {
		props: { title: String, wide: Boolean },
		emits: ['close'],
		components: { Ic: Ic },
		template: `<div class="overlay" @mousedown.self="$emit('close')">
			<div class="modal" :class="{ wide: wide }" role="dialog" aria-modal="true">
				<header><h2>{{ title }}</h2><button class="btn btn-ghost btn-sm x" @click="$emit('close')" aria-label="Fechar"><Ic n="x"/></button></header>
				<div class="body"><slot/></div>
				<footer v-if="$slots.footer"><slot name="footer"/></footer>
			</div></div>`,
		mounted: function () { this.esc = (e) => { if (e.key === 'Escape') { this.$emit('close'); } }; document.addEventListener('keydown', this.esc); },
		unmounted: function () { document.removeEventListener('keydown', this.esc); },
	};

	/** Campo com busca em uma tabela (cliente, equipamento, produto...). */
	const Autocomplete = {
		props: { table: String, modelValue: [Number, String], label: String, placeholder: String, allowNew: Boolean, clearable: { type: Boolean, default: true } },
		emits: ['update:modelValue', 'select', 'new'],
		template: `<div class="ac">
			<input class="input" :placeholder="placeholder || 'Buscar...'" v-model="text" @focus="open" @input="search" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="pick(items[sel])" @blur="close">
			<button v-if="clearable && modelValue && modelValue !== '0'" type="button" class="clear" @mousedown.prevent="clear" aria-label="Limpar">×</button>
			<div class="list" v-if="show && (items.length || allowNew)">
				<div v-for="(it, i) in items" :key="it.id" :class="{ sel: i === sel }" @mousedown.prevent="pick(it)">{{ it.label }}<small v-if="it.sub">{{ it.sub }}</small></div>
				<div v-if="allowNew" class="new" @mousedown.prevent="$emit('new', text)">+ Cadastrar novo</div>
			</div></div>`,
		data: function () { return { text: this.label || '', items: [], show: false, sel: 0 }; },
		watch: { label: function (v) { this.text = v || ''; } },
		created: function () { this.search = debounce(this.load, 220); },
		methods: {
			load: async function () {
				const seq = (this.seq = (this.seq || 0) + 1);
				try {
					const r = await get('lookup/' + this.table, { q: this.text });
					if (seq !== this.seq) { return; } // resposta de uma busca já superada
					this.items = r.itens; this.sel = 0; this.show = true;
				} catch (e) { this.items = []; }
			},
			open: function () { if (!this.items.length) { this.load(); } else { this.show = true; } },
			close: function () { setTimeout(() => { this.show = false; this.text = this.label || (this.modelValue && this.modelValue !== '0' ? this.text : ''); }, 120); },
			move: function (d) { this.sel = Math.max(0, Math.min(this.items.length - 1, this.sel + d)); },
			pick: function (it) { if (!it) { return; } this.text = it.label; this.show = false; this.$emit('update:modelValue', it.id); this.$emit('select', it); },
			clear: function () { this.text = ''; this.$emit('update:modelValue', 0); this.$emit('select', null); },
		},
	};

	/** Gráfico Chart.js. */
	const ChartBox = {
		props: { type: String, labels: Array, datasets: Array, options: Object, height: { type: Number, default: 240 } },
		template: '<div class="chart-box" :style="{ height: height + \'px\' }"><canvas ref="c"></canvas></div>',
		mounted: function () { this.draw(); },
		unmounted: function () { if (this.chart) { this.chart.destroy(); } },
		watch: { datasets: { deep: true, handler: function () { this.draw(); } }, labels: function () { this.draw(); } },
		methods: {
			scales: function (money) {
				if (this.type === 'doughnut') { return {}; }
				const horizontal = this.options && this.options.indexAxis === 'y';
				const value = { beginAtZero: true, grid: { color: '#f0f1f3' }, ticks: { callback: function (v) { return money ? 'R$ ' + fmt.num(v) : fmt.num(v); } } };
				const cat = { grid: { display: false } };
				return horizontal ? { x: value, y: cat } : { y: value, x: cat };
			},
			draw: function () {
				if (!window.Chart) { return; }
				if (this.chart) { this.chart.destroy(); }
				const money = this.options && this.options.money;
				this.chart = new Chart(this.$refs.c, {
					type: this.type,
					data: { labels: this.labels, datasets: this.datasets },
					options: Object.assign({
						responsive: true, maintainAspectRatio: false, animation: { duration: 500 },
						plugins: {
							legend: { display: this.type === 'doughnut' || (this.datasets || []).length > 1, position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
							tooltip: { callbacks: { label: function (c) { const v = c.parsed && c.parsed.y !== undefined ? c.parsed.y : c.parsed; return (c.dataset.label ? c.dataset.label + ': ' : '') + (money ? fmt.money(v) : fmt.num(v, 1)); } } },
						},
						scales: this.scales(money),
					}, this.options || {}),
				});
			},
		},
	};
	const PALETTE = ['#f5a400', '#2b2b2b', '#2563eb', '#16a34a', '#7c3aed', '#dc2626', '#0891b2', '#ea580c', '#64748b', '#db2777'];

	/* ============================================================ formulários */

	function displayValue(f, v, labels, name) {
		if (v === null || v === undefined || v === '') { return '—'; }
		switch (f.type) {
			case 'money': case 'readonly_money': return fmt.money(v);
			case 'decimal': return fmt.num(v, 2);
			case 'date': case 'readonly_date': return fmt.date(v);
			case 'checkbox': return Number(v) ? 'Sim' : 'Não';
			case 'relation': case 'readonly_relation': return (labels && labels[name]) || '—';
			case 'select': case 'readonly_status': {
				const o = (f.options || []).find(function (x) { return x.value === String(v); });
				return o ? o.label : (f.badge ? statusLabel(f.badge, v) : v);
			}
			default: return v;
		}
	}

	async function uploadImage(file) {
		const fd = new FormData();
		fd.append('file', file, file.name);
		const res = await fetch(D.wpApi + 'media', { method: 'POST', credentials: 'same-origin', headers: { 'X-WP-Nonce': D.nonce }, body: fd });
		const data = await res.json();
		if (!res.ok) { throw new Error(data.message || 'Falha ao enviar a imagem.'); }
		return { id: data.id, url: (data.media_details && data.media_details.sizes && data.media_details.sizes.medium ? data.media_details.sizes.medium.source_url : data.source_url) };
	}

	const FieldInput = {
		components: { Autocomplete: Autocomplete, Badge: Badge, Ic: Ic },
		props: { name: String, f: Object, modelValue: null, labels: Object, isNew: Boolean },
		emits: ['update:modelValue', 'cep', 'label'],
		template: `<label class="field" :class="{ full: f.type === 'textarea' || f.width === 'full', check: f.type === 'checkbox' }">
			<template v-if="f.type === 'checkbox'">
				<input type="checkbox" :checked="Number(modelValue) === 1" @change="$emit('update:modelValue', $event.target.checked ? 1 : 0)"><span>{{ f.label }}</span>
			</template>
			<template v-else>
				<span>{{ f.label }}<b v-if="f.required" style="color:#dc2626"> *</b></span>
				<div v-if="ro" class="readonly"><Badge v-if="f.badge && modelValue" :e="f.badge" :s="modelValue"/><template v-else>{{ shown }}</template></div>
				<textarea v-else-if="f.type === 'textarea'" rows="3" :value="modelValue" @input="up($event.target.value)"></textarea>
				<select v-else-if="f.type === 'select'" :value="String(modelValue === null ? '' : modelValue)" @change="up($event.target.value)">
					<option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
				</select>
				<Autocomplete v-else-if="f.type === 'relation'" :table="f.rel" :model-value="modelValue" :label="labels && labels[name]" @update:model-value="up" @select="(it) => $emit('label', it ? it.label : '')"/>
				<div v-else-if="f.type === 'media'" class="quick" style="align-items:center">
					<img v-if="img" :src="img" style="width:72px;height:72px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb">
					<label class="btn btn-sm"><Ic n="camera"/> {{ uploading ? 'Enviando...' : (img ? 'Trocar foto' : 'Enviar foto') }}<input type="file" accept="image/*" hidden @change="upload"></label>
					<button v-if="img" type="button" class="btn btn-sm btn-ghost" @click="up(0); $emit('label', '')">remover</button>
				</div>
				<input v-else :type="inputType" :step="step" :min="f.type === 'money' ? 0 : null" :value="modelValue" @input="up($event.target.value)" @blur="blur" :inputmode="f.type === 'document' || f.type === 'cep' ? 'numeric' : null" :placeholder="f.type === 'cep' ? '00000-000' : ''">
				<small v-if="f.help" class="help">{{ f.help }}</small>
			</template>
		</label>`,
		data: function () { return { uploading: false }; },
		computed: {
			ro: function () { return this.f.type.indexOf('readonly') === 0 || (this.f.readonly_edit && !this.isNew); },
			shown: function () { return displayValue(this.f, this.modelValue, this.labels, this.name); },
			img: function () { return Number(this.modelValue) ? (this.labels && this.labels[this.name]) : ''; },
			inputType: function () { return { money: 'number', decimal: 'number', int: 'number', date: 'date', email: 'email', url: 'url', tel: 'tel' }[this.f.type] || 'text'; },
			step: function () { return { money: '0.01', decimal: '0.001', int: '1' }[this.f.type] || null; },
		},
		methods: {
			up: function (v) { this.$emit('update:modelValue', v); },
			blur: function (e) {
				if (this.f.type === 'cep') {
					const cep = String(e.target.value).replace(/\D/g, '');
					if (cep.length === 8) {
						fetch('https://viacep.com.br/ws/' + cep + '/json/').then(function (r) { return r.json(); }).then((d) => { if (!d.erro) { this.$emit('cep', d); } }).catch(function () {});
					}
				}
			},
			upload: async function (e) {
				const file = e.target.files[0];
				if (!file) { return; }
				this.uploading = true;
				try { const r = await uploadImage(file); this.$emit('label', r.url); this.up(r.id); } catch (err) { toast(err.message, 'error'); }
				this.uploading = false;
			},
		},
	};

	/** Formulário gerado a partir do esquema do módulo. */
	const RecordForm = {
		components: { FieldInput: FieldInput },
		props: { schema: Object, rec: Object, labels: Object, isNew: Boolean, exclude: { type: Array, default: function () { return []; } } },
		template: `<div>
			<div v-for="(sec, i) in sections" :key="i" :class="{ 'form-section': i > 0 }">
				<h4 v-if="sec.title">{{ sec.title }}</h4>
				<div class="form-grid">
					<FieldInput v-for="name in sec.fields" :key="name" :name="name" :f="schema.fields[name]" v-model="rec[name]" :labels="labels" :is-new="isNew" @cep="cep" @label="(l) => labels[name] = l"/>
				</div>
			</div>
		</div>`,
		computed: {
			sections: function () {
				const out = [{ title: '', fields: [] }];
				Object.keys(this.schema.fields).forEach((name) => {
					const f = this.schema.fields[name];
					if (f.section) { out.push({ title: f.section, fields: [] }); }
					if (f.type === 'user' || this.exclude.indexOf(name) !== -1) { return; }
					out[out.length - 1].fields.push(name);
				});
				return out.filter(function (s) { return s.fields.length; });
			},
		},
		methods: {
			cep: function (d) {
				const r = this.rec;
				if (!r.logradouro) { r.logradouro = d.logradouro; }
				if (!r.bairro) { r.bairro = d.bairro; }
				r.cidade = d.localidade;
				r.uf = d.uf;
			},
		},
	};

	/** Itens de produto/serviço (OS e vendas). */
	const ProductItems = {
		components: { Autocomplete: Autocomplete, Ic: Ic },
		props: { items: Array, locked: Boolean },
		template: `<div>
			<p v-if="locked" class="muted small">Itens bloqueados nesta situação (estoque já movimentado).</p>
			<table class="items">
				<thead><tr><th style="width:34%">Produto, peça ou serviço</th><th>Descrição</th><th style="width:90px">Qtd</th><th style="width:120px">Valor unit.</th><th style="width:100px">Desconto</th><th style="width:110px;text-align:right">Total</th><th style="width:36px"></th></tr></thead>
				<tbody>
					<tr v-for="(it, i) in items" :key="it._k">
						<td><Autocomplete table="produtos" :model-value="it.ref_id" :label="it.nome || it.descricao" @select="(p) => pick(it, p)" :clearable="false" v-if="!locked"/><div v-else class="readonly">{{ it.descricao }}</div></td>
						<td><input class="input" v-model="it.descricao" :disabled="locked"></td>
						<td><input class="input" type="number" min="0" step="0.001" v-model.number="it.qtd" :disabled="locked"></td>
						<td><input class="input" type="number" min="0" step="0.01" v-model.number="it.valor_unit" :disabled="locked"></td>
						<td><input class="input" type="number" min="0" step="0.01" v-model.number="it.desconto" :disabled="locked"></td>
						<td class="tot">{{ money(total(it)) }}</td>
						<td><button v-if="!locked" type="button" class="btn btn-ghost btn-sm" @click="items.splice(i, 1)" aria-label="Remover"><Ic n="trash"/></button></td>
					</tr>
				</tbody>
			</table>
			<div class="quick" style="justify-content:space-between;align-items:center;margin-top:6px">
				<button v-if="!locked" type="button" class="btn btn-sm" @click="add"><Ic n="plus"/> Adicionar item</button>
				<strong>Itens: {{ money(sum) }}</strong>
			</div>
		</div>`,
		computed: { sum: function () { return this.items.reduce((a, it) => a + this.total(it), 0); } },
		methods: {
			money: fmt.money,
			total: function (it) { return Math.max(0, (Number(it.qtd) || 0) * (Number(it.valor_unit) || 0) - (Number(it.desconto) || 0)); },
			add: function () { this.items.push({ _k: Math.random(), id: 0, ref_id: 0, descricao: '', qtd: 1, valor_unit: 0, desconto: 0 }); },
			pick: function (it, p) { if (!p) { return; } it.ref_id = p.id; it.nome = p.label; it.descricao = p.nome; it.valor_unit = Number(p.preco_venda) || 0; },
		},
	};

	/* ========================================================= janelas de ação */

	function openAction(type, data) { store.modal = { type: type, data: data || {} }; }
	function closeAction() { store.modal = null; }
	function done(msg, route) {
		toast(msg);
		store.modal = null;
		store.tick++;
		if (route) { go(route); }
	}
	async function contractOp(id, op, dados) {
		const r = await post('contract/' + id + '/action', { op: op, dados: dados || {} });
		return r;
	}
	const CHECK_ITEMS = ['Limpo', 'Completo', 'Acessórios', 'Cabos/mangueiras', 'Funcionando', 'Combustível/óleo ok', 'Sem avarias'];

	/** Entrega / retirada: checklist de saída por item. */
	const DeliverModal = {
		components: { Modal: Modal, Ic: Ic },
		props: { data: Object },
		template: `<Modal :title="'Entrega — ' + (rec ? rec.registro.numero : '')" wide @close="close">
			<div v-if="!rec" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div class="form-grid" style="margin-bottom:12px">
					<label class="field"><span>Data da entrega / retirada</span><input type="date" v-model="date"></label>
					<div class="field"><span>Período</span><div class="readonly">{{ dias }} dia(s) — devolução em <b>{{ fmtDate(newEnd) }}</b></div></div>
				</div>
				<div v-for="it in rec.itens" :key="it.id" class="ret-item">
					<div class="h"><strong>{{ it.descricao }}</strong><span class="muted">qtd {{ num(it.qtd) }}</span></div>
					<div class="checks"><label v-for="c in checks" :key="c" :class="{ on: (form[it.id].marks || []).includes(c) }"><input type="checkbox" :value="c" v-model="form[it.id].marks">{{ c }}</label></div>
					<div class="form-grid">
						<label class="field"><span>Horímetro na saída</span><input type="number" step="0.1" v-model="form[it.id].horimetro"></label>
						<label class="field" style="grid-column: span 2"><span>Observações do estado</span><input v-model="form[it.id].obs" placeholder="Ex.: pequeno risco na lateral"></label>
					</div>
				</div>
			</template>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="busy || !rec" @click="save"><Ic n="out"/> Confirmar entrega e iniciar locação</button></template>
		</Modal>`,
		data: function () { return { rec: null, date: D.hoje, form: {}, busy: false, checks: CHECK_ITEMS }; },
		computed: {
			dias: function () { return this.rec ? Math.max(0, daysBetween(this.rec.registro.data_inicio, this.rec.registro.data_prev_devolucao)) : 0; },
			newEnd: function () { return addDays(this.date, this.dias); },
		},
		created: async function () {
			try {
				const r = await get('record/contratos/' + this.data.id);
				const f = {};
				r.itens.forEach(function (it) { f[it.id] = { marks: ['Limpo', 'Completo', 'Funcionando'], horimetro: '', obs: it.checklist_saida || '' }; });
				this.form = f;
				this.date = r.registro.data_inicio > D.hoje ? r.registro.data_inicio : D.hoje;
				this.rec = r;
			} catch (e) { toast(e.message, 'error'); closeAction(); }
		},
		methods: {
			num: fmt.num, fmtDate: fmt.date, close: closeAction,
			save: async function () {
				this.busy = true;
				const itens = {};
				Object.keys(this.form).forEach((id) => { const v = this.form[id]; itens[id] = { horimetro: v.horimetro, checklist: [v.marks.join(', '), v.obs].filter(Boolean).join(' — ') }; });
				try { const r = await contractOp(this.data.id, 'entregar', { data: this.date, itens: itens }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	/** Devolução total ou parcial com atraso, avarias e revisão. */
	const ReturnModal = {
		components: { Modal: Modal, Ic: Ic },
		props: { data: Object },
		template: `<Modal :title="'Devolução — ' + (rec ? rec.registro.numero : '')" wide @close="close">
			<div v-if="!rec" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div class="form-grid" style="margin-bottom:10px">
					<label class="field"><span>Data da devolução</span><input type="date" v-model="date"></label>
					<div class="field"><span>Devolução prevista</span><div class="readonly">{{ fmtDate(rec.registro.data_prev_devolucao) }} <b v-if="late > 0" style="color:#dc2626">· {{ late }} dia(s) de atraso</b><b v-else style="color:#15803d">· no prazo</b></div></div>
				</div>
				<div v-for="it in pend" :key="it.id" class="ret-item">
					<div class="h">
						<strong>{{ it.descricao }}</strong>
						<span class="muted small">pendente {{ num(it.qtd - it.qtd_devolvida) }}</span>
						<div class="stepper"><button type="button" @click="step(it, -1)">−</button><input type="number" v-model.number="form[it.id].qtd" min="0" :max="it.qtd - it.qtd_devolvida"><button type="button" @click="step(it, 1)">+</button></div>
						<button class="btn btn-xs" type="button" @click="form[it.id].qtd = it.qtd - it.qtd_devolvida">todos</button>
					</div>
					<div class="checks"><label v-for="c in checks" :key="c" :class="{ on: form[it.id].marks.includes(c) }"><input type="checkbox" :value="c" v-model="form[it.id].marks">{{ c }}</label></div>
					<div class="form-grid">
						<label class="field"><span>Horímetro no retorno</span><input type="number" step="0.1" v-model="form[it.id].horimetro"></label>
						<label class="field"><span>Avarias encontradas</span><input v-model="form[it.id].avarias" placeholder="Descreva, se houver"></label>
						<label class="field"><span>Cobrar avaria (R$)</span><input type="number" min="0" step="0.01" v-model.number="form[it.id].valor_avaria"></label>
						<label class="field check"><input type="checkbox" v-model="form[it.id].os"><span>Abrir OS de revisão</span></label>
					</div>
				</div>
				<div class="form-grid" style="margin-top:6px">
					<label class="field check" v-if="late > 0"><input type="checkbox" v-model="cobrarAtraso"><span>Cobrar {{ late }} diária(s) excedente(s)</span></label>
					<label class="field" v-if="Number(rec.registro.caucao) > 0"><span>Caução de {{ money(rec.registro.caucao) }}</span><select v-model="caucao"><option value="">manter</option><option value="devolvido">devolvida ao cliente</option><option value="retido">retida</option></select></label>
				</div>
				<div class="preview" style="margin-top:12px">
					<div class="l"><span>Diárias excedentes</span><b>{{ money(lateFee) }}</b></div>
					<div class="l"><span>Avarias</span><b>{{ money(damage) }}</b></div>
					<div class="l"><span>Itens que continuam com o cliente</span><b>{{ num(remaining) }}</b></div>
				</div>
			</template>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="busy || !rec || !count" @click="save"><Ic n="in"/> {{ remaining > 0 ? 'Registrar devolução parcial' : 'Confirmar devolução e encerrar' }}</button></template>
		</Modal>`,
		data: function () { return { rec: null, date: D.hoje, form: {}, busy: false, cobrarAtraso: true, caucao: '', checks: CHECK_ITEMS }; },
		computed: {
			pend: function () { return this.rec ? this.rec.itens.filter(function (it) { return it.qtd - it.qtd_devolvida > 0; }) : []; },
			late: function () { return this.rec ? Math.max(0, daysBetween(this.rec.registro.data_prev_devolucao, this.date)) : 0; },
			lateFee: function () {
				if (!this.cobrarAtraso || this.late <= 0) { return 0; }
				const pct = Number(this.rec.multa_atraso_pct) || 0;
				return this.pend.reduce((a, it) => a + (Number((it.tarifas || {}).diaria) || 0) * this.late * (Number(this.form[it.id].qtd) || 0) * (1 + pct / 100), 0);
			},
			damage: function () { return this.pend.reduce((a, it) => a + (Number(this.form[it.id].valor_avaria) || 0), 0); },
			count: function () { return this.pend.reduce((a, it) => a + (Number(this.form[it.id].qtd) || 0), 0); },
			remaining: function () { return this.pend.reduce((a, it) => a + (it.qtd - it.qtd_devolvida) - (Number(this.form[it.id].qtd) || 0), 0); },
		},
		created: async function () {
			try {
				const r = await get('record/contratos/' + this.data.id);
				r.itens.forEach(function (it) { it.qtd = Number(it.qtd); it.qtd_devolvida = Number(it.qtd_devolvida); });
				const f = {};
				r.itens.forEach(function (it) { f[it.id] = { qtd: it.qtd - it.qtd_devolvida, marks: [], horimetro: '', avarias: '', valor_avaria: 0, os: false }; });
				this.form = f;
				this.rec = r;
			} catch (e) { toast(e.message, 'error'); closeAction(); }
		},
		methods: {
			num: fmt.num, money: fmt.money, fmtDate: fmt.date, close: closeAction,
			step: function (it, d) { const f = this.form[it.id]; f.qtd = Math.max(0, Math.min(it.qtd - it.qtd_devolvida, (Number(f.qtd) || 0) + d)); },
			save: async function () {
				this.busy = true;
				const itens = {};
				this.pend.forEach((it) => {
					const v = this.form[it.id];
					if (Number(v.qtd) > 0) { itens[it.id] = { qtd: v.qtd, horimetro: v.horimetro, checklist: v.marks.join(', '), avarias: v.avarias, valor_avaria: v.valor_avaria, os: v.os ? 1 : 0 }; }
				});
				try { const r = await contractOp(this.data.id, 'devolver', { data: this.date, itens: itens, cobrar_atraso: this.cobrarAtraso ? 1 : 0, caucao_status: this.caucao }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	const RenewModal = {
		components: { Modal: Modal, Ic: Ic },
		props: { data: Object },
		template: `<Modal :title="'Renovar ' + data.numero" @close="close">
			<p class="muted">Devolução prevista hoje: <b>{{ fmtDate(data.fim) }}</b></p>
			<div class="quick" style="margin-bottom:12px"><button v-for="n in [1, 7, 15, 30]" :key="n" class="btn btn-sm" :class="{ 'btn-dark': date === plus(n) }" @click="date = plus(n)">+{{ n }} dia{{ n > 1 ? 's' : '' }}</button></div>
			<div class="form-grid">
				<label class="field"><span>Nova data de devolução</span><input type="date" :min="plus(1)" v-model="date"></label>
				<label class="field check"><input type="checkbox" v-model="recalc"><span>Recalcular valores pelo novo período</span></label>
			</div>
			<div class="preview" style="margin-top:12px"><div class="l"><span>Dias a mais</span><b>{{ extra }}</b></div><div class="l"><span>Novo período total</span><b>{{ total }} dias</b></div></div>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="busy || extra <= 0" @click="save"><Ic n="refresh"/> Renovar</button></template>
		</Modal>`,
		data: function () { return { date: addDays(this.data.fim, 7), recalc: true, busy: false }; },
		computed: {
			extra: function () { return daysBetween(this.data.fim, this.date); },
			total: function () { return rentalDays(this.data.inicio, this.date); },
		},
		methods: {
			fmtDate: fmt.date, close: closeAction,
			plus: function (n) { return addDays(this.data.fim, n); },
			save: async function () {
				this.busy = true;
				try { const r = await contractOp(this.data.id, 'renovar', { nova_data: this.date, recalcular: this.recalc ? 1 : 0 }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	const BillModal = {
		components: { Modal: Modal, Ic: Ic },
		props: { data: Object },
		template: `<Modal :title="'Faturar ' + data.numero" @close="close">
			<div class="form-grid">
				<label class="field"><span>Valor a faturar</span><input type="number" step="0.01" min="0.01" v-model.number="valor"></label>
				<label class="field"><span>Parcelas</span><input type="number" min="1" max="36" v-model.number="parcelas"></label>
				<label class="field"><span>1º vencimento</span><input type="date" v-model="venc"></label>
				<label class="field"><span>Intervalo</span><select v-model.number="intervalo"><option :value="30">Mensal</option><option :value="15">15 dias</option><option :value="7">Semanal</option></select></label>
				<label class="field"><span>Forma de pagamento</span><select v-model="forma"><option v-for="(l, k) in formas" :key="k" :value="k">{{ l }}</option></select></label>
			</div>
			<div class="preview" style="margin-top:12px"><div class="l" v-for="(p, i) in preview" :key="i"><span>{{ i + 1 }}ª — {{ fmtDate(p.d) }}</span><b>{{ money(p.v) }}</b></div></div>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="busy || valor <= 0" @click="save"><Ic n="money"/> Gerar cobrança</button></template>
		</Modal>`,
		data: function () { return { valor: Number(this.data.a_faturar) || 0, parcelas: 1, venc: D.hoje, intervalo: 30, forma: this.data.forma || 'pix', busy: false, formas: D.pagamentos }; },
		computed: {
			preview: function () {
				const n = Math.max(1, Math.min(36, this.parcelas || 1));
				const base = Math.floor((this.valor / n) * 100) / 100;
				const out = [];
				for (let i = 0; i < n; i++) {
					let d = this.venc;
					if (i) { if (this.intervalo === 30) { const x = new Date(this.venc + 'T12:00:00'); x.setMonth(x.getMonth() + i); d = x.toISOString().slice(0, 10); } else { d = addDays(this.venc, i * this.intervalo); } }
					out.push({ d: d, v: i === n - 1 ? Math.round((this.valor - base * (n - 1)) * 100) / 100 : base });
				}
				return out;
			},
		},
		methods: {
			money: fmt.money, fmtDate: fmt.date, close: closeAction,
			save: async function () {
				this.busy = true;
				try { const r = await contractOp(this.data.id, 'faturar', { valor: this.valor, parcelas: this.parcelas, vencimento: this.venc, intervalo: this.intervalo, forma: this.forma }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	const ExtraModal = {
		components: { Modal: Modal },
		props: { data: Object },
		template: `<Modal title="Lançar adicional" @close="close">
			<div class="form-grid"><label class="field full"><span>Descrição</span><input v-model="desc" placeholder="Ex.: limpeza, frete extra, horas de operador"></label><label class="field"><span>Valor (R$)</span><input type="number" min="0.01" step="0.01" v-model.number="valor"></label></div>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="!desc || valor <= 0" @click="save">Lançar</button></template>
		</Modal>`,
		data: function () { return { desc: '', valor: 0 }; },
		methods: {
			close: closeAction,
			save: async function () { try { const r = await contractOp(this.data.id, 'adicional', { descricao: this.desc, valor: this.valor }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); } },
		},
	};

	/** Baixa de conta a receber/pagar. */
	const PayModal = {
		components: { Modal: Modal, Ic: Ic },
		props: { data: Object },
		template: `<Modal :title="(data.tipo === 'receber' ? 'Receber' : 'Pagar') + ' — ' + data.descricao" @close="close">
			<p v-if="data.encargos.dias > 0" class="notice warning">Vencido há {{ data.encargos.dias }} dia(s). Multa e juros calculados pela configuração.</p>
			<div class="form-grid">
				<label class="field"><span>Data do pagamento</span><input type="date" v-model="f.data"></label>
				<label class="field"><span>Valor principal pago</span><input type="number" step="0.01" min="0.01" :max="data.restante" v-model.number="f.valor"></label>
				<label class="field"><span>Multa</span><input type="number" step="0.01" min="0" v-model.number="f.multa"></label>
				<label class="field"><span>Juros</span><input type="number" step="0.01" min="0" v-model.number="f.juros"></label>
				<label class="field"><span>Desconto</span><input type="number" step="0.01" min="0" v-model.number="f.desconto"></label>
				<label class="field"><span>Forma</span><select v-model="f.forma"><option v-for="(l, k) in formas" :key="k" :value="k">{{ l }}</option></select></label>
			</div>
			<div class="preview" style="margin-top:12px">
				<div class="l"><span>Total {{ data.tipo === 'receber' ? 'recebido' : 'pago' }}</span><b>{{ money(total) }}</b></div>
				<div class="l" v-if="f.valor < data.restante"><span>Saldo que fica em aberto</span><b>{{ money(data.restante - f.valor) }}</b></div>
			</div>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="busy || f.valor <= 0" @click="save"><Ic n="check"/> Confirmar baixa</button></template>
		</Modal>`,
		data: function () { return { busy: false, formas: D.pagamentos, f: { data: D.hoje, valor: this.data.restante, multa: this.data.encargos.multa || 0, juros: this.data.encargos.juros || 0, desconto: 0, forma: this.data.forma || 'pix' } }; },
		computed: { total: function () { return (this.f.valor || 0) + (this.f.multa || 0) + (this.f.juros || 0) - (this.f.desconto || 0); } },
		methods: {
			money: fmt.money, close: closeAction,
			save: async function () {
				this.busy = true;
				try { const r = await post('finance/' + this.data.id + '/action', { op: 'baixar', dados: this.f }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	const FiscalModal = {
		components: { Modal: Modal },
		props: { data: Object },
		template: `<Modal :title="'Emitir ' + (data.sugestao.tipo === 'nfe' ? 'NF-e' : 'NFS-e')" @close="close">
			<p v-if="data.origem === 'contrato'" class="notice info">Locação de bem móvel não tem ISS (Súmula Vinculante 31 do STF) — para ela use a fatura de locação. Use NFS-e só para frete, montagem ou operador, se a prefeitura exigir.</p>
			<p v-if="!data.sugestao.ativo" class="notice warning">Integração desligada: o registro fica pendente para emitir no portal e anotar número e chave.</p>
			<div class="form-grid"><label class="field"><span>Valor</span><input type="number" step="0.01" min="0.01" v-model.number="valor"></label><label class="field full"><span>Discriminação</span><input v-model="desc"></label></div>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="busy || valor <= 0" @click="save">Emitir</button></template>
		</Modal>`,
		data: function () { return { valor: this.data.sugestao.valor, desc: this.data.sugestao.discriminacao, busy: false }; },
		methods: {
			close: closeAction,
			save: async function () {
				this.busy = true;
				try { const r = await post('fiscal', { origem: this.data.origem, origem_id: this.data.id, valor: this.valor, discriminacao: this.desc }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	const StockModal = {
		components: { Modal: Modal },
		props: { data: Object },
		template: `<Modal :title="'Movimentar estoque — ' + data.nome" @close="close">
			<div class="seg" style="margin-bottom:12px"><button v-for="(l, k) in tipos" :key="k" :class="{ on: tipo === k }" @click="tipo = k">{{ l }}</button></div>
			<div class="form-grid">
				<label class="field"><span>{{ tipo === 'ajuste' ? 'Saldo contado' : 'Quantidade' }}</span><input type="number" step="0.001" v-model.number="qtd"></label>
				<label class="field" v-if="tipo === 'entrada'"><span>Custo unitário</span><input type="number" step="0.01" min="0" v-model.number="custo"></label>
				<label class="field full"><span>Observação</span><input v-model="obs" placeholder="Nota fiscal, motivo..."></label>
			</div>
			<p class="muted small">Estoque atual: {{ data.estoque }}</p>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" @click="save">Confirmar</button></template>
		</Modal>`,
		data: function () { return { tipo: 'entrada', qtd: 1, custo: 0, obs: '', tipos: { entrada: 'Entrada', saida: 'Saída', ajuste: 'Ajuste de inventário' } }; },
		methods: {
			close: closeAction,
			save: async function () { try { const r = await post('stock/' + this.data.id, { tipo: this.tipo, qtd: this.qtd, custo: this.custo, obs: this.obs }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); } },
		},
	};

	/** Medição pro-rata: escolhe o período, vê o cálculo e gera a cobrança. */
	const MeasureModal = {
		components: { Modal: Modal, Ic: Ic },
		props: { data: Object },
		template: `<Modal :title="'Medição ' + (sug ? sug.numero : '') + ' — ' + data.numero" wide @close="close">
			<p class="muted small" style="margin-top:0">Cobra os dias e as quantidades que ficaram com o cliente no período, à diária do contrato (valor mensal ÷ 30). Devoluções parciais e itens incluídos entram pela data real.</p>
			<div class="form-grid">
				<label class="field"><span>Início do período</span><input type="date" v-model="ini" @change="load"></label>
				<label class="field"><span>Fim do período</span><input type="date" v-model="fim" :min="ini" @change="load"></label>
				<div class="field"><span>Dias</span><div class="readonly">{{ p ? p.dias : '—' }}</div></div>
			</div>
			<p v-if="sug && sug.final" class="notice info" style="margin-top:10px">Medição final: vai até a data em que o último equipamento voltou.</p>
			<p v-else-if="fim > today" class="notice warning" style="margin-top:10px">O período ainda não terminou: o cálculo considera que o que está na obra continua lá até {{ date(fim) }}.</p>
			<div v-if="!p" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<table class="t" style="margin-top:10px">
					<thead><tr><th>Equipamento</th><th>Quantidade × dias</th><th class="n">Diárias</th><th class="n">Diária</th><th class="n">Valor</th></tr></thead>
					<tbody>
						<tr v-if="!p.linhas.length"><td colspan="5" class="muted">Nenhum equipamento com o cliente no período.</td></tr>
						<tr v-for="l in p.linhas" :key="l.item_id"><td><b>{{ l.descricao }}</b></td><td class="small">{{ l.faixas.map(f => num(f.qtd) + ' un × ' + f.dias + ' d (' + short(f.de) + '–' + short(f.ate) + ')').join(' + ') }}</td><td class="n">{{ num(l.diarias) }}</td><td class="n">{{ money4(l.diaria) }}</td><td class="n"><b>{{ money(l.valor) }}</b></td></tr>
					</tbody>
				</table>
				<div class="preview" style="margin-top:10px">
					<div class="l"><span>Equipamentos (pro-rata)</span><b>{{ money(p.valor_itens) }}</b></div>
					<div class="l" v-for="x in p.adicionais" :key="x.id"><span>{{ x.descricao }}</span><b>{{ money(x.valor) }}</b></div>
					<div class="l" v-if="p.valor_frete"><span>Frete (1ª medição)</span><b>{{ money(p.valor_frete) }}</b></div>
					<div class="l" v-if="p.desconto"><span>Desconto (1ª medição)</span><b>− {{ money(p.desconto) }}</b></div>
					<div class="l" style="font-size:16px;border-top:1px solid #e5e7eb;padding-top:6px;margin-top:4px"><span>Total da medição</span><b>{{ money(p.total) }}</b></div>
				</div>
				<div class="form-grid" style="margin-top:12px">
					<label class="field"><span>Parcelas</span><input type="number" min="1" max="12" v-model.number="parcelas"></label>
					<label class="field"><span>1º vencimento</span><input type="date" v-model="venc"></label>
					<label class="field"><span>Forma</span><select v-model="forma"><option v-for="(l, k) in formas" :key="k" :value="k">{{ l }}</option></select></label>
					<label class="field full"><span>Observações do boletim</span><input v-model="obs" placeholder="Opcional"></label>
				</div>
			</template>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="busy || !p || p.total <= 0" @click="save"><Ic n="chart"/> Gerar medição e cobrança</button></template>
		</Modal>`,
		data: function () { return { sug: null, p: null, ini: '', fim: '', parcelas: 1, venc: D.hoje, forma: this.data.forma || 'boleto', obs: '', busy: false, formas: D.pagamentos, today: D.hoje }; },
		created: async function () {
			try {
				const r = await get('contract/' + this.data.id + '/medicao');
				this.sug = r.sugestao; this.ini = r.inicio; this.fim = r.fim; this.p = r;
			} catch (e) { toast(e.message, 'error'); closeAction(); }
		},
		methods: {
			money: fmt.money, num: fmt.num, date: fmt.date, short: fmt.short, close: closeAction,
			money4: function (v) { return 'R$ ' + (Number(v) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 4 }); },
			load: async function () {
				if (!this.ini || !this.fim || this.fim < this.ini) { return; }
				try { this.p = await get('contract/' + this.data.id + '/medicao', { inicio: this.ini, fim: this.fim }); } catch (e) { toast(e.message, 'error'); }
			},
			save: async function () {
				this.busy = true;
				try { const r = await contractOp(this.data.id, 'medir', { inicio: this.ini, fim: this.fim, parcelas: this.parcelas, vencimento: this.venc, forma: this.forma, obs: this.obs }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	/** Cadastro rápido de cliente dentro da locação. */
	const QuickClientModal = {
		components: { Modal: Modal, RecordForm: RecordForm },
		props: { data: Object },
		template: `<Modal title="Novo cliente" wide @close="close">
			<RecordForm :schema="schema" :rec="rec" :labels="labels" is-new :exclude="['limite_credito','bloqueado','motivo_bloqueio','obs']"/>
			<template #footer><button class="btn" @click="close">Cancelar</button><button class="btn btn-primary" :disabled="busy" @click="save">Salvar cliente</button></template>
		</Modal>`,
		data: function () {
			const rec = {};
			Object.keys(D.modulos.clientes.fields).forEach(function (k) { const f = D.modulos.clientes.fields[k]; rec[k] = f.default !== undefined ? f.default : (f.options ? f.options[0].value : ''); });
			rec.nome = this.data.nome || '';
			return { schema: D.modulos.clientes, rec: rec, labels: {}, busy: false };
		},
		methods: {
			close: closeAction,
			save: async function () {
				this.busy = true;
				try {
					const r = await post('record/clientes', { campos: this.rec });
					toast('Cliente cadastrado.');
					const cb = this.data.onSave;
					store.modal = null;
					if (cb) { cb({ id: r.id, label: r.registro.nome }); }
				} catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	const ACTION_MODALS = { medir: MeasureModal, entregar: DeliverModal, devolver: ReturnModal, renovar: RenewModal, faturar: BillModal, adicional: ExtraModal, pagar: PayModal, nota: FiscalModal, estoque: StockModal, cliente: QuickClientModal };

	/** Executa uma ação de contrato vinda de um botão (abre janela quando precisa). */
	async function runContractAction(card, op) {
		if (['entregar', 'devolver', 'renovar', 'faturar', 'adicional', 'medir'].indexOf(op) !== -1) { openAction(op, card); return; }
		const confirmText = { cancelar: 'Cancelar esta locação?', reabrir: 'Reabrir como orçamento?', voltar_orcamento: 'Liberar a reserva e voltar para orçamento?' }[op];
		if (confirmText && !window.confirm(confirmText)) { return; }
		try {
			const r = await contractOp(card.id, op);
			done(r.mensagem, op === 'duplicar' ? 'contratos/' + r.id + '/editar' : null);
		} catch (e) { toast(e.message, 'error'); }
	}
	const ACTION_LABELS = {
		reservar: ['Aprovar e reservar', 'check', 'btn-dark'], entregar: ['Entregar', 'out', 'btn-primary'], devolver: ['Receber devolução', 'in', 'btn-primary'],
		renovar: ['Renovar', 'refresh', ''], faturar: ['Faturar', 'money', ''], medir: ['Gerar medição', 'chart', ''], voltar_orcamento: ['Liberar reserva', 'back', ''], cancelar: ['Cancelar', 'x', 'btn-danger'],
		reabrir: ['Reabrir', 'refresh', ''], duplicar: ['Duplicar', 'copy', ''], email: ['Enviar por e-mail', 'mail', ''],
	};

	/* ================================================================ painel */

	function urgency(c) {
		if (c.status === 'atrasado') { return 'late'; }
		if (c.dias_restantes === 0) { return 'today'; }
		if (c.dias_restantes > 3) { return 'ok'; }
		return '';
	}

	const RentalCard = {
		components: { Ic: Ic, Badge: Badge },
		props: { c: Object },
		template: `<article class="rental" :class="urg">
			<div class="top">
				<div style="min-width:0">
					<a class="num" :href="'#/contratos/' + c.id">{{ c.numero }}</a> <span v-if="c.medicao" class="badge b-reservado" style="font-size:10px" title="Cobrança por medição (pro-rata)">medição</span>
					<div class="client" @click="open">{{ c.cliente ? c.cliente.nome : '—' }}</div>
					<div class="where" v-if="c.local_obra"><Ic n="pin"/>{{ c.local_obra }}</div>
				</div>
				<div class="countdown">
					<template v-if="c.status === 'atrasado'"><strong>{{ c.dias_atraso }}</strong><span>dia{{ c.dias_atraso > 1 ? 's' : '' }} de atraso</span></template>
					<template v-else-if="c.dias_restantes === 0"><strong>Hoje</strong><span>devolução</span></template>
					<template v-else><strong>{{ c.dias_restantes }}</strong><span>dia{{ c.dias_restantes > 1 ? 's' : '' }} restante{{ c.dias_restantes > 1 ? 's' : '' }}</span></template>
				</div>
			</div>
			<div class="equip-chips"><span v-for="it in c.itens" :key="it.id" v-show="it.pendente > 0"><b v-if="it.pendente > 1">{{ num(it.pendente) }}×</b> {{ it.descricao }}</span></div>
			<div>
				<div class="progress" :title="c.dias_passados + ' de ' + c.dias_total + ' dias'"><div :style="{ width: (c.status === 'atrasado' ? 100 : c.progresso) + '%' }"></div></div>
				<div class="dates" style="margin-top:5px"><span>{{ short(c.inicio) }}</span><span>dia {{ Math.min(c.dias_passados, c.dias_total) }} de {{ c.dias_total }}</span><span>{{ short(c.fim) }}</span></div>
			</div>
			<div class="foot">
				<span class="val">{{ money(c.total) }}</span>
				<a v-if="c.whatsapp" class="btn btn-wa btn-xs" :href="c.whatsapp" target="_blank" rel="noopener" title="Chamar no WhatsApp"><Ic n="wa"/></a>
				<button class="btn btn-xs" @click="act('renovar')"><Ic n="refresh"/> Renovar</button>
				<button class="btn btn-primary btn-xs" @click="act('devolver')"><Ic n="in"/> Devolver</button>
			</div>
		</article>`,
		computed: { urg: function () { return urgency(this.c); } },
		methods: {
			money: fmt.money, short: fmt.short, num: fmt.num,
			open: function () { go('contratos/' + this.c.id); },
			act: function (op) { runContractAction(this.c, op); },
		},
	};

	const DashboardPage = {
		components: { Ic: Ic, Badge: Badge, RentalCard: RentalCard, ChartBox: ChartBox },
		template: `<div class="page">
			<div class="page-head">
				<div><p class="hello">{{ greeting }}, {{ firstName }}</p><div class="sub">{{ today }}</div></div>
				<div class="actions">
					<span class="refresh"><span class="dot"></span>atualizado às {{ data ? data.gerado_em : '--:--' }}</span>
					<button class="btn btn-sm" @click="load" title="Atualizar"><Ic n="refresh"/></button>
					<a class="btn" href="#/frota"><Ic n="calendar"/> Agenda da frota</a>
				</div>
			</div>
			<div v-if="!data" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div class="kpis">
					<a class="kpi" @click.prevent="filter = 'todas'" href="#"><div class="kic tone-amber"><Ic n="truck"/></div><div><div class="kv">{{ k.ativos }}</div><div class="kl">Locações em andamento</div></div></a>
					<a class="kpi" :class="{ alert: k.atrasados }" @click.prevent="filter = 'atrasadas'" href="#"><span v-if="k.atrasados" class="pulse"></span><div class="kic tone-red"><Ic n="alert"/></div><div><div class="kv">{{ k.atrasados }}</div><div class="kl">Devoluções atrasadas</div></div></a>
					<a class="kpi" @click.prevent="filter = 'hoje'" href="#"><div class="kic tone-amber"><Ic n="clock"/></div><div><div class="kv">{{ k.vencem_hoje }}</div><div class="kl">Vencem hoje</div></div></a>
					<a class="kpi" href="#/contratos?f_status=solicitacao"><span v-if="k.solicitacoes" class="pulse" style="background:#ea580c"></span><div class="kic tone-blue"><Ic n="mail"/></div><div><div class="kv">{{ k.solicitacoes }}</div><div class="kl">Pedidos do site</div></div></a>
					<a class="kpi" href="#/contratos?f_status=reservado"><div class="kic tone-purple"><Ic n="calendar"/></div><div><div class="kv">{{ k.reservas }}</div><div class="kl">Reservas a sair</div></div></a>
					<a class="kpi" href="#/equipamentos"><div class="kic tone-green"><Ic n="box"/></div><div><div class="kv">{{ num(k.disponiveis) }}<small class="muted" style="font-size:13px"> / {{ num(k.frota) }}</small></div><div class="kl">Disponíveis na frota</div></div></a>
					<a class="kpi" href="#/frota"><div class="kic tone-dark"><Ic n="gauge"/></div><div><div class="kv">{{ num(k.utilizacao, 1) }}%</div><div class="kl">Utilização da frota</div></div></a>
					<template v-if="fin">
						<a class="kpi" href="#/financeiro?f_status=hoje"><div class="kic tone-green"><Ic n="money"/></div><div><div class="kv">{{ money(k.receber_hoje) }}</div><div class="kl">A receber hoje</div></div></a>
						<a class="kpi" :class="{ alert: k.vencido > 0 }" href="#/financeiro?f_status=vencido"><div class="kic tone-red"><Ic n="receipt"/></div><div><div class="kv">{{ money(k.vencido) }}</div><div class="kl">Vencido a receber</div></div></a>
						<a class="kpi" href="#/relatorios/faturamento"><div class="kic tone-dark"><Ic n="chart"/></div><div><div class="kv">{{ money(k.recebido_mes) }}</div><div class="kl">Recebido no mês</div></div></a>
					</template>
				</div>

				<section class="card">
					<h3><Ic n="truck"/> Locações em andamento <span class="muted small" style="font-weight:500">· {{ money(k.em_locacao_valor) }} em contratos ativos</span>
						<span class="more"><span class="seg"><button :class="{ on: view === 'cards' }" @click="view = 'cards'"><Ic n="grid"/> Cartões</button><button :class="{ on: view === 'lista' }" @click="view = 'lista'"><Ic n="list"/> Lista</button></span></span>
					</h3>
					<div class="toolbar">
						<div class="chips">
							<button v-for="f in filters" :key="f.k" class="chip" :class="{ on: filter === f.k, danger: f.k === 'atrasadas' && f.n }" @click="filter = f.k">{{ f.l }} <span class="n">{{ f.n }}</span></button>
						</div>
						<span class="grow"></span>
						<input class="input" v-model="q" placeholder="Filtrar por cliente, obra ou equipamento">
						<select class="input" v-model="sort" style="min-width:160px"><option value="urgencia">Mais urgentes</option><option value="valor">Maior valor</option><option value="cliente">Cliente A–Z</option><option value="recentes">Saída mais recente</option></select>
					</div>
					<div v-if="!shown.length" class="empty"><Ic n="check"/>Nenhuma locação neste filtro.</div>
					<div v-else-if="view === 'cards'" class="rentals"><RentalCard v-for="c in shown" :key="c.id" :c="c"/></div>
					<div v-else class="table-wrap"><table class="t">
						<thead><tr><th>Contrato</th><th>Cliente</th><th>Equipamentos</th><th>Saída</th><th>Devolução</th><th>Situação</th><th class="n">Total</th><th></th></tr></thead>
						<tbody><tr v-for="c in shown" :key="c.id" class="click" :class="{ danger: c.status === 'atrasado' }" @click="go('contratos/' + c.id)">
							<td><b>{{ c.numero }}</b></td><td>{{ c.cliente && c.cliente.nome }}</td><td class="small">{{ c.itens.filter(i => i.pendente > 0).map(i => (i.pendente > 1 ? num(i.pendente) + '× ' : '') + i.descricao).join(', ') }}</td>
							<td>{{ date(c.inicio) }}</td><td>{{ date(c.fim) }}</td>
							<td><Badge e="contrato" :s="c.status"/> <span v-if="c.status === 'atrasado'" class="small">{{ c.dias_atraso }}d</span><span v-else class="small muted">{{ c.dias_restantes }}d</span></td>
							<td class="n">{{ money(c.total) }}</td>
							<td class="n" @click.stop><button class="btn btn-primary btn-xs" @click="act(c, 'devolver')">Devolver</button></td>
						</tr></tbody></table></div>
				</section>

				<div class="grid grid-3" style="margin-top:16px">
					<section class="card">
						<h3><Ic n="calendar"/> Agenda <a class="more" href="#/frota">ver frota</a></h3>
						<div v-for="d in data.agenda" :key="d.dia" class="agenda-day">
							<h5>{{ d.label }} · {{ short(d.dia) }}</h5>
							<div v-if="!d.saidas.length && !d.devolucoes.length" class="muted small" style="padding:4px 0 8px">Sem movimentação.</div>
							<div v-for="c in d.saidas" :key="'s' + c.id" class="agenda-item">
								<div class="ic-wrap tone-purple"><Ic n="out"/></div>
								<div class="tx"><strong>{{ c.cliente && c.cliente.nome }}</strong><span>Saída · {{ c.numero }} · {{ c.itens.length }} item(ns){{ c.entrega !== 'retirada' ? ' · entregar' : ' · cliente retira' }}</span></div>
								<button class="btn btn-xs btn-primary" @click="act(c, 'entregar')">Entregar</button>
							</div>
							<div v-for="c in d.devolucoes" :key="'d' + c.id" class="agenda-item">
								<div class="ic-wrap tone-amber"><Ic n="in"/></div>
								<div class="tx"><strong>{{ c.cliente && c.cliente.nome }}</strong><span>Devolução · {{ c.numero }}{{ c.entrega === 'entrega_coleta' ? ' · coletar' : '' }}</span></div>
								<button class="btn btn-xs" @click="act(c, 'devolver')">Receber</button>
							</div>
						</div>
					</section>
					<section class="card">
						<h3><Ic n="board"/> Funil de locação <a class="more" href="#/contratos?visao=quadro">abrir quadro</a></h3>
						<div v-for="col in funnel" :key="col.k" style="margin-bottom:12px">
							<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px"><Badge e="contrato" :s="col.k"/><b>{{ col.items.length }} · {{ money(col.total) }}</b></div>
							<div v-for="c in col.items.slice(0, 3)" :key="c.id" class="agenda-item" style="cursor:pointer" @click="go('contratos/' + c.id)">
								<div class="tx"><strong>{{ c.cliente && c.cliente.nome }}</strong><span>{{ c.numero }} · {{ short(c.inicio) }} a {{ short(c.fim) }}</span></div>
								<b class="small">{{ money(c.total) }}</b>
							</div>
							<div v-if="col.items.length > 3" class="small muted">+ {{ col.items.length - 3 }} outro(s)</div>
						</div>
					</section>
					<section class="card">
						<h3><Ic n="alert"/> Atenção</h3>
						<div v-if="!attention.length" class="empty"><Ic n="check"/>Tudo em ordem.</div>
						<div v-for="(a, i) in attention" :key="i" class="alert-item" @click="go(a.abrir)"><Ic n="alert"/><span>{{ a.texto }}</span></div>
					</section>
				</div>

				<div class="grid grid-2" style="margin-top:16px">
					<section class="card"><h3><Ic n="chart"/> Faturamento — últimos 6 meses</h3>
						<ChartBox type="bar" :labels="data.receita.map(r => r.mes)" :datasets="revenueSets" :options="{ money: true }"/>
					</section>
					<section class="card"><h3><Ic n="gauge"/> Ocupação da frota agora</h3>
						<div class="grid grid-2" style="align-items:center">
							<ChartBox type="doughnut" :height="200" :labels="['Locados', 'Disponíveis', 'Em manutenção']" :datasets="[{ data: [k.locados, k.disponiveis, k.manutencao], backgroundColor: ['#f5a400', '#16a34a', '#ea580c'], borderWidth: 0 }]" :options="{ cutout: '68%' }"/>
							<div class="bar-list">
								<div v-for="c in data.categorias.slice(0, 7)" :key="c.categoria" class="r"><span class="small" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ c.categoria }}</span><div class="b"><div :style="{ width: c.pct + '%' }"></div></div><b class="small">{{ c.pct }}%</b></div>
							</div>
						</div>
					</section>
				</div>
			</template>
		</div>`,
		data: function () {
			let view = 'cards';
			try { view = localStorage.getItem('dl_view') || 'cards'; } catch (e) {}
			return { data: null, filter: 'todas', q: '', sort: 'urgencia', view: view };
		},
		computed: {
			k: function () { return this.data.kpis; },
			fin: function () { return D.usuario.financeiro; },
			firstName: function () { return D.usuario.nome.split(' ')[0]; },
			greeting: greeting,
			today: todayLong,
			filters: function () {
				const a = this.data.ativos;
				const c = function (fn) { return a.filter(fn).length; };
				return [
					{ k: 'todas', l: 'Todas', n: a.length },
					{ k: 'atrasadas', l: 'Atrasadas', n: c(function (x) { return x.status === 'atrasado'; }) },
					{ k: 'hoje', l: 'Vencem hoje', n: c(function (x) { return x.status !== 'atrasado' && x.dias_restantes === 0; }) },
					{ k: '3dias', l: 'Próximos 3 dias', n: c(function (x) { return x.status !== 'atrasado' && x.dias_restantes > 0 && x.dias_restantes <= 3; }) },
					{ k: 'semana', l: 'Esta semana', n: c(function (x) { return x.status !== 'atrasado' && x.dias_restantes <= 7; }) },
					{ k: 'emdia', l: 'Em dia', n: c(function (x) { return x.dias_restantes > 7; }) },
				];
			},
			shown: function () {
				const q = this.q.toLowerCase();
				const f = this.filter;
				let list = this.data.ativos.filter(function (x) {
					if (f === 'atrasadas' && x.status !== 'atrasado') { return false; }
					if (f === 'hoje' && (x.status === 'atrasado' || x.dias_restantes !== 0)) { return false; }
					if (f === '3dias' && (x.status === 'atrasado' || x.dias_restantes < 1 || x.dias_restantes > 3)) { return false; }
					if (f === 'semana' && (x.status === 'atrasado' || x.dias_restantes > 7)) { return false; }
					if (f === 'emdia' && x.dias_restantes <= 7) { return false; }
					if (!q) { return true; }
					const hay = [x.numero, x.cliente && x.cliente.nome, x.local_obra].concat(x.itens.map(function (i) { return i.descricao; })).join(' ').toLowerCase();
					return hay.indexOf(q) !== -1;
				});
				const s = this.sort;
				list = list.slice().sort(function (a, b) {
					if (s === 'valor') { return b.total - a.total; }
					if (s === 'cliente') { return ((a.cliente && a.cliente.nome) || '').localeCompare((b.cliente && b.cliente.nome) || ''); }
					if (s === 'recentes') { return b.inicio.localeCompare(a.inicio); }
					return a.dias_restantes - b.dias_restantes;
				});
				return list;
			},
			funnel: function () {
				const f = this.data.funil;
				return ['solicitacao', 'orcamento', 'reservado'].map(function (k) { return { k: k, items: f[k], total: f[k].reduce(function (a, c) { return a + c.total; }, 0) }; });
			},
			attention: function () {
				const out = this.data.ativos.filter(function (c) { return c.status === 'atrasado'; }).slice(0, 5).map(function (c) { return { texto: 'Devolução atrasada há ' + c.dias_atraso + ' dia(s): ' + c.numero + ' — ' + (c.cliente ? c.cliente.nome : ''), abrir: 'contratos/' + c.id }; });
				return out.concat(this.data.alertas);
			},
			revenueSets: function () {
				const r = this.data.receita;
				const sets = [{ label: 'Contratado', data: r.map(function (x) { return x.contratado; }), backgroundColor: '#2b2b2b', borderRadius: 6 }];
				if (this.fin) { sets.push({ label: 'Recebido', data: r.map(function (x) { return x.recebido; }), backgroundColor: '#f5a400', borderRadius: 6 }); }
				return sets;
			},
		},
		watch: {
			view: function (v) { try { localStorage.setItem('dl_view', v); } catch (e) {} },
		},
		created: function () {
			this.load();
			this.timer = setInterval(() => { if (!store.modal && document.visibilityState === 'visible') { this.load(); } }, 60000);
			this.stop = watch(function () { return store.tick; }, () => this.load());
		},
		unmounted: function () { clearInterval(this.timer); this.stop(); },
		methods: {
			money: fmt.money, num: fmt.num, date: fmt.date, short: fmt.short, go: go,
			act: function (c, op) { runContractAction(c, op); },
			load: async function () {
				try {
					this.data = await get('dashboard');
					store.counts.solicitacoes = this.data.kpis.solicitacoes;
					store.counts.atrasados = this.data.kpis.atrasados;
				} catch (e) { toast(e.message, 'error'); }
			},
		},
	};

	/* ================================================================ listas */

	const ListPage = {
		components: { Ic: Ic, Badge: Badge, Autocomplete: Autocomplete },
		props: { module: String, embedded: Boolean },
		template: `<div :class="{ page: !embedded }">
			<div class="page-head" v-if="!embedded">
				<h1>{{ m.plural }}</h1><span class="sub" v-if="data">{{ data.total }} registro(s)</span>
				<div class="actions">
					<span v-if="module === 'equipamentos'" class="seg"><button :class="{ on: grid }" @click="grid = true"><Ic n="grid"/></button><button :class="{ on: !grid }" @click="grid = false"><Ic n="list"/></button></span>
					<button class="btn" @click="exportCsv"><Ic n="down"/> Exportar</button>
					<a v-if="!m.no_create" class="btn btn-primary" :href="'#/' + module + '/novo' + (module === 'financeiro' && filters.tipo ? '?tipo=' + filters.tipo : '')"><Ic n="plus"/> Novo</a>
				</div>
			</div>
			<div v-if="module === 'financeiro'" class="tabs">
				<button :class="{ on: !filters.tipo }" @click="setF('tipo', '')">Tudo</button>
				<button :class="{ on: filters.tipo === 'receber' }" @click="setF('tipo', 'receber')">A receber</button>
				<button :class="{ on: filters.tipo === 'pagar' }" @click="setF('tipo', 'pagar')">A pagar</button>
			</div>
			<div v-if="module === 'financeiro' && data && data.totais" class="kpis">
				<div class="kpi"><div class="kic tone-green"><Ic n="money"/></div><div><div class="kv">{{ money(data.totais.rec) }}</div><div class="kl">A receber no filtro · recebido {{ money(data.totais.recp) }}</div></div></div>
				<div class="kpi"><div class="kic tone-red"><Ic n="receipt"/></div><div><div class="kv">{{ money(data.totais.pag) }}</div><div class="kl">A pagar no filtro · pago {{ money(data.totais.pagp) }}</div></div></div>
				<div class="kpi" @click="setF('status', 'vencido')"><div class="kic tone-red"><Ic n="alert"/></div><div><div class="kv">Vencidos</div><div class="kl">clique para filtrar</div></div></div>
				<div class="kpi" @click="setF('status', 'hoje')"><div class="kic tone-amber"><Ic n="clock"/></div><div><div class="kv">Hoje</div><div class="kl">vencendo hoje</div></div></div>
			</div>
			<div class="toolbar">
				<input class="input" v-model="q" @input="reload" placeholder="Buscar..." style="min-width:260px">
				<template v-for="f in m.filters" :key="f.field">
					<div v-if="f.type === 'relation'" style="min-width:220px"><Autocomplete :table="f.rel" :model-value="filters[f.field]" :label="relLabels[f.field]" :placeholder="f.label + ': todos'" @select="(it) => { relLabels[f.field] = it ? it.label : ''; setF(f.field, it ? it.id : ''); }"/></div>
					<select v-else-if="!(module === 'financeiro' && f.field === 'tipo')" class="input" style="min-width:150px" :value="filters[f.field] || ''" @change="setF(f.field, $event.target.value)">
						<option value="">{{ f.label }}: todos</option>
						<option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
					</select>
				</template>
				<template v-if="m.date_filter"><input type="date" class="input" style="min-width:0" v-model="de" @change="reload" title="de"><input type="date" class="input" style="min-width:0" v-model="ate" @change="reload" title="até"></template>
				<button v-if="hasFilters" class="btn btn-ghost btn-sm" @click="clear">limpar filtros</button>
			</div>
			<div v-if="!data" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div v-if="module === 'equipamentos' && grid" class="equip-grid">
					<div v-if="!data.rows.length" class="empty">Nenhum equipamento.</div>
					<div v-for="r in data.rows" :key="r.id" class="equip" @click="go(module + '/' + r.id)">
						<div class="ph" :style="r.raw.foto ? { backgroundImage: 'url(' + r.raw.foto + ')', backgroundSize: 'cover' } : {}"><Ic v-if="!r.raw.foto" n="box"/><Badge e="equipamento" :s="r.raw.status"/></div>
						<div class="bd">
							<div class="cd">{{ r.raw.codigo }} {{ r.raw.categoria ? '· ' + r.raw.categoria : '' }}</div>
							<div class="nm">{{ r.raw.nome }}</div>
							<div class="meter" v-if="r.raw.controle === 'quantidade'" :title="r.raw.locados + ' locados de ' + r.raw.qtd_total"><i v-for="n in Math.min(20, Number(r.raw.qtd_total))" :key="n" :class="{ out: n <= meterOut(r.raw) }"></i></div>
							<div class="row"><span class="small muted">{{ r.raw.controle === 'quantidade' ? num(r.raw.livres) + ' de ' + num(r.raw.qtd_total) + ' livres' : (r.raw.livres ? 'Livre agora' : 'Ocupado') }}</span><b>{{ money(r.raw.valor_diaria) }}<small class="muted">/dia</small></b></div>
						</div>
					</div>
				</div>
				<div v-else class="card" style="padding:0">
					<div class="table-wrap"><table class="t">
						<thead><tr><th v-for="(f, name) in cols" :key="name" :class="{ n: isNum(f) }">{{ f.label }}</th></tr></thead>
						<tbody>
							<tr v-if="!data.rows.length"><td :colspan="Object.keys(cols).length" class="empty">Nenhum registro encontrado.</td></tr>
							<tr v-for="r in data.rows" :key="r.id" class="click" :class="{ danger: ['atrasado', 'vencido'].includes(r.raw.status) }" @click="go(module + '/' + r.id)">
								<td v-for="(f, name) in cols" :key="name" :class="{ n: isNum(f) }"><Badge v-if="f.badge" :e="f.badge" :s="r.raw[name]"/><template v-else>{{ r.cells[name] }}</template></td>
							</tr>
						</tbody>
					</table></div>
				</div>
				<div class="pager" v-if="data.pages > 1">
					<span>Página {{ data.page }} de {{ data.pages }}</span>
					<span class="quick"><button class="btn btn-sm" :disabled="page <= 1" @click="page--; load()">Anterior</button><button class="btn btn-sm" :disabled="page >= data.pages" @click="page++; load()">Próxima</button></span>
				</div>
			</template>
		</div>`,
		data: function () {
			const q = store.route.query;
			const filters = {};
			Object.keys(q).forEach(function (k) { if (k.indexOf('f_') === 0) { filters[k.slice(2)] = q[k]; } });
			return { data: null, q: q.s || '', filters: filters, relLabels: {}, de: q.de || '', ate: q.ate || '', page: 1, grid: true };
		},
		computed: {
			m: function () { return D.modulos[this.module]; },
			cols: function () { const out = {}; Object.keys(this.m.fields).forEach((k) => { if (this.m.fields[k].list) { out[k] = this.m.fields[k]; } }); return out; },
			hasFilters: function () { return this.q || this.de || this.ate || Object.keys(this.filters).some((k) => this.filters[k]); },
		},
		created: function () {
			this.reload = debounce(() => { this.page = 1; this.load(); }, 250);
			this.load();
			this.stop = watch(function () { return store.tick; }, () => this.load());
		},
		unmounted: function () { this.stop(); },
		watch: { module: function () { this.filters = {}; this.q = ''; this.page = 1; this.data = null; this.load(); } },
		methods: {
			money: fmt.money, num: fmt.num, go: go,
			meterOut: function (e) { const t = Math.min(20, Number(e.qtd_total)); return Math.round(t * Number(e.locados) / Math.max(1, Number(e.qtd_total))); },
			isNum: function (f) { return ['money', 'readonly_money', 'decimal', 'int'].indexOf(f.type) !== -1; },
			params: function (extra) {
				const p = Object.assign({ s: this.q, de: this.de, ate: this.ate, page: this.page }, extra || {});
				Object.keys(this.filters).forEach((k) => { p['f_' + k] = this.filters[k]; });
				return p;
			},
			load: async function () {
				try { this.data = await get('list/' + this.module, this.params()); } catch (e) { toast(e.message, 'error'); }
			},
			setF: function (k, v) { this.filters[k] = v; this.page = 1; this.load(); },
			clear: function () { this.filters = {}; this.relLabels = {}; this.q = ''; this.de = ''; this.ate = ''; this.page = 1; this.load(); },
			exportCsv: async function () {
				try {
					const r = await get('list/' + this.module, this.params({ page: 1, por_pagina: 2000 }));
					const names = Object.keys(this.m.fields).filter((k) => this.m.fields[k].type !== 'user');
					const rows = [['ID'].concat(names.map((k) => this.m.fields[k].label))];
					r.rows.forEach((row) => { rows.push([row.id].concat(names.map((k) => { const f = this.m.fields[k]; return f.list ? row.cells[k] : displayValue(f, row.raw[k], {}, k); }))); });
					downloadCsv(this.module + '-' + D.hoje + '.csv', rows);
				} catch (e) { toast(e.message, 'error'); }
			},
		},
	};

	/** Quadro (kanban) das locações com arrastar e soltar. */
	const BoardView = {
		components: { Ic: Ic, Badge: Badge },
		template: `<div>
			<p class="muted small" style="margin-top:0">Arraste os cartões entre as colunas: aprovar orçamento reserva os equipamentos; soltar em <b>Em locação</b> abre a entrega; soltar em <b>Encerradas</b> abre a devolução.</p>
			<div v-if="!cols" class="empty"><span class="spinner dark"></span></div>
			<div v-else class="board">
				<div v-for="col in cols" :key="col.k" class="col" :class="{ over: over === col.k }" @dragover.prevent="over = col.k" @dragleave="over = over === col.k ? null : over" @drop.prevent="drop(col.k)">
					<h4><Badge e="contrato" :s="col.k === 'ativo' ? 'ativo' : col.k"/><span class="n">{{ col.items.length }}</span></h4>
					<div class="hint">{{ col.hint }}</div>
					<div v-for="c in col.items" :key="c.id" class="kcard" :class="['k-' + c.status, { dragging: drag && drag.id === c.id }]" draggable="true" @dragstart="drag = c" @dragend="drag = null; over = null" @click="go('contratos/' + c.id)">
						<div class="kt"><span>{{ c.numero }}</span><span v-if="c.status === 'atrasado'" style="color:#dc2626">{{ c.dias_atraso }}d atraso</span><span v-else-if="c.status === 'ativo'">{{ c.dias_restantes }}d</span><span v-else>{{ short(c.inicio) }}</span></div>
						<div class="kc">{{ c.cliente && c.cliente.nome }}</div>
						<div class="kd">{{ c.itens.map(i => i.descricao).join(', ') || 'sem itens' }}</div>
						<div class="kt"><span class="kv">{{ money(c.total) }}</span><span>{{ short(c.inicio) }} → {{ short(c.fim) }}</span></div>
					</div>
				</div>
			</div>
		</div>`,
		data: function () { return { cols: null, drag: null, over: null }; },
		created: function () { this.load(); this.stop = watch(function () { return store.tick; }, () => this.load()); },
		unmounted: function () { this.stop(); },
		methods: {
			money: fmt.money, short: fmt.short, go: go,
			load: async function () {
				try {
					const [d, enc] = await Promise.all([get('dashboard'), get('list/contratos', { f_status: 'encerrado', por_pagina: 12 })]);
					const closed = enc.rows.map(function (r) { return { id: r.id, numero: r.raw.numero, status: 'encerrado', cliente: { nome: r.cells.cliente_id }, itens: [], total: Number(r.raw.total), inicio: r.raw.data_inicio, fim: r.raw.data_prev_devolucao }; });
					this.cols = [
						{ k: 'solicitacao', hint: 'Pedidos que chegaram pelo site', items: d.funil.solicitacao },
						{ k: 'orcamento', hint: 'Aguardando o cliente aprovar', items: d.funil.orcamento },
						{ k: 'reservado', hint: 'Equipamentos garantidos', items: d.funil.reservado },
						{ k: 'ativo', hint: 'Com o cliente agora', items: d.ativos },
						{ k: 'encerrado', hint: 'Devolvidos recentemente', items: closed },
					];
				} catch (e) { toast(e.message, 'error'); }
			},
			drop: async function (to) {
				const c = this.drag;
				this.over = null;
				if (!c) { return; }
				const from = c.status === 'atrasado' ? 'ativo' : c.status;
				if (from === to) { return; }
				if (to === 'ativo') { if (['orcamento', 'reservado', 'solicitacao'].indexOf(from) !== -1) { openAction('entregar', c); } else { toast('Só orçamentos e reservas podem ser entregues.', 'warning'); } return; }
				if (to === 'encerrado') { if (from === 'ativo') { openAction('devolver', c); } else { toast('Só locações em andamento podem ser devolvidas.', 'warning'); } return; }
				try { const r = await post('contract/' + c.id + '/status', { para: to }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
			},
		},
	};

	const ContractsPage = {
		components: { Ic: Ic, ListPage: ListPage, BoardView: BoardView },
		template: `<div class="page">
			<div class="page-head">
				<h1>Locações</h1>
				<div class="actions">
					<span class="seg"><button :class="{ on: view === 'lista' }" @click="setView('lista')"><Ic n="list"/> Lista</button><button :class="{ on: view === 'quadro' }" @click="setView('quadro')"><Ic n="board"/> Quadro</button></span>
					<a class="btn" href="#/frota"><Ic n="calendar"/> Agenda da frota</a>
				</div>
			</div>
			<BoardView v-if="view === 'quadro'"/>
			<ListPage v-else module="contratos" embedded/>
		</div>`,
		data: function () {
			const q = store.route.query;
			const filtered = Object.keys(q).some(function (k) { return k.indexOf('f_') === 0; });
			let saved = 'lista';
			try { saved = localStorage.getItem('dl_contracts_view') || 'lista'; } catch (e) {}
			return { view: q.visao || (filtered ? 'lista' : saved) };
		},
		methods: { setView: function (v) { this.view = v; try { localStorage.setItem('dl_contracts_view', v); } catch (e) {} } },
	};

	/* ============================================================== locação */

	const ContractView = {
		components: { Ic: Ic, Badge: Badge },
		props: { id: [String, Number] },
		template: `<div class="page">
			<a class="back" href="#/contratos"><Ic n="back"/> Locações</a>
			<div v-if="!r" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div class="detail-head">
					<div class="ttl">
						<h1>{{ c.numero }} <Badge e="contrato" :s="card.status"/><span v-if="c.origem === 'site'" class="badge b-solicitacao">veio do site</span></h1>
						<p><a :href="'#/clientes/' + c.cliente_id"><b>{{ r.rotulos.cliente_id }}</b></a><template v-if="card.local_obra"> · {{ card.local_obra }}</template></p>
					</div>
					<div class="acts">
						<a v-if="card.whatsapp" class="btn btn-wa" :href="card.whatsapp" target="_blank" rel="noopener"><Ic n="wa"/> WhatsApp</a>
						<a v-if="!closed" class="btn" :href="'#/contratos/' + c.id + '/editar'"><Ic n="edit"/> Editar</a>
						<button v-for="a in mainActions" :key="a" class="btn" :class="label(a)[2]" @click="act(a)"><Ic :n="label(a)[1]"/> {{ label(a)[0] }}</button>
					</div>
				</div>

				<div class="steps">
					<div v-for="s in steps" :key="s.k" :class="s.cls">{{ s.l }}</div>
				</div>

				<div v-for="(a, i) in r.alertas" :key="i" class="notice" :class="a.type">{{ a.message }}</div>

				<div class="grid grid-main">
					<div>
						<section class="card" v-if="c.status === 'ativo' || c.status === 'reservado'">
							<div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
								<div><div class="muted small">{{ c.status === 'reservado' ? 'Saída prevista' : 'Devolução prevista' }}</div><div class="big">{{ date(c.status === 'reservado' ? c.data_inicio : c.data_prev_devolucao) }}</div></div>
								<div v-if="c.status === 'ativo'" style="flex:1;min-width:220px">
									<div class="progress" style="height:10px"><div :style="{ width: (card.status === 'atrasado' ? 100 : card.progresso) + '%', background: card.status === 'atrasado' ? '#dc2626' : '' }"></div></div>
									<div class="dates small muted" style="display:flex;justify-content:space-between;margin-top:6px"><span>{{ date(c.data_inicio) }}</span><span>dia {{ Math.min(card.dias_passados, card.dias_total) }} de {{ card.dias_total }}</span><span>{{ date(c.data_prev_devolucao) }}</span></div>
								</div>
								<div style="text-align:right">
									<div v-if="card.status === 'atrasado'" class="big" style="color:#dc2626">{{ card.dias_atraso }} dia(s) de atraso</div>
									<div v-else-if="c.status === 'ativo'" class="big">{{ card.dias_restantes === 0 ? 'Vence hoje' : card.dias_restantes + ' dia(s)' }}</div>
									<div v-else class="big">{{ card.dias_total }} dia(s)</div>
								</div>
							</div>
						</section>

						<section class="card">
							<h3><Ic n="box"/> Equipamentos</h3>
							<div class="table-wrap"><table class="t">
								<thead><tr><th>Equipamento</th><th class="n">Qtd</th><th>Cobrança</th><th class="n">Unitário</th><th class="n">Total</th><th v-if="c.status !== 'orcamento' && c.status !== 'solicitacao'">Devolução</th></tr></thead>
								<tbody>
									<tr v-for="it in r.itens" :key="it.id">
										<td><a :href="'#/equipamentos/' + it.ref_id"><b>{{ it.descricao }}</b></a><div v-if="it.checklist_saida" class="small muted">Saída: {{ it.checklist_saida }}</div><div v-if="it.avarias" class="small" style="color:#b91c1c">Avarias: {{ it.avarias }}</div></td>
										<td class="n">{{ num(it.qtd) }}</td>
										<td>{{ it.periodo_tipo === 'pacote' ? 'Melhor tarifa' : num(it.periodos) + ' × ' + (periodos[it.periodo_tipo] || {}).label }}</td>
										<td class="n">{{ money(it.valor_unit) }}</td>
										<td class="n"><b>{{ money(it.total) }}</b></td>
										<td v-if="c.status !== 'orcamento' && c.status !== 'solicitacao'"><span v-if="Number(it.qtd_devolvida) >= Number(it.qtd)" class="badge b-encerrado">devolvido {{ date(it.data_retorno) }}</span><span v-else-if="Number(it.qtd_devolvida) > 0" class="badge b-ativo">{{ num(it.qtd_devolvida) }} de {{ num(it.qtd) }}</span><span v-else class="muted small">com o cliente</span></td>
									</tr>
								</tbody>
							</table></div>
							<div v-if="r.adicionais.length" style="margin-top:12px">
								<h3 style="font-size:13px"><Ic n="plus"/> Adicionais</h3>
								<div v-for="a in r.adicionais" :key="a.id" class="agenda-item"><div class="tx"><strong>{{ a.descricao }}</strong></div><b>{{ money(a.total) }}</b><button v-if="!closed" class="btn btn-ghost btn-xs" @click="removeExtra(a)" title="Remover"><Ic n="trash"/></button></div>
							</div>
							<button v-if="!closed && c.status !== 'solicitacao'" class="btn btn-sm" style="margin-top:8px" @click="act('adicional')"><Ic n="plus"/> Lançar adicional</button>
						</section>

						<section class="card">
							<h3><Ic n="pin"/> Obra e entrega</h3>
							<dl class="kv-list">
								<dt>Entrega</dt><dd>{{ entregaLabel }}</dd>
								<template v-if="c.local_obra"><dt>Obra</dt><dd>{{ c.local_obra }}</dd></template>
								<template v-if="c.endereco_entrega"><dt>Endereço</dt><dd>{{ c.endereco_entrega }} <a class="small" target="_blank" rel="noopener" :href="'https://www.google.com/maps/search/' + encodeURIComponent(c.endereco_entrega)">abrir mapa</a></dd></template>
								<template v-if="c.responsavel_obra"><dt>Responsável</dt><dd>{{ c.responsavel_obra }} {{ c.telefone_obra }}</dd></template>
								<template v-if="c.condicao_pagamento"><dt>Pagamento</dt><dd>{{ c.condicao_pagamento }}</dd></template>
								<template v-if="c.obs"><dt>Observações</dt><dd>{{ c.obs }}</dd></template>
								<template v-if="c.obs_interna"><dt>Interno</dt><dd style="white-space:pre-wrap">{{ c.obs_interna }}</dd></template>
							</dl>
						</section>
					</div>

					<div>
						<section class="card">
							<h3><Ic n="money"/> Valores</h3>
							<div class="totals">
								<div class="l"><span>Equipamentos</span><b>{{ money(c.subtotal) }}</b></div>
								<div class="l" v-if="Number(c.adicionais)"><span>Adicionais</span><b>{{ money(c.adicionais) }}</b></div>
								<div class="l" v-if="Number(c.valor_frete)"><span>Frete</span><b>{{ money(c.valor_frete) }}</b></div>
								<div class="l" v-if="Number(c.desconto)"><span>Desconto</span><b>− {{ money(c.desconto) }}</b></div>
								<div class="l grand"><span>Total</span><span>{{ money(c.total) }}</span></div>
								<div class="l small"><span>Já faturado</span><b>{{ money(c.valor_faturado) }}</b></div>
								<div class="l small" v-if="card.a_faturar > 0"><span>A faturar</span><b style="color:#b45309">{{ money(card.a_faturar) }}</b></div>
								<div class="l small" v-if="Number(c.caucao)"><span>Caução</span><b>{{ money(c.caucao) }} · {{ caucaoLabel }}</b></div>
							</div>
							<button v-if="fin && r.acoes.includes('faturar') && card.a_faturar > 0" class="btn btn-primary btn-block" style="margin-top:12px" @click="act('faturar')"><Ic n="money"/> Faturar {{ money(card.a_faturar) }}</button>
						</section>

						<section class="card" v-if="r.medicoes">
							<h3><Ic n="chart"/> Medições (pro-rata)</h3>
							<p class="muted small" style="margin-top:-6px">Cobrança a cada {{ c.medicao_ciclo }} dias pelos dias e quantidades que ficaram com o cliente. Total acima é só a estimativa do período.</p>
							<div v-if="!r.medicoes.length" class="muted small">Nenhuma medição ainda.</div>
							<div v-for="m in r.medicoes" :key="m.id" class="agenda-item">
								<div class="tx"><strong>Medição {{ m.numero }} · {{ money(m.total) }}</strong><span>{{ date(m.inicio) }} a {{ date(m.fim) }}<template v-if="m.status === 'cancelada'"> · cancelada</template></span></div>
								<a v-if="fin" class="btn btn-xs" :href="m.doc" target="_blank" rel="noopener" title="Boletim de medição"><Ic n="print"/></a>
								<button v-if="fin && m.status === 'gerada' && m.id === lastMeasure" class="btn btn-xs btn-ghost" title="Cancelar medição" @click="cancelMeasure(m)"><Ic n="x"/></button>
							</div>
							<template v-if="r.medicao_sugestao && !r.medicao_sugestao.nada">
								<p class="small" style="margin:10px 0 6px">Próxima: <b>{{ r.medicao_sugestao.final ? 'medição final' : 'medição ' + r.medicao_sugestao.numero }}</b> — {{ date(r.medicao_sugestao.inicio) }} a {{ date(r.medicao_sugestao.fim) }} <span v-if="r.medicao_sugestao.pronta" class="badge b-atrasado">pendente</span></p>
								<button v-if="fin" class="btn btn-primary btn-block" @click="act('medir')"><Ic n="chart"/> Gerar medição</button>
							</template>
						</section>

						<section class="card" v-if="r.cobrancas && r.cobrancas.length">
							<h3><Ic n="receipt"/> Cobranças</h3>
							<div v-for="f in r.cobrancas" :key="f.id" class="agenda-item" style="cursor:pointer" @click="go('financeiro/' + f.id)">
								<div class="tx"><strong>{{ money(f.valor) }}</strong><span>vence {{ date(f.vencimento) }}{{ f.data_pagamento ? ' · pago ' + date(f.data_pagamento) : '' }}</span></div><Badge e="financeiro" :s="f.situacao"/>
							</div>
						</section>

						<section class="card">
							<h3><Ic n="print"/> Documentos</h3>
							<div class="doclinks">
								<a v-for="d in r.documentos" :key="d.tipo" :href="d.url" target="_blank" rel="noopener"><Ic n="file"/> {{ d.nome }}</a>
							</div>
							<div class="quick" style="margin-top:10px">
								<button class="btn btn-sm" @click="copyLink"><Ic n="link"/> Copiar link para o cliente</button>
								<button v-if="r.cliente && r.cliente.email" class="btn btn-sm" @click="act('email')"><Ic n="mail"/> Enviar por e-mail</button>
							</div>
						</section>

						<section class="card" v-if="fin">
							<h3><Ic n="receipt"/> Nota fiscal</h3>
							<div v-for="n in r.notas" :key="n.id" class="agenda-item" style="cursor:pointer" @click="go('notas/' + n.id)"><div class="tx"><strong>{{ n.tipo.toUpperCase() }} {{ n.numero || '' }}</strong><span>{{ money(n.valor) }}</span></div><Badge e="nota" :s="n.status"/></div>
							<p class="muted small">Locação usa a fatura de locação (sem ISS). NFS-e só para frete/serviços.</p>
							<button class="btn btn-sm" @click="emitNote"><Ic n="plus"/> Emitir NFS-e</button>
						</section>

						<section class="card">
							<h3><Ic n="clock"/> Histórico</h3>
							<ul class="timeline-hist"><li v-for="(h, i) in r.historico" :key="i"><b>{{ h.acao }}</b><small>{{ h.quando }} · {{ h.usuario }}</small><small v-if="h.detalhes">{{ h.detalhes }}</small></li></ul>
							<div class="quick" style="margin-top:10px">
								<button v-for="a in otherActions" :key="a" class="btn btn-sm" :class="label(a)[2]" @click="act(a)">{{ label(a)[0] }}</button>
								<button v-if="r.excluir === true" class="btn btn-sm btn-danger" @click="remove"><Ic n="trash"/> Excluir</button>
							</div>
						</section>
					</div>
				</div>
			</template>
		</div>`,
		data: function () { return { r: null, periodos: D.periodos, fin: D.usuario.financeiro }; },
		computed: {
			c: function () { return this.r.registro; },
			card: function () { return this.r.cartao; },
			closed: function () { return ['encerrado', 'cancelado'].indexOf(this.c.status) !== -1; },
			mainActions: function () { return this.r.acoes.filter(function (a) { return ['reservar', 'entregar', 'devolver', 'renovar'].indexOf(a) !== -1; }); },
			lastMeasure: function () { const ok = (this.r.medicoes || []).filter(function (m) { return m.status === 'gerada'; }); return ok.length ? ok[ok.length - 1].id : 0; },
			otherActions: function () { return this.r.acoes.filter(function (a) { return ['voltar_orcamento', 'cancelar', 'reabrir', 'duplicar'].indexOf(a) !== -1; }); },
			entregaLabel: function () { return { retirada: 'Cliente retira', entrega: 'Locadora entrega', entrega_coleta: 'Locadora entrega e coleta' }[this.c.entrega] || this.c.entrega; },
			caucaoLabel: function () { return { nao_cobrado: 'não cobrada', recebido: 'recebida', devolvido: 'devolvida', retido: 'retida' }[this.c.caucao_status]; },
			steps: function () {
				const st = this.c.status;
				const order = ['orcamento', 'reservado', 'ativo', 'encerrado'];
				const idx = st === 'solicitacao' ? 0 : order.indexOf(st);
				const late = this.card.status === 'atrasado';
				if (st === 'cancelado') { return [{ k: 'c', l: 'Cancelado', cls: 'bad' }]; }
				return [
					{ k: 'o', l: st === 'solicitacao' ? 'Pedido do site' : 'Orçamento', cls: idx > 0 ? 'done' : 'now' },
					{ k: 'r', l: 'Reservado', cls: idx > 1 ? 'done' : (idx === 1 ? 'now' : '') },
					{ k: 'a', l: late ? 'Em locação — atrasado' : 'Em locação', cls: idx > 2 ? 'done' : (idx === 2 ? (late ? 'bad' : 'now') : '') },
					{ k: 'e', l: 'Devolvido', cls: idx === 3 ? 'done' : '' },
				];
			},
		},
		created: function () { this.load(); this.stop = watch(function () { return store.tick; }, () => this.load()); },
		unmounted: function () { this.stop(); },
		watch: { id: function () { this.load(); } },
		methods: {
			money: fmt.money, date: fmt.date, num: fmt.num, go: go,
			label: function (a) { return ACTION_LABELS[a] || [a, 'check', '']; },
			load: async function () { try { this.r = await get('record/contratos/' + this.id); } catch (e) { toast(e.message, 'error'); } },
			act: function (a) { runContractAction(Object.assign({}, this.card, { forma: this.c.forma_pagamento }), a); },
			cancelMeasure: async function (m) { if (!window.confirm('Cancelar a medição ' + m.numero + '? As parcelas em aberto dela serão canceladas.')) { return; } try { const r = await contractOp(this.c.id, 'cancelar_medicao', { medicao: m.id }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); } },
			removeExtra: async function (a) { if (!window.confirm('Remover este adicional?')) { return; } try { const r = await contractOp(this.c.id, 'remover_adicional', { item: a.id }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); } },
			emitNote: function () { openAction('nota', { origem: 'contrato', id: this.c.id, sugestao: this.r.nota_sugestao }); },
			copyLink: function () {
				const d = this.r.documentos.find((x) => x.tipo === (['orcamento', 'solicitacao'].indexOf(this.c.status) !== -1 ? 'orcamento' : 'contrato'));
				navigator.clipboard.writeText(d.publico).then(function () { toast('Link copiado — cole no WhatsApp ou e-mail do cliente.'); }, function () { window.prompt('Copie o link:', d.publico); });
			},
			remove: async function () {
				if (!window.confirm('Excluir definitivamente este orçamento?')) { return; }
				try { const r = await api('DELETE', 'record/contratos/' + this.c.id); toast(r.mensagem); go('contratos'); } catch (e) { toast(e.message, 'error'); }
			},
		},
	};

	/** Editor de orçamento/contrato com preço e disponibilidade ao vivo. */
	const ContractEditor = {
		components: { Ic: Ic, Autocomplete: Autocomplete, RecordForm: RecordForm },
		props: { id: [String, Number] },
		template: `<div class="page">
			<a class="back" :href="id ? '#/contratos/' + id : '#/contratos'"><Ic n="back"/> {{ id ? 'Voltar à locação' : 'Locações' }}</a>
			<div v-if="!rec" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div class="page-head"><h1>{{ id ? 'Editar ' + (rec.numero || '') : 'Nova locação' }}</h1>
					<div class="actions"><button class="btn btn-primary" :disabled="busy" @click="save"><Ic n="check"/> {{ busy ? 'Salvando...' : 'Salvar' }}</button></div>
				</div>
				<div class="grid grid-main">
					<div>
						<section class="card">
							<h3><Ic n="users"/> Cliente e período</h3>
							<div class="form-grid">
								<label class="field" style="grid-column: span 2"><span>Cliente *</span>
									<Autocomplete table="clientes" v-model="rec.cliente_id" :label="labels.cliente_id" placeholder="Nome, CPF ou CNPJ" allow-new @new="newClient" @select="(it) => { labels.cliente_id = it ? it.label : ''; blocked = it && Number(it.bloqueado); }"/>
									<small v-if="blocked" class="help" style="color:#dc2626">Cliente bloqueado — não poderá reservar nem retirar.</small>
								</label>
								<label class="field"><span>Início *</span><input type="date" v-model="rec.data_inicio"></label>
								<label class="field"><span>Devolução prevista *</span><input type="date" :min="rec.data_inicio" v-model="rec.data_prev_devolucao"></label>
							</div>
							<div class="quick" style="margin-top:10px;align-items:center"><span class="muted small">Duração:</span><button v-for="n in [1, 3, 7, 15, 30, 60]" :key="n" class="btn btn-xs" :class="{ 'btn-dark': days === n }" @click="rec.data_prev_devolucao = endFor(rec.data_inicio, n)">{{ n }} dia{{ n > 1 ? 's' : '' }}</button><b style="margin-left:auto">{{ days }} dia(s)</b></div>
						</section>

						<section class="card">
							<h3><Ic n="box"/> Equipamentos</h3>
							<div class="table-wrap"><table class="items">
								<thead><tr><th style="width:30%">Equipamento</th><th style="width:96px">Qtd</th><th style="width:150px">Cobrança</th><th style="width:80px">Períodos</th><th style="width:110px">Unitário</th><th style="width:90px">Desconto</th><th style="width:110px;text-align:right">Total</th><th style="width:36px"></th></tr></thead>
								<tbody>
									<tr v-for="(it, i) in items" :key="it._k">
										<td>
											<Autocomplete table="equipamentos" :model-value="it.ref_id" :label="it.nome || it.descricao" :clearable="false" placeholder="Buscar equipamento" @select="(e) => pick(it, e)"/>
											<div v-if="it.q" class="avail" :class="it.q.livres >= it.qtd ? 'ok' : 'no'">{{ it.q.livres >= it.qtd ? '✔ ' + num(it.q.livres) + ' livre(s) no período' : '✖ só ' + num(it.q.livres) + ' livre(s) no período' }}<span v-if="it.q.melhor.total" class="muted" style="font-weight:500"> · melhor: {{ money(it.q.melhor.total) }} ({{ it.q.melhor.descricao }})</span></div>
										</td>
										<td><input class="input" type="number" min="1" step="1" v-model.number="it.qtd" @change="check(it)"></td>
										<td><select class="input" v-model="it.periodo_tipo" @change="price(it)"><option v-for="(p, k) in periodos" :key="k" :value="k">{{ p.label }}</option></select></td>
										<td><input class="input" type="number" min="0" step="1" v-model.number="it.periodos" :disabled="it.periodo_tipo === 'pacote'"></td>
										<td><input class="input" type="number" min="0" step="0.01" v-model.number="it.valor_unit"></td>
										<td><input class="input" type="number" min="0" step="0.01" v-model.number="it.desconto"></td>
										<td class="tot">{{ money(total(it)) }}</td>
										<td><button type="button" class="btn btn-ghost btn-sm" @click="items.splice(i, 1)" aria-label="Remover"><Ic n="trash"/></button></td>
									</tr>
								</tbody>
							</table></div>
							<div class="quick" style="margin-top:8px"><button class="btn btn-sm" @click="add()"><Ic n="plus"/> Adicionar equipamento</button><button class="btn btn-sm" @click="bestAll" title="Usa a combinação mais barata de diárias, semanas, quinzenas e meses"><Ic n="star"/> Aplicar melhor tarifa em todos</button></div>
						</section>

						<section class="card">
							<h3><Ic n="pin"/> Obra, entrega e observações</h3>
							<RecordForm :schema="schema" :rec="rec" :labels="labels" :is-new="!id" :exclude="exclude"/>
						</section>
					</div>
					<div style="position:sticky;top:76px">
						<section class="card">
							<h3><Ic n="money"/> Resumo</h3>
							<div class="totals">
								<div class="l"><span>Equipamentos</span><b>{{ money(sub) }}</b></div>
								<label class="l" style="align-items:center"><span>Frete</span><input class="input" style="width:120px;text-align:right" type="number" min="0" step="0.01" v-model.number="rec.valor_frete"></label>
								<label class="l" style="align-items:center"><span>Desconto</span><input class="input" style="width:120px;text-align:right" type="number" min="0" step="0.01" v-model.number="rec.desconto"></label>
								<div class="l grand"><span>Total</span><span>{{ money(grand) }}</span></div>
								<div class="l small muted"><span>Caução sugerida</span><span>{{ money(deposit) }} <button v-if="deposit && !Number(rec.caucao)" class="btn btn-xs" @click="rec.caucao = deposit">usar</button></span></div>
							</div>
							<div v-if="rec.cobranca === 'medicao'" class="notice info" style="margin-top:12px">Cobrança por medição: este total é uma estimativa. A cada {{ rec.medicao_ciclo || 30 }} dias o sistema cobra só os dias e quantidades que ficaram com o cliente. Dica: use a cobrança <b>Mensal</b> nos itens.</div>
							<div v-if="conflicts" class="notice error" style="margin-top:12px">{{ conflicts }} item(ns) sem disponibilidade suficiente no período. Dá para salvar como orçamento, mas não reservar.</div>
							<button class="btn btn-primary btn-block" style="margin-top:12px" :disabled="busy" @click="save"><Ic n="check"/> Salvar {{ id ? 'alterações' : 'orçamento' }}</button>
						</section>
					</div>
				</div>
			</template>
		</div>`,
		data: function () { return { rec: null, labels: {}, items: [], busy: false, blocked: false, schema: D.modulos.contratos, periodos: D.periodos, exclude: ['numero', 'cliente_id', 'status', 'data_inicio', 'data_prev_devolucao', 'data_encerramento', 'subtotal', 'adicionais', 'total', 'valor_faturado', 'valor_frete', 'desconto'] }; },
		computed: {
			days: function () { return rentalDays(this.rec.data_inicio, this.rec.data_prev_devolucao); },
			sub: function () { return this.items.reduce((a, it) => a + this.total(it), 0); },
			grand: function () { return Math.max(0, this.sub + (Number(this.rec.valor_frete) || 0) - (Number(this.rec.desconto) || 0)); },
			deposit: function () { return this.items.reduce(function (a, it) { return a + (it.q ? it.q.caucao * (it.qtd || 0) : 0); }, 0); },
			conflicts: function () { return this.items.filter(function (it) { return it.q && it.q.livres < it.qtd; }).length; },
		},
		created: async function () {
			try {
				if (this.id) {
					const r = await get('record/contratos/' + this.id);
					this.rec = r.registro;
					this.labels = r.rotulos;
					this.items = r.itens.map(function (it) { return Object.assign({}, it, { _k: Math.random(), qtd: Number(it.qtd), periodos: Number(it.periodos), valor_unit: Number(it.valor_unit), desconto: Number(it.desconto) }); });
				} else {
					const q = store.route.query;
					const r = await get('new/contratos', q);
					this.rec = r.registro;
					this.labels = r.rotulos;
					if (q.equip) { this.add(); const e = (await get('lookup/equipamentos', { q: '' })).itens.find(function (x) { return String(x.id) === String(q.equip); }); if (e) { this.pick(this.items[0], e); } }
				}
				if (!this.items.length) { this.add(); }
				this.items.forEach((it) => { if (it.ref_id) { this.check(it); } });
			} catch (e) { toast(e.message, 'error'); }
			this.recheck = debounce(() => { this.items.forEach((it) => { if (it.ref_id) { this.check(it, true); } }); }, 300);
		},
		watch: {
			'rec.data_inicio': function (n, o) { if (o && this.recheck) { this.recheck(); } },
			'rec.data_prev_devolucao': function (n, o) { if (o && this.recheck) { this.recheck(); } },
		},
		methods: {
			money: fmt.money, num: fmt.num, addDays: addDays, endFor: endFor,
			total: function (it) { return Math.max(0, (Number(it.qtd) || 0) * (Number(it.periodos) || 0) * (Number(it.valor_unit) || 0) - (Number(it.desconto) || 0)); },
			add: function () { this.items.push({ _k: Math.random(), id: 0, ref_id: 0, descricao: '', qtd: 1, periodo_tipo: 'pacote', periodos: 1, valor_unit: 0, desconto: 0, q: null }); },
			pick: function (it, e) { if (!e) { return; } it.ref_id = e.id; it.nome = e.label; it.descricao = e.nome; this.check(it, true); },
			check: async function (it, reprice) {
				try {
					it.q = await get('quote', { equip: it.ref_id, inicio: this.rec.data_inicio, fim: this.rec.data_prev_devolucao, excluir: this.id || 0 });
					if (reprice) { this.price(it); }
				} catch (e) { it.q = null; }
			},
			price: function (it) {
				if (!it.q) { return; }
				const p = this.periodos[it.periodo_tipo];
				if (it.periodo_tipo === 'pacote') {
					it.periodos = 1;
					it.valor_unit = it.q.melhor.total;
					const base = (it.descricao || '').split(' — ')[0] || it.nome || '';
					it.descricao = it.q.melhor.descricao ? base + ' — ' + it.q.melhor.descricao : base;
				} else if (p && p.dias) {
					it.valor_unit = Number(it.q.tarifas[it.periodo_tipo]) || 0;
					it.periodos = Math.ceil(it.q.dias / p.dias);
					it.descricao = it.descricao.split(' — ')[0];
				}
			},
			bestAll: function () { this.items.forEach((it) => { it.periodo_tipo = 'pacote'; this.price(it); }); },
			newClient: function (text) {
				openAction('cliente', { nome: text, onSave: (c) => { this.rec.cliente_id = c.id; this.labels.cliente_id = c.label; } });
			},
			save: async function () {
				if (!this.rec.cliente_id || this.rec.cliente_id === '0') { toast('Escolha o cliente.', 'error'); return; }
				this.busy = true;
				const itens = this.items.filter(function (it) { return it.ref_id; }).map(function (it) { return { id: it.id, ref_id: it.ref_id, descricao: it.descricao, qtd: it.qtd, periodo_tipo: it.periodo_tipo, periodos: it.periodos, valor_unit: it.valor_unit, desconto: it.desconto }; });
				try {
					const r = await post('record/contratos' + (this.id ? '/' + this.id : ''), { campos: this.rec, itens: itens });
					toast(r.mensagem);
					store.tick++;
					go('contratos/' + r.id);
				} catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	/* ==================================================== agenda da frota */

	const FleetPage = {
		components: { Ic: Ic, Autocomplete: Autocomplete },
		template: `<div class="page">
			<div class="page-head">
				<h1>Agenda da frota</h1><span class="sub">ocupação de cada equipamento por dia</span>
			</div>
			<div class="toolbar">
				<span class="quick"><button class="btn btn-sm" @click="shift(-7)"><Ic n="back"/></button><button class="btn btn-sm" @click="from = addDays(today, -3); load()">Hoje</button><button class="btn btn-sm" @click="shift(7)" style="transform:scaleX(-1)"><Ic n="back"/></button></span>
				<input type="date" class="input" style="min-width:0" v-model="from" @change="load">
				<span class="seg"><button v-for="n in [14, 30, 60]" :key="n" :class="{ on: days === n }" @click="days = n; load()">{{ n }} dias</button></span>
				<div style="min-width:220px"><Autocomplete table="categorias" :model-value="cat" :label="catLabel" placeholder="Todas as categorias" @select="(c) => { cat = c ? c.id : 0; catLabel = c ? c.label : ''; load(); }"/></div>
				<input class="input" v-model="q" @input="reload" placeholder="Filtrar equipamento">
				<span class="grow"></span>
				<div class="legend"><span><i class="tl-ativo"></i>Em locação</span><span><i class="tl-atrasado"></i>Atrasado</span><span><i class="tl-reservado"></i>Reservado</span><span><i class="tl-orcamento" style="border:1.5px dashed #2563eb"></i>Orçamento</span></div>
			</div>
			<div v-if="!t" class="empty"><span class="spinner dark"></span></div>
			<div v-else class="timeline">
				<div class="tl-grid" :style="{ gridTemplateColumns: '230px ' + (t.dias.length * W) + 'px' }">
					<div class="tl-corner">{{ t.linhas.length }} equipamento(s)</div>
					<div style="display:grid;position:sticky;top:0;z-index:3" :style="{ gridTemplateColumns: 'repeat(' + t.dias.length + ', ' + W + 'px)' }">
						<div v-for="d in t.dias" :key="d.data" class="tl-head" :class="{ fds: d.fds, hoje: d.hoje }"><b>{{ d.dia }}</b>{{ d.semana }}</div>
					</div>
					<template v-for="row in t.linhas" :key="row.id">
						<div class="tl-name" @click="go('equipamentos/' + row.id)" :style="{ height: rowH(row) + 'px' }">{{ row.nome }}<small>{{ row.frota > 1 ? 'frota ' + row.frota : '' }}{{ row.status === 'manutencao' ? ' · em manutenção' : '' }}</small></div>
						<div class="tl-lane" :style="laneStyle(row)" @click="emptyClick($event, row)">
							<div v-for="(b, i) in placed(row)" :key="i" class="tl-bar" :class="'tl-' + b.status" :title="b.numero + ' — ' + b.cliente + ' (' + date(b.inicio) + ' a ' + date(b.fim) + ')' + (b.qtd > 1 ? ' · ' + b.qtd + ' un.' : '')" :style="{ position: 'absolute', left: (b.col * W + 2) + 'px', width: (b.span * W - 4) + 'px', top: (6 + b.lane * 30) + 'px', height: '24px', margin: 0 }" @click.stop="go('contratos/' + b.contrato)">{{ b.qtd > 1 ? b.qtd + '× ' : '' }}{{ b.cliente }}</div>
						</div>
					</template>
				</div>
			</div>
			<p class="muted small">Clique num dia vazio para orçar aquele equipamento a partir da data.</p>
		</div>`,
		data: function () { return { t: null, from: addDays(D.hoje, -3), days: 30, cat: 0, catLabel: '', q: '', today: D.hoje }; },
		computed: { W: function () { return this.days <= 14 ? 64 : (this.days <= 30 ? 42 : 26); } },
		created: function () { this.reload = debounce(this.load, 300); this.load(); this.stop = watch(function () { return store.tick; }, () => this.load()); },
		unmounted: function () { this.stop(); },
		methods: {
			date: fmt.date, go: go, addDays: addDays,
			load: async function () { try { this.t = await get('timeline', { de: this.from, dias: this.days, cat: this.cat, q: this.q }); } catch (e) { toast(e.message, 'error'); } },
			shift: function (n) { this.from = addDays(this.from, n); this.load(); },
			placed: function (row) {
				const lanes = [];
				return row.bars.map(function (b) {
					let lane = 0;
					while (lanes[lane] !== undefined && lanes[lane] > b.col) { lane++; }
					lanes[lane] = b.col + b.span;
					return Object.assign({ lane: lane }, b);
				});
			},
			rowH: function (row) { const n = this.placed(row).reduce(function (a, b) { return Math.max(a, b.lane + 1); }, 1); return Math.max(46, 12 + n * 30); },
			laneStyle: function (row) {
				const W = this.W;
				const hoje = this.t.dias.findIndex(function (d) { return d.hoje; });
				const bg = ['repeating-linear-gradient(90deg, transparent 0, transparent ' + (W - 1) + 'px, #f1f2f4 ' + (W - 1) + 'px, #f1f2f4 ' + W + 'px)'];
				if (hoje >= 0) { bg.push('linear-gradient(90deg, transparent ' + (hoje * W) + 'px, #fff8e6 ' + (hoje * W) + 'px, #fff8e6 ' + ((hoje + 1) * W) + 'px, transparent ' + ((hoje + 1) * W) + 'px)'); }
				return { position: 'relative', height: this.rowH(row) + 'px', borderBottom: '1px solid #f1f2f4', backgroundImage: bg.join(','), cursor: 'cell', backgroundColor: row.status === 'manutencao' ? '#fff7ed' : '#fff' };
			},
			emptyClick: function (e, row) {
				const idx = Math.floor(e.offsetX / this.W);
				const d = this.t.dias[idx];
				if (d) { go('contratos/novo?equip=' + row.id + '&data_inicio=' + d.data + '&data_prev_devolucao=' + addDays(d.data, 7)); }
			},
		},
	};

	/* ============================================================ registros */

	const RecordPage = {
		components: { Ic: Ic, Badge: Badge, RecordForm: RecordForm, ProductItems: ProductItems },
		props: { module: String, id: [String, Number] },
		template: `<div class="page">
			<a class="back" :href="'#/' + module"><Ic n="back"/> {{ m.plural }}</a>
			<div v-if="!rec" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div class="detail-head">
					<div class="ttl"><h1>{{ title }} <Badge v-if="statusField && rec[statusField]" :e="m.entity" :s="extra.situacao || rec[statusField]"/></h1><p v-if="subtitle">{{ subtitle }}</p></div>
					<div class="acts">
						<template v-if="module === 'equipamentos' && id"><a class="btn" :href="'#/contratos/novo?equip=' + id"><Ic n="file"/> Orçar locação</a><a class="btn" :href="'#/os/novo?equipamento_id=' + id"><Ic n="tool"/> Abrir OS</a></template>
						<template v-if="module === 'clientes' && id"><a v-if="extra.info && extra.info.whatsapp" class="btn btn-wa" :href="extra.info.whatsapp" target="_blank" rel="noopener"><Ic n="wa"/> WhatsApp</a><a class="btn" :href="'#/contratos/novo?cliente_id=' + id"><Ic n="file"/> Nova locação</a></template>
						<template v-if="module === 'financeiro' && id && fin">
							<button v-if="rec.status === 'aberto'" class="btn btn-primary" @click="pay"><Ic n="check"/> {{ rec.tipo === 'receber' ? 'Receber' : 'Pagar' }}</button>
							<button v-if="rec.status === 'pago'" class="btn" @click="finOp('estornar', 'Estornar este pagamento?')">Estornar baixa</button>
							<button v-if="rec.status === 'aberto'" class="btn btn-danger" @click="finOp('cancelar', 'Cancelar este lançamento?')">Cancelar</button>
							<button v-if="rec.status === 'cancelado'" class="btn" @click="finOp('reabrir')">Reabrir</button>
						</template>
						<button class="btn btn-primary" :disabled="busy" @click="save"><Ic n="check"/> {{ busy ? 'Salvando...' : 'Salvar' }}</button>
					</div>
				</div>
				<div class="grid grid-main" style="margin-top:16px">
					<div>
						<section class="card"><RecordForm :schema="m" :rec="rec" :labels="labels" :is-new="!id"/></section>
						<section class="card" v-if="m.items === 'produto'"><h3><Ic n="box"/> Peças, produtos e serviços</h3><ProductItems :items="items" :locked="itemsLocked"/></section>
					</div>
					<div>
						<template v-if="module === 'equipamentos' && extra.info">
							<section class="card">
								<div v-if="extra.info.foto" style="margin:-18px -18px 14px;border-radius:12px 12px 0 0;overflow:hidden"><img :src="extra.info.foto" style="width:100%;display:block;max-height:220px;object-fit:cover"></div>
								<h3><Ic n="gauge"/> Situação agora</h3>
								<div class="kpis" style="grid-template-columns:repeat(3,1fr);margin:0">
									<div class="kpi" style="cursor:default;padding:10px"><div><div class="kv">{{ extra.info.frota }}</div><div class="kl">frota</div></div></div>
									<div class="kpi" style="cursor:default;padding:10px"><div><div class="kv">{{ num(extra.info.locados) }}</div><div class="kl">locados</div></div></div>
									<div class="kpi" style="cursor:default;padding:10px"><div><div class="kv" style="color:#15803d">{{ num(extra.info.livres_hoje) }}</div><div class="kl">livres hoje</div></div></div>
								</div>
								<p v-if="extra.info.preventiva" class="notice" :class="extra.info.preventiva.vencida ? 'error' : 'info'" style="margin:12px 0 0">{{ extra.info.preventiva.vencida ? 'Manutenção preventiva vencida pelo horímetro.' : 'Próxima preventiva em ' + num(extra.info.preventiva.faltam) + ' h.' }}</p>
							</section>
							<section class="card"><h3><Ic n="calendar"/> Agenda</h3>
								<div v-if="!extra.info.agenda.length" class="muted small">Sem reservas ou locações futuras.</div>
								<div v-for="a in extra.info.agenda" :key="a.id" class="agenda-item" style="cursor:pointer" @click="go('contratos/' + a.id)"><div class="tx"><strong>{{ a.cliente }}</strong><span>{{ a.numero }} · {{ date(a.inicio) }} a {{ date(a.fim) }}{{ a.qtd > 1 ? ' · ' + num(a.qtd) + ' un.' : '' }}</span></div><Badge e="contrato" :s="a.status"/></div>
							</section>
							<section class="card" v-if="extra.info.manutencoes.length"><h3><Ic n="tool"/> Manutenções</h3>
								<div v-for="o in extra.info.manutencoes" :key="o.id" class="agenda-item" style="cursor:pointer" @click="go('os/' + o.id)"><div class="tx"><strong>{{ o.numero }}</strong><span>{{ o.tipo }} · {{ date(o.data_abertura) }} · {{ money(o.total) }}</span></div><Badge e="os" :s="o.status"/></div>
							</section>
						</template>

						<template v-if="module === 'clientes' && extra.info">
							<section class="card"><h3><Ic n="money"/> Financeiro</h3>
								<div v-if="extra.info.em_aberto !== null" class="totals"><div class="l"><span>Em aberto</span><b>{{ money(extra.info.em_aberto) }}</b></div><div class="l"><span>Vencido</span><b :style="{ color: extra.info.vencido > 0 ? '#dc2626' : '' }">{{ money(extra.info.vencido) }}</b></div></div>
								<div style="margin-top:12px"><span v-if="extra.info.tem_acesso" class="badge b-encerrado">acesso à área do cliente ativo</span><button v-else-if="extra.info.pode_acesso" class="btn btn-sm" @click="access"><Ic n="users"/> Criar acesso à área do cliente</button></div>
							</section>
							<section class="card"><h3><Ic n="file"/> Locações</h3>
								<div v-if="!extra.info.contratos.length" class="muted small">Nenhuma locação ainda.</div>
								<div v-for="c in extra.info.contratos" :key="c.id" class="agenda-item" style="cursor:pointer" @click="go('contratos/' + c.id)"><div class="tx"><strong>{{ c.numero }}</strong><span>{{ date(c.inicio) }} a {{ date(c.fim) }} · {{ money(c.total) }}</span></div><Badge e="contrato" :s="c.status"/></div>
							</section>
						</template>

						<template v-if="module === 'produtos' && id">
							<section class="card"><h3><Ic n="box"/> Estoque</h3>
								<div class="big">{{ num(rec.estoque_atual, 2) }} <small class="muted" style="font-size:14px">{{ rec.unidade }}</small></div>
								<button v-if="rec.tipo !== 'servico'" class="btn btn-sm" style="margin-top:8px" @click="openAction('estoque', { id: id, nome: rec.nome, estoque: num(rec.estoque_atual, 2) + ' ' + rec.unidade })">Movimentar estoque</button>
								<div style="margin-top:12px"><div v-for="mv in extra.movimentos" :key="mv.id" class="agenda-item"><div class="tx"><strong>{{ mv.tipo }} · {{ num(mv.qtd, 2) }}</strong><span>{{ mv.criado_em.slice(0, 16) }} {{ mv.obs }}</span></div></div></div>
							</section>
						</template>

						<template v-if="module === 'financeiro' && id">
							<section class="card"><h3><Ic n="receipt"/> Situação</h3>
								<div class="totals">
									<div class="l"><span>Valor</span><b>{{ money(rec.valor) }}</b></div>
									<div class="l" v-if="rec.status === 'aberto'"><span>Restante</span><b>{{ money(extra.restante) }}</b></div>
									<template v-if="rec.status === 'pago'"><div class="l"><span>Pago em</span><b>{{ date(rec.data_pagamento) }}</b></div><div class="l"><span>Total pago</span><b>{{ money(Number(rec.valor_pago) + Number(rec.juros) + Number(rec.multa) - Number(rec.desconto)) }}</b></div></template>
									<div class="l" v-if="extra.encargos && extra.encargos.dias > 0 && rec.status === 'aberto'"><span>Atraso</span><b style="color:#dc2626">{{ extra.encargos.dias }} dia(s)</b></div>
								</div>
								<p v-if="extra.origem" style="margin:12px 0 0">Origem: <a :href="'#/' + extra.origem.rota"><b>{{ extra.origem.nome }}</b></a></p>
							</section>
						</template>

						<section class="card" v-if="extra.documentos && extra.documentos.length"><h3><Ic n="print"/> Documentos</h3><div class="doclinks"><a v-for="d in extra.documentos" :key="d.tipo" :href="d.url" target="_blank" rel="noopener"><Ic n="file"/> {{ d.nome }}</a></div></section>
						<section class="card" v-if="extra.cobrancas && extra.cobrancas.length"><h3><Ic n="receipt"/> Cobranças</h3><div v-for="f in extra.cobrancas" :key="f.id" class="agenda-item" style="cursor:pointer" @click="go('financeiro/' + f.id)"><div class="tx"><strong>{{ money(f.valor) }}</strong><span>vence {{ date(f.vencimento) }}</span></div><Badge e="financeiro" :s="f.situacao"/></div></section>
						<section class="card" v-if="extra.nota_sugestao"><h3><Ic n="receipt"/> Nota fiscal</h3>
							<div v-for="n in extra.notas" :key="n.id" class="agenda-item" style="cursor:pointer" @click="go('notas/' + n.id)"><div class="tx"><strong>{{ n.tipo.toUpperCase() }} {{ n.numero || '' }}</strong><span>{{ money(n.valor) }}</span></div><Badge e="nota" :s="n.status"/></div>
							<button class="btn btn-sm" @click="openAction('nota', { origem: module === 'os' ? 'os' : 'venda', id: id, sugestao: extra.nota_sugestao })"><Ic n="plus"/> Emitir {{ extra.nota_sugestao.tipo === 'nfe' ? 'NF-e' : 'NFS-e' }}</button>
						</section>
						<section class="card" v-if="module === 'notas' && rec.pdf_url"><a class="btn btn-block" :href="rec.pdf_url" target="_blank" rel="noopener"><Ic n="file"/> Abrir PDF da nota</a></section>

						<section class="card" v-if="id">
							<h3><Ic n="clock"/> Histórico</h3>
							<ul class="timeline-hist"><li v-for="(h, i) in extra.historico" :key="i"><b>{{ h.acao }}</b><small>{{ h.quando }} · {{ h.usuario }}</small><small v-if="h.detalhes">{{ h.detalhes }}</small></li></ul>
							<div style="margin-top:10px"><button v-if="extra.excluir === true" class="btn btn-sm btn-danger" @click="remove"><Ic n="trash"/> Excluir</button><p v-else-if="extra.excluir" class="muted small">{{ extra.excluir }}</p></div>
						</section>
					</div>
				</div>
			</template>
		</div>`,
		data: function () { return { rec: null, labels: {}, items: [], extra: {}, busy: false, fin: D.usuario.financeiro }; },
		computed: {
			m: function () { return D.modulos[this.module]; },
			statusField: function () { return this.m.fields.status ? 'status' : null; },
			title: function () { const r = this.rec; return this.id ? (r.numero || r.nome || r.descricao || r.referencia || this.m.singular + ' #' + this.id) : 'Novo(a) ' + this.m.singular.toLowerCase(); },
			subtitle: function () {
				const r = this.rec;
				if (this.module === 'equipamentos') { return [r.marca, r.modelo, r.numero_serie ? 'série ' + r.numero_serie : ''].filter(Boolean).join(' · '); }
				if (this.module === 'clientes') { return [r.documento, r.cidade && r.cidade + (r.uf ? '/' + r.uf : '')].filter(Boolean).join(' · '); }
				if (this.module === 'financeiro' && this.id) { return (r.tipo === 'receber' ? 'A receber' : 'A pagar') + ' · vence ' + fmt.date(r.vencimento); }
				return '';
			},
			itemsLocked: function () { return !!Number(this.rec.estoque_baixado) || (this.module === 'vendas' && this.rec.status === 'cancelada'); },
		},
		created: function () { this.load(); this.stop = watch(function () { return store.tick; }, () => { if (this.id) { this.load(); } }); },
		unmounted: function () { this.stop(); },
		watch: { id: function () { this.load(); }, module: function () { this.load(); } },
		methods: {
			money: fmt.money, date: fmt.date, num: fmt.num, go: go, openAction: openAction,
			apply: function (r) {
				this.rec = r.registro;
				this.labels = r.rotulos || {};
				this.extra = r;
				this.items = (r.itens || []).map(function (it) { return Object.assign({}, it, { _k: Math.random(), qtd: Number(it.qtd), valor_unit: Number(it.valor_unit), desconto: Number(it.desconto) }); });
			},
			load: async function () {
				try {
					if (this.id) { this.apply(await get('record/' + this.module + '/' + this.id)); } else { const r = await get('new/' + this.module, store.route.query); this.apply(r); if (this.m.items && !this.items.length) { this.items.push({ _k: 1, id: 0, ref_id: 0, descricao: '', qtd: 1, valor_unit: 0, desconto: 0 }); } }
				} catch (e) { toast(e.message, 'error'); }
			},
			save: async function () {
				this.busy = true;
				const body = { campos: this.rec };
				if (this.m.items) { body.itens = this.items.filter(function (it) { return it.ref_id || it.descricao; }).map(function (it) { return { id: it.id, ref_id: it.ref_id, descricao: it.descricao, qtd: it.qtd, valor_unit: it.valor_unit, desconto: it.desconto }; }); }
				try {
					const r = await post('record/' + this.module + (this.id ? '/' + this.id : ''), body);
					toast(r.mensagem);
					if (!this.id) { go(this.module + '/' + r.id); } else { this.apply(r); }
				} catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
			remove: async function () {
				if (!window.confirm('Excluir definitivamente? Não há como desfazer.')) { return; }
				try { const r = await api('DELETE', 'record/' + this.module + '/' + this.id); toast(r.mensagem); go(this.module); } catch (e) { toast(e.message, 'error'); }
			},
			pay: function () { openAction('pagar', { id: this.id, tipo: this.rec.tipo, descricao: this.rec.descricao, restante: this.extra.restante, encargos: this.extra.encargos, forma: this.rec.forma_pagamento }); },
			finOp: async function (op, ask) {
				if (ask && !window.confirm(ask)) { return; }
				try { const r = await post('finance/' + this.id + '/action', { op: op }); done(r.mensagem); } catch (e) { toast(e.message, 'error'); }
			},
			access: async function () { try { const r = await post('client/' + this.id + '/access'); done(r.mensagem); } catch (e) { toast(e.message, 'error'); } },
		},
	};

	/* ============================================================ relatórios */

	const ReportsPage = {
		components: { Ic: Ic, ChartBox: ChartBox },
		props: { rkey: String },
		template: `<div class="page">
			<div class="page-head"><h1>Relatórios</h1>
				<div class="actions"><button class="btn" @click="csv" :disabled="!r"><Ic n="down"/> Exportar planilha</button><button class="btn" onclick="window.print()"><Ic n="print"/> Imprimir</button></div>
			</div>
			<div class="tabs"><button v-for="(rep, k) in list" :key="k" :class="{ on: k === key }" @click="go('relatorios/' + k)">{{ rep.titulo }}</button></div>
			<div class="toolbar" v-if="r && r.period">
				<input type="date" class="input" style="min-width:0" v-model="de" @change="load"><span class="muted">até</span><input type="date" class="input" style="min-width:0" v-model="ate" @change="load">
				<span class="quick"><button class="btn btn-sm" @click="range('mes')">Este mês</button><button class="btn btn-sm" @click="range('anterior')">Mês passado</button><button class="btn btn-sm" @click="range('90')">90 dias</button><button class="btn btn-sm" @click="range('ano')">Este ano</button></span>
			</div>
			<div v-if="!r" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div class="kpis" v-if="Object.keys(r.summary).length"><div v-for="(v, l) in r.summary" :key="l" class="kpi" style="cursor:default"><div><div class="kv">{{ v }}</div><div class="kl">{{ l }}</div></div></div></div>
				<section class="card" v-if="r.chart && r.chart.items.length"><ChartBox :type="r.chart.type" :labels="r.chart.items.map(i => i.label)" :datasets="chartSets" :options="chartOpts"/></section>
				<p v-if="r.note" class="muted small">{{ r.note }}</p>
				<section class="card" style="padding:0;margin-top:16px"><div class="table-wrap"><table class="t">
					<thead><tr><th v-for="c in r.cols" :key="c">{{ c }}</th><th v-if="hasWa"></th></tr></thead>
					<tbody>
						<tr v-if="!r.rows.length"><td :colspan="r.cols.length" class="empty">Nada a mostrar no período.</td></tr>
						<tr v-for="(row, i) in r.rows" :key="i" :class="[row.tone, { click: row.open }]" @click="row.open && go(row.open)"><td v-for="(c, j) in row.cells" :key="j">{{ c }}</td><td v-if="hasWa" @click.stop><a v-if="row.whatsapp" class="btn btn-wa btn-xs" :href="row.whatsapp" target="_blank" rel="noopener"><Ic n="wa"/> Cobrar</a></td></tr>
					</tbody>
				</table></div></section>
			</template>
		</div>`,
		data: function () { const d = new Date(D.hoje + 'T12:00:00'); return { r: null, de: D.hoje.slice(0, 8) + '01', ate: new Date(d.getFullYear(), d.getMonth() + 1, 0, 12).toISOString().slice(0, 10) }; },
		computed: {
			list: function () { const o = {}; Object.keys(D.relatorios).forEach(function (k) { if (D.relatorios[k].ok) { o[k] = D.relatorios[k]; } }); return o; },
			key: function () { return this.rkey && this.list[this.rkey] ? this.rkey : Object.keys(this.list)[0]; },
			hasWa: function () { return this.r.rows.some(function (x) { return x.whatsapp; }); },
			money: function () { return ['faturamento', 'fluxo', 'clientes'].indexOf(this.key) !== -1; },
			chartSets: function () {
				const it = this.r.chart.items;
				return [{ label: this.r.chart.label, data: it.map(function (i) { return i.value; }), backgroundColor: this.r.chart.type === 'line' ? 'rgba(245,164,0,.15)' : (this.r.chart.type === 'doughnut' ? PALETTE : '#f5a400'), borderColor: '#f5a400', fill: this.r.chart.type === 'line', tension: .3, borderRadius: 6, borderWidth: this.r.chart.type === 'doughnut' ? 0 : 2 }];
			},
			chartOpts: function () { return { money: this.money, indexAxis: this.r.chart.type === 'bar' && this.r.chart.items.length > 8 ? 'y' : 'x' }; },
		},
		created: function () { this.load(); },
		watch: { key: function () { this.r = null; this.load(); } },
		methods: {
			go: go,
			load: async function () { try { this.r = await get('report/' + this.key, { de: this.de, ate: this.ate }); } catch (e) { toast(e.message, 'error'); } },
			range: function (k) {
				const d = new Date(D.hoje + 'T12:00:00');
				const iso = function (x) { return new Date(x.getFullYear(), x.getMonth(), x.getDate(), 12).toISOString().slice(0, 10); };
				if (k === 'mes') { this.de = iso(new Date(d.getFullYear(), d.getMonth(), 1)); this.ate = iso(new Date(d.getFullYear(), d.getMonth() + 1, 0)); }
				if (k === 'anterior') { this.de = iso(new Date(d.getFullYear(), d.getMonth() - 1, 1)); this.ate = iso(new Date(d.getFullYear(), d.getMonth(), 0)); }
				if (k === '90') { this.de = addDays(D.hoje, -90); this.ate = D.hoje; }
				if (k === 'ano') { this.de = d.getFullYear() + '-01-01'; this.ate = d.getFullYear() + '-12-31'; }
				this.load();
			},
			csv: function () { downloadCsv('relatorio-' + this.key + '-' + this.de + '.csv', [this.r.cols].concat(this.r.rows.map(function (x) { return x.cells; }))); },
		},
	};

	/* ========================================================== configurações */

	const SettingsPage = {
		components: { Ic: Ic },
		template: `<div class="page">
			<div class="page-head"><h1>Configurações</h1><div class="actions"><a v-if="wpAdmin" class="btn" :href="wpAdmin">Painel do WordPress</a><button class="btn btn-primary" :disabled="!s || busy" @click="save"><Ic n="check"/> Salvar</button></div></div>
			<div v-if="!s" class="empty"><span class="spinner dark"></span></div>
			<template v-else>
				<div class="tabs"><button v-for="(f, sec) in s.secoes" :key="sec" :class="{ on: tab === sec }" @click="tab = sec">{{ sec }}</button></div>
				<section class="card">
					<div class="form-grid">
						<label v-for="(f, k) in s.secoes[tab]" :key="k" class="field" :class="{ full: f[1] === 'textarea' || f[1] === 'longtext', check: f[1] === 'checkbox' }">
							<template v-if="f[1] === 'checkbox'"><input type="checkbox" :checked="Number(v[k]) === 1" @change="v[k] = $event.target.checked ? 1 : 0"><span>{{ f[0] }}</span></template>
							<template v-else>
								<span>{{ f[0] }}</span>
								<textarea v-if="f[1] === 'textarea'" rows="3" v-model="v[k]"></textarea>
								<textarea v-else-if="f[1] === 'longtext'" rows="18" v-model="v[k]" style="font-family:ui-monospace,monospace;font-size:12.5px"></textarea>
								<select v-else-if="f[1] === 'select'" v-model="v[k]"><option v-for="(l, o) in f[2]" :key="o" :value="o">{{ l }}</option></select>
								<select v-else-if="f[1] === 'page'" v-model="v[k]"><option value="0">— escolha —</option><option v-for="p in s.paginas" :key="p.value" :value="p.value">{{ p.label }}</option></select>
								<input v-else-if="f[1] === 'password'" type="password" autocomplete="new-password" v-model="v[k]" :placeholder="s.valores.fiscal_token_definido ? '•••••• já configurado (deixe vazio para manter)' : ''">
								<input v-else :type="{ number: 'number', email: 'email', url: 'url', color: 'color' }[f[1]] || 'text'" step="0.01" v-model="v[k]">
							</template>
						</label>
					</div>
				</section>
			</template>
		</div>`,
		data: function () { return { s: null, v: {}, tab: 'Empresa', busy: false, wpAdmin: D.wpAdmin }; },
		created: async function () { try { this.s = await get('settings'); this.v = Object.assign({}, this.s.valores); } catch (e) { toast(e.message, 'error'); } },
		methods: {
			save: async function () {
				this.busy = true;
				try { const r = await post('settings', { valores: this.v }); toast(r.mensagem); this.s.valores = r.valores; this.v.fiscal_token = ''; } catch (e) { toast(e.message, 'error'); }
				this.busy = false;
			},
		},
	};

	/* ================================================================ casca */

	const NAV = [
		{ group: 'Operação' },
		{ r: 'painel', l: 'Painel', i: 'home' },
		{ r: 'contratos', l: 'Locações', i: 'file', count: 'solicitacoes' },
		{ r: 'frota', l: 'Agenda da frota', i: 'calendar' },
		{ r: 'equipamentos', l: 'Equipamentos', i: 'truck' },
		{ r: 'clientes', l: 'Clientes', i: 'users' },
		{ r: 'os', l: 'Ordens de serviço', i: 'tool' },
		{ group: 'Comercial' },
		{ r: 'vendas', l: 'Vendas', i: 'cart' },
		{ r: 'produtos', l: 'Produtos e estoque', i: 'box' },
		{ r: 'fornecedores', l: 'Fornecedores', i: 'users' },
		{ r: 'categorias', l: 'Categorias', i: 'grid' },
		{ group: 'Gestão' },
		{ r: 'financeiro', l: 'Financeiro', i: 'money', fin: true },
		{ r: 'notas', l: 'Notas fiscais', i: 'receipt', fin: true },
		{ r: 'relatorios', l: 'Relatórios', i: 'chart' },
		{ r: 'configuracoes', l: 'Configurações', i: 'gear', config: true },
	];

	const GlobalSearch = {
		components: { Ic: Ic },
		template: `<div class="search">
			<Ic n="search"/>
			<input ref="i" v-model="q" @input="find" @focus="open = true" @blur="close" @keydown.down.prevent="sel = Math.min(res.length - 1, sel + 1)" @keydown.up.prevent="sel = Math.max(0, sel - 1)" @keydown.enter.prevent="pick(res[sel])" placeholder="Buscar contrato, cliente ou equipamento   ( / )">
			<div class="results" v-if="open && q.length > 1">
				<div v-if="loading" class="empty"><span class="spinner dark"></span></div>
				<div v-else-if="!res.length" class="empty">Nada encontrado para "{{ q }}".</div>
				<a v-for="(r, i) in res" :key="i" :href="'#/' + r.rota" :class="{ sel: i === sel }" @mousedown.prevent="pick(r)"><span class="k">{{ r.tipo }}</span><span><div class="t">{{ r.titulo }}</div><div class="s">{{ r.sub }}</div></span></a>
			</div>
		</div>`,
		data: function () { return { q: '', res: [], open: false, sel: 0, loading: false }; },
		created: function () {
			this.find = debounce(async () => {
				if (this.q.length < 2) { this.res = []; return; }
				this.loading = true;
				try { this.res = (await get('search', { q: this.q })).resultados; this.sel = 0; } catch (e) { this.res = []; }
				this.loading = false;
			}, 250);
		},
		mounted: function () {
			this.key = (e) => { if (e.key === '/' && ['INPUT', 'TEXTAREA', 'SELECT'].indexOf(document.activeElement.tagName) === -1) { e.preventDefault(); this.$refs.i.focus(); } };
			document.addEventListener('keydown', this.key);
		},
		unmounted: function () { document.removeEventListener('keydown', this.key); },
		methods: {
			close: function () { setTimeout(() => { this.open = false; }, 150); },
			pick: function (r) { if (!r) { return; } this.open = false; this.q = ''; this.$refs.i.blur(); go(r.rota); },
		},
	};

	const App = {
		components: { Ic: Ic, GlobalSearch: GlobalSearch, DashboardPage: DashboardPage, ContractsPage: ContractsPage, ContractView: ContractView, ContractEditor: ContractEditor, FleetPage: FleetPage, ListPage: ListPage, RecordPage: RecordPage, ReportsPage: ReportsPage, SettingsPage: SettingsPage },
		template: `<div class="shell">
			<aside class="sidebar" :class="{ open: s.sidebar }">
				<div class="brand"><a href="#/painel"><img :src="logo" :alt="empresa"></a><small>Gestão de locações</small></div>
				<nav class="nav">
					<template v-for="(n, i) in nav" :key="i">
						<div v-if="n.group" class="group">{{ n.group }}</div>
						<a v-else :href="'#/' + n.r" :class="{ active: section === n.r }" @click="s.sidebar = false"><Ic :n="n.i"/>{{ n.l }}<span v-if="n.count && s.counts[n.count]" class="count">{{ s.counts[n.count] }}</span><span v-if="n.r === 'painel' && s.counts.atrasados" class="count" title="devoluções atrasadas">{{ s.counts.atrasados }}</span></a>
					</template>
				</nav>
				<div class="me"><div class="avatar">{{ user.iniciais }}</div><div class="who"><strong>{{ user.nome }}</strong><a :href="logout">Sair</a></div></div>
				<div class="credit">Desenvolvido por <img v-if="criador.logo" :src="criador.logo" :alt="criador.nome"><b v-else class="seo-text">SEO <span>AMPLIFY</span></b></div>
			</aside>
			<div v-if="s.sidebar" class="drawer-ov" @click="s.sidebar = false"></div>
			<div class="main">
				<header class="topbar">
					<button class="btn btn-ghost menu-btn" @click="s.sidebar = true" aria-label="Menu"><Ic n="menu"/></button>
					<GlobalSearch/>
					<span class="spacer"></span>
					<a class="btn btn-primary hide-sm" href="#/contratos/novo"><Ic n="plus"/> Nova locação</a>
				</header>
				<a class="fab" href="#/contratos/novo" aria-label="Nova locação"><Ic n="plus"/></a>
				<DashboardPage v-if="r.name === 'painel'"/>
				<ContractsPage v-else-if="r.name === 'contratos'" :key="r.path + JSON.stringify(r.query)"/>
				<ContractEditor v-else-if="r.name === 'contratos-novo'" :key="'n' + JSON.stringify(r.query)"/>
				<ContractEditor v-else-if="r.name === 'contrato-editar'" :id="r.params.id" :key="'e' + r.params.id"/>
				<ContractView v-else-if="r.name === 'contrato'" :id="r.params.id"/>
				<FleetPage v-else-if="r.name === 'frota'"/>
				<ReportsPage v-else-if="r.name === 'relatorios'" :rkey="r.params.key"/>
				<SettingsPage v-else-if="r.name === 'configuracoes'"/>
				<RecordPage v-else-if="r.name === 'registro-novo'" :module="r.params.module" :key="'new' + r.params.module + JSON.stringify(r.query)"/>
				<RecordPage v-else-if="r.name === 'registro'" :module="r.params.module" :id="r.params.id" :key="r.params.module + r.params.id"/>
				<ListPage v-else-if="r.name === 'lista'" :module="r.params.module" :key="r.path + JSON.stringify(r.query)"/>
			</div>
			<component v-if="s.modal" :is="modals[s.modal.type]" :data="s.modal.data" :key="s.modal.type + (s.modal.data.id || '')"/>
			<div class="toasts" aria-live="polite"><div v-for="t in s.toasts" :key="t.id" class="toast" :class="t.type"><Ic :n="t.type === 'error' ? 'alert' : 'check'"/><span>{{ t.message }}</span></div></div>
		</div>`,
		data: function () { return { s: store, logo: D.logo, criador: D.criador, empresa: D.empresa.nome, user: D.usuario, logout: D.logoutUrl, modals: ACTION_MODALS }; },
		computed: {
			r: function () { return store.route; },
			section: function () {
				const n = store.route.name;
				if (n.indexOf('contrat') === 0) { return 'contratos'; }
				if (n === 'registro' || n === 'registro-novo' || n === 'lista') { return store.route.params.module; }
				return n;
			},
			nav: function () { return NAV.filter(function (n) { return (!n.fin || D.usuario.financeiro) && (!n.config || D.usuario.config); }); },
		},
	};

	createApp(App).mount('#app');
}());
