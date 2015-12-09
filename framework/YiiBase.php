<?php
defined ( 'YII_PATH' ) or define ( 'YII_PATH', dirname ( __FILE__ ) );
class YiiBase {
	public static $classMap = array ();
	public static $enableIncludePath = true;
	private static $_aliases = array (
			'system' => YII_PATH 
	); // alias => path
	private static $_imports = array (); // alias => class name or directory
	private static $_includePaths; // list of include paths
	private static $_app;
	private static $_logger;
	public static function createWebApplication($config = null) {
		return self::createApplication ( 'CWebApplication', $config );
	}
	public static function createApplication($class, $config = null) {
		return new $class ( $config );
	}
	public static function app() {
		return self::$_app;
	}
	//别名路径
	public static function getPathOfAlias($alias) {
		if (isset ( self::$_aliases [$alias] ))
			return self::$_aliases [$alias];
		elseif (($pos = strpos ( $alias, '.' )) !== false) {
			$rootAlias = substr ( $alias, 0, $pos );
			if (isset ( self::$_aliases [$rootAlias] ))
				return self::$_aliases [$alias] = rtrim ( self::$_aliases [$rootAlias] . DIRECTORY_SEPARATOR . str_replace ( '.', DIRECTORY_SEPARATOR, substr ( $alias, $pos + 1 ) ), '*' . DIRECTORY_SEPARATOR );
		}
		return false;
	}
	public static function setPathOfAlias($alias, $path) {
		if (empty ( $path ))
			unset ( self::$_aliases [$alias] );
		else
			self::$_aliases [$alias] = rtrim ( $path, '\\/' );
	}
	public static function setApplication($app) {
		if (self::$_app === null || $app === null)
			self::$_app = $app;
	}
	public static function import($alias, $forceInclude = false) {
		if (isset ( self::$_imports [$alias] )) //是否已经存在路径
			return self::$_imports [$alias];
		
		if (class_exists ( $alias, false ) || interface_exists ( $alias, false ))//类是否已经定义,针对如urlManager这样的已定义于$_coreClasses[]的类
			return self::$_imports [$alias] = $alias;
		if (($pos = strrpos ( $alias, '.' )) === false) 		//直接是文件名
		{
			// try to autoload the class with an autoloader if $forceInclude is true
			if ($forceInclude && (Yii::autoload ( $alias, true ) || class_exists ( $alias, true )))
				self::$_imports [$alias] = $alias;
			return $alias;
		}
		
		$className = ( string ) substr ( $alias, $pos + 1 );
		$isClass = $className !== '*';
		//是否为路径+类名
		if ($isClass && (class_exists ( $className, false ) || interface_exists ( $className, false )))
			return self::$_imports [$alias] = $className;
		//获取真实路径
		if (($path = self::getPathOfAlias ( $alias )) !== false) {
			//是否以*结尾，如application.utils.*
			if ($isClass) {
				if ($forceInclude) {
					if (is_file ( $path . '.php' ))
						require ($path . '.php');
					else
						throw new CException ( Yii::t ( 'yii', 'Alias "{alias}" is invalid. Make sure it points to an existing PHP file and the file is readable.', array (
								'{alias}' => $alias 
						) ) );
					self::$_imports [$alias] = $className;
				} else
					self::$classMap [$className] = $path . '.php';
				return $className;
			} else 			// a directory
			{
				if (self::$_includePaths === null) {
					self::$_includePaths = array_unique ( explode ( PATH_SEPARATOR, get_include_path () ) );
					if (($pos = array_search ( '.', self::$_includePaths, true )) !== false)
						unset ( self::$_includePaths [$pos] );
				}
				
				array_unshift ( self::$_includePaths, $path );
				
				if (self::$enableIncludePath && set_include_path ( '.' . PATH_SEPARATOR . implode ( PATH_SEPARATOR, self::$_includePaths ) ) === false)
					self::$enableIncludePath = false;
				return self::$_imports [$alias] = $path;
			}
		}
	}
	//创建组件实例
	public static function createComponent($config) {
		if (is_string ( $config )) {
			$type = $config;
			$config = array ();
		} elseif (isset ( $config ['class'] )) {
			$type = $config ['class'];
			unset ( $config ['class'] );
		}
		if (!class_exists ( $type, false )) {
			$type = Yii::import ( $type, true );
		}
		if (($n = func_num_args ()) > 1) {
			$args = func_get_args ();
			if ($n === 2)
				$object = new $type ( $args [1] );
			elseif ($n === 3)
				$object = new $type ( $args [1], $args [2] );
			elseif ($n === 4)
				$object = new $type ( $args [1], $args [2], $args [3] );
			else {
				unset ( $args [0] );
				$class = new ReflectionClass ( $type );
				// Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
				// $object=$class->newInstanceArgs($args);
				$object = call_user_func_array ( array (
						$class,
						'newInstance' 
				), $args );
			}
		} else
			$object = new $type ();
		foreach ( $config as $key => $value )
			$object->$key = $value;
		
		return $object;
	}
	//按需加载相应的php
	public static function autoload($className)
	{
		// use include so that the error PHP file may appear
		if(isset(self::$classMap[$className]))
			include(self::$classMap[$className]);
		elseif(isset(self::$_coreClasses[$className]))
			include(self::$_coreClasses[$className]);
		else
		{
			// include class file relying on include_path
			if(strpos($className,'\\')===false)  // class without namespace
			{
				if(self::$enableIncludePath===false)
				{
					foreach(self::$_includePaths as $path)
					{
						$classFile=$path.DIRECTORY_SEPARATOR.$className.'.php';
						if(is_file($classFile))
						{
							include($classFile);
							break;
						}
					}
				}
				else
					include($className.'.php');
			}
			return class_exists($className,false) || interface_exists($className,false);
		}
		return true;
	}
	private static $_coreClasses = array (
		'CApplication' => '/base/CApplication.php',
		'CModule' => '/base/CModule.php',
		'CWebApplication' => '/base/CWebApplication.php',
		'CUrlManager' => 'CUrlManager.php',
		'CComponent' => '/base/CComponent.php',
		'CUrlRule' => 'CUrlRule.php',
		'CController' => 'CController.php',
		'CInlineAction' => '/actions/CInlineAction.php',
		'CAction' => '/actions/CAction.php',
		'CFilterChain' => '/filters/CFilterChain.php',
		'CFilter' => '/filters/CFilter.php',
		'CList' => '/collections/CList.php',
		'CHttpRequest' => 'CHttpRequest.php',
		'CDb' => 'CDb.php',
		'CInlineFilter' => 'filters/CInlineFilter.php'
		);
}

spl_autoload_register ( array (
		'YiiBase',
		'autoload' 
) );