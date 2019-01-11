<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

/**
 * Расширение для подключения помощников вида в PHP-шаблонах.
 *
 * Имена хэлперов, доступных в шаблоне:
 * 'macros' - имя функции для вызова макросов
 * 'pageByPath' - имя функции для получения страницы по path
 * 'pageById' - имя функции для получения страницы по ID
 * 'translate' - имя функции для получения перевода для метки
 * 'objectById' - имя функции для получения объекта по ID
 * 'user' - имя функции для выполнения usel
 * 'getParam' - имя функции для получения GET-параметра
 * 'commonVar' - имя функции для получения общей для шаблонов переменной
 * 'setCommonVar' - имя функции для установки общей для шаблонов переменной
 * 'isSetCommonVar' - имя функции для проверки наличия общей для шаблонов переменной
 */
class ViewPhpExtension implements IPhpExtension
{
    /**
     * @var array $systemModules список системных модулей
     */
    private $systemModules = array('core', 'system', 'custom');
    /**
     * @var array $cacheLabels кэш меток локализации
     */
    private $cacheLabels = array();
    /**
     * @var array $commonVars хранит массив общих для шаблона переменных
     */
    private $commonVars = array();

    /**
     * Возвращает общие для шаблонов переменные.
     * @return array
     */
    public function getCommonVars()
    {
        return $this->commonVars;
    }

    /**
     * Устанавливает общие для шаблонов переменные.
     * @param string $name имя устанасливаемой переменной
     * @param array $commonVars
     */
    public function setCommonVars($name, $commonVars)
    {
        $this->commonVars[$name] = $commonVars;
    }

    /**
     * Возвращает перевод метоки.
     * @param string $label метка
     * @return array
     */
    public function getCacheLabel($label)
    {
        return $this->cacheLabels[$label];
    }

    /**
     * Устанавливает список меток.
     * @param array $cacheLabels
     */
    public function setCacheLabels(array $cacheLabels)
    {
        $this->cacheLabels = $cacheLabels;
    }

    /**
     * Проверяет существование метки перевода.
     * @param string $label метка
     * @return bool
     */
    public function isSetLabel($label)
    {
        return isset($this->cacheLabels[$label]);
    }
    /**
     * Проверяет не пустой ли массив с метками.
     * @return bool
     */
    public function isNotEmptyCacheLabels()
    {
        return empty($this->cacheLabels);
    }

