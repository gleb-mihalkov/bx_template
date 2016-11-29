<?php

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

			if ($isTransform) {
				array_unshift($args, $value);
				return call_user_func_array($fn, $args);
			}

			return $value;
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

	/// ------------------------------
	/// Функции преобразования данных.
	/// ------------------------------
			
		/**
		 * Получает значение по ключу 'VALUE'.
		 * @param  Mixed $value Свойство.
		 * @return Mixed        Значение.
		 */
		function _bt_fn_value($value) {
			return empty($value['VALUE']) ? $value['VALUE'] : null;
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

	/// -------------------------------------------
	/// Методы получения преобразованного значения.
	/// -------------------------------------------

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