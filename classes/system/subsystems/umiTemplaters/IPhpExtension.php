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
 * Расширение для PHP-шаблонизатора/
 */
interface IPhpExtension
{
    /**
     * Возвращает имя расширения.
     * @return string
     */
    public function getName();
    /**
     * Возвращает список функций.
     * @return array вида [$functionName => $callable, ...]
     */
    public function getFunctions();
}
?>