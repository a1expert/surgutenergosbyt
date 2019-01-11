function users_check_reg_form() {
	users_login = document.getElementById('users_login');
	users_password = document.getElementById('users_password');
	users_password_check = document.getElementById('users_password_check');
	users_email = document.getElementById('users_email');

	if(!users_login || !users_password || !users_password_check || !users_email) {
		alert(getLabel('js-client-users-not_correct_form'));
		return false;
	}

	if(users_login.value == "") {
		alert(getLabel('js-client-users-must_fill_field_login'));
		users_login.focus();
		return false;
	}

	if(users_password.value == "") {
		alert(getLabel('js-client-users-must_fill_field_password'));
		users_password.focus();
		return false;
	}

	if(users_password.value != users_password_check.value) {
		alert(getLabel('js-client-users-password_not_correct'));
		users_password_check.focus();
		return false;
	}

	if(users_email.value == "") {
		alert(getLabel('js-client-users-must_fill_field_email'));
		users_email.focus();
		return false;
	}

	return true;
}
