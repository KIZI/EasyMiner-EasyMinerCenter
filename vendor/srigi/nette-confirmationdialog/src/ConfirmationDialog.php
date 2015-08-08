<?php

/**
 * Confirmation dialog with dynamic signals
 *
 * Copyright (c) 2009-2011 Lukáš Doležal @ GDMT (dolezal@gdmt.cz)
 *
 * This source file is subject to the "GNU Lesser General Public Licenee" (LGPL)
 *
 * @copyright  Copyright (c) 2009 Lukáš Doležal (dolezal@gdmt.cz)
 * @license    http://www.gnu.org/copyleft/lgpl.html  GNU Lesser General Public License
 * @link       http://nettephp.com/cs/extras/confirmation-dialog
 * @package    ConfirmationDialog
 */

use Nette\Application\UI\Form,
	Nette\Utils\Html;


class ConfirmationDialog extends Nette\Application\UI\Control
{

	/**
	 * @var array localization strings
	 */
	public static $_strings = array(
		'yes' => 'Yes',
		'no' => 'No',
		'expired' => 'Confirmation token has expired. Please try action again.',
	);

	/** @var Nette\Application\UI\Form */
	private $form;

	/** @var Nette\Utils\Html confirmation question */
	private $question;

	/** @var Nette\Http\Session */
	private $session;

	/** @var array storage of confirmation handlers */
	private $confirmationHandlers;

	/** @var bool */
	public $visible = FALSE;

	/** @var string */
	public $cssClass = 'confirmation-dialog';


	/**
	 * @param Nette\Http\SessionSection $session
	 * @param Nette\ComponentModel\IContainer|null $parent
	 * @param null $name
	 */
	public function __construct(\Nette\Http\SessionSection $session, $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);

		$this->session = $session;
		$this->question = Html::el('p')->addAttributes(array('class'=>"{$this->cssClass}--question"));

		$this->form = new Form($this, 'form');
		$this->form->getElementPrototype()->class = "{$this->cssClass}--form";
		$this->form->getRenderer()->wrappers['controls']['container'] = NULL;

		$this->form->addHidden('token');

		$this->form->addSubmit('yes', self::$_strings['yes'])
			->onClick[] = array($this, 'confirmClicked');

		$this->form->addSubmit('no', self::$_strings['no'])
			->onClick[] = array($this, 'cancelClicked');

