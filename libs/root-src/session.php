<?php
	/**
	 * Проверяет время жизни сессии или обновляет ее.
	 * Возвращает время, оставшееся до конца жизни сессии
	 */
	require CURRENT_WORKING_DIR . "/libs/config.php";
	$action = getRequest("a");
	$session = session::getInstance();

	// User credits saved in cookies
	if (!empty($_COOKIE['u-login']) && !empty($_COOKIE['u-password-md5'])) {
		echo SESSION_LIFETIME * 60;
		exit();
	}

	if (system_getSession()) {
		if (false !== ($remainingTime = $session->isValid())) {
			if($action == "ping") {
				$session->setValid();
				$remainingTime = $session->isValid();
			}
			echo $remainingTime;
			exit();
		} else {
			session::destroy();
			system_removeSession();
			exit("-1");
		}
	} elseif($action == "ping" && getRequest('u-login') && getRequest('u-password')) { // отправлена авторизация
		if (umiAuth::tryPreAuth() != umiAuth::PREAUTH_INVALID) {
			$permissionCollection = permissionsCollection::getInstance();
			if ($permissionCollection->isSv($permissionCollection->getUserId())) {
				$session->setValid();
				echo SESSION_LIFETIME * 60;
				exit();
			} else {
				exit("-1");
			}
		} else {
			exit("-1");
		}
	} else {
		exit("-1");
	}
?>