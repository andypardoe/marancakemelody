<?php
@session_start(); 



/**
 * Controller dell'applicazione.
 *
 * @author		Pereira Pulido Nuno Ricardo | Namaless | namaless@gmail.com
 * @copyright	Copyright 1981-2008, Namaless.
 * @link		http://www.namaless.com Namaless Blog
 * @version		1.0
 * @license		http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Caricamento della classe per il multi linguaggio.
 */
uses('L10n');

/**
 * @author Namaless
 **/
class AppController extends Controller
{
	/**
	 * Caricamento degli Helpers.
	 *
	 * @var mixed
	 */
	var $helpers = array('Ajax','Head','Html');
	//var $helpers = array('Html', 'Form', 'Javascript', 'Time', 'Text');
	
	/**
	 * Caricamento dei Componenti.
	 *
	 * @var mixed
	 */
	var $components = array('RequestHandler');
	var $cleandata = array();
	

	/**
	 * Esegue il metodo prima di filtrare i dati.
	 */
	function beforeFilter()
	{
		$this->L10n = new L10n();


		if(!empty($this->data)) {
			uses('sanitize');
			$sanitize = new Sanitize();
			$this->cleandata = $sanitize->clean($this->data);
		}


		if ( isset($this->params['lang']) )
		{
			$this->lang = $this->params['lang'];
			$this->L10n->get($this->lang);

/*			$router =& Router::getInstance();
			if ( ! ereg($this->lang, $router->__paths[0]["base"]) )
			{
				$router->__paths[0]["base"] = $router->__paths[0]["base"] . "/" . $this->lang;
			}	*/
		}
		else
		{
			$this->L10n->get();
			$this->lang = $this->L10n->locale;

/*			if ( strlen($this->lang) > 3 )
			{
				switch ( $this->lang )
				{
					case 'en_us':
						$this->lang = "eng";
					break;
				}
			}

			$router =& Router::getInstance();
			$router->__paths[0]["base"] = $router->__paths[0]["base"] . "/" . $this->lang;

			$this->redirect("/" . $this->lang . "/" . $this->params['url']['url']);		*/
		}
		
		// Imposto il titolo del sito.
		$this->pageTitle = __("Site Title - ", TRUE);
	}

	/**
	 * Esegue il metodo prima di renderizzare la template.
	 */
	function beforeRender()
	{
		$this->_persistValidation();
	}

	/**
	 * Called with some arguments (name of default model, or model from var $uses),
	 * models with invalid data will populate data and validation errors into the session.
	 *
	 * Called without arguments, it will try to load data and validation errors from session
	 * and attach them to proper models. Also merges $data to $this->data in controller.
	 *
	 * @author poLK
	 * @author drayen aka Alex McFadyen
	 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
	 */
	function _persistValidation()
	{
		$args = func_get_args();

		if ( empty($args) )
		{
			if ( $this->Session->check('Validation') )
			{
				$validation = $this->Session->read('Validation');
				$this->Session->del('Validation');

				foreach ( $validation AS $modelName => $sessData )
				{
					if ( $this->name != $sessData['controller'] )
					{
						if ( in_array($modelName, $this->modelNames) )
						{
							$Model =& $this->{$modelName};
						}
						else if ( ClassRegistry::isKeySet($modelName) )
						{
							$Model =& ClassRegistry::getObject($modelName);
						}
						else
						{
							continue;
						}

						$Model->data = $sessData['data'];
						$Model->validationErrors = $sessData['validationErrors'];
						$this->data = Set::merge($sessData['data'],$this->data);
					}
				}
			}
		}
		else
		{
			foreach ( $args AS $modelName )
			{
				if ( in_array($modelName, $this->modelNames ) AND ! empty($this->{$modelName}->validationErrors) )
				{
					$this->Session->write('Validation.'.$modelName, array(
														'controller'		=>	$this->name,
														'data' 				=> $this->{$modelName}->data,
														'validationErrors' 	=> $this->{$modelName}->validationErrors
					));
				}
			}
		}
	}
	
	

	

}