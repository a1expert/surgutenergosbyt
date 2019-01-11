<?php
/**
 * Класс для генерации адресов страниц
 */
	class umiLinksHelper {
		/* @var umiLinksHelper $instance экземпляр класса */
		private static $instance = null;
		/* @var array $links массив со сгенерированным адресами страниц */
		private $links;
		/* @var string $urlPrefix префикс адреса страницы */
		private $urlPrefix = '';
		/* @var string $urlSuffix суффикс адреса страницы */
		private $urlSuffix = '';
		/* @var int $defaultPageId идентификатор главной страницы */
		private $defaultPageId = null;
		/* @var bool $isPathAbsolute включен ли режим генерации абсолютных адресов */
		private $isPathAbsolute = null;
		/* @var int $defaultLangId идентификатор языка домена по-умолчанию */
		private $defaultLangId;
		/* @var int $currentDomainId идентификатор текущего домена */
		private $currentDomainId;
		/* @const string разделитесь адресов страниц */
		const URL_SEPARATOR = '/';
		/* @const string протокол */
		const PROTOCOL = 'http://';

		/**
		 * Возвращает экземпляр текущего класса
		 * @return umiLinksHelper
		 */
		public static function getInstance() {
			if (is_null(self::$instance)) {
				self::$instance = new umiLinksHelper();
			}
			return self::$instance;
		}

		/**
		 * Возвращает ссылку на страницу.
		 * При необходимости, полностью ее формирует.
		 * Может использоваться независимо от других публичных методов класса
		 * @param iUmiHierarchyElement $element объект класса
		 * @return string
		 */
		public function getLink(iUmiHierarchyElement $element) {

			$pageId = (int) $element->getId();

			if ($this->isLoadedPage($pageId)) {
				return $this->createLink($pageId);
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT DISTINCT
  cms3_hierarchy_relations.rel_id AS page_id,
  cms3_hierarchy.alt_name,
  cms3_hierarchy.rel              AS parent_id
FROM cms3_hierarchy_relations
  LEFT JOIN cms3_hierarchy ON cms3_hierarchy_relations.rel_id = cms3_hierarchy.id
WHERE cms3_hierarchy_relations.child_id = $pageId AND cms3_hierarchy_relations.rel_id IS NOT NULL
ORDER BY cms3_hierarchy_relations.id;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$rows = array();
			foreach($result as $row) {
				$rows[] = $row;
			}

			$this->generateFullLinks($rows, $pageId, $element->getAltName(), $element->getParentId());

			if (!$this->isLoadedPage($pageId)) {
				$this->links[$pageId] = self::URL_SEPARATOR . $element->getAltName();
			}

			return $this->createLink($pageId);
		}

		/**
		 * Возвращает ссылку на страницу, если ранее
		 * в класс были загружены данные ее родителей или она сама.
		 * (см. loadLinkPartForPages()).
		 * @param umiHierarchyElement $element объект страницы
		 * @return bool|string
		 */
		public function getLinkByParts(umiHierarchyElement $element) {

			$parentId = $element->getParentId();
			$umiHierarchy = umiHierarchy::getInstance();
			$pageId = $element->getId();

			if (!$this->isLoadedPage($parentId) && !$umiHierarchy->isExists($pageId)) {
				return false;
			}

			$path = null;

			if ($this->isLoadedPage($pageId)) {
				return $this->createLink($pageId);
			}

			if (!$this->isLoadedPage($parentId)) {
				$path = self::URL_SEPARATOR . $element->getAltName();
			}

			if (is_null($path)) {
				$path = $this->links[$parentId] . self::URL_SEPARATOR . $element->getAltName();
			}

			$this->links[$pageId] = $path;

			return $this->createLink($pageId);
		}

		/**
		 * Загружает данные для формирования адресов страниц.
		 * @param array $pageIds массив с идентификаторами страниц
		 * @return bool
		 */
		public function loadLinkPartForPages(array $pageIds) {

			if (count($pageIds) === 0) {
				return false;
			}

			$pageIdsToLoad = array();

			foreach($pageIds as $key => $value) {
				$pageId = intval($value);
				if (!$this->isLoadedPage($pageId)) {
					$pageIdsToLoad[$key] = $pageId;
				}
			}

			if (count($pageIdsToLoad) === 0) {
				return true;
			}

			$pageIds = implode(',', $pageIdsToLoad);
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT DISTINCT
  cms3_hierarchy_relations.rel_id AS page_id,
  cms3_hierarchy.alt_name,
  cms3_hierarchy.rel              AS parent_id
FROM cms3_hierarchy_relations
  LEFT JOIN cms3_hierarchy ON cms3_hierarchy_relations.rel_id = cms3_hierarchy.id
WHERE cms3_hierarchy_relations.child_id IN ($pageIds) AND cms3_hierarchy_relations.rel_id IS NOT NULL
ORDER BY cms3_hierarchy_relations.id;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return false;
			}

			$rows = array();
			foreach($result as $row) {
				$rows[] = $row;
			}

			return $this->generatePartOfLinks($rows);
		}

		/**
		 * Загружены ли данные страницы
		 * @param int $elementId ид страницы
		 * @return bool
		 */
		public function isLoadedPage($elementId) {
			return isset($this->links[intval($elementId)]);
		}

		/**
		 * Выгрузить данные страницы
		 * @param int $elementId ид страницы
		 * @return bool
		 */
		public function unloadPage($elementId) {

			if (!$this->isLoadedPage($elementId)) {
				return false;
			}

			unset($this->links[intval($elementId)]);
			return true;
		}

		/**
		 * Очистить все загруженные данные
		 * @return void
		 */
		public function clearCache() {
			$this->links = array();
		}

		/**
		 * Конструктор
		 */
		private function __construct() {
			$cmsController = cmsController::getInstance();
			$config = mainConfiguration::getInstance();

			$this->urlPrefix = $cmsController->getUrlPrefix();

			if ($config->get('seo', 'url-suffix.add')) {
				$this->urlSuffix = $config->get('seo', 'url-suffix');
			}

			$currentDomain = $cmsController->getCurrentDomain();

			if ($currentDomain instanceof domain) {
				$this->currentDomainId = $currentDomain->getId();
				$this->defaultLangId = $currentDomain->getDefaultLangId();
			}
		}

		/**
		 * "Склеивает" конечную ссылку на страницу, с
		 * учетом всех параметров.
		 * @param int $pageId ид страницы
		 * @return bool|string
		 */
		private function createLink($pageId) {
			static $cache = array();

			$pageId = intval($pageId);

			if (isset($cache[$pageId])) {
				return $cache[$pageId];
			}

			if (!$this->isLoadedPage($pageId)) {
				return false;
			}

			$pageLink = $this->links[$pageId];
			$pageLink = rtrim($pageLink, '/');

			if ($this->getDefaultElementId() === $pageId) {
				$pageLink = '';
			}

			$pageLink .= $this->urlSuffix;
			$umiHierarchy = umiHierarchy::getInstance();

			$page = $umiHierarchy->getElement($pageId);
			if (!$page instanceof umiHierarchyElement) {
				return false;
			}

			$elementDomainId = (int) $page->getDomainId();

			$domainPrefix = '';
			if ($elementDomainId !== $this->currentDomainId || $this->isPathAbsolute()) {
				$umiDomains = $umiHierarchy->domainsCollection;
				$elementDomain = $umiDomains->getDomain($elementDomainId);
				if ($elementDomain instanceof domain) {
					$domainPrefix = self::PROTOCOL . (string) $elementDomain->getHost();
				}
			}

			$elementLangId = (int) $page->getLangId();
			$langPrefix = '';
			if ($elementLangId !== $this->defaultLangId) {
				$umiLangs = $umiHierarchy->langsCollection;
				$elementLang = $umiLangs->getLang($elementLangId);
				if ($elementLang instanceof lang) {
					$langPrefix = self::URL_SEPARATOR . (string) $elementLang->getPrefix();
				}
			}

			return $cache[$pageId] = $domainPrefix  . $langPrefix . $this->urlPrefix . $pageLink;
		}

		/**
		 * Включен ли абсолютный режим генерации адресов
		 * @return bool
		 */
		private function isPathAbsolute() {
			if (is_null($this->isPathAbsolute)) {
				$this->isPathAbsolute = umiHierarchy::getInstance()->isPathAbsolute();
			}
			return (bool) $this->isPathAbsolute;
 		}

		/**
		 * Возвращает id главной страницы
		 * @return int
		 */
		private function getDefaultElementId() {
			if (is_null($this->defaultPageId)) {
				$this->defaultPageId = umiHierarchy::getInstance()->getDefaultElementId();
			}
			return (int) $this->defaultPageId;
		}

		/**
		 * Генерирует полные данные об адресе страниц.
		 * @param array $rows массив с данными о иерархических связях страницы
		 * @param int $pageId ид страницы
		 * @param string $pageAltName псевдостатических адрес страницы
		 * @param int $parentId ид родителя страницы
		 * @return bool
		 */
		private function generateFullLinks(array $rows, $pageId, $pageAltName, $parentId) {

			if (count($rows) == 0) {
				return false;
			}

			$parents = array();

			foreach ($rows as $row) {
				if (!isset($parents[$row['parent_id']])) {
					$this->links[$row['page_id']] = self::URL_SEPARATOR . $row['alt_name'];
					$parents[$row['page_id']] = self::URL_SEPARATOR . $row['alt_name'];
				} else {
					$this->links[$row['page_id']] = $parents[$row['parent_id']] . self::URL_SEPARATOR . $row['alt_name'];
					$parents[$row['page_id']] = $parents[$row['parent_id']] . self::URL_SEPARATOR . $row['alt_name'];
				}
			}

			if (isset($parents[$parentId])) {
				$this->links[$pageId] = $parents[$parentId] . self::URL_SEPARATOR . $pageAltName;
				return true;
			}

			return false;
		}

		/**
		 * Генерирует данные об адресах родителей страниц
		 * @param array $rows массив с данными о иерархических связях страниц
		 * @return bool
		 */
		private function generatePartOfLinks(array $rows) {

			if (count($rows) == 0) {
				return false;
			}

			$links = &$this->links;
			$parents = array();

			foreach ($rows as $row) {
				if (!isset($parents[$row['parent_id']])) {
					$links[$row['page_id']] = self::URL_SEPARATOR . $row['alt_name'];
					$parents[$row['page_id']] = self::URL_SEPARATOR . $row['alt_name'];
				} else {
					$links[$row['page_id']] = $parents[$row['parent_id']] . self::URL_SEPARATOR . $row['alt_name'];
					$parents[$row['page_id']] = $parents[$row['parent_id']] . self::URL_SEPARATOR . $row['alt_name'];
				}
			}

			if (count($parents) === 0) {
				return false;
			}

			return true;
		}
	}
?>