		$this->form['yes']->getControlPrototype()->class = 'btn';
		$this->form['no']->getControlPrototype()->class = 'btn';
	}


	/**
	 * Overrides signal method formater. This provide "dynamicaly named signals"
	 * @param string $signal
	 * @return string
	 */
	public function formatSignalMethod($signal)
	{
		if (    stripos($signal, 'confirm') === 0
			&&  isset($this->confirmationHandlers[lcfirst(substr($signal, 7))])
		) {
			return '_handleShow';
		}

		parent::formatSignalMethod($signal);
	}


	/**
	 * Access to Yes or No form button controls.
	 * @param string $name Only 'yes' or 'no' is accepted
	 * @throws Nette\MemberAccessException
	 * @return Nette\Forms\Controls\SubmitButton
	 */
	public function getFormButton($name)
	{
		$name = (string)$name;
		if ($name !== 'yes' && $name !== 'no') {
			throw new Nette\MemberAccessException("Only 'yes' or 'no' is accepted in \$name. '$name' given.");
		}

		return $this->form[$name];
	}


	/**
	 * Return element prototype of nested Form
	 * @return Nette\Utils\Html
	 */
	public function getFormElementPrototype()
	{
		return $this->form->getElementPrototype();
	}


	/**
	 * Return question element protype
	 * @return Nette\Utils\Html
	 */
	public function getQuestionPrototype()
	{
		return $this->question;
	}


	/**
	 * Set question
	 * @param string $text
	 */
	public function setQuestionText($text)
	{
		$this->question->setText($text);
		$this->invalidateControl();
	}


	/**
	 * Generate unique token key
	 * @param string $name
	 * @return string
	 */
	protected function generateToken($name = '')
	{
		return base_convert(md5(uniqid('confirm' . $name, true)), 16, 36);
	}


	/************** configuration **************/

	/**
	 * Add confirmation handler to "dynamicaly named signals".
	 * @param string $name Confirmation/signal name
	 * @param callback|Nette\Callback $methodCallback Callback called when confirmation succeed
	 * @param callback|string $question Callback ($confirmForm, $params) or string containing question text.
	 * @throws Nette\InvalidArgumentException
	 * @return ConfirmationDialog
	 */
	public function addConfirmer($name, $methodCallback, $question)
	{
		if (!preg_match('/[A-Za-z_]+/', $name)) {
			throw new Nette\InvalidArgumentException("Confirmation name contain is invalid.");
		}
		if (isset($this->confirmationHandlers[$name])) {
			throw new Nette\InvalidArgumentException("Confirmation '$name' already exists.");
		}
		if (!is_callable($methodCallback)) {
			throw new Nette\InvalidArgumentException('$methodCallback must be callable.');
		}
		if (!is_callable($question) && !is_string($question)) {
			throw new Nette\InvalidArgumentException('$question must be callback or string.');
		}

		$this->confirmationHandlers[$name] = array(
			'handler' => $methodCallback,
			'question' => $question,
		);

		return $this;
	}

	/**
	 * Show dialog for confirmation
	 * @param string $confirmName
	 * @param array $params
	 * @throws Nette\InvalidArgumentException
	 * @throws Nette\InvalidStateException
	 */
	public function showConfirm($confirmName, $params = array())
	{
		if (!is_string($confirmName)) {
			throw new Nette\InvalidArgumentException('$confirmName must be string.');
		}
		if (!isset($this->confirmationHandlers[$confirmName])) {
			throw new Nette\InvalidStateException("confirmation '$confirmName' do not exist.");
		}
		if (!is_array($params)) {
			throw new Nette\InvalidArgumentException('$params must be array.');
		}

		$confirm = $this->confirmationHandlers[$confirmName];

		if (is_callable($confirm['question'])) {
			$question = call_user_func_array($confirm['question'], array($this, $params));
		} else {
			$question = $confirm['question'];
		}

		if ($question instanceof Html) {
			$this->question->setHtml($question);
		} else {
			$this->question->setText($question);
		}

		$token = $this->generateToken($confirmName);
		$this->form['token']->value = $token;
		$this->session->$token = array(
			'confirm' => $confirmName,
			'params' => $params,
		);

		$this->visible = TRUE;
		$this->invalidateControl();
	}


	/************** signals processing **************/

	/**
	 * Dynamicaly named signal receiver
	 */
	function _handleShow()
	{
		list(,$signal) = $this->presenter->getSignal();
		$confirmName = (substr($signal, 7));
		$confirmName{0} = strtolower($confirmName{0});
		$params = $this->getParameter();

		$this->showConfirm($confirmName, $params);
	}


	/**
	 * Confirm YES clicked
	 * @param Nette\Forms\Controls\SubmitButton $button
	 */
	public function confirmClicked($button)
	{
		$form = $button->getForm(TRUE);
		$values = $form->getValues();
		if (!isset($this->session->{$values['token']})) {
			if (self::$_strings['expired'] != '') {
				$this->presenter->flashMessage(self::$_strings['expired']);
			}
			$this->invalidateControl();
			return;
		}

		$action = $this->session->{$values['token']};
		unset($this->session->{$values['token']});

		$this->visible = FALSE;
		$this->invalidateControl();

		$callback = $this->confirmationHandlers[$action['confirm']]['handler'];

		$args = $action['params'];
		$args[] = $this;
		call_user_func_array($callback, $args);

		if (!$this->presenter->isAjax() && $this->visible == FALSE) {
			$this->presenter->redirect('this');
		}
	}


	/**
	 * Confirm NO clicked
	 * @param Nette\Forms\Controls\SubmitButton $button
	 */
	public function cancelClicked($button)
	{
		$form = $button->getForm(TRUE);
		$values = $form->getValues();
		if (isset($this->session->{$values['token']})) {
			unset($this->session->{$values['token']});
		}

		$this->visible = FALSE;
		$this->invalidateControl();
		$this->presenter->redirect('this');
	}


	/************** rendering **************/

	/**
	 * @return bool
	 */
	public function isVisible()
	{
		return $this->visible;
	}


	/**
	 * @param string|NULL
	 * @return Nette\Templating\ITemplate
	 */
	protected function createTemplate($class = NULL)
	{
		$template = parent::createTemplate();
		$template->setFile(__DIR__ . '/confirmationDialog.latte');

		return $template;
	}


	public function render()
	{
		if ($this->visible) {
			if ($this->form['token']->value === NULL) {
				throw new InvalidStateException('Token is not set!');
			}
		}

		$this->template->visible = $this->visible;
		$this->template->class = $this->cssClass;
		$this->template->question = $this->question;

		return $this->template->render();
	}

}
