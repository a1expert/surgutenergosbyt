<?php
	/**
	 * Класс для отправки писем.
	 */
	class umiMail implements iUmiMail {
		private $template, $is_commited = false, $is_sended = false,
		$subject = "", $from_email = "no_reply@no_reply.ru", $from_name = "No_address",
		$files = Array(), $recipients = Array(), $reply_to = Array(), $copy = Array(), $hidden_copy = Array(), $priority,
		$mess_body, $charset, $content, $boundary, $sTxtBody, $arrHeaders = array(), $arrContentImages = array(),
		$arrAttachmentsImages = array(), $arrAttachments = array();
		private static $arrImagesCache = array(), $arrAttachmentsCache = array();
        /**
         * @var string $transportProvider транспорт, через который происходит отправка письма
         */
        private $transportProvider = 'phpMail';
        /**
         * @var array $sendResult результат отправки письма
         */
        private $sendResult = array();
        /**
         * @var bool $innerImgToAttach флаг конвертаци картинок из контента во вложение
         */
        private $innerImgToAttach = true;

        /**
         * Конструктор.
         * @param string $template
         * @param string $provider
         */
        public function __construct($template = "default", $provider = 'phpMail') {
            $this->template = $template;
            $this->boundary = md5(uniqid("myboundary"));
            $this->charset  = "utf-8";
            $this->priority = "normal";
            $this->transportProvider = $provider;

            $innerImgToAttachment = mainConfiguration::getInstance()->get('umi-mail', 'innerImgToAttachment');
            if (!is_null($innerImgToAttachment)) {
                $this->innerImgToAttach = (bool) $innerImgToAttachment;
            }
        }

        /**
         * Возвращает результат отправленного письма.
         * @throws coreException в случае, если письмо не было отправлено
         * @return array()
         */
        public function getSendResult()
        {
            if (!$this->is_sended) {
                throw new coreException('Mail was not send.');
            }

            return $this->sendResult;
        }

        /**
         * Добавляет получателя в общий список.
         * @param string $email e-mail получателя
         * @param bool|string $name имя получателя
         * @return bool false - если в $email некорректный адрес, true в противном случае
         */
		public function addRecipient($email, $name = false) {
			if(self::checkEmail($email)) {
				$info = array($email, $name);
				if(in_array($info, $this->recipients) === false) {
					$this->recipients[] = $info;
				}
				return true;
			}

            return false;
		}

        /**
         * Добавляет получателя в копию.
         * @param string $email e-mail получателя
         * @param bool|string $name имя получателя
         * @return bool false - если в $email некорректный адрес, true в противном случае
         */
		public function addCopy($email, $name = false) {
			if (self::checkEmail($email)) {
				$copy = ($name ? '"' . $name . '" ' : "") . "<" . $email . ">";
				if (!in_array($copy, $this->copy)) $this->copy[] = $copy;
				return true;
			}

            return false;
		}

		/**
		* Добавляет получателя в скрытую копию.
		* @param string $email e-mail получателя
		* @return bool false - если в $email некорректный адрес, true в противном случае
		*/
		public function addHiddenCopy($email) {
			if (self::checkEmail($email)) {
				if (!in_array($email, $this->hidden_copy)) $this->hidden_copy[] = $email;
				return true;
			}
			else return false;
		}

        /**
         * Добавляет получателя ответа.
         * @param string $email e-mail получателя
         * @param bool|string $name имя получателя
         * @return bool false - если в $email некорректный адрес, true в противном случае
         */
		public function addReplyTo($email, $name = false) {
			if (self::checkEmail($email)) {
				$reply_to = ($name ? '"' . $name . '" ' : "") . "<" . $email . ">";
				if (!in_array($reply_to, $this->reply_to)) $this->reply_to[] = $reply_to;
				return true;
			}

            return false;
		}

        /**
         * Устанавливает отправителя.
         * @param string $email e-mail отправителя
         * @param bool|string $name имя отправителя
         * @return bool false - если в $email некорректный адрес, true в противном случае
         */
		public function setFrom($email, $name = false) {
			if (self::checkEmail($email)) {
				$this->from_email = $email;
				$this->from_name = str_replace(array("\"", "'"), array("\\\"", "\\'"), $name);
				return true;
			}

            return false;
		}

		/**
		 * Устанавливает тему письма.
		 * @param string $subject тема письма
		 */
		public function setSubject($subject) {
			$this->subject = (string) str_replace("\n", " ", str_replace("\r", "", $subject));
		}

        /**
         * Устанавлиает текст письма, заменяя макросы значениями.
         * @param string $contentString текст письма
         * @param bool $parseTplMacros парсить ли tpl макросы
         */
		public function setContent($contentString, $parseTplMacros = true) {
			$this->content = (string) $contentString;
			$this->content = str_replace("&#037;", "%", $this->content);

            if ($parseTplMacros) {
                $this->content = def_module::parseTPLMacroses($this->content);
            }
		}

		/**
		 * Устанавливает текст письма, не производя никакую обработку.
		 * @param string $sTxtContent текст письма
		 */
		public function setTxtContent($sTxtContent) {
			$this->sTxtBody = (string) $sTxtContent;
		}

		/**
		 * Устанавливает приоритет письма.
		 * @param string $level приоритет
		 */
		public function setPriorityLevel($level = "normal") {
			switch($level) {
				case "highest": $this->priority='1 (Highest)'; break;
				case "hight":   $this->priority='2 (Hight)';   break;
				case "normal":  $this->priority='3 (Normal)';  break;
				case "low":     $this->priority='4 (Low)';     break;
				case "lowest":  $this->priority='5 (Lowest)';  break;
				default:        $this->priority='3 (Normal)';  break;
			}
		}

		/**
		 * Устанавливает уровень важности. Зарезервировано, не используется.
		 * @param String $level уровень важности
		 */
		public function setImportanceLevel($level = "normal") {
			//TODO
		}

		/**
		 * Выставляет флаг, что письмо обработано.
		 */
		public function commit() {
			$this->is_commited = true;
		}

		private function formAttachement() {

		}

		private function addHTMLImage($sImagePath, $sCType = "image/jpeg") {
			$sRealPath = $sImagePath;
			if (strtolower(substr($sImagePath, 0, 7)) !== 'http://') {
				if (!file_exists($sImagePath)) {
					if (isset($_SERVER['SERVER_NAME'])) {
						$host = $_SERVER['SERVER_NAME'];
					}
					else {
						$domainsCollection = domainsCollection::getInstance();
						$host = $domainsCollection->getDefaultDomain()->getHost();
					}
					$sRealPath = 'http://' . $host . "/" . ltrim($sImagePath , '/');
				}
			}

			if (isset(self::$arrImagesCache[$sRealPath])) {
				$this->arrAttachmentsImages[$sRealPath] = self::$arrImagesCache[$sRealPath];
				$this->arrContentImages[$sImagePath] = $sRealPath;
				return true;
			}

			if (false !== ($sImageData = @file_get_contents($sRealPath))) {
				$sBaseName = basename($sRealPath);
				$this->arrAttachmentsImages[$sRealPath] = array(
						'name' => $sBaseName,
						'path' => $sImagePath,
						'data' => $sImageData,
						'content-type' => $sCType,
						'sizes' => @getimagesize($sImagePath),
						'cid' => md5(uniqid(rand(), true))
						);
				self::$arrImagesCache[$sRealPath] = $this->arrAttachmentsImages[$sRealPath];
				$this->arrContentImages[$sImagePath] = $sRealPath;
				return true;
			} else {
				return false;
			}
		}


		private function addAttachment($sPath, $sCType="application/octet-stream") {
			if (isset(self::$arrAttachmentsCache[$sPath])) {
				$this->arrAttachments[$sPath] = self::$arrAttachmentsCache[$sPath];
				return true;
			}

			$sBaseName = basename($sPath);
			if (false !== ($sFileData = @file_get_contents($sPath))) {
				$this->arrAttachments[$sPath] = array(
						'name' => $sBaseName,
						'path' => $sPath,
						'data' => $sFileData,
						'content-type' => $sCType,
						'disposition' => 'attachment',
						'cid' => md5(uniqid(rand(), true))
						);
				self::$arrAttachmentsCache[$sPath] = $this->arrAttachments[$sPath];
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Очищает список прикрепленных файлов.
		 */
		public static function clearFilesCache() {
			self::$arrAttachmentsCache = array();
			self::$arrImagesCache = array();
		}

		/**
		 * Устанавливает заголовки письма
		 * @param array $arrXHeaders новые заголовки
		 * @param boolean $bOverwrite true - переписывать уже установленные совпадающие заголовки
		 * @return array текущие заголовки (после установки)
		 */
		public function getHeaders($arrXHeaders = array(), $bOverwrite = false) {
			$arrHeaders =  array();
			$arrHeaders['MIME-Version'] = '1.0';
			$arrHeaders = array_merge($arrHeaders, $arrXHeaders);

            $this->arrHeaders = $bOverwrite ? array_merge($this->arrHeaders, $arrHeaders) : array_merge($arrHeaders, $this->arrHeaders);

			return $this->encodeHeaders($this->arrHeaders);
		}

		private function encodeHeaders($arrHeaders) {
			$arrResult = array();

			foreach ($arrHeaders as $sHdrName => $sHdrVal) {
				$arrHdrVals = preg_split("/(\s)/", $sHdrVal, -1, PREG_SPLIT_DELIM_CAPTURE);
				$sPrevVal = "";
				$sEncHeader = "";
				foreach ($arrHdrVals as $sHdrVal) {
					if (!trim($sHdrVal)) {
						$sPrevVal .= $sHdrVal;
						continue;
					} else {
						$sHdrVal = $sPrevVal . $sHdrVal;
						$sPrevVal = "";
					}

					$sQPref = $sQSuff = '';
					if (substr($sHdrVal, 0, 1) == "\"") {
						$sHdrVal = substr($sHdrVal, 1);
						$sQPref = "\"";
					}
					if (substr($sHdrVal, -1, 1) == "\"") {
						$sHdrVal = substr($sHdrVal, 0, -1);
						$sQSuff = "\"";
					}

					if (preg_match('/[\x80-\xFF]{1}/', $sHdrVal)) {
						$sHdrVal = iconv_mime_encode($sHdrName, $sHdrVal, array('input-charset' => 'UTF-8', 'output-charset' => 'UTF-8', 'line-break-chars'=> umiMimePart::UMI_MIMEPART_CRLF));
						$sHdrVal = preg_replace("/^" . preg_quote($sHdrName, '/') . "\:\ /", "", $sHdrVal);
					}
					$sEncHeader .= $sQPref . $sHdrVal . $sQSuff;
				}
				$arrResult[$sHdrName] = $sEncHeader;
			}

			return $arrResult;
		}

		private function parseContent() {
			$content = $this->content;

			if (mainConfiguration::getInstance()->get('system', 'use-old-templater') && is_null(getRequest('scheme')) === false) unset($_REQUEST['scheme']);

			try {
				list($template_body) = def_module::loadTemplatesForMail("mail/" . $this->template, "body");
			} catch(publicException $e) {
				$template_body = "%header%\n%content%";
			}
			$block_arr = Array();

			$block_arr['header'] = $this->subject;
			$block_arr['content'] = $this->content;

			$sContent = def_module::parseTemplateForMail($template_body, $block_arr);

            if ($this->innerImgToAttach) {
                $arrImagesUrls1 = array();
                $arrImagesUrls2 = array();
                if (preg_match_all('#<\w+[^>]+\s((?i)src|background|href(?-i))\s*=\s*(["\']?)?([\w\?=\.\-_:\/]+.(jpeg|jpg|gif|png|bmp))\2#i', $sContent, $arrMatches)) {
                    $arrImagesUrls1 = isset($arrMatches[3]) ? $arrMatches[3] : array();
                }
                if (preg_match_all('#(?i)url(?-i)\(\s*(["\']?)([\w\.\-_:\/]+.(jpeg|jpg|gif|png|bmp))\1\s*\)#', $sContent, $arrMatches)) {
                    $arrImagesUrls2 = isset($arrMatches[2]) ? $arrMatches[2] : array();
                }

                $arrImagesUrls = array_unique(array_merge($arrImagesUrls1, $arrImagesUrls2));

                foreach ($arrImagesUrls as $imageUrl) {
                    $this->addHTMLImage($imageUrl);
                }
            }


			// convert relative links to absolute
			$sHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$sContent = preg_replace('#(href)\s*=\s*(["\']?)?(/([^\s"\']+))#i', '$1=$2http://'.$sHost.'$3', $sContent);

			return $sContent;
		}

		/**
		 * Выполняет отправку сформированного письма.
		 * @return bool false - в случае ошибки или true в любом друго случае
		 */
		public function send() {
            if ($this->is_sended) {
                return true;
            }

            if ($this->content != "") {
				$this->arrHeaders["From"] = ($this->from_name ? "\"" . $this->from_name . "\" " : "") . "<" . $this->from_email . ">";
				$this->arrHeaders["X-Mailer"] = "UMI.CMS";
				if (sizeof($this->reply_to)) $this->arrHeaders["Reply-To"] = implode(", ", $this->reply_to);
				if (sizeof($this->copy)) $this->arrHeaders["Cc"] = implode(", ", $this->copy);
				if (sizeof($this->hidden_copy)) $this->arrHeaders["Bcc"] = implode(", ", $this->hidden_copy);
				$this->arrHeaders["X-Priority"] = $this->priority;
				//$this->headers.="X-Importance: ".$this->importance."\n";

				$content = $this->parseContent();
				foreach ($this->arrContentImages as $sImagePath => $sRealPath) {
					if (!isset($this->arrAttachmentsImages[$sRealPath])) continue;

					$arrImgInfo = $this->arrAttachmentsImages[$sRealPath];
					$arrSearchReg = array(
									'/(\s)((?i)src|background|href(?-i))\s*=\s*(["\']?)' . preg_quote($sImagePath, '/') . '\3/',
									'/(?i)url(?-i)\(\s*(["\']?)' . preg_quote($sImagePath, '/') . '\1\s*\)/'
									);
					$arrReplace = array(
									'\1\2=\3cid:' . $arrImgInfo['cid'] .'\3',
									'url(\1cid:' . $arrImgInfo['cid'] . '\2)'
									);
					$content = preg_replace($arrSearchReg, $arrReplace, $content);
				}

                /** @var umiFile $oFile */
                foreach ($this->files as $oFile) {
					$this->addAttachment($oFile->getFilePath());
				}

				$bNeedAttachments = (bool) count($this->arrAttachments);
				$bNeedHtmlImages = (bool) count($this->arrAttachmentsImages);
				$bNeedHtmlBody = (bool) strlen($content);
				$bNeedTxtBody = (bool) strlen($this->sTxtBody);
				$bOnlyTxtBody = !$bNeedHtmlBody && (bool) strlen($content);

				$oMainPart =  new umiMimePart('', array());
				switch (true) {
					case $bOnlyTxtBody && !$bNeedAttachments:
						$oMainPart = $oMainPart->addTextPart($this->sTxtBody);
						break;

					case !$bNeedHtmlBody && !$bNeedTxtBody && $bNeedAttachments:
						$oMainPart = $oMainPart->addMixedPart();
						foreach ($this->arrAttachments as $arrAtthInfo) {
							$oMainPart->addAttachmentPart($arrAtthInfo);
						}
						break;

					case $bOnlyTxtBody && $bNeedAttachments:
						$oMainPart = $oMainPart->addMixedPart();
						$oMainPart->addTextPart($this->sTxtBody);
						foreach ($this->arrAttachments as $arrAtthInfo) {
							$oMainPart->addAttachmentPart($arrAtthInfo);
						}
						break;

					case $bNeedHtmlBody && !$bNeedHtmlImages && !$bNeedAttachments:
						$oMainPart = $oMainPart->addMixedPart();
						if ($bNeedTxtBody) {
							$oAlternativePart = $oMainPart->addAlternativePart();
							$oAlternativePart->addTextPart($this->sTxtBody);
							$oAlternativePart->addHtmlPart($content);
						} else {
							$oMainPart = $oMainPart->addHtmlPart($content);
						}
						break;

					case $bNeedHtmlBody && $bNeedHtmlImages && !$bNeedAttachments:
						$oMainPart = $oMainPart->addRelatedPart();
						if ($bNeedTxtBody) {
							$oAlternativePart = $oMainPart->addAlternativePart();
							$oAlternativePart->addTextPart($this->sTxtBody);
							$oAlternativePart->addHtmlPart($content);
						} else {
							$oMainPart->addHtmlPart($content);
						}
						foreach ($this->arrAttachmentsImages as $arrImgInfo) {
							$oMainPart->addHtmlImagePart($arrImgInfo);
						}
						break;

					case $bNeedHtmlBody && !$bNeedHtmlImages && $bNeedAttachments:
						$oMainPart = $oMainPart->addMixedPart();
						if ($bNeedTxtBody) {
							$oAlternativePart = $oMainPart->addAlternativePart();
							$oAlternativePart->addTextPart($this->sTxtBody);
							$oAlternativePart->addHtmlPart($content);
						} else {
							$oMainPart->addHtmlPart($content);
						}
						foreach ($this->arrAttachments as $arrAtthInfo) {
							$oMainPart->addAttachmentPart($arrAtthInfo);
						}
						break;

					case $bNeedHtmlBody && $bNeedHtmlImages && $bNeedAttachments:
						$oMainPart = $oMainPart->addMixedPart();
						if ($bNeedTxtBody) {
							$oAlternativePart = $oMainPart->addAlternativePart();
							$oAlternativePart->addTextPart($this->sTxtBody);
							$oRelPart = $oAlternativePart->addRelatedPart();
						} else {
							$oRelPart = $oMainPart->addRelatedPart();
						}
						$oRelPart->addHtmlPart($content);
						foreach ($this->arrAttachmentsImages as $arrImgInfo) {
							$oRelPart->addHtmlImagePart($arrImgInfo);
						}
						foreach ($this->arrAttachments as $arrAtthInfo) {
							$oMainPart->addAttachmentPart($arrAtthInfo);
						}
						break;
				}

				$arrEncodedPart = $oMainPart->encodePart();
				$this->mess_body = $arrEncodedPart['body'];

				$arrHeaders = $this->getHeaders($arrEncodedPart['headers'], true);
				$sHeaders = "";

				foreach ($arrHeaders as $sHdrName => $sHdrVal) {
					$sHeaders .= $sHdrName.": ".$sHdrVal . umiMimePart::UMI_MIMEPART_CRLF;
				}

				foreach($this->recipients as $recnt) {
					// mailto
					$sRecipientName = trim(str_replace("\n", " ", $recnt[1]));
					$sRecipientEmail = trim($recnt[0]);

					if (!strlen($sRecipientEmail)) continue;

					$sMailTo = $sRecipientEmail;

					if (strlen($sRecipientName)) {
						$sMailTo = iconv_mime_encode("", $recnt[1], array('input-charset' => 'UTF-8', 'output-charset' => 'UTF-8', 'line-break-chars'=> ''));
						$sMailTo = ltrim($sMailTo, " :");
						$sMailTo .= " <".$sRecipientEmail.">";
					}

					$sSubject = "";
					if (strlen($this->subject)) {
						$sSubject = iconv_mime_encode("", $this->subject, array('input-charset' => 'UTF-8', 'output-charset' => 'UTF-8', 'line-break-chars'=> ''));
						$sSubject = ltrim($sSubject, " :");
					}

                    $this->sendResult = $this->internalMail($sMailTo, $sSubject, $this->mess_body, $sHeaders);

					$oEventPoint = new umiEventPoint("core_sendmail");
					$oEventPoint->setParam("to", $sMailTo);
					$oEventPoint->setParam("subject", $sSubject);
					$oEventPoint->setParam("body", $this->mess_body);
					$oEventPoint->setParam("headers", $sHeaders);
					$oEventPoint->setMode("after");
					$oEventPoint->call();
				}
				$this->is_sended = true;
			} else {
                return false;
            }
		}

        /**
         * Возвращает результат отправки письма.
         * @param $mailTo
         * @param $subject
         * @param $message
         * @param $headers
         * @return array
         * @throws coreException
         */
        private function internalMail($mailTo, $subject, $message, $headers) {
            $isOk = true;
            switch ($this->transportProvider) {
                case 'phpMail' : {
                    $isOk = mail($mailTo, $subject, $message, $headers);
                    break;
                }
                case 'array' : { break; }
                default : {
                    throw new coreException('Unknown mail transport');
                }
            }

            return array(
                'mailTo' => $mailTo,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
                'isOk' => $isOk
            );
        }

		/**
		 * Прикреаляет файл к письму
		 * @param umiFile $file прикрепляемый файл
		 * @return Boolean true - в случае успеха (если файл еще не прикреплен)
		 */
		public function attachFile(umiFile $file) {
			if(in_array($file, $this->files) === false) {
				$this->files[] = $file;
				return true;
			}
		}

		/**
		 * Деструктор.
		 */
		public function __destruct() {
			if($this->is_commited && !$this->is_sended) {
				$this->send();
			}
		}

		/**
		 * Проверяет валидность e-mail адреса
		 * @param string $email адрес
		 * @return boolean true - валидный, false - не валидный
		 */
		public static function checkEmail($email) {
			return (bool) preg_match("/^[a-z0-9\._-]+@[a-z0-9\._-]+\.[a-z]{2,}$/i", $email);
		}


		protected function quoted_printable_encode($text, $header_charset = 'utf-8') {
			$length = strlen($text);

			for($whitespace = "", $line = 0, $encode = "", $index = 0; $index < $length; $index++) {
				$character=substr($text,$index,1);
				$order=Ord($character);
				$encode=0;
				switch($order) {
					case 9:
					case 32:
						if($header_charset == "") {
							$previous_whitespace=$whitespace;
							$whitespace=$character;
							$character= "";
						} else {
							if($order==32) {
								$character= "_";
							} else {
								$encode=1;
							}
						}
						break;

					case 10:
					case 13:
						if($whitespace!= "") {
							if($header_charset == "" && ($line+3) > 75) {
								$encoded.= "=\n";
								$line=0;
							}
						$encoded .= sprintf("=%02X", ord($whitespace));
						$line += 3;
						$whitespace = "";
					}

					$encoded .= $character;
					$line = 0;
					continue 2;

					default:
						if($order > 127 || $order < 32 || $character == "=" || ($header_charset != "" && ($character == "?" || $character == "_" || $character == "(" || $character == ")"))) {
							$encode=1;
						}
						break;
				}


				if($whitespace != "") {
					if($header_charset == "" && ($line+1) > 75) {
						$encoded .= "=\n";
						$line = 0;
					}

					$encoded .= $whitespace;
					$line++;
					$whitespace = "";
				}

				if($character != "") {
					if($encode) {
						$character = sprintf("=%02X", $order);
						$encoded_length = 3;
					} else {
						$encoded_length = 1;
					}

					if($header_charset == "" && ($line + $encoded_length) > 75) {
						$encoded .= "=\n";
						$line = 0;
					}

					$encoded .= $character;
					$line += $encoded_length;
				}
			}

			if($whitespace != "") {
				if($header_charset == "" && ($line+3) > 75) {
					$encoded .= "=\n";
					$encoded .= sprintf("=%02X", ord($whitespace));
				}

				if($header_charset != "" && $text != $encoded) {
					  return( "=?$header_charset?q?$encoded?=");
				} else {
					return($encoded);
				}
			}
		}
	};

?>