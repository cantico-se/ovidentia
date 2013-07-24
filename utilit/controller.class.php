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
	 * Dynamically creates a proxy class for this controller with all public, non-final and non-static functions
	 * overriden so that they return an action (Widget_Action) corresponding to each of them.
	 *
	 * @param string $classname
	 * @return bab_Controller
	 */
	static function getProxyInstance($classname)
	{
		$class = new ReflectionClass($classname);
		$proxyClassname = $class->name . self::PROXY_CLASS_SUFFIX;
		if (!class_exists($proxyClassname)) {
			$classStr = 'class ' . $proxyClassname . ' extends ' . $class->name . ' {' . "\n";
			$methods = $class->getMethods();

			foreach ($methods as $method) {
				if ($method->name === '__construct' || !$method->isPublic() || $method->isStatic() || $method->isFinal() || $method->name === 'setCrm' || $method->name === 'Crm') {
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

			// We define the proxy class
			eval($classStr);
		}

		return new $proxyClassname();
	}


	protected function proxy()
	{
		return self::getProxyInstance(get_class($this));
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
	 * @param string			$previous 		UID of previous page
	 * @return mixed
	 */
	protected function execAction(Widget_Action $action, $previous = null)
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
	 * Get object name to use in URL from the controller classname
	 * @param string $classname
	 * @return string
	 */
	abstract protected function getObjectName($classname);
	


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
		$classname = substr(get_class($this), 0, -strlen(self::PROXY_CLASS_SUFFIX));
		if (!method_exists($classname, $methodName)) {
			throw new bab_InvalidActionException($classname . '::' . $action->getMethod());
		}
		$method = new ReflectionMethod($classname, $methodName);
		

		$objectName = $this->getObjectName($method->class);
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

		$action->setMethod($this->Crm()->controllerTg, $objectName . '.' . $methodName, $actionParams);

		$docComment = $method->getDocComment();
		if (strpos($docComment, '@ajax') !== false) {
			$action->setAjax(true);
		}

		return $action;
	}


	/**
	 * Tries to dispatch the action to the correct sub-controller.
	 *
	 * @param Widget_Action 	$action
	 * @return mixed
	 */
	public function execute(Widget_Action $action)
	{
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

		$previous 		= null;
		$successAction 	= null;
		$failedAction 	= null;

		if (isset($_REQUEST['_ctrl_previous']))
		{
			$previous = $_REQUEST['_ctrl_previous'];
		}

		if (isset($_REQUEST['_ctrl_success'][$method]))
		{
			$successAction = Widget_Action::fromUrl($_REQUEST['_ctrl_success'][$method]);
		}

		if (isset($_REQUEST['_ctrl_failed'][$method]))
		{
			$failedAction = Widget_Action::fromUrl($_REQUEST['_ctrl_failed'][$method]);
		}


		
		$returnedValue = $objectController->execAction($action, $previous);
		
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
			// a save action return true, goto defaut location defined in the button
			
			if (!isset($successAction))
			{
				throw new Exception(sprintf('Missing the success action to redirect from %s', $method));
			} 
			
			$url = new bab_url($successAction->url());
			$url->location();
		}

		return $returnedValue;
	}



}