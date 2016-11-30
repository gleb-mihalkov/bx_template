<?php
	// Блокировка прямого вызова файла.
	if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

	/// ----------
	/// Константы.
	/// ----------
	
		// Дата вида '31.01.2016'.
		define('BT_DATE', 'd.m.Y');

		// Дата вида '31 Января 2016'.
		define('BT_DATE_RU', 'j F Y');

		// Время вида '14:30'.
		define('BT_TIME', 'H:i');

		// Дата вида '31.01.2016 14:30'.
		define('BT_DATETIME', 'd.m.Y H:i');

		// Дата вида '31 Января 2016 года, 14:30'.
		define('BT_DATETIME_RU', 'j F Y года, H:i');

		// Показывает, что следует отображать кнопку редактирования.
		define('BT_EDIT', 'EDIT');

		// Показывает, что следует отображать кнопку удаления.
		define('BT_DELETE', 'DEL');

	/// ------------------------------------------------------
	/// Методы работы с источниками данных компонентов Bitrix.
	/// ------------------------------------------------------
		
		/**
		 * Получает список новостей из компонента или null, если массив ITEMS не задан или пуст.
		 * @param  Array $arResult Данные компонента.
		 * @return Array           Список новостей или null.
		 */
		function bt_news_list($arResult) {
			return empty($arResult['ITEMS']) ? null : $arResult['ITEMS'];
		}

	/// ----------------------------------------
	/// Функции получения значения по селектору.
	/// ----------------------------------------
			
		/**
		 * Получает значение по ключу в ассоциативном массиве или null, если ключ не задан.
		 * @param  Array  $array Массив.
		 * @param  String $key   Ключ.
		 * @return Mixed         Значение.
		 */
		function _bt_get_safe($array, $key) {
			return isset($array[$key]) ? $array[$key] : null;
		}

		/**
		 * Получает непустое значение по ключу в ассоциативном массиве или null.
		 * @param  Array  $array Массив.
		 * @param  String $key   Ключ.
		 * @return Mixed         Значение или null.
		 */
		function _bt_get_full($array, $key) {
			return empty($array[$key]) ? null : $array[$key];
		}

		/**
		 * Вызывает пользовательскую функцию преобразования значения.
		 * @param  Array    $array Массив, в котором находится значение.
		 * @param  String   $key   Ключ, по которому находится значение.
		 * @param  Mixed    $value Значение.
		 * @param  Callable $fn    Функция преобразования значения.
		 * @param  Array    $args  Дополнительные аргументы для функции.
		 * @return Mixed           Результат выполнения функции.
		 */
		function _bt_call($array, $key, $value, $fn, $args) {
			$argsLength = count($args);

			for ($i = 0; $i < $argsLength; $i++) {
				$keyword = $args[$i];

				if ($keyword === '__KEY__') {
					$args[$i] = $key;
					continue;
				}

				if ($keyword === '__ARRAY__') {
					$args[$i] = $array;
					continue;
				}
			}

			array_unshift($args, $value);
			return call_user_func_array($fn, $args);
		}

		/**
		 * Получает значение по ключу в ассоциативном массиве или null, если значение не найдено.
		 * @param  Array    $array  Массив.
		 * @param  String   $key    Ключ.
		 * @param  Callable $fn     Функция преобразования значения.
		 * @param  Array    $args   Дополнительные аргументы для функции преобразования значения.
		 * @return Mixed            Значение или null.
		 */
		function _bt_get_value($array, $key, $fn = null, $args = array()) {
			$value = _bt_get_safe($array, $key);
			$isTransform = isset($value) && isset($fn);
			return $isTransform ? _bt_call($array, $key, $value, $fn, $args) : $value;
		}

		/**
		 * Получает значение по пути в ассоциативном массиве.
		 * @param  Array    $array  Массив.
		 * @param  String   $path   Селектор пути.
		 * @param  Callable $fn     Функция преобразования значения.
		 * @param  Array    $args   Дополнительные аргументы для функции преобразования значения.
		 * @return Mixed            Значение или null, если значение не найдено.
		 */
		function _bt_get_path($array, $path, $fn = null, $args = array()) {
			$path = explode('.', $path);
			$pathLength = count($path);

			$current = $array;

			for ($i = 0; $i < $pathLength; $i++) {
				$prop = trim($path[$i]);
				if (empty($prop)) return null;


				if ($i + 1 === $pathLength) {
					return _bt_get_value($current, $prop, $fn, $args);
				}

				$value = _bt_get_safe($current, $prop);
				if (!is_array($value)) return null;

				$current = $value;
			}

			return null;
		}

		/**
		 * Получает значение по селектору пути из элемента инфоблока или null, если значение не найдено.
		 * @param  Array    $arItem Элемент инфоблока.
		 * @param  String   $path   Селектор пути.
		 * @oaram  Callable $fn     Функция преобразования значения.
		 * @param  Array    $args   Дополнительные аргументы для функции преобразования значения.
		 * @return Mixed            Значение или null.
		 */
		function _bt_get_one($arItem, $path, $fn = null, $args = array()) {
			if ($path[0] === '#') $path = 'PROPERTIES.'.substr($path, 1);
			if ($path[0] === '@') $path = 'PREVIEW_'.substr($path, 1);
			if ($path[0] === '%') $path = 'DETAIL_'.substr($path, 1);

			return strpos($path, '.') === false
				? _bt_get_value($arItem, $path, $fn, $args) : _bt_get_path($arItem, $path, $fn, $args);
		}

		/**
		 * Получает значение по селектору из элемента инфоблока или null, если значение не найдено.
		 * @param  Array    $arItem Элемент инфоблока.
		 * @param  String   $select Селектор.
		 * @param  Callable $fn     Функция преобразования значения.
		 * @param  Array    $args   Дополнительные аргументы для функции преобразования значения.
		 * @return Mixed            Значение или null.
		 */
		function _bt_get($arItem, $select, $fn = null, $args = array()) {
			if (empty($arItem) || empty($select)) return null;

			if (strpos($select, '|') === false) {
				$select = trim($select);
				return empty($select) ? null : _bt_get_one($arItem, $select, $fn, $args);
			}

			$list = explode('|', $select);

			foreach ($list as $item) {
				$item = trim($item);
				if (empty($item)) continue;

				$value = _bt_get_one($arItem, $item, $fn, $args);
				if ($value === null) continue;

				return $value;
			}

			return null;
		}

	/// ---------------------------
	/// Функции получения значения.
	/// ---------------------------
	
		/**
		 * Получает значение по селектору. Если значение не найдено, возвращается значение
		 * по умолчанию.
		 * @param  Array  $arItem Массив, в котором производится поиск.
		 * @param  String $select Селектор.
		 * @param  String $def    Значение по умолчанию.
		 * @return Mixed          Значение.
		 */
		function bt_get($arItem, $select, $def = null) {
			$value = _bt_get($arItem, $select);
			return isset($value) ? $value : $def;
		}

		/**
		 * Получает непустое значение по селектору.
		 * @param  Array  $arItem Массив, в котором производится поиск.
		 * @param  String $select Селектор.
		 * @param  Mixed  $def    Значение по умолчанию.
		 * @return Mixed          Значение.
		 */
		function bt_full($arItem, $select, $def = null) {
			$value = _bt_get($arItem, $select);
			return empty($value) ? $def : $value;
		}

		/**
		 * Возвращает ID элемента.
		 * @param  Array  $arItem Элемент.
		 * @return String         ID.
		 */
		function bt_id($arItem) {
			return _bt_get_full($arItem, 'ID');
		}

		/**
		 * Возвращает название элемента.
		 * @param  Array  $arItem Элемент.
		 * @return String         Название.
		 */
		function bt_name($arItem) {
			return _bt_get_full($arItem, 'NAME');
		}

		/**
		 * Возвращает символьный код элемента.
		 * @param  Array  $arItem Элемент.
		 * @return String         Символьный код.
		 */
		function bt_code($arItem) {
			return _bt_get_full($arItem, 'CODE');
		}

		/**
		 * Добавляет кнопку редактирования.
		 * @param  Array     $arItem    Элемент инфоблока.
		 * @param  String    $id        ID элемента.
		 * @param  String    $block     ID инфоблока.
		 * @param  Component $component Компонент Bitrix.
		 * @return Boolean              True, если кнопка добавлена, иначе false.
		 */
		function _bt_action_edit($arItem, $id, $block, $component) {
			$edit = _bt_get_full($arItem, 'EDIT_LINK');
				
			if (empty($edit)) {
				/** @todo Получение ссылки вручную. */
			}

			if (empty($edit)) return false;

			$opts = CIBlock::GetArrayByID($block, "ELEMENT_EDIT");
			$component->AddEditAction($id, $edit, $opts);

			return true;
		}

		/**
		 * Добавляет кнопку удаления.
		 * @param  Array     $arItem    Элемент инфоблока.
		 * @param  String    $id        ID элемента.
		 * @param  String    $block     ID инфоблока.
		 * @param  Component $component Компонент Bitrix.
		 * @return Boolean              True, если кнопка добавлена, иначе false.
		 */
		function _bt_action_delete($arItem, $id, $block, $component) {
			$delete = _bt_get_full($arItem, 'DELETE_LINK');
				
			if (empty($delete)) {
				/** @todo Получение ссылки вручную. */
			}

			if (empty($delete)) return false;

			$params = array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM'));
			$opts = CIBlock::GetArrayByID($block, "ELEMENT_DELETE");
			$component->AddDeleteAction($id, $delete, $opts, $params);

			return true;
		}

		/**
		 * Возвращает ID области редактирования элемента инфоблока из 
		 * публичной части сайта.
		 * @param  Array     $arItem    Элемент инфоблока.
		 * @param  Component $component Компонент Bitrix (в шаблоне - переменная $this).
		 * @param  String    $flagA     Первый флаг кнопки.
		 * @param  String    $flagB     Второй флаг кнопки.
		 * @return String               Идетификатор области.
		 */
		function bt_action($arItem, $component, $flagA = BT_EDIT, $flagB = null) {
			$block = _bt_get_full($arItem, 'IBLOCK_ID');
			$id = _bt_get_full($arItem, 'ID');

			$isExit = empty($block) || empty($id);
			if ($isExit) return null;

			if ($flagA === BT_EDIT) {
				$isEdit = _bt_action_edit($arItem, $id, $block, $component);
			}

			if ($flagA === BT_DELETE) {
				$isDelete = _bt_action_delete($arItem, $id, $block, $component);
			}

			if ($flagA !== BT_EDIT && $flagB === BT_EDIT) {
				$isEdit = _bt_action_edit($arItem, $id, $block, $component);
			}

			if ($flagA !== BT_DELETE && $flagB === BT_DELETE) {
				$isDelete = _bt_action_delete($arItem, $id, $block, $component);
			}

			$isArea = $isEdit || $isDelete;
			$area = $isArea ? $component->GetEditAreaId($id) : null;

			return $area;
		}

	/// ------------------------------
	/// Функции преобразования данных.
	/// ------------------------------
			
		/**
		 * Получает значение по ключу 'VALUE'.
		 * @param  Mixed  $value Свойство.
		 * @return Mixed        Значение.
		 */
		function _bt_fn_value($value) {
			return empty($value['VALUE']) ? $value : $value['VALUE'];
		}

		/**
		 * Получает ссылку на файл.
		 * @param  Array  $value Описание файла или его ID.
		 * @return String        Ссылка на файл.
		 */
		function _bt_fn_file($value) {
			if (empty($value)) return null;

			$id = $value;

			if (is_array($value)) {
				$isVal = !empty($value['VALUE']);
				$isSrc = !empty($value['SRC']);

				if ($isSrc) return $value['SRC'];
				if ($isVal) $id = $value['VALUE'];
			}

			$file = CFile::GetPath($id);
			return empty($file) ? null : $file;
		}

		/**
		 * Получает ссылку на сжатое изображение.
		 * @param  Mixed  $value  Описание файла изображения или его ID.
		 * @param  Number $width  Максимальная ширина изображения.
		 * @param  Number $height Максимальная высота изображения.
		 * @return String         Ссылка на изображение.
		 */
		function _bt_fn_image($value, $width, $height) {
			if (empty($value)) return null;

			$id = $value;

			if (is_array($value)) {
				$isSrc = !empty($value['SRC']) && !empty($value['ID']);
				$isVal = !empty($value['VALUE']);

				if ($isSrc) $id = $value['ID'];
				else if ($isVal) $id = $value['VALUE'];
				else return null;
			}

			$isResize = !empty($width);

			if ($isResize) {
				$height = empty($height) ? $width : $height;

				$size = array(
					'height' => $height,
					'width' => $width
				);

				$type = BX_RESIZE_IMAGE_PROPORTIONAL;
				$file = CFile::ResizeImageGet($id, $size, $type);
				$file = empty($file['src']) ? null : $file['src'];
			}
			else {
				$file = CFile::GetPath($id);
			}

			return empty($file) ? null : $file;
		}

		/**
		 * Возвращает дату в указанном формате.
		 * @param  Mixed  $value Свойство или значение даты.
		 * @return String        Дата в указанном формате.
		 */
		function _bt_fn_date($value, $format) {
			if (empty($value)) return null;

			$value = empty($value['VALUE']) ? $value : $value['VALUE'];
			if (!is_string($value)) return null;

			$isConvert = !empty($format);

			if ($isConvert) {
				$time = MakeTimeStamp($value);
				if (!isset($time)) return null;

				$value = FormatDate($format, $time);
			}

			return empty($value) ? null : $value;
		}

		/**
		 * Получает текст и исходный формат текста из элемента инфоблока.
		 * @param  String $key   Начальный ключ для поиска текста.
		 * @param  Array  $array Массив, в котором производится поиск.
		 * @return Array         Текст.
		 */
		function _bt_fn_text_get($array, $key) {
			$text = null;

			$node = _bt_get_safe($array, '~'.$key);
			$node = isset($node) ? $node : _bt_get_safe($array, $key);

			if (is_array($node)) {
				$value = _bt_get_safe($node, '~TEXT');
				$value = isset($value) ? $value : _bt_get_safe($node, 'TEXT');

				if (!isset($value)) {
					$value = _bt_get_safe($node, '~VALUE');
					$value = isset($value) ? $value : _bt_get_safe($node, 'VALUE');

					$text = isset($value)
						? (is_array($value) ? _bt_get_safe($value, 'TEXT') : $value)
						: null;
				}
				else {
					$text = $value;
				}
			}

			return isset($text) ? $text : $node.'';
		}

		/**
		 * Получает тип текста (TEXT | HTML).
		 * @param  String $text Текст.
		 * @return String       Тип текста.
		 */
		function _bt_fn_text_type($text) {
			$match = preg_match('/<[^>]+>/', $text);
			return $match ? 'HTML' : 'TEXT';
		}

		/**
		 * Преобразует текстовое содержимое инфоблока в нужный формат (текст или HTML).
		 * @param  Mixed  $value Текстовая строка или массив свойства.
		 * @param  String $key   Ключ, по которому находится значение.
		 * @param  Array  $obj   Родительский массив.
		 * @return String        Содержимое в указанном формате.
		 */
		function _bt_fn_text($value, $key, $array, $format) {
			if (empty($value)) return '';

			$text = _bt_fn_text_get($array, $key);
			$type = _bt_fn_text_type($text);

			$isNative = $type === $format;
			if ($isNative) return $text;

			$result = $format === 'TEXT'
				? HTMLToTxt($text, '', array(), false)
				: TxtToHTML($text);

			return $result;
		}

	/// -------------------------------------------
	/// Методы получения преобразованного значения.
	/// -------------------------------------------

		/**
		 * Получает значение по селектору, преобразовывая его с помощью функции $fn.
		 * @param  Array    $arItem Массив, в котором производится поиск.
		 * @param  String   $select Селектор.
		 * @param  Callable $fn     Функция преобразования значения.
		 * @param  Mixed    $arg    Дополнительные аргументы для функции.
		 * @return Mixed            Значение или null.
		 */
		function bt_func() {
			$args = func_get_args();

			$arItem = isset($args[0]) ? $args[0] : null;
			$select = isset($args[1]) ? $args[1] : null;
			$fn = isset($args[2]) ? $args[2] : null;
			$args = array_slice($args, 3);

			return _bt_get($arItem, $select, $fn, $args);
		}

		/**
		 * Получает значение свойства элемента по селектору.
		 * @param  Array  $arItem Элемент инфоблока.
		 * @param  String $select Селектор.
		 * @param  Mixed  $def    Значение по умолчанию.
		 * @return Mixed          Значение.
		 */
		function bt_value($arItem, $select, $def = null) {
			$value = bt_func($arItem, $select, '_bt_fn_value');
			return empty($value) ? $def : $value;
		}

		/**
		 * Получает ссылку на файл, находящийся в свойстве инфоблока по селектору.
		 * @param  Array  $arItem Элемент инфоблока.
		 * @param  String $select Селектор.
		 * @param  String $def    Значение по умолчанию.
		 * @return String         Ссылка на файл.
		 */
		function bt_file($arItem, $select, $def = null) {
			$value = bt_func($arItem, $select, '_bt_fn_file');
			return empty($value) ? $def : $value;
		}

		/**
		 * Возвращает ссылку на обработанное изображение из свойства элемента.
		 * @param  Array  $arItem     Элемент инфоблока.
		 * @param  String $select     Селектор.
		 * @param  Number $width      Максимальная ширина изображения.
		 * @param  Number $height     Максимальная высота изображения.
		 * @param  String $def        Изображение по умолчанию.
		 * @return String             Ссылка на изображение.
		 */
		function bt_image($arItem, $select, $width = null, $height = null, $def = null) {
			$value = bt_func($arItem, $select, '_bt_fn_image', $width, $height);
			return empty($value) ? $def : $value;
		}

		/**
		 * Возвращает дату из свойства элемента.
		 * @param  Array $arItem Элемент инфоблока.
		 * @param  String $select Селектор.
		 * @param  String $format Выходной формат даты.
		 * @param  String $def    Значение по умолчанию.
		 * @return String         Дата.
		 */
		function bt_date($arItem, $select, $format = null, $def = null) {
			$value = bt_func($arItem, $select, '_bt_fn_date', $format);
			return empty($value) ? $def : $value;
		}

		/**
		 * Возвращает содержимое элемента в виде отформатированного текста.
		 * @param  Array  $arItem Элемент инфоблока.
		 * @param  String $select Селектор.
		 * @param  String $def    Текст по умолчанию.
		 * @return String         Текст.
		 */
		function bt_text($arItem, $select, $def = null) {
			$value = bt_func($arItem, $select, '_bt_fn_text', '__KEY__', '__ARRAY__', 'TEXT');
			return empty($value) ? $def : $value;
		}

		/**
		 * Возвращает содержимое элемента в виде HTML-кода.
		 * @param  Array  $arItem Элемент инфоблока.
		 * @param  String $select Селектор.
		 * @param  String $def    Текст по умолчанию.
		 * @return String         Текст.
		 */
		function bt_html($arItem, $select, $def = null) {
			$value = bt_func($arItem, $select, '_bt_fn_text', '__KEY__', '__ARRAY__', 'HTML');
			return empty($value) ? $def : $value;
		}

	/// ---------------
	/// Прочие функции.
	/// ---------------
		
		/**
		 * Возвращает true, если функция была вызвана в $count-ый раз, иначе возращает false.
		 * Вызов bt_is_count() запускает новый отсчет.
		 * @param  Number  $count Указывает, в который раз возвратить true.
		 * @return Boolean        True или false.
		 */
		function bt_is_count($count = 0) {
			static $now = 0;

			if ($count === 0) {
				$now = 0;
				return null;
			}

			return ++$now === $count;
		}
	