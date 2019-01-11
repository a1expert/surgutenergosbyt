Для разработки необходимо установить две версии UMI.CMS 2.
Одна из них будет использоваться непосредственно для разработки, вторая - для тестирования внесённых изменений.

Необходимы установленные apache, mysql, 5.2.1 <= PHP <= 5.5. [Полное описание системных требований](http://help.docs.umi-cms.ru/vvedenie/ustanovka_i_nastrojka/sistemnie_trebovaniya/)

**Установка версии для тестирования внесённых изменений:**
* Заходим на [страницу загрузок UMI.CMS](http://www.umi-cms.ru/product/downloads/)
* Скачиваем [инсталлятор UMI.CMS второй версии](http://www.umi-cms.ru/product/downloads/full/).
* Настраиваем для apache виртуальный хост с доменом localhost, указывающим на каталог, где будет храниться версия для 
 тестирования изменений и перезапускаем (или перезагружаем конфиги без перезапуска) apache
* Распаковываем скачанный архив в вышеуказанный каталог
* Проверяем, что данный каталог доступен для записи пользователю, из под которого запущен вебсервер
* Запускаем установку из браузера по адресу http://localhost/install.php
* В процессе установки выбираем получение бесплатного ключа (на домене localhost он не имеет ограничений по сроку действия)
 и указываем реквизиты доступа к mysql, демосайт можно выбрать любой
* Проверить работоспособность сайта по адресу http://localhost
Пример конфигурации для сервера apache версии 2.4
```
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot {yourprojectsdir}/umicms2demosite
    <Directory {yourprojectsdir}/umicms2demosite>
        AllowOverride All
        Options FollowSymLinks MultiViews
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/umicms2demosite-error.log
    CustomLog ${APACHE_LOG_DIR}/umicms2demosite-access.log combined
</VirtualHost>
```

**Установка версии для разработки:**
* [Делаем свой форк от репозитория проекта](https://help.github.com/articles/fork-a-repo/)
* [Клонируем полученный репозиторий локально](https://help.github.com/articles/which-remote-url-should-i-use/)
* Копируем файл config.ini.default в config.ini и указываем свои реквизиты доступа к БД в разделе [connections]
* Импортируем дамп базы данных mysql `mysql -h {yourmysqlhost} -u {yourmysqllogin} -p {yourdatabasename} < ./dump/dump.sql`
* Следует проверить и в случае необходимости рекурсивно добавить права на запись каталогу sys-temp пользователю, от которого запускается apache.
 Для этого в ОС Linux необходимо выполнить команду `chmod -R 777 sys-temp` 
* Добавляем в конфиги apache виртуальный хост, указывающий на каталог проекта, указав в качестве хоста localhost и порт отличный от 80 (пример ниже)
 и перезапускаем (или перезагружаем конфиги без перезапуска) apache
* Заходим по адресу http://localhost:{порт, указанный в конфиге apache}/admin и логинимся в админку с логином admin и паролем umicms2
* В IDE необходимо настроить автоматическую выгрузку внесённых изменений в каталог версии для тестирования изменений (в IDE PhpStorm меню Tools -> Deployment) 
* При внесении изменений в базу через админку или вручную необходимо сделать дамп БД, чтобы закоммитить его в репозиторий `mysqldump --skip-extended-insert -h {yourmysqlhost} -u {yourmysqllogin} -p {yourdatabasename} > ./dump/dump.sql`
 Использование `--skip-extended-insert` позволяет сгенерировать более удобный дамп для просмотра внесённых изменений.
 Просьба не редактировать дамп базы данных вручную. Пулл реквест, не позволяющий прочесть изменения в БД, принят не будет.
 Остальные [требования к контрибьютингу](CONTRIBUTING.md)
Пример конфигурации для сервера apache версии 2.4
```
Listen *:82
<VirtualHost *:82>
    ServerName localhost
    DocumentRoot {yourprojectsdir}/umicms2dev
    <Directory {yourprojectsdir}/umicms2dev>
        AllowOverride All
        Options FollowSymLinks MultiViews
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/umicms2dev-error.log
    CustomLog ${APACHE_LOG_DIR}/umicms2dev-access.log combined
</VirtualHost>
```