    /**
     * Возвращает список системных модулей.
     * @return array
     */
    public function getSystemModules()
    {
        return $this->systemModules;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return __CLASS__;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'macros' => $this->getWidgetHelper(),
            'getPageByPath' => $this->getPageByPath(),
            'getPageById' => $this->getPageById(),
            'translate' => $this->translate(),
            'getObjectById' => $this->getObject(),
            'usel' => $this->execUsel(),
            'getRequest' => $this->getRequest(),
            'getRawRequest' => $this->getRawRequest(),
            'getCommonVar' => $this->getVar(),
            'setCommonVar' => $this->setVar(),
            'isSetCommonVar' => $this->isSetVar()
        );
    }

	/**
	 * Возвращает помощник вида для вызова макросов
	 * @return callable
	 */
	protected function getWidgetHelper() {
		$that = $this;
		return function ($moduleName, $method, $arguments = array()) use ($that) {

			if (!isset($moduleName)) {
				throw new RuntimeException('Cannot set module name');
			}

			if (!isset($method)) {
				throw new RuntimeException('Cannot set macros name');
			}

			if (!isset($arguments)) {
				throw new RuntimeException('Cannot set macros params');
			}

			if (in_array($moduleName, $that->getSystemModules())) {
				$module = &system_buildin_load($moduleName);
			} else {
				$module = cmsController::getInstance()->getModule($moduleName);
			}

			if(!in_array($moduleName, $that->getSystemModules())) {
				$isAllowed = (bool) system_is_allowed($moduleName, $method);
			} else {
				$isAllowed = true;
			}

			if (!$isAllowed) {
				return null;
			}

			$key = '';
			$cacheEnabled = (bool) mainConfiguration::getInstance()->get('cache', 'streams.cache-enabled');
			$cacheLifeTime = (int) mainConfiguration::getInstance()->get('cache', 'streams.cache-lifetime');
			$cacheAllowed = ($cacheEnabled && $cacheLifeTime > 0) ? true : false;
			$cacheFrontend = cacheFrontend::getInstance();

			if ($cacheAllowed) {
				$key = $moduleName . '/' . $method . http_build_query($arguments) . $cacheFrontend->getQueryStringHash();
				if ($result = $cacheFrontend->loadData($key)) {
					return $result;
				}
			}

			$result = call_user_func_array(array($module, $method), $arguments);

			if ($cacheAllowed) {
				$cacheFrontend->saveData($key, $result, $cacheLifeTime);
			}

			return $result;
		};
	}

    /**
     * Возвращает помошник вида для получения страницы по path
     * @return callable
     */
    protected function getPageByPath()
    {
        return function ($path) {
            return umiHierarchy::getInstance()->getElement(
                umiHierarchy::getInstance()->getIdByPath($path)
            );
        };
    }

    /**
     * Возвращает помошник вида для получения страницы по ID
     * @return callable
     */
    protected function getPageById()
    {
        return function ($id) {
            return umiHierarchy::getInstance()->getElement($id);
        };
    }

    /**
     * Возвращает объект по ID.
     * @return callable
     */
    protected function getObject()
    {
        return function ($id) {
            return umiObjectsCollection::getInstance()->getObject($id);
        };
    }

    /**
     * Возвращает перевод метки.
     * @return callable
     */
    protected function translate()
    {
        $that = $this;
        return function ($label, $path = false) use ($that) {
            if ($that->isNotEmptyCacheLabels()) {
                $fileI18N = cmsController::getInstance()->getTemplatesDirectory() . 'i18n/i18n.' . cmsController::getInstance()->getLang()->getPrefix() . '.php';
                if (file_exists($fileI18N)) {
                    $that->setCacheLabels(require $fileI18N);
                    if ($that->isSetLabel($label)) {
                        return $that->getCacheLabel($label);
                    }
                }
            } else if ($that->isSetLabel($label)) {
                return $that->getCacheLabel($label);
            }
            return getLabel($label, $path);
        };
    }

    /**
     * Возвращает обработанный Request-параметр.
     * @return callable
     */
    protected function getRequest()
    {
        return function($name, $default = null) {
            $param = getRequest($name);
            if (!$param) {
                return $default;
            }

            return htmlspecialchars($param);
        };
    }

    /**
     * Возвращает Request-параметр.
     * @return callable
     */
    protected function getRawRequest()
    {
        return function($name, $default = null) {
            $param = getRequest($name);
            if (!$param) {
                return $default;
            }

            return $param;
        };
    }

    /**
     * Возвращает значение запрошенной общей переменной.
     * @return callable
     */
    protected function getVar()
    {
        $that = $this;
        return function ($name) use ($that) {
            $commonVars = $that->getCommonVars();
            return $commonVars[$name];
        };
    }

    /**
     * Устанавливает значение общей переменной.
     * @return callable
     */
    protected function setVar()
    {
        $that = $this;
        return function ($name, $value) use ($that) {
            $that->setCommonVars($name, $value);
        };
    }

    /**
     * Проверяет существование общей переменной.
     * @return callable
     */
    protected function isSetVar()
    {
        $that = $this;
        return function ($name) use ($that) {
            $commonVars = $that->getCommonVars();
            return isset($commonVars[$name]);
        };
    }

    /**
     * Выполняет usel запрос.
     * @return callable
     */
    private function execUsel()
    {
        return function ($uselName, $params = null) {
            $stream = new uselStream;
            return $stream->call($uselName, $params);
        };
    }
}
?>