<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';


bab_functionality::get('Widgets')->includePhpClass('Widget_Action');



class bab_UnknownActionException extends Exception
{
	function __construct($action, $code = 0)
	{
		$message = 'Unknown method "' . $action->getController() . '::' . $action->getMethod() . '"';
		parent::__construct($message, $code);
	}
}

class bab_MissingActionParameterException extends Exception
{
	function __construct($action, $parameterName, $code = 0)
	{
		$message = 'Mandatory parameter "' . $parameterName . '" missing in ' . $action->getController() . '::' . $action->getMethod();
		parent::__construct($message, $code);
	}
}

class bab_InvalidActionException extends Exception
{
    
}


/**
 * bab_AccessException are thrown when the user tries to
 * perform an action or access a page that she is not
 * allowed to.
 * 
 */
class bab_AccessException extends Exception
{
	/**
	 * Require credential if not logged in
	 * @var unknown_type
	 */
	public $require_credential = true;
}


/**
 * bab_SaveException are thrown in save methods of controller.
 * this will go the failed action set on the submit button
 * 
 * display message in page
 */
class bab_SaveException extends Exception 
{
	/**
	 * true : redirect to failed action
	 * false : execute failed action directly without redirect
	 * 		(can be used to redirect ot the same form and populate
	 * 		 fields with posted data)
	 *
	 * @var bool
	 */
	public $redirect = true;
}

/**
 * Dsiplay error in page instead of message
 *
 */
class bab_SaveErrorException extends bab_SaveException {}



abstract class bab_Controller
{
	const PROXY_CLASS_SUFFIX = 'Proxy';

	/**
	 * The http request accepts json.
	 * @var boolean
	 */
	protected static $acceptJson = null;

	/**
	 * The http request accepts html.
	 * @var boolean
	 */
	protected static $acceptHtml = null;



	protected function initAccept()
	{
		self::$acceptJson = (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
		self::$acceptHtml = (strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false) || (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false);
		return $this;
	}




	/**
	 * The http request accepts json.
	 * @return boolean
	 */
	protected static function acceptJson()
	{
		if (!isset(self::$acceptJson)) {
			self::initAccept();
		}
		return self::$acceptJson;
	}


	/**
	 * The http request accepts html.
	 * @return boolean
	 */
	protected static function acceptHtml()
	{
		if (!isset(self::$acceptHtml)) {
			self::initAccept();
		}
		return self::$acceptHtml;
	}
	
	
	/**
	 * PHP code
	 * @return string
	 */ 
	protected static function getClassCode($proxyClassname, $classname, Array $methods) {
	    
	    $classStr = 'class ' . $proxyClassname . ' extends ' . $classname . ' {' . "\n";
	    
	    
	    foreach ($methods as $method) {
	        if ($method->name === '__construct' || !$method->isPublic() || $method->isStatic() || $method->isFinal()) {
	            continue;
	        }
	    
	    
	        $classStr .= '	public function ' . $method->name . '(';
	        $parameters = $method->getParameters();
	        $parametersStr = array();
	        foreach ($parameters as $parameter) {
	    
	            if ($parameter->isDefaultValueAvailable()) {
	                $parametersStr[] = '$' . $parameter->name . ' = ' . var_export($parameter->getDefaultValue(), true);
	            } else {
	                $parametersStr[] = '$' . $parameter->name;
	            }
	        }
	        $classStr .= implode(', ', $parametersStr);
	        $classStr .= ') {' . "\n";
	        $classStr .= '		$args = func_get_args();' . "\n";
	        $classStr .= '		return $this->getMethodAction(__FUNCTION__, $args);' . "\n";
	        $classStr .= '	}' . "\n";
	    }
	    $classStr .= '}' . "\n";
	    
	    
	    return $classStr;
	}



	/**
	 * Dynamically creates a proxy class for this controller with all public, non-final and non-static functions
	 * overriden so that they return an action (Widget_Action) corresponding to each of them.
	 *
	 * @param string $classname
	 * @return bab_Controller
	 */
	protected static function getProxyInstance($classname)
	{
		$class = new ReflectionClass($classname);
		$proxyClassname = $classname . self::PROXY_CLASS_SUFFIX;
		if (!class_exists($proxyClassname)) {
		    
		    $methods = $class->getMethods();
			
			// We define the proxy class
			eval(self::getClassCode($proxyClassname, $classname, $methods));
		}

		return new $proxyClassname();
	}
	
	
	
	/**
	 * Dynamically creates a proxy class in the current namespace for this controller with all public, non-final and non-static functions
	 * overriden so that they return an action (Widget_Action) corresponding to each of them.
	 * 
	 * @param string $namespace
	 * @param string $classname
	 * @return bab_Controller
	 */
	protected static function getNsProxyInstance($namespace, $classname)
	{
	    $class = new \ReflectionClass($namespace.'\\'.$classname);
	    $proxyClassname = $classname . self::PROXY_CLASS_SUFFIX;
	    if (!class_exists($namespace.'\\'.$proxyClassname)) {

	        $methods = $class->getMethods();
	        	
	        // We define the proxy class
	        eval('namespace '.$namespace.';'. "\n".self::getClassCode($proxyClassname, $classname, $methods));
	    }
	
	    $proxyClassname = $namespace.'\\'.$proxyClassname;
	    
	    return new $proxyClassname();
	}
	
    
	
    /**
     * @return bab_Controller
     */ 
	protected function proxy()
	{
	    $className = get_class($this);
	    if (false === strpos($className, '\\')) {
	        return self::getProxyInstance($className);
	    }
	    
	    $namespace = join('\\', array_slice(explode('\\', $className), 0, -1));
	    $className = join('', array_slice(explode('\\', $className), -1));
	    
		return self::getNsProxyInstance($namespace, $className);
	}
	
	
	
	/**
	 * Instanciates a controller class.
	 *
	 * @return bab_Controller
	 */
	public static function ControllerProxy($className, $proxy = true)
	{
		if ($proxy) {
			return self::getProxyInstance($className);
		}
	
		return new $className();
	}
	
	
	
	/**
	 * Instanciates a controller class.
	 * 
	 * @param  string  $namespace  Namespace of your controller class
	 * @param  string  $className  Class name without the namespace
	 * @param  bool    get the proxy instance insead
	 *
	 * @return bab_Controller
	 */
	public static function nsControllerProxy($namespace, $className, $proxy = true)
	{
	    if ($proxy) {
	        return self::getNsProxyInstance($namespace, $className);
	    }
	    
	    $className = $namespace.'\\'.$className;
	
	    return new $className();
	}
	


	/**
	 * Displays the list of available actions on this controller.
	 *
	 * @param Widget_Action $action
	 * @return mixed
	 */
	public function listAvailableActions()
	{
		$object = new ReflectionObject($this);
		$objectComment = $object->getDocComment();

		$methods = $object->getMethods();
		foreach ($methods as $method) {
			if (!$method->isPublic() || $method->isStatic() || $method->isFinal()) {
				continue;
			}
			echo "<pre>";
			$actionMethod = $object->getMethod($method->getName());
			echo "\t" . $actionMethod->getDocComment() . "\n";
			
			$methodName = $method->getName();
			$actionParams = array();
			$parameters = $actionMethod->getParameters();
			foreach ($parameters as $parameter) {
				if ($parameter->isDefaultValueAvailable()) {
					$actionParams[$parameter->getName()] = $parameter->getDefaultValue();
				} else {
					$actionParams[$parameter->getName()] = '';
				}
			}

			echo $methodName. "\n";
			print_r($actionParams);
			echo "</pre>";
		}
		die;

	}



	/**
	 * Tries to execute the method corresponding to $action
	 * on the current object.
	 *
	 * Called by bab_Controller::execute()
	 *
	 * @param Widget_Action 	$action
	 * @return mixed
	 */
	protected function execAction(Widget_Action $action)
	{
		$methodStr = $action->getMethod();

		list($objectName, $methodName) = explode('.', $methodStr);
		
		if (!method_exists($this, $methodName)) {
			header('HTTP/1.0 400 Bad Request');
			throw new bab_UnknownActionException($action);
		}
		
		$method = new ReflectionMethod($this, $methodName);
		$parameters = $method->getParameters();
		$args = array();
		foreach ($parameters as $parameter) {
			$parameterName = $parameter->getName();
			if ($action->parameterExists($parameter->getName())) {
				$args[$parameterName] = $action->getParameter($parameterName);
			} elseif ($parameter->isDefaultValueAvailable()) {
				$args[$parameterName] = $parameter->getDefaultValue();
			} else {
				throw new bab_MissingActionParameterException($action, $parameterName);
			}
		}

		return $method->invokeArgs($this, $args);
	}
	
	
	/**
	 * Get tg value to use in URL
	 * @return string
	 */
	abstract protected function getControllerTg();
	
	
	/**
	 * Get object name to use in URL from the controller classname
	 * @param string $classname        Does not include the namespace
	 * @return string
	 */
	protected function getObjectName($classname)
	{
	    return strtolower($classname);
	}
	


	/**
	 * Returns the action object corresponding to the current object method $methodName
	 * with the parameters $args.
	 *
	 * @param string $methodName
	 * @param array $args
	 * @return Widget_Action	Or null on error.
	 */
	protected function getMethodAction($methodName, $args)
	{
        $fullClassName = substr(get_class($this), 0, -strlen(self::PROXY_CLASS_SUFFIX));
	    $className = join('', array_slice(explode('\\', $fullClassName), -1));
		$classname = substr($className, 0, -strlen(self::PROXY_CLASS_SUFFIX));
		if (!method_exists($fullClassName, $methodName)) {
			throw new bab_InvalidActionException($fullClassName . '::' . $methodName);
		}
		$method = new ReflectionMethod($fullClassName, $methodName);

		$objectName = $this->getObjectName($className);
		$parameters = $method->getParameters();
		$actionParams = array();
		$argNumber = 0;
		foreach ($parameters as $parameter) {
			$parameterName = $parameter->getName();
			if (isset($args[$argNumber])) {
				$actionParams[$parameterName] = $args[$argNumber];
			} elseif ($parameter->isDefaultValueAvailable()) {
				$actionParams[$parameterName] = $parameter->getDefaultValue();
			} else {
				$actionParams[$parameterName] = null;
			}
			$argNumber++;
		}

		$action = new Widget_Action();

		$action->setMethod($this->getControllerTg(), $objectName . '.' . $methodName, $actionParams);

		$docComment = $method->getDocComment();
		if (strpos($docComment, '@ajax') !== false) {
			$action->setAjax(true);
		}

		return $action;
	}




	/**
	 * 
	 * @param string $result   'success' or 'failed'
	 * @param string $method
	 * @return Widget_Action|NULL
	 */
	protected static function getRedirectAction($result, $method)
	{
	    // Check if the redirect url has been specified in the request for this method / result.
	    if (isset($_REQUEST['_ctrl_' . $result][$method])) {
	        return Widget_Action::fromUrl($_REQUEST['_ctrl_' . $result][$method]);
	    }

	    // Or we use the referer url if available.
	    if (isset($_SERVER['HTTP_REFERER'])) {
            return Widget_Action::fromUrl($_SERVER['HTTP_REFERER']);
	    }

	    return null;
	}



	
	/**
	 * Adds an error message to display on the page.
	 * @param string $text
	 * @since 8.2.0
	 */
	public function addError($text)
	{
	    $babBody = bab_getBody();
	    $babBody->addError($text);
	}



	/**
	 * Adds an information message to display on the page.
	 * @param string $text
	 * @since 8.2.0
	 */
	public function addMessage($text)
	{
	    $babBody = bab_getBody();
	    $babBody->addMessage($text);
	}




	/**
	 * Performs an http redirection to the specified action.
	 * @param Widget_Action $action
	 * @since 8.2.0
	 */
	public function redirect(Widget_Action $action)
	{
	    $babBody = bab_getBody();
	    $errors = $babBody->errors;
	    $messages = $babBody->messages;

	    foreach ($errors as $error) {
	        $babBody->addNextPageError($error);
	    }
	    foreach ($messages as $message) {
	        $babBody->addNextPageMessage($message);
	    }

	    $action->location();
	}



	/**
	 * Tries to dispatch the action to the correct sub-controller.
	 *
	 * @param Widget_Action 	$action
	 * @return mixed
	 */
	public function execute(Widget_Action $action)
	{
		require_once dirname(__FILE__).'/urlincl.php';
		require_once dirname(__FILE__).'/json.php';
		
		$method = $action->getMethod();

		if (!isset($method) || '' === $method) {
			return false;
		}

	
		list($objectName, $methodName) = explode('.', $method);
		
		$objectController = $this->{$objectName}(false);

		if ( ! ($objectController instanceof bab_Controller)) {
			return false;
		}


		
		try {
			$returnedValue = $objectController->execAction($action);
		} catch (bab_AccessException $e) {
			
			if ($e->require_credential && !bab_isUserLogged())
			{
				bab_requireCredential($e->getMessage());
			} else {
				$this->addError($e->getMessage());
				$returnedValue = bab_Widgets()->babPage();
			}
			
		} catch (bab_SaveException $e) {
			
		    $failedAction = self::getRedirectAction('failed', $method);
		    if ($e instanceof bab_SaveErrorException) {
		        $this->addError($e->getMessage());
		    } else {
		        $this->addMessage($e->getMessage());
		    }
			if ($e->redirect) {
				if (!isset($failedAction)) {
					throw new Exception(sprintf('Missing the failed action to redirect from %s', $method));
				}
				
				$this->redirect($failedAction);
			} else {

			    if (0 == count($failedAction->getParameters())) {
					throw new Exception('Error, incorrect action');
				}
				$returnedValue = $objectController->execAction($failedAction);
			}
		}

		
		if ($returnedValue instanceof Widget_Displayable_Interface) {

			$W = bab_Widgets();
			if ($returnedValue instanceof Widget_BabPage) {
			    
				// If the action returned a page, we display it.
				$returnedValue->displayHtml();

			} else {

				$htmlCanvas = $W->HtmlCanvas();
				if (self::$acceptJson) {
					$itemId = $returnedValue->getId();
					$returnArray = array($itemId => $returnedValue->display($htmlCanvas));
					header('Cache-Control: no-cache, must-revalidate');
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
					header('Content-type: application/json');
					die(bab_json_encode($returnArray));
				} else {
					header('Cache-Control: no-cache, must-revalidate');
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
					header('Content-type: text/html');
					die($returnedValue->display($htmlCanvas));
				}
			}

		} else if (is_array($returnedValue)) {

			$htmlCanvas = $W->HtmlCanvas();
			$returnedArray = array();
			foreach ($returnedValue as $id => &$item) {
				if ($item instanceof Widget_Displayable_Interface) {
					$returnedArray[$item->getId()] = $item->display($htmlCanvas);
				}
			}
			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Content-type: application/json');
			die(bab_json_encode($returnArray));

		} else if (true === $returnedValue) {
			// If the method returns true, we redirect to the 'success' location defined in the button

		    $successAction = self::getRedirectAction('success', $method);
			if (!isset($successAction)) {
				throw new Exception(sprintf('Missing the success action to redirect from %s', $method));
			} 
		
			$this->redirect($successAction);
		}

		return $returnedValue;
	}

}