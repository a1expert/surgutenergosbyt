<?php
	class umiAuth extends singleton implements iSingleton, iUmiAuth {
		/** Авторизация неверная */
		const PREAUTH_INVALID = 0;
		/** Авторизация прошла успешна */
		const PREAUTH_SUCCESS_NEW = 1;
		/** Авторизация успешно восстановлена */
		const PREAUTH_SUCCESS_RESTORE = 2;
		/** Авторизация уже выполнена */
		const PREAUTH_ALREADY = 3;
		/** Авторизация не требуется */
		const PREAUTH_NEEDNOT = 4;

		public function __construct() {}

		/**
		 * {@inheritdoc}
		 */
		public static function getInstance($c = NULL) {
			parent::getInstance(__CLASS__);
		}

		/**
		 * Пре-авторизация
		 *
		 * Авторизация с помощью значений $_REQUEST
		 *
		 * @return int статус авторизации, PREAUTH_ константы
		 */
		public function tryPreAuth() {
			$passwordMd5 = "";
			$sessionExpected = "";

			$fieldLogin = 'u-login';
			$fieldPassword = 'u-password';
			$fieldPasswordMd5 = 'u-password-md5';
			$fieldSessionId = 'u-session-id';

			if($login = getCookie($fieldLogin)) {
				if($passwordMd5 = getCookie($fieldPassword)) {
					$passwordMd5 = md5($passwordMd5);
				} else {
					$passwordMd5 = getCookie($fieldPasswordMd5);
				}
			}

			if (function_exists('apache_request_headers')) {
				$apacheHeaders = apache_request_headers();

				if (isset($apacheHeaders[$fieldLogin])) {
					$login = umiObjectProperty::filterInputString(str_replace(chr(0), "", $apacheHeaders[$fieldLogin]));
				}

				if (isset($apacheHeaders[$fieldPasswordMd5])) {
					$passwordMd5 = umiObjectProperty::filterInputString(str_replace(chr(0), "", $apacheHeaders[$fieldPasswordMd5]));
				} elseif (isset($apacheHeaders[$fieldPassword])) {
					$passwordMd5 = md5(umiObjectProperty::filterInputString(str_replace(chr(0), "", $apacheHeaders[$fieldPassword])));
				}

				if (isset($apacheHeaders[$fieldSessionId])) {
					$sessionExpected = umiObjectProperty::filterInputString(str_replace(chr(0), "", $apacheHeaders[$fieldSessionId]));
				}
			}

			if (isset($_REQUEST[$fieldLogin])) {
				$login = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_REQUEST[$fieldLogin]));
			}
			if (isset($_REQUEST[$fieldPasswordMd5])) {
				$passwordMd5 = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_REQUEST[$fieldPasswordMd5]));
			} elseif (isset($_REQUEST[$fieldPassword])) {
				$passwordMd5 = md5(umiObjectProperty::filterInputString(str_replace(chr(0), "", $_REQUEST[$fieldPassword])));
			}
			if (isset($_REQUEST[$fieldSessionId])) {
				$sessionExpected = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_REQUEST[$fieldSessionId]));
			}

			if (strlen($login) && strlen($passwordMd5)) {
				$objectTypeId = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");
				$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);

				$loginFieldId = $objectType->getFieldId("login");
				$passwordFieldId = $objectType->getFieldId("password");
				$isActiveId = $objectType->getFieldId("is_activated");

				$sel = new umiSelection;
				$sel->addLimit(1);
				$sel->addObjectType($objectTypeId);

				$sel->addPropertyFilterEqual($loginFieldId, $login);
				$sel->addPropertyFilterEqual($passwordFieldId, $passwordMd5);
				$sel->addPropertyFilterEqual($isActiveId, 1);

				$result = umiSelectionsParser::runSelection($sel);

				if(sizeof($result) === 1) {
					$userId = intval($result[0]);

					$session = session::getInstance();
					$currentSession = session::getId();
					system_runSession();

					// maybe already authorized :
					if ($session->get("cms_login") === $login && $session->get("cms_pass") === $passwordMd5 && $session->get("user_id") === $userId) {
						return self::PREAUTH_ALREADY;
					}

					if (getRequest('mobile_application') == 'true' && !regedit::getInstance()->getVal('//modules/emarket')) {
						$data = array(
							"data" => array(
								"type" => null,
								"action" => null,
								"error" => array(
									"code" => 0,
									"message" => getLabel('label-module-emarket-is-absent')
								)
							)
						);

						$buffer = outputBuffer::current();
						$buffer->clear();

						if (getRequest("xmlMode") == 'force') {
							$dom = new DOMDocument('1.0', 'utf-8');
							$rootNode = $dom->createElement("result");
							$dom->appendChild($rootNode);
							$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');
							$translator = new xmlTranslator($dom);
							$translator->translateToXml($rootNode, $data);

							$buffer->contentType('text/xml');
							$buffer->push($dom->saveXML());
						} elseif(getRequest("jsonMode") == 'force') {
							$translator = new jsonTranslator;

							$buffer->contentType('text/javascript');
							$buffer->option('generation-time', false);
							$buffer->push($translator->translateToJson($data));
						} else {
							throw new publicException(getLabel('label-module-emarket-is-absent'));
						}
						exit();
					}

					// try to restore
					if (strlen($sessionExpected)) {
						// stop current session :
						if (strlen($currentSession)) {
							session::destroy();
						}

						// restore expected :
						session::setId($sessionExpected);
						session::getInstance();

						$eventPoint = new umiEventPoint("users_prelogin_successfull");
						$eventPoint->setParam("prelogin_mode", self::PREAUTH_SUCCESS_RESTORE);
						$eventPoint->setParam("user_id", $userId);
						umiEventsController::getInstance()->callEvent($eventPoint);

						return self::PREAUTH_SUCCESS_RESTORE; // RETURN
					} else {
						$session = session::recreateInstance();

						$session->set("cms_login", $login);
						$session->set("cms_pass", $passwordMd5);
						$session->set("user_id", $userId);

						$permissions = permissionsCollection::getInstance();
						if ($permissions->isAdmin($userId)) {
							$session->set('csrf_token', md5(rand() . microtime()));
							if ($permissions->isSv($userId)) {
								$session->set('user_is_sv', true);
							}
							$session->setValid();
						}

						session::recreateInstance();

						$eventPoint = new umiEventPoint("users_prelogin_successfull");
						$eventPoint->setParam("prelogin_mode", self::PREAUTH_SUCCESS_NEW);
						$eventPoint->setParam("user_id", $userId);
						umiEventsController::getInstance()->callEvent($eventPoint);

						// ==== remember me : ====
						if (intval(getRequest('u-login-store')) || strtoupper(getRequest('u-login-store')) === 'ON') {
							setcookie($fieldLogin, $login, (time() + 2678400), "/"); // +1 month
							setcookie($fieldPasswordMd5, $passwordMd5, (time() + 2678400), "/"); // +1 month
						} elseif (getRequest('mobile_application') == 'true') {
							// Unlimited for mobile application
							setcookie($fieldLogin, $login, 0, "/");
							setcookie($fieldPasswordMd5, $passwordMd5, 0, "/");
						}
						return self::PREAUTH_SUCCESS_NEW;
					}
				}
			}

			return self::PREAUTH_INVALID;
		}
	}
?>