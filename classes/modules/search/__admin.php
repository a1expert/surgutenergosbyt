<?php
	abstract class __search extends baseModuleAdmin {
		public function config() {
			$config = mainConfiguration::getInstance();
			$params = array(
				'config' => array(
					'boolean:using-sphinx'	=> null,
				)
			);

			$mode = getRequest("param0");
			if($mode == "do") {
				$params = $this->expectParams($params);
				$config->set('modules', 'search.using-sphinx', $params['config']['boolean:using-sphinx']);
				$config->set('kernel', 'pages-auto-index', $params['config']['boolean:using-sphinx'] == 1 ? 0 : 1);
				$this->chooseRedirect();
			}

			$params['config']['boolean:using-sphinx'] =  $config->get('modules', 'search.using-sphinx');

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}


		public function index_control() {
			$config = mainConfiguration::getInstance();
			$searchEngine = $config->get('modules', 'search.using-sphinx');
			if ($searchEngine){
				$this->redirect($this->pre_lang . "/admin/search/sphinx_control/");
			}

			$regedit = regedit::getInstance();
			$searchModel = searchModel::getInstance();
			
			$params = array(
				"info" => array(
					"status:index_pages"		=> NULL,
					"status:index_words"		=> NULL,
					"status:index_words_uniq"	=> NULL,
					"status:index_last"			=> NULL
				),
				"globals" => array(
					"int:per_page"				=> NULL,
					"int:one_iteration_index"	=> NULL
				)
			);

			$mode = getRequest("param0");
			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVal("//modules/search/per_page", $params['globals']['int:per_page']);
				$regedit->setVal("//modules/search/one_iteration_index", $params['globals']['int:one_iteration_index']);
				$this->chooseRedirect();
			}

			$params['info']['status:index_pages'] = $searchModel->getIndexPages();
			$params['info']['status:index_words'] = $searchModel->getIndexWords();
			$params['info']['status:index_words_uniq'] = $searchModel->getIndexWordsUniq();
			$params['info']['status:index_last'] = ($index_last = $searchModel->getIndexLast()) ? date("Y-m-d H:i:s", $index_last) : "-";
			$params['globals']['int:per_page'] = $regedit->getVal("//modules/search/per_page");
			$params['globals']['int:one_iteration_index'] = $regedit->getVal("//modules/search/one_iteration_index");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

        public function sphinx_control() {
			$config = mainConfiguration::getInstance();
			$searchEngine = $config->get('modules', 'search.using-sphinx');
			if (!$searchEngine){
				$this->redirect($this->pre_lang . "/admin/search/index_control/");
			}

			$this->setDataType("settings");
			$this->setActionType("modify");

			$params = array(
				"sphinx-options" 	=> array(
					"string:host"	=> NULL,
					"int:port"		=> NULL,
					"string:dir"	=> NULL,
				),
				"fields-weight-options"	=> array(
					'int:title' 			=> NULL,
					'int:h1' 				=> NULL,
					'int:meta_keywords' 	=> NULL,
					'int:meta_descriptions' => NULL,
					'int:field_content' 	=> NULL,
					'int:tags' 				=> NULL
				),
				"generate-config"	=> array()
			);

			$mode = (string) getRequest('param0');
			if($mode == "do") {
				$params = $this->expectParams($params);
				$config->set('sphinx', 'sphinx.host', $params['sphinx-options']['string:host']);
				$config->set('sphinx', 'sphinx.port', $params['sphinx-options']['int:port']);

				$params['sphinx-options']['string:dir'] = preg_replace('#(\/$)|(\\\$)#', '', $params['sphinx-options']['string:dir']);

				$config->set('sphinx', 'sphinx.dir', $params['sphinx-options']['string:dir']);
				$config->set('sphinx', 'sphinx.title', $params['fields-weight-options']['int:title']);
				$config->set('sphinx', 'sphinx.h1', $params['fields-weight-options']['int:h1']);
				$config->set('sphinx', 'sphinx.meta_keywords', $params['fields-weight-options']['int:meta_keywords']);
				$config->set('sphinx', 'sphinx.meta_descriptions', $params['fields-weight-options']['int:meta_descriptions']);
				$config->set('sphinx', 'sphinx.field_content', $params['fields-weight-options']['int:field_content']);
				$config->set('sphinx', 'sphinx.tags', $params['fields-weight-options']['int:tags']);

				$this->chooseRedirect();
			}

			$params['sphinx-options']['string:host'] =  $config->get('sphinx', 'sphinx.host');
			$params['sphinx-options']['int:port'] =  $config->get('sphinx', 'sphinx.port');
			$params['sphinx-options']['string:dir'] = $config->get('sphinx', 'sphinx.dir');
			$params['fields-weight-options']['int:title'] =  $config->get('sphinx', 'sphinx.title');
			$params['fields-weight-options']['int:h1'] =  $config->get('sphinx', 'sphinx.h1');
			$params['fields-weight-options']['int:meta_keywords'] =  $config->get('sphinx', 'sphinx.meta_keywords');
			$params['fields-weight-options']['int:meta_descriptions'] =  $config->get('sphinx', 'sphinx.meta_descriptions');
			$params['fields-weight-options']['int:field_content'] =  $config->get('sphinx', 'sphinx.field_content');
			$params['fields-weight-options']['int:tags'] =  $config->get('sphinx', 'sphinx.tags');

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
        }

		public function truncate() {
			searchModel::getInstance()->truncate_index();
			$this->redirect($this->pre_lang . "/admin/search/");
		}


		public function reindex() {
			searchModel::getInstance()->index_all();
			$this->redirect($this->pre_lang . "/admin/search/");
		}
		
		public function partialReindex() {
			$this->setDataType("settings");
			$this->setActionType("view");

			$lastId = (int) getRequest("lastId");
			$search = searchModel::getInstance();
			
			$total = (int) $search->getAllIndexablePages();
			$limit = regedit::getInstance()->getVal("//modules/search/one_iteration_index");
			if ($limit==0) {
				$limit = 5;
			}
			$result = $search->index_all($limit, $lastId);
			
			$data = Array(
				'index-status' => Array(
					'attribute:current' => $result['current'],
					'attribute:total' => $total,
					'attribute:lastId' => $result['lastId']
				)
			);

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * Генерация базового View для контента
		 */
		public function generateView() {
			$contentIndex = new SphinxIndexGenerator('sphinx_content_index');
			$this->setIndexType($contentIndex);

			$sql = $contentIndex->generateViewQuery();
			mysql_query($sql);

			$config = mainConfiguration::getInstance();
			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			$dir = new umiDirectory($pathToSphinx);
			if (empty($pathToSphinx)) {
				$pathToSphinx = CURRENT_WORKING_DIR . DIRECTORY_SEPARATOR . 'sys-temp' . DIRECTORY_SEPARATOR . 'sphinx';
			}

			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToConfig);

			if (file_exists($pathToConfig)) {
				$fileName = $pathToConfig . 'view.sql';
				file_put_contents($fileName, $sql);
			}
			if (mysql_errno() == 0) {
				$this->sendJson(array(
					'status'  => 'ok',
					'message' => getLabel('build-view-finish')
				));
			} else {
				$this->sendJson(array(
					'status'  => 'fail',
					'message' => getLabel('build-view-finish-error')
				));
			}
		}

		/**
		 * Генерация базового конфига для Sphinx
		 */
		public function generateSphinxConfig() {
			$config = mainConfiguration::getInstance();

			$mySqlPort = $config->get('connections', 'core.port');
			if (empty($mySqlPort)) {
				$mySqlPort = 3306;
			}

			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			$dir = new umiDirectory($pathToSphinx);
			if (empty($pathToSphinx)) {
				$pathToSphinx = CURRENT_WORKING_DIR . DIRECTORY_SEPARATOR . 'sys-temp' . DIRECTORY_SEPARATOR . 'sphinx';
			}
			$pathToIndex = $pathToSphinx . DIRECTORY_SEPARATOR . 'index' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToIndex);
			$binlog = $pathToSphinx . DIRECTORY_SEPARATOR . 'log';
			$pathToLog = $pathToSphinx . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToLog);
			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToConfig);

			$contentIndex = new SphinxIndexGenerator('sphinx_content_index');
			$this->setIndexType($contentIndex);

			$configSphinx = $contentIndex->generateSphinxConfig(array(
				'{mySqlHost}' => $config->get('connections', 'core.host'),
				'{mySqlUser}' => $config->get('connections', 'core.login'),
				'{mySqlPass}' => $config->get('connections', 'core.password'),
				'{mySqlDB}' => $config->get('connections', 'core.dbname'),
				'{mySqlPort}' => $mySqlPort,
				'{pathToIndex}' => $pathToIndex,
				'{listen}' => $config->get('sphinx', 'sphinx.port'),
				'{pathToLog}' => $pathToLog,
				'{binlog}' => $binlog,
			));

			if (file_exists($pathToConfig)) {
				$fileName = $pathToConfig . 'sphinx.conf';
				file_put_contents($fileName, $configSphinx);
				$this->sendJson(array(
					'status'  => 'ok',
					'message' => getLabel('build-config-sphinx-finish')
				));
			} else {
				$this->sendJson(array(
					'status'  => 'fail',
					'message' => getLabel('build-config-sphinx-finish-error')
				));
			}
		}

		public function sendJson($data) {
			$buffer = outputBuffer::current();
			$buffer->option('generation-time', false);
			$buffer->clear();
			$buffer->push(
				json_encode(array(
						'result' => $data
					)
				)
			);
			$buffer->end();
		}

		public function isExistsConfig() {
			$config = mainConfiguration::getInstance();
			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			if (empty($pathToSphinx)) {
				$pathToSphinx = CURRENT_WORKING_DIR . DIRECTORY_SEPARATOR . 'sys-temp' . DIRECTORY_SEPARATOR . 'sphinx';
			}
			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'sphinx.conf';
			$this->sendJson(array(
				'response' => file_exists($pathToConfig)
			));
		}

		/**
		 * Добавляет поля во View
		 * @param $contentIndex SphinxIndexGenerator
		 */
		public function setIndexType($contentIndex) {
			$types = umiObjectTypesCollection::getInstance();

			$pagesType = $types->getSubTypesList($types->getType('root-pages-type')->getId());

			$indexFields = array(
				'title',
				'h1',
				'meta_keywords',
				'meta_descriptions',
				'content',
				'tags',
				'is_unindexed',
				'readme',
				'anons',
				'description',
				'descr',
				'message',
				'question',
				'answers',
			);

			$contentIndex->addPagesList($pagesType, $types, $indexFields);

			$event = new umiEventPoint("sphinxCreateView");
			$event->addRef("contentIndex", $contentIndex);
			$event->setMode("before");
			$event->call();
		}
	};
?>
