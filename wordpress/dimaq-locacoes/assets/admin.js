/* global jQuery, DL, wp */
(function ($) {
	'use strict';

	var money = function (v) {
		return 'R$ ' + (Number(v) || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	};
	var num = function ($el) {
		return parseFloat(String($el.val() || '0').replace(',', '.')) || 0;
	};
	var daysBetween = function (a, b) {
		if (!a || !b) { return 1; }
		var d = Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
		return Math.max(1, d);
	};
	var contractDays = function () {
		return daysBetween($('#f_data_inicio').val(), $('#f_data_prev_devolucao').val());
	};
	var periodField = { diaria: 'diaria', semanal: 'semanal', quinzenal: 'quinzenal', mensal: 'mensal' };

	/* ---------- itens ---------- */
	function rowTotal($tr) {
		var qtd = num($tr.find('.dl-item-qtd'));
		var periods = $tr.find('.dl-item-periods').length ? num($tr.find('.dl-item-periods')) : 1;
		var unit = num($tr.find('.dl-item-unit'));
		var disc = num($tr.find('.dl-item-discount'));
		var total = Math.max(0, qtd * periods * unit - disc);
		$tr.find('.dl-item-total').text(money(total));
		return total;
	}
	function sumItems() {
		var sum = 0;
		$('.dl-items-table tbody tr').each(function () { sum += rowTotal($(this)); });
		$('.dl-items-sum span').text(money(sum));
	}
	function applyPeriod($tr) {
		var $opt = $tr.find('.dl-item-ref option:selected');
		var $period = $tr.find('.dl-item-period');
		if (!$period.length || !$opt.val() || $opt.val() === '0') { return; }
		var p = $period.val();
		if (periodField[p]) {
			$tr.find('.dl-item-unit').val(Number($opt.data(periodField[p]) || 0).toFixed(2));
			var len = Number($period.find('option:selected').data('dias')) || 1;
			$tr.find('.dl-item-periods').val(Math.ceil(contractDays() / len));
		}
	}
	function bestPrice($tr) {
		var ref = $tr.find('.dl-item-ref').val();
		if (!ref || ref === '0') { return; }
		$.post(DL.ajax, {
			action: 'dl_best_price', nonce: DL.nonce, equip: ref,
			inicio: $('#f_data_inicio').val(), fim: $('#f_data_prev_devolucao').val()
		}, function (res) {
			if (!res || !res.success || !res.data.total) { return; }
			$tr.find('.dl-item-period').val('pacote');
			$tr.find('.dl-item-periods').val(1);
			$tr.find('.dl-item-unit').val(Number(res.data.total).toFixed(2));
			$tr.find('.dl-item-desc').val(res.data.nome + ' — ' + res.data.descricao);
			sumItems();
		});
	}
	var rowIndex = 1000;
	function addRow(equipId) {
		var tpl = $('#dl-item-row-tpl').html();
		if (!tpl) { return null; }
		var $tr = $(tpl.replace(/__i__/g, 'n' + (rowIndex++)));
		$('.dl-items-table tbody').append($tr);
		if (equipId) {
			$tr.find('.dl-item-ref').val(String(equipId)).trigger('change');
		}
		return $tr;
	}

	$(document).on('click', '.dl-add-item', function () { addRow(); });
	$(document).on('click', '.dl-remove-item', function () { $(this).closest('tr').remove(); sumItems(); });
	$(document).on('change', '.dl-item-ref', function () {
		var $tr = $(this).closest('tr');
		var $opt = $(this).find('option:selected');
		if ($opt.val() === '0') { return; }
		$tr.find('.dl-item-desc').val($opt.data('nome'));
		if ($tr.find('.dl-item-period').length) {
			if ($tr.find('.dl-item-period').val() === 'pacote') { bestPrice($tr); } else { applyPeriod($tr); }
		} else {
			$tr.find('.dl-item-unit').val(Number($opt.data('preco') || 0).toFixed(2));
		}
		sumItems();
	});
	$(document).on('change', '.dl-item-period', function () {
		var $tr = $(this).closest('tr');
		if ($(this).val() === 'pacote') { bestPrice($tr); } else { applyPeriod($tr); }
		sumItems();
	});
	$(document).on('input change', '.dl-item-qtd, .dl-item-periods, .dl-item-unit, .dl-item-discount', sumItems);
	$(document).on('click', '.dl-best-price', function () {
		$('.dl-items-table tbody tr').each(function () { bestPrice($(this)); });
	});
	$(document).on('change', '#f_data_inicio, #f_data_prev_devolucao', function () {
		$('.dl-items-table tbody tr').each(function () {
			var $tr = $(this);
			if ($tr.find('.dl-item-period').val() === 'pacote') { bestPrice($tr); } else { applyPeriod($tr); }
		});
		sumItems();
	});

	/* ---------- mídia ---------- */
	$(document).on('click', '.dl-media-pick', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.dl-media');
		var frame = wp.media({ title: 'Escolher imagem', multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			$box.find('input').val(a.id);
			$box.find('img').attr('src', (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url)).show();
		});
		frame.open();
	});
	$(document).on('click', '.dl-media-clear', function () {
		var $box = $(this).closest('.dl-media');
		$box.find('input').val(0);
		$box.find('img').hide();
	});

	/* ---------- CEP (ViaCEP) ---------- */
	$(document).on('blur', '.dl-cep', function () {
		var cep = String($(this).val()).replace(/\D/g, '');
		if (cep.length !== 8) { return; }
		$.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function (d) {
			if (!d || d.erro) { return; }
			if (!$('#f_logradouro').val()) { $('#f_logradouro').val(d.logradouro); }
			if (!$('#f_bairro').val()) { $('#f_bairro').val(d.bairro); }
			$('#f_cidade').val(d.localidade);
			$('#f_uf').val(d.uf);
			$('#f_numero').trigger('focus');
		});
	});

	/* ---------- máscaras simples ---------- */
	$(document).on('input', '.dl-mask-doc', function () {
		var d = this.value.replace(/\D/g, '').slice(0, 14);
		if (d.length <= 11) {
			d = d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
		} else {
			d = d.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
		}
		this.value = d;
	});
	$(document).on('input', '.dl-mask-phone', function () {
		var d = this.value.replace(/\D/g, '').slice(0, 11);
		this.value = d.length > 10 ? d.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3') : d.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3').replace(/-$/, '');
	});

	/* ---------- selects grandes com busca ---------- */
	function searchable($sel) {
		if ($sel.find('option').length < 15 || $sel.data('dl-search')) { return; }
		$sel.data('dl-search', 1);
		var $in = $('<input type="search" class="dl-select-filter" placeholder="filtrar...">');
		$sel.before($in);
		$in.on('input', function () {
			var q = this.value.toLowerCase();
			$sel.find('option').each(function () {
				var show = !q || this.value === '0' || this.text.toLowerCase().indexOf(q) !== -1;
				$(this).prop('hidden', !show);
			});
		});
	}

	$(function () {
		$('select.dl-searchable').each(function () { searchable($(this)); });
		sumItems();
		var m = window.location.search.match(/[?&]equip=(\d+)/);
		if (m && $('#dl-item-row-tpl').length && !$('.dl-items-table tbody tr').length) {
			addRow(m[1]);
		}
		if ($('#dl-item-row-tpl').length && !$('.dl-items-table tbody tr').length) {
			addRow();
		}
	});
}(jQuery));
