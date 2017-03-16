//if (window.$) {
	jQuery(document).ready(function() {
		jQuery('#wmi-btn-add').click(function( event ) {
			event.preventDefault();
			addField();
		});
		jQuery('#wmi-btn-del').live('click', function( event ) {
			event.preventDefault();
			jQuery(this).parent().parent().remove();
		});
		jQuery('#submit').click(function( event ) {
			var fields = new Array();
			jQuery('#wmi-field-mapping>tr').each(function() {
				var vals = {};
				var mktofield = jQuery(this).find('#mktocell>input').val();
				var localfield = jQuery(this).find('#fieldcell>input').val();
				vals[mktofield] = localfield;
				if (mktofield != '' && localfield != '') fields.push(vals);
			});
			var j = JSON.stringify(fields);
			jQuery('#wmi-mkto-field-map').val(j);
			//alert(jQuery('#wmi-mkto-field-map').val());
			//event.preventDefault();
		});
		jQuery('#wmi-btn-detect').click(function( event ) {
			event.preventDefault();
			jQuery(this).attr('disabled',true);
			var url = jQuery('#detectpages').val();
			detectFields(url, jQuery(this));
		});
	});
	
	function addField(val) {
		if (!val) val = '';
		var output = '<tr valign="top">';
		output = output + '<th id="mktocell" scope="row"><input type="text" id="" value="" placeholder="Marketo Field Name" /></th>';
		output = output + '<td id="fieldcell"><input type="text" id="" value="' + val + '" placeholder="Local Field Name" /><a href="#" id="wmi-btn-del" class="button" style="margin-left: 10px;">Delete</a></td>';
		output = output + '</tr>';
		jQuery('#wmi-field-mapping').append(output);
	}
	
	function detectFields(url, hobj) {
		jQuery.get(url, function(data) {
			var obj = jQuery(data);
				var fields = false;
				jQuery.each(obj.find('input'), function() {
					var a = jQuery(this).attr('name');
					if (a != 's' && a != '' && a) {
						addField(a);
						fields = true;
					}
				});
				jQuery.each(obj.find('select'), function() {
					var a = jQuery(this).attr('name');
					if (a != 's' && a != '' && a) {
						addField(a);
						fields = true;
					}
				});
				jQuery.each(obj.find('textarea'), function() {
					var a = jQuery(this).attr('name');
					if (a != 's' && a != '' && a) {
						addField(a);
						fields = true;
					}
				});
				if (!fields) {
					alert('No compatible form fields were found on that page.');
				}
			hobj.attr('disabled',false);
		});
	}
	
/*} else {
	alert('The Wordpress Marketo Lead Tracking plugin requires JQuery. Please include JQuery in your theme.');
}*/