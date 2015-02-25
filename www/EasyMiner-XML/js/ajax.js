//from: components/com_kbi/assets/js.js

function KbiPostArb(id, src_type, query_type, xslt)
{
	var params = document.getElementById('arb' + id).innerHTML;
	var results_element = $('arb_result' + id);

	src_type = typeof(src_type) != 'undefined' ? src_type : 3;
	query_type = typeof(query_type) != 'undefined' ? query_type : 2;
	xslt = typeof(xslt) != 'undefined' ? xslt : 2;

	KbiQueryPost(query_type, src_type, params, xslt, results_element);
}

/**
 * Handles AJAX request for KBI.
 *
 * @param query ID of stored query or query in native language
 * @param source ID of stored source or JSON definition of source
 * @param params Query parameters (XML or JSON)
 * @param xslt XSL transformation to be aplied on results to get HTML
 * @param result JavaScript element to be used as placeholder for results
 * @returns
 */
function KbiQueryPost(query, source, params, xslt, result)
{
	var service_url = '/index.php?option=com_kbi&amp;task=query&amp;format=raw';

	result
		.empty()
		.addClass('ajax-loading')
		.removeClass('ajax-error')
		.removeClass('hidden')
		.addEvent('click', function() {
			result.removeClass('ajax-loading');
		});

	var myAjax = new Ajax(service_url,
		{
			method : 'post',
			update : result,
			data : {
				source : source, // typ_zdroje (Lucene, Ontopia..)
				query : query, // typ_dotazu (vyjimka, podobnost)
				parameters : params, // arBuilder = vygenerovane XML
				xslt : xslt, // nic
			},
			onComplete : function(response) {
				result.removeClass('ajax-loading');
			},
			onFailure : function(error) {
				result
					.removeClass('ajax-loading')
					.addClass('ajax-error')
					.setAttribute('title', error.responseText);
			}
		}
	).request();

	return false;
}