<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

/**
 * PHP шаблонизатор.
 */
class PhpTemplateEngine
{
    /**
     * @var callable[] $functions
     */
    protected $functions = array();

    /**
     * Дабавляет расширение с функциями.
     * @param IPhpExtension $extension
     * @return $this
     */
    public function addExtension(IPhpExtension $extension)
    {
        foreach ($extension->getFunctions() as $functionName => $function) {
            $this->functions[$functionName] = $function;
        }

        return $this;
    }

    /**
     * Magic method: вызывает помошник вида.
     * @param string $name имя помошника вида
     * @param array $arguments аргументы
     * @throws RuntimeException если коллекция помощников вида не была внедрена
     * @return string
     */
    public function callHelper($name, array $arguments)
    {
        if (!isset($this->functions[$name])) {
            throw new RuntimeException(sprintf('Function "%s" does not exist', $name));
        }

        $start_time = microtime(true);
        $result = call_user_func_array($this->functions[$name], $arguments);
        $executionTime = number_format(microtime(true) - $start_time, 6);

        umiBaseStream::addLineCallLog(
            array($name . ': ' . var_export($arguments, true) , $executionTime)
        );

        return $result;
    }
}
?>