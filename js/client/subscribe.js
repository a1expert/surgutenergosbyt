function subscribe_check_reg_form() {
	subscribe_login = document.getElementById('subscribe_login');
	subscribe_password = document.getElementById('subscribe_password');
	subscribe_password_check = document.getElementById('subscribe_password_check');
	subscribe_email = document.getElementById('subscribe_email');

	if(!subscribe_login || !subscribe_password || !subscribe_password_check || !subscribe_email) {
		alert(getLabel('js-client-subscribe-not_correct_form'));
		return false;
	}

	if(subscribe_login.value == "") {
		alert(getLabel('js-client-subscribe-must_fill_field_login'));
		return false;
	}

	if(subscribe_password.value == "") {
		alert(getLabel('js-client-subscribe-must_fill_field_password'));
		return false;
	}

	if(subscribe_password.value != subscribe_password_check.value) {
		alert(getLabel('js-client-subscribe-password_not_correct'));
		return false;
	}

	if(subscribe_email.value == "") {
		alert(getLabel('js-client-subscribe-must_fill_field_email'));
		return false;
	}

	return true;
}
