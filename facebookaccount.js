function register_with_facebook() {
	document.getElementById('fbuid').value = '';
	FB.login(function(response) {
		if (response.authResponse) {
			var user = response.authResponse;
			FB.api('/me', function(response) {
				// Populate form fields
				document.getElementById('fbuid').value = response.id;
				document.getElementById('user_login').value = response.username;
				document.getElementById('user_email').value = response.email;
				// Add message box
				var message = document.createElement('div');
				message.className = "facebook_login_message";
				message.innerHTML = "Facebook connesso correttamente. Clicca \"Conferma\" per completare la tua registrazione.<br /> Puoi comunque accedere con email e password. Una nuova password ti è stata inviata via email.";
				var loginForm = document.getElementById('registerform');
				loginForm.parentNode.insertBefore(message, loginForm);
				// Hide Facebook Connect button
				document.getElementById('fa-register-button').style.display = 'none';
				// Change submit button text
				document.getElementById('wp-submit').value = "Conferma";
			});
			
		} else {
			console.log('User cancelled login or did not fully authorize.');
		}
	}, {scope: 'email'});
}

function login_with_facebook() {
	document.getElementById('fbuid').value = '';
	FB.login(function(response) {
		if (response.authResponse) {
			var user = response.authResponse;
			FB.api('/me', function(response) {
				// Add message box
				var message = document.createElement('div');
				message.className = "facebook_login_message";
				message.innerText = "Ciao " + response.username + "! Accesso in corso.";
				var loginForm = document.getElementById('loginform');
				loginForm.parentNode.insertBefore(message, loginForm);
				// Populate form fields and hide elements while wp redirects
				document.getElementById('loginform').style.display = "none";
				document.getElementById('nav').style.display = "none";
				
				document.getElementById('fbuid').name = 'fbuid';
				document.getElementById('fbuid').value = response.id;
				document.getElementById('user_login').value = response.username;
				document.getElementById('loginform').submit();
			});
			
		} else {
			console.log('User cancelled login or did not fully authorize.');
			/*$('#account-form input').removeAttr("disabled");*/
		}
		//$('#account-form input').removeAttr("disabled"); // showing from as active
	}, {scope: 'email' /*'user_birthday,user_hometown,email'*/});
}