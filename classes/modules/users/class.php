<?php
class users extends def_module {
	public $user_login = "%users_anonymous_login%";
	public $user_id;
	public $user_fullname = "%users_anonymous_fullname%";
	public $groups = "";

	public function __construct() {
		parent::__construct();

		define('SV_GROUP_ID', $this->umiObjectsCollection->getObjectIdByGUID('users-users-15'));
		define('SV_USER_ID', $this->umiObjectsCollection->getObjectIdByGUID('system-supervisor'));

		$commonTabs = $this->getCommonTabs();
		if ($commonTabs) {
			$commonTabs->add('users_list_all', array('users_list'));
			$commonTabs->add('groups_list');
		}

		$this->__admin();
		$this->is_auth();
	}

	public function __call($a, $b) {
		return parent::__call($a, $b);
	}

	public function __admin() {
		parent::__admin();

		$this->loadCommonExtension();

		if ($this->cmsController->getCurrentMode() == "admin" && !class_exists("__imp__" . get_class($this))) {
			$this->__loadLib("__config.php");
			$this->__implement("__config_" . get_class($this));

			$this->__loadLib("__messages.php");
			$this->__implement("__messages_users");

			$this->loadAdminExtension();

			$this->__loadLib("__custom_adm.php");
			$this->__implement("__custom_adm_users");
		} else {
			$this->__loadLib("__register.php");
			$this->__implement("__register_users");

			$this->__loadLib("__forget.php");
			$this->__implement("__forget_users");

			$this->__loadLib("__profile.php");
			$this->__implement("__profile_users");

			$this->__loadLib("__list.php");
			$this->__implement("__list_users");
		}

		$this->__loadLib("__loginza.php");
		$this->__implement("__loginza_" . get_class($this));

		$this->__loadLib("__import.php");
		$this->__implement("__imp__" . get_class($this));

		$this->__loadLib("__author.php");
		$this->__implement("__author_users");

		$this->__loadLib("__settings.php");
		$this->__implement("__settings_users");

		$this->loadSiteExtension();

		$this->__loadLib("__custom.php");
		$this->__implement("__custom_users");
	}

	public function login($template = "default") {
		if(!$template) $template = "default";

		$from_page = getRequest('from_page');

		if(!$from_page) {
			$from_page = getServer('REQUEST_URI');
		}

		if (defined("CURRENT_VERSION_LINE") && CURRENT_VERSION_LINE=='demo') {
			list($template_login) = self::loadTemplates("users/".$template, "login_demo");
		} else {
			list($template_login) = self::loadTemplates("users/".$template, "login");
		}

		$block_arr = Array();
		$block_arr['from_page'] = self::protectStringVariable($from_page);

		return self::parseTemplate($template_login, $block_arr);
	}

	public function login_do() {
		$res = "";
		$login = getRequest('login');
		$password = getRequest('password');

		$from_page = getRequest('from_page');

		if(strlen($login) == 0) {
			return $this->auth();
		}

		$permissions = permissionsCollection::getInstance();
		$cmsController = $this->cmsController;

		$user = $permissions->checkLogin($login, $password);

		if($user instanceof iUmiObject) {
			$permissions->loginAsUser($user);

			if ($permissions->isAdmin($user->id)) {
				$session = session::getInstance();
				$session->set('csrf_token', md5(rand() . microtime()));
				if ($permissions->isSv($user->id)) {
					$session->set('user_is_sv', true);
				}
				$session->setValid();
			}

			session::commit();
			system_runSession();

			$oEventPoint = new umiEventPoint("users_login_successfull");
			$oEventPoint->setParam("user_id", $user->id);
			$this->setEventPoint($oEventPoint);

			if($cmsController->getCurrentMode() == "admin") {
				ulangStream::getLangPrefix();
				system_get_skinName();
				$this->chooseRedirect($from_page);
			} else {
				if(!$from_page) {
					$from_page = getServer('HTTP_REFERER');
				}
				$this->redirect($from_page ? $from_page : ($this->pre_lang . '/users/auth/'));
			}

		} else {
			$oEventPoint = new umiEventPoint("users_login_failed");
			$oEventPoint->setParam("login", $login);
			$oEventPoint->setParam("password", $password);
			$this->setEventPoint($oEventPoint);

			if($cmsController->getCurrentMode() == "admin") {
				throw new publicAdminException(getLabel('label-text-error'));
			}

			return $this->auth();
		}

		return $res;
	}


