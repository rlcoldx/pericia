$(document).ready(function () {

	$('#enviar_notificacao').submit(function(e){

		e.preventDefault();
		var domain = $('body').data('domain');
		var formData = new FormData(this);
		$('#salvar').text('ENVIANDO AGUARDE...');
		$('#salvar').prop('type', 'button');

		$.ajax({
			url: domain + '/notificacao/enviar',
			data: formData,
			type: 'POST',
			success: function(data){
				setTimeout(function(){
					location.reload();
				}, 1500);
				Swal.fire('', 'ENVIADO COM SUCESSO!', 'success');
			},
			processData: false,
			cache: false,
			contentType: false
		});
	});

	$('.select2').select2();

});