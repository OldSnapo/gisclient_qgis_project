var loadingGif = '<img src="../images/ajax_loading.gif">';
$(document).ready(function() {

	$('div#mapfiles_manager a[data-action="download_qgis"]').button({icons:{primary:'ui-icon-extlink'}, text:false});

	$('div#mapfiles_manager a[data-action="refresh_qgis"]').button({icons:{primary:'ui-icon-refresh'}, text:false}).click(function(event) {
		event.preventDefault();

		var activeLink = this;
		var activeLinkContainer = $(this).parent();
		$(activeLink).hide();
		$(activeLinkContainer).append(loadingGif);

		var params = {
			action: 'refresh',
			project: $('input#project').val(),
			mapset: $(this).attr('data-mapset')
		}

		$.ajax({
			url: 'ajax/qgis_project.php',
			type: 'POST',
			dataType: 'json',
			data: params,
			success: function(response) {
				$(activeLink).show();
				$('img', activeLinkContainer).remove();
				if(typeof(response) != 'object' || typeof(response.result) == 'undefined') {
					return alert('Error');
				}
				if(response.result != 'ok') {
					if(response.result == 'error' && typeof(response.error) == 'object' && typeof(response.error.type) != 'undefined' && response.error.type == 'qgis_errors') {
						var qgisErrText = '<b style="color:black;">Si sono verificati errori nella generazione del progetto QGis, uno o più layer potrebbero essere non validi e/o da corregere manualmente:<br><br></b>';
						qgisErrText += response.error.text;
						$('#error_dialog').html(qgisErrText);
						$('#error_dialog').dialog({
							width: 600,
							title: 'Error'
						});
						return;
					}
					return alert('Error');
				}
			},
			error: function() {
				$(activeLink).show();
				$('img', activeLinkContainer).remove();
				alert('Error');
			}
		});
	});
});
