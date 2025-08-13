(function($){
	function fetchBrands(){
		return $.get(ULPAjax.ajax_url, { action: 'ulp_get_brands', nonce: ULPAjax.nonce });
	}
	function fetchModels(brand){
		return $.get(ULPAjax.ajax_url, { action: 'ulp_get_models', brand: brand, nonce: ULPAjax.nonce });
	}
	function fetchParts(){
		return $.get(ULPAjax.ajax_url, { action: 'ulp_get_parts', nonce: ULPAjax.nonce });
	}
	function renderOptions($select, items, key){
		$select.empty();
		$select.append($('<option>').val('').text('— انتخاب —'));
		(items || []).forEach(function(it){
			if (typeof it === 'string') {
				$select.append($('<option>').val(it).text(it));
			} else if (key && it[key]) {
				$select.append($('<option>').val(it[key]).text(it[key] + (it.price ? (' - ' + it.price) : ''));
			} else if (it.name) {
				$select.append($('<option>').val(it.name).text(it.name));
			}
		});
	}

	$(document).on('ready', function(){
		var $brand = $('#ulp-brand');
		var $model = $('#ulp-model');
		var $cpu = $('#ulp-cpu');
		var $ram = $('#ulp-ram');
		var $gpu = $('#ulp-gpu');
		var $storage = $('#ulp-storage');

		$.when(fetchBrands(), fetchParts()).done(function(brandsRes, partsRes){
			if (brandsRes[0] && brandsRes[0].success) {
				renderOptions($brand, brandsRes[0].data.brands);
			}
			if (partsRes[0] && partsRes[0].success) {
				renderOptions($cpu, partsRes[0].data.cpu, 'name');
				renderOptions($ram, partsRes[0].data.ram, 'name');
				renderOptions($gpu, partsRes[0].data.gpu, 'name');
				var storageList = (partsRes[0].data.ssd || []).concat(partsRes[0].data.hdd || []);
				renderOptions($storage, storageList, 'name');
			}
		});

		$brand.on('change', function(){
			var b = $(this).val();
			$model.empty();
			if (!b) return;
			fetchModels(b).done(function(res){
				if (res && res.success) {
					renderOptions($model, res.data.models);
				}
			});
		});

		$('#ulp-form').on('submit', function(e){
			e.preventDefault();
			var formData = $(this).serialize();
			$.post(ULPAjax.ajax_url, formData).done(function(res){
				if (res.success) {
					renderResult(res.data);
				} else {
					showError(res.data && res.data.message ? res.data.message : 'خطایی رخ داد.');
				}
			}).fail(function(){
				showError('خطا در ارتباط با سرور.');
			});
		});

		function showError(msg){
			$('#ulp-result').show().html('<div class="ulp-result"><p style="color:#b91c1c;">' + escapeHtml(msg) + '</p></div>');
		}

		function renderResult(data){
			var html = '';
			html += '<div class="prices">';
			html += '  <div class="price-box"><div>حداکثر قیمت</div><div style="font-size:20px;font-weight:700;">' + formatMoney(data.final_price, data.currency) + '</div></div>';
			html += '  <div class="price-box"><div>حداقل قیمت</div><div style="font-size:20px;font-weight:700;">' + formatMoney(data.min_price, data.currency) + '</div></div>';
			html += '</div>';
			html += '<hr />';
			html += '<div class="muted">جزئیات محاسبه</div>';
			html += '<ul>';
			html += '  <li>قیمت پایه: ' + formatMoney(data.base_price, data.currency) + '</li>';
			html += '  <li>قیمت پس از استهلاک: ' + formatMoney(data.depreciated_price, data.currency) + '</li>';
			html += '  <li>مبلغ استهلاک: ' + formatMoney(data.depreciation_amount || (data.base_price - data.depreciated_price), data.currency) + '</li>';
			html += '  <li>ضریب وضعیت: ' + escapeHtml(String(data.condition_multiplier)) + '</li>';
			html += '  <li>مجموع تعدیلات قطعات: ' + formatMoney(data.total_adjustment, data.currency) + '</li>';
			html += '</ul>';
			$('#ulp-result').show().html(html);
		}

		function formatMoney(num, currency){
			try { num = parseInt(num, 10) || 0; } catch(e) { num = 0; }
			return new Intl.NumberFormat('fa-IR').format(num) + ' ' + (currency || 'IRR');
		}
		function escapeHtml(s){
			return $('<div>').text(s).html();
		}
	});
})(jQuery);