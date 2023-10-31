
document.addEventListener('DOMContentLoaded', function() {
	// Act on all CloudTables insert points which are using Ajax data loading
	document.querySelectorAll('div[data-ct-ajax]').forEach(function(el) {
		let unique = el.getAttribute('data-ct-ajax');

		wp.ajax
			.post('cloudtables_access', {
				uniq: unique
			})
			.done(function(response) {
				// Insert the CloudTables loader from the information returned
				let json = typeof response === 'string'
					? JSON.parse(response)
					: response;

				el.setAttribute('data-ct-insert', json.insert);
				el.setAttribute('data-token', json.token);

				let script = document.createElement('script');
				script.setAttribute('src', json.src);

				document.body.append(script);
			})
			.fail(function(err) {
				throw new Error('Unable to load CloudTable - information not found in session');
			});
	});
}, false);
