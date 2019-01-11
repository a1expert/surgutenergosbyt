<?php
/**
 * This file is part of UMI.CMS.
 *
 * @link http://umi-cms.ru
 * @copyright Copyright (c) 2007-2014 Umisoft ltd. (http://umisoft.ru)
 * @license For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP шаблонизатор.
 *
 * @method mixed macros($module, $method, $arguments = array()) выполняет вызов макроса
 * @method umiHierarchyElement getPageByPath($path) возвращает страницу по её url
 * @method umiHierarchyElement getPageById($id) возвращает страницу по её Id
 * @method string translate($label, $path = false) возвращает перевод метки
 * @method umiObject getObjectById($id) возвращает объект по ID
 * @method array usel($uselName, $params = null) выполняет usel
 * @method mixed getParam($name, $default = null, $safe = true) возвращает GET-параметр
 * @method mixed getCommonVar($name) возвращает запрошенную общую переменную
 * @method mixed setCommonVar($name, $value) устанавливает общую переменную
 * @method mixed isSetCommonVar($name) проверяет существование общей переменной
 */
class umiTemplaterPHP extends umiTemplater implements IFullResult
{
    /**
     * Имя файла с шаблоном отображения ошибок
     */
    const ERROR_TEMPLATE = 'errors.phtml';

    /**
     * @var PhpTemplateEngine $templateEngine
     */
    protected $templateEngine;
    /**
     * @var string $templatesDirectory
     */
    protected $templatesDirectory;

    /**
     * {@inheritdoc}
     */
    public function __construct($templatesSource)
    {
        $this->templateEngine = new PhpTemplateEngine();

        $this->templateEngine->addExtension(new ViewPhpExtension());

        $this->templatesDirectory = cmsController::getInstance()->getTemplatesDirectory();

        parent::__construct($templatesSource);
    }

    /**
     * Возвращает путь до директории, в которой хранятся шаблоны.
     * @return string
     */
    public function getTemplatesDirectory()
    {
        return $this->templatesDirectory;
    }

    /**
     * Загружает пользовательские расширения.
     */
    public function loadExtension(array $extensions)
    {
        foreach ($extensions as $extension) {
            $fileExtension = CURRENT_WORKING_DIR . $extension . '.php';
            if (file_exists($fileExtension)) {
                require $fileExtension;

                $extensionName = explode('/', $extension);
                $extensionName = $extensionName[count($extensionName) - 1];

                $this->templateEngine->addExtension(new $extensionName());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function loadTemplates($templatesSource)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public static function getTemplates($templatesSource)
    {
        $result = array();
        $templates = func_get_args();
        unset($templates[0]);

        $allTemplates = self::loadTemplates($templatesSource);

        if (!count($templates)) return $allTemplates;

        foreach ($templates as $name) {
            $result[] = isset($allTemplates[$name]) ? $allTemplates[$name] : "";
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($variables, $content = null)
    {
        return $this->applyTemplate($variables, $this->templatesSource);
    }

    /**
     * Выполняет шаблонизацию по заданному шаблону.
     * @param mixed $variables переменные передаваемые в шаблон
     * @param string $template путь до шаблона
     * @return string
     * @throws Exception
     */
    public function render($variables, $template)
    {
        return $this->applyTemplate($variables, $this->templatesDirectory . $template . '.phtml');
    }

    /**
     * Magic method: вызывает помощник шаблонов.
     * @param string $name имя помощника шаблонов
     * @param array $arguments аргументы
     * @throws RuntimeException если коллекция помощников вида не была внедрена
     * @return string
     */
    public function __call($name, array $arguments)
    {
        return $this->templateEngine->callHelper($name, $arguments);
    }

    /**
     * Выполняет шаблонизацию.
     * @param array $variables переменные передаваемые в шаблон
     * @param string $template путь до шаблона
     * @return string
     * @throws Exception
     */
    private function applyTemplate($variables, $template)
    {
        if (!is_readable($template)) {
            throw new RuntimeException(sprintf(
                'Cannot render template. PHP template file "%s" is not readable.',
                $template
            ));
        }

        ob_start();
        try {
            /** @noinspection PhpIncludeInspection */
            require $template;
        } catch (\Exception $error) {
            ob_end_clean();

            if (file_exists($this->templatesDirectory . self::ERROR_TEMPLATE)) {
                ob_start();
                /** @noinspection PhpIncludeInspection */
                require $this->templatesDirectory . self::ERROR_TEMPLATE;
            } else {
                throw $error;
            }

        }
        return ob_get_clean();
    }
}
?>