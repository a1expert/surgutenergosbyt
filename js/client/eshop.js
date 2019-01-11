function eshop_subscribe_check() {
	fname = document.getElementById('fname');
	lname = document.getElementById('lname');
	mname = document.getElementById('mname');
	email = document.getElementById('email');

	if(fname.value == "") {
		alert(getLabel('js-client-eshop-must_fill-field-name'));
		fname.focus();
		return false;
	}


	if(lname.value == "") {
		alert(getLabel('js-client-eshop-must_fill-field-family'));
		lname.focus();
		return false;
	}


	if(mname.value == "") {
		alert(getLabel('js-client-eshop-must_fill-field-surname'));
		mname.focus();
		return false;
	}

	if(email.value == "") {
		alert(getLabel('js-client-eshop-must_fill-field-email'));
		email.focus();
		return false;
	}

	return true;
}