	public function welcome($template = "default") {
		if(!$template) $template = "default";

		if($this->is_auth()) {
			return $this->auth($template);
		} else {
			return "";
		}
	}

	public function auth($template = "default") {
		if(!$template) $template = "default";

		if($this->is_auth()) {
			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->redirect($this->pre_lang . "/admin/");
			} else {
				list($template_logged) = self::loadTemplates("users/".$template, "logged");

				$block_arr = Array();
				$block_arr['xlink:href'] = "uobject://" . $this->user_id;
				$block_arr['user_id'] = $this->user_id;
				$block_arr['user_name'] = $this->user_fullname;
				$block_arr['user_login'] = $this->user_login;

				return self::parseTemplate($template_logged, $block_arr, false, $this->user_id);
			}
		} else {
			$res = $this->login($template);
		}

		return $res;
	}

	/**
	 * @deprecated
	 */
	public function ping() {
		if (getRequest('u-login') && getRequest('u-password')) {
			umiAuth::tryPreAuth();
		}

		$ping = '0';
		if($this->is_auth()) {
			$ping = 'ok';
			$this->updateUserLastRequestTime($this->user_id);
		}

		$buffer =outputBuffer::current();
		$buffer->contentType('text/html');
		$buffer->option('generation-time', false);
		$buffer->clear();
		$buffer->push($ping);
		$buffer->end();
	}

	public function is_auth() {
		static $isAuth;

		if ($isAuth === false || $isAuth === true) {
			return $isAuth;
		}

		$umiObject = $this->umiObjectsCollection;
		$guestId = $umiObject->getObjectIdByGUID('system-guest');
		$userId = getSession('user_id');

		$this->user_login = "%users_anonymous_login%";
		$this->user_fullname = "%users_anonymous_fullname%";
		$userTypeId = $this->umiTypesHelper->getObjectTypeIdByGuid('users-user');

		if ($userId == $guestId) {
			$this->user_id = $userId;
			return $isAuth = false;
		}

		if (!is_null($userId) && $umiObject->isExists($userId)) {
			$umiPropertiesHelper = $this->umiPropertiesHelper;
			$password = $umiPropertiesHelper->getPropertyValue($userId, 'password', $userTypeId);

			if (getSession('cms_pass') != $password) {
				unset($_SESSION['user_id']);
				$this->user_id = $guestId;
				return $isAuth = false;
			}

			return $this->initUser($userId, $userTypeId);
		}

		$existUser = $this->getUserIdBySessionData();
		if (sizeof($existUser) == 1) {
			$userId = $existUser[0]['id'];
			return $this->initUser($userId, $userTypeId);
		}

		$this->user_id = $guestId;
		$_SESSION['user_id'] = $guestId;
		return $isAuth = false;
	}

	public function logout() {
		$session = session::getInstance();

		$session->del(array(
			'cms_login', 'cms_pass', 'user_id',
			'u-login', 'u-password', 'u-password-md5', 'u-session-id'
		));

		setcookie('u-login', "", time() - 3600, "/");
		setcookie('u-password', "", time() - 3600, "/");
		setcookie('u-password-md5', "", time() - 3600, "/");

		setcookie(ini_get('session.name'), "", time() - 3600, "/");
		system_removeSession();

		setcookie ('eip-panel-state-first', "", time() - 3600, "/");

		session::destroy();

		$redirect_url = getRequest('redirect_url');
		$this->redirect($redirect_url);
	}


	public static function protectStringVariable($stringVariable = "") {
		$stringVariable = htmlspecialchars($stringVariable);
		return $stringVariable;
	}

	public function getFavourites($userId = false) {
		if(!$userId) {
			$userId = getRequest('param0');
		}
		$objects = $this->umiObjectsCollection;
		$permissions = permissionsCollection::getInstance();
		$regedit = $this->regedit;

		$user = $objects->getObject($userId);
		if($user instanceof iUmiObject == false) return;

		$isTrashAllowed = $permissions->isAllowedMethod($userId, 'data', 'trash');
		$userDockModules = explode(',', $user->user_dock);
		$items = array();
		foreach($userDockModules as $moduleName) {
			if($regedit->getVal('/modules/' . $moduleName) == false && $moduleName != 'trash') continue;

			if($permissions->isAllowedModule(false,	$moduleName) == false)	{
				if($moduleName == 'trash') {
					if($isTrashAllowed == false) continue;
				} else continue;
			}

			$items[] = self::parseTemplate("", array(
				'attribute:id'	=> $moduleName,
				'attribute:label' => getLabel('module-' . $moduleName)
			));
		}

		return self::parseTemplate("", array(
			'subnodes:items'	=> $items
		));
	}

	public function updateUserLastRequestTime($user_id) {

        $config = $this->mainConfiguration;

        $calculateUserLastRequestTime = intval($config->get('modules', 'users.calculate-last-request-time'));

        if ($calculateUserLastRequestTime === 0) {
            return false;
        }

		if ($user_id == $this->umiObjectsCollection->getObjectIdByGUID('system-guest')) {
			return false;
		}

		$user_object = $this->umiObjectsCollection->getObject($user_id);

		if($user_object instanceof iUmiObject == false) {
			return false;
		}

		if($this->cmsController->getCurrentMode() != "admin") {
			$time = time();

			if(($user_object->last_request_time + 60) < $time) {
				$user_object->last_request_time = $time;
				$user_object->commit();
			}
		}
	}

	public function getObjectEditLink($objectId, $type = false) {
		if ($type == 'author') return $this->pre_lang . "/admin/data/guide_item_edit/" . $objectId . "/";
		return $this->pre_lang . "/admin/users/edit/" . $objectId . "/";
	}

	/**
	 * Проверяет e-mail на уникальность
	 * @param string $email - проверяемый e-mail
	 * @param integer $userId - id редактируемого пользователя
	 * @return boolean true если e-mail уникален или используется этим пользователем или не задан, false если e-mail используется другим пользователем
	 */
	public function checkIsUniqueEmail($email, $userId = false) {
		if (!$email) {
			return true;
		}

		$sel = new selector('objects');
		$sel->types('hierarchy-type')->name('users', 'user');
		$sel->where('e-mail')->equals($email);
		$sel->limit(0, 1);

		if ($sel->first) {
			return ($userId !== false) ? ($sel->first->id == $userId) : false;
		} else {
			return true;
		}
	}

	/**
	 * Проверяет логин на уникальность
	 * @param string $login - проверяемый логин
	 * @param integer $userId - id редактируемого пользователя
	 * @return boolean true если логин уникален или используется этим пользователем или не задан, false если логин используется другим пользователем
	 */
	public function checkIsUniqueLogin($login, $userId = false) {
		if (!$login) {
			return true;
		}

		$sel = new selector('objects');
		$sel->types('hierarchy-type')->name('users', 'user');
		$sel->where('login')->equals($login);
		$sel->limit(0, 1);

		if($sel->first) {
			return ($userId !== false) ? ($sel->first->id == $userId) : false;
		} else {
			return true;
		}
	}

	/**
	 * Фильтрует значение логина и проверяет его
	 * @param string $login Проверяемое имя пользлвателя
	 * @param integer $userId - id редактируемого пользователя
	 * @param boolean $public Режим проверки (из публички или из админки)
	 * @return string | false $valid отфильтрованный логин или false если логин не валиден
	 */
	public function validateLogin($login, $userId = false, $public = false) {
		$valid = false;
		//Filters
		$login = trim($login);
		$valid = $login ? $login : (bool) $login;
		//Validators
		$minLength = 1;
		if (!preg_match("/^\S+$/", $login) && $login) {
			$this->errorAddErrors('error-login-wrong-format');
			$valid = false;
		}
		if ($public) {
			$minLength = 3;
			if (mb_strlen($login, 'utf-8') > 40) {
				$this->errorAddErrors('error-login-long');
				$valid = false;
			}
		}
		if (mb_strlen($login, 'utf-8') < $minLength) {
			$this->errorAddErrors('error-login-short');
			$valid = false;
		}
		if (!$this->checkIsUniqueLogin($login, $userId)) {
			$this->errorAddErrors('error-login-exists');
			$valid = false;
		}
		return $valid;
	}

	/**
	 * Фильтрует значение пароля и проверяет его, сравнивает при необходимости с подтверждением и логином
	 * @param string $password пароль
	 * @param string $login логин
	 * @param string $passwordConfirmation подтверждение пароля
	 * @param boolean $public Режим проверки (из публички или из админки)
	 * @return string | false $valid отфильтрованный пароль или false если пароль не валиден
	 */
	public function validatePassword($password, $passwordConfirmation = null, $login = false, $public = false) {
		$valid = false;
		//Filters
		$password = trim($password);
		$valid = $password ? $password : (bool) $password;
		//Validators
		$minLength = 1;
		if (!preg_match("/^\S+$/", $password) && $password) {
			$this->errorAddErrors('error-password-wrong-format');
			$valid = false;
		}
		if ($login && ($password == trim($login))) {
			$this->errorAddErrors('error-password-equal-login');
			$valid = false;
		}
		if ($public) {
			$minLength = 3;
			if (!is_null($passwordConfirmation)) {
				if ($password != $passwordConfirmation) {
					$this->errorAddErrors('error-password-wrong-confirm');
					$valid = false;
				}
			}
		}
		if (mb_strlen($password, 'utf-8') < $minLength) {
			$this->errorAddErrors('error-password-short');
			$valid = false;
		}
		return $valid;
	}

	/**
	 * Фильтрует значение e-mail'а и проверяет его
	 * @param string $email
	 * @param integer $userId - id редактируемого пользователя
	 * @param boolean $requireActivation
	 * @return string | boolean $valid отфильтрованный e-mail, false если e-mail не валиден, true если e-mail не указан, а активация не требуется
	 */
	public function validateEmail($email, $userId = false, $requireActivation = true) {
		$valid = false;
		//Filters
		$email = strtolower(trim($email));
		$valid = $email ? $email : (bool) $email;
		//Validators
		if($email) {
			if (!umiMail::checkEmail($email)) {
				$this->errorAddErrors('error-email-wrong-format');
				$valid = false;
			}
			if (!$this->checkIsUniqueEmail($email, $userId)) {
				$this->errorAddErrors('error-email-exists');
				$valid = false;
			}
		} elseif ($requireActivation) {
			$this->errorAddErrors('error-email-required');
			$valid = false;
		} else {
			return $valid = '';
		}
		return $valid;
	}
	
	public function restoreUser() {
		$userId = getSession('old_user_id');

		$objects = $this->umiObjectsCollection;
		$user = $objects->getObject($userId);
		if (!$user instanceof umiObject) {
			return false;
		}

		session::destroy();
		setcookie('u-login', "", time() - 3600, "/");
		setcookie('u-password', "", time() - 3600, "/");
		setcookie('u-password-md5', "", time() - 3600, "/");
		setcookie ('eip-panel-state-first', "", time() - 3600, "/");
		setcookie('customer-id', "", time() - 3600, "/");

		$permissions = permissionsCollection::getInstance();
		$permissions->loginAsUser($userId);
		if ($permissions->isAdmin($userId)) {
			$session = session::getInstance();
			$session->set('csrf_token', md5(rand() . microtime()));
			if ($permissions->isSv($userId)) {
				$session->set('user_is_sv', true);
			}
			$session->setValid();
		}

		$this->redirect("/");
	}

	private function getUserIdBySessionData() {

		$login = getSession('cms_login');
		$pass = getSession('cms_pass');
		$existUser = array();

		if (!$login || !$pass) {
			return $existUser;
		}

		$existUser = new selector('objects');
		$existUser->types('hierarchy-type')->name('users', 'user');
		$existUser->where('login')->equals($login);
		$existUser->where('password')->equals($pass);
		$existUser->option('no-length')->value(true);
		$existUser->option('return')->value('id');
		$existUser->limit(0, 1);

		return $existUser->result();
	}

	private function initUser($userId, $userTypeId) {
		$userId = intval($userId);
		$userTypeId = intval($userTypeId);
		$umiPropertiesHelper = $this->umiPropertiesHelper;

		$login = $umiPropertiesHelper->getPropertyValue($userId, 'login', $userTypeId);
		$fname = $umiPropertiesHelper->getPropertyValue($userId, 'fname', $userTypeId);
		$lname = $umiPropertiesHelper->getPropertyValue($userId, 'lname', $userTypeId);
		$groups = $umiPropertiesHelper->getPropertyValue($userId, 'groups', $userTypeId);

		$this->updateUserLastRequestTime($userId);

		$this->groups = $groups;
		$this->user_id = $userId;
		$this->user_login = $login;
		$this->user_fullname = "{$fname} {$lname}";

		$_SESSION['user_id'] = $userId;
		system_runSession();
		return $is_auth = true;
	}
};
?>