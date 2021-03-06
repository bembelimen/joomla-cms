<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\HTML\Helpers;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Log\Log;

/**
 * Utility class for Bootstrap elements.
 *
 * @since  3.0
 */
abstract class Bootstrap
{
	/**
	 * @var    array  Array containing information for loaded files
	 * @since  3.0
	 */
	protected static $loaded = [];

	/**
	 * @var    array  Array containing the available components
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $scripts = ['alert', 'button', 'carousel', 'collapse', 'dropdown', 'modal', 'popover', 'scrollspy', 'tab', 'toast', 'tooltip'];

	/**
	 * @var    array  Array containing the components loaded
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $loadedScripts = [];

	/**
	 * Add javascript support for Bootstrap alerts
	 *
	 * @param   string  $selector  Common class for the alerts
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 *
	 * @since   3.0
	 */
	public static function alert($selector = '.alert') :void
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'alert');

		$doc           = Factory::getDocument();
		$scriptOptions = $doc->getScriptOptions('bootstrap.alert');
		$options       = [$selector];

		if (is_array($scriptOptions))
		{
			$options = array_merge($scriptOptions, $options);
		}

		$doc->addScriptOptions('bootstrap.alert', $options, false);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap buttons
	 *
	 * @param   string  $selector  Common class for the buttons
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 *
	 * @since   3.1
	 */
	public static function button($selector = '.button') :void
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'button');

		$doc           = Factory::getDocument();
		$scriptOptions = $doc->getScriptOptions('bootstrap.button');
		$options       = [$selector];

		if (is_array($scriptOptions))
		{
			$options = array_merge($scriptOptions, $options);
		}

		$doc->addScriptOptions('bootstrap.button', $options, false);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap carousels
	 *
	 * @param   string  $selector  Common class for the carousels.
	 * @param   array   $params    An array of options for the carousel.
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 *
	 * @since   3.0
	 *
	 * Options for the carousel can be:
	 * - interval  number   5000   The amount of time to delay between automatically cycling an item.
	 *                             If false, carousel will not automatically cycle.
	 * - keyboard  boolean  true   Whether the carousel should react to keyboard events.
	 * - pause     string|  hover  Pauses the cycling of the carousel on mouseenter and resumes the cycling
	 *             boolean         of the carousel on mouseleave.
	 * - slide     string|  false  Autoplays the carousel after the user manually cycles the first item.
	 *             boolean         If "carousel", autoplays the carousel on load.
	 */
	public static function carousel($selector = '.carousel', $params = []) :void
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include Bootstrap Carousel
		HTMLHelper::_('bootstrap.loadComponent', 'carousel');

		// Setup options object
		$opt['interval'] = isset($params['interval']) ? (int) $params['interval'] : 5000;
		$opt['keyboard'] = isset($params['keyboard']) ? (bool) $params['keyboard'] : true;
		$opt['pause']    = isset($params['pause']) ? $params['pause'] : 'hover';
		$opt['slide']    = isset($params['slide']) ? (bool) $params['slide'] : false;
		$opt['wrap']     = isset($params['wrap']) ? (bool) $params['wrap'] : true;
		$opt['touch']    = isset($params['touch']) ? (bool) $params['touch'] : true;

		Factory::getDocument()->addScriptOptions('bootstrap.carousel', [$selector => (object) array_filter((array) $opt)]);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap collapse
	 *
	 * @param   string  $selector  Common class for the collapse
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * Options for the collapse can be:
	 * - parent    string   false  If parent is provided, then all collapsible elements under the specified parent will
	 *                             be closed when this collapsible item is shown.
	 * - toggle    boolean  true   Toggles the collapsible element on invocation
	 */
	public static function collapse($selector = '.collapse') :void
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'collapse');

		// Setup options object
		$opt['parent'] = isset($params['parent']) ? $params['parent'] : false;
		$opt['toggle'] = isset($params['toggle']) ? (bool) $params['toggle'] : true;

		Factory::getDocument()->addScriptOptions('bootstrap.collapse', [$selector => (object) array_filter((array) $opt)]);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap dropdowns
	 *
	 * @param   string  $selector  Common class for the dropdowns
	 * @param   string  $options   The options for the dropdowns
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * Options for the collapse can be:
	 * - flip       boolean  true          Allow Dropdown to flip in case of an overlapping on the reference element
	 * - boundary   string   scrollParent  Overflow constraint boundary of the dropdown menu
	 * - reference  string   toggle        Reference element of the dropdown menu. Accepts 'toggle' or 'parent'
	 * - display    string   dynamic       By default, we use Popper for dynamic positioning. Disable this with static
	 */
	public static function dropdown($selector = '.dropdown-toggle', $options = []) :void
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'dropdown');

		// Setup options object
		$opt['flip'] = isset($params['flip']) ? $params['flip'] : true;
		$opt['boundary'] = isset($params['boundary']) ? $params['boundary'] : 'scrollParent';
		$opt['reference'] = isset($params['reference']) ? $params['reference'] : 'toggle';
		$opt['display'] = isset($params['display']) ? $params['display'] : 'dynamic';
		$opt['popperConfig'] = isset($params['popperConfig']) ? (bool) $params['popperConfig'] : true;

		Factory::getDocument()->addScriptOptions('bootstrap.dropdown', [$selector => (object) array_filter((array) $opt)]);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Method to render a Bootstrap modal
	 *
	 * @param   string  $selector  The ID selector for the modal.
	 * @param   array   $options   An array of options for the modal.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * Options for the modal can be:
	 * - backdrop     string|  true  Includes a modal-backdrop element. Alternatively, specify static
	 *                boolean         for a backdrop which doesn't close the modal on click.
	 * - keyboard     boolean  true  Closes the modal when escape key is pressed
	 * - focus        boolean  true  Closes the modal when escape key is pressed
	 */
	public static function modal($selector = '.modal', $options = []) :void
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Setup options object
		$opt['backdrop'] = isset($options['backdrop']) ? (bool) $options['backdrop'] : true;
		$opt['keyboard'] = isset($options['keyboard']) ? (bool) $options['keyboard'] : true;
		$opt['focus']    = isset($options['focus']) ? (bool) $options['focus'] : true;

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'modal');

		Factory::getDocument()->addScriptOptions('bootstrap.modal', [$selector => (object) array_filter((array) $opt)]);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap popovers
	 *
	 * Use element's Title as popover content
	 *
	 * @param   string  $selector  Selector for the popover
	 * @param   array   $options   The options for the popover
	 *
	 * @return  void
	 *
	 * @since   3.0
	 *
	 * - Options for the popover can be:
	 * - animation    boolean  true   Apply a CSS fade transition to the popover
	 * - container    string|  false  Appends the popover to a specific element. Eg.: 'body'
	 *                boolean
	 * - content      string   null   Default content value if data-bs-content attribute isn't present
	 * - delay        number   0      Delay showing and hiding the popover (ms)
	 *                                 does not apply to manual trigger type
	 * - html         boolean  true   Insert HTML into the popover. If false, innerText property will be used
	 *                                 to insert content into the DOM.
	 * - placement    string   right  How to position the popover - auto | top | bottom | left | right.
	 *                                 When auto is specified, it will dynamically reorient the popover
	 * - selector     string   false  If a selector is provided, popover objects will be delegated to the
	 *                                 specified targets.
	 * - template     string   null   Base HTML to use when creating the popover.
	 * - title        string   null   Default title value if `title` tag isn't present
	 * - trigger      string   click  How popover is triggered - click | hover | focus | manual
	 * - offset       integer  0      Offset of the popover relative to its target.
	 */
	public static function popover($selector = '[data-bs-toggle="popover"]', $options = []) :void
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Setup options object
		$opt['animation']         = isset($options['animation']) ? (bool) $options['animation'] : true;
		$opt['container']         = isset($options['container']) ? $options['container'] : 'body';
		$opt['content']           = isset($options['content']) ? $options['content'] : null;
		$opt['delay']             = isset($options['delay']) ? (int) $options['delay'] : 0;
		$opt['html']              = isset($options['html']) ? (bool) $options['html'] : true;
		$opt['placement']         = isset($options['placement']) ? $options['placement'] : null;
		$opt['selector']          = isset($options['selector']) ? $options['selector'] : false;
		$opt['template']          = isset($options['template']) ? $options['template'] : null;
		$opt['title']             = isset($options['title']) ? $options['title'] : null;
		$opt['trigger']           = isset($options['trigger']) ? $options['trigger'] : 'click';
		$opt['fallbackPlacement'] = isset($options['fallbackPlacement']) ? $options['fallbackPlacement'] : null;
		$opt['boundary']          = isset($options['boundary']) ? $options['boundary'] : 'scrollParent';
		$opt['customClass']       = isset($options['customClass']) ? $options['customClass'] : null;
		$opt['sanitize']          = isset($options['sanitize']) ? (bool) $options['sanitize'] : null;
		$opt['allowList']         = isset($options['allowList']) ? $options['allowList'] : null;

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'popover');

		Factory::getDocument()->addScriptOptions('bootstrap.popover', [$selector => (object) array_filter((array) $opt)]);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap Scrollspy
	 *
	 * @param   string  $selector  The ID selector for the ScrollSpy element.
	 * @param   array   $options   An array of options for the ScrollSpy.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 *
	 * Options for the Scrollspy can be:
	 * - offset  number  Pixels to offset from top when calculating position of scroll.
	 * - method  string  Finds which section the spied element is in.
	 * - target  string  Specifies element to apply Scrollspy plugin.
	 */
	public static function scrollspy($selector = '[data-bs-spy="scroll"]', $options = []) :void
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Setup options object
		$opt['offset']         = isset($options['offset']) ? (int) $options['offset'] : 10;
		$opt['method']         = isset($options['method']) ? $options['method'] : 'auto';
		$opt['target']           = isset($options['target']) ? $options['target'] : null;

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'scrollspy');

		Factory::getDocument()->addScriptOptions('bootstrap.scrollspy', [$selector => (object) array_filter((array) $opt)]);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap tab
	 *
	 * @param   string  $selector  Common class for the tabs
	 * @param   array   $options   Options for the tabs
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function tab($selector = '.myTab', $options = []) :void
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include the Bootstrap component Tab
		HTMLHelper::_('bootstrap.loadComponent', 'tab');

		Factory::getDocument()->addScriptOptions('bootstrap.tabs', [$selector => (object) $options]);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap tooltips
	 *
	 * Add a title attribute to any element in the form
	 * title="title::text"
	 *
	 * @param   string  $selector  The ID selector for the tooltip.
	 * @param   array   $options   An array of options for the tooltip.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 *
	 *                             Options for the tooltip can be:
	 * - animation    boolean          apply a css fade transition to the popover
	 * - container    string|boolean   Appends the popover to a specific element: { container: 'body' }
	 * - delay        number|object    delay showing and hiding the popover (ms) - does not apply to manual trigger type
	 *                                                              If a number is supplied, delay is applied to both hide/show
	 *                                                              Object structure is: delay: { show: 500, hide: 100 }
	 * - html         boolean          Insert HTML into the popover. If false, jQuery's text method will be used to
	 *                                 insert content into the dom.
	 * - placement    string|function  how to position the popover - top | bottom | left | right
	 * - selector     string           If a selector is provided, popover objects will be
	 *                                                              delegated to the specified targets.
	 * - template     string           Base HTML to use when creating the popover.
	 * - title        string|function  default title value if `title` tag isn't present
	 * - trigger      string           how popover is triggered - hover | focus | manual
	 * - constraints  array            An array of constraints - passed through to Popper.
	 * - offset       string           Offset of the popover relative to its target.
	 */
	public static function tooltip($selector = '[data-bs-toggle=tooltip]', $options = []) :void
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'tooltip');

		// Setup options object
		$opt['animation']         = isset($options['animation']) ? (bool) $options['animation'] : true;
		$opt['container']         = isset($options['container']) ? $options['container'] : 'body';
		$opt['delay']             = isset($options['delay']) ? (int) $options['delay'] : 0;
		$opt['html']              = isset($options['html']) ? (bool) $options['html'] : true;
		$opt['placement']         = isset($options['placement']) ? $options['placement'] : null;
		$opt['selector']          = isset($options['selector']) ? $options['selector'] : false;
		$opt['template']          = isset($options['template']) ? $options['template'] : null;
		$opt['title']             = isset($options['title']) ? $options['title'] : null;
		$opt['trigger']           = isset($options['trigger']) ? $options['trigger'] : 'hover focus';
		$opt['fallbackPlacement'] = isset($options['fallbackPlacement']) ? $options['fallbackPlacement'] : null;
		$opt['boundary']          = isset($options['boundary']) ? $options['boundary'] : 'clippingParents';
		$opt['customClass']       = isset($options['customClass']) ? $options['customClass'] : null;
		$opt['sanitize']          = isset($options['sanitize']) ? (bool) $options['sanitize'] : true;
		$opt['allowList']         = isset($options['allowList']) ? $options['allowList'] : null;

		Factory::getDocument()->addScriptOptions('bootstrap.tooltip', [$selector => (object) array_filter((array) $opt)]);

		// Set static array
		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add javascript support for Bootstrap toasts
	 *
	 * @param   string  $selector  Common class for the toasts
	 * @param   array   $options   Options for the toasts
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function toast($selector = '.toast', $options = []) :void
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'toast');

		// Setup options object
		$opt['animation'] = isset($options['animation']) ? (string) $options['animation'] : null;
		$opt['autohide']  = isset($options['autohide']) ? (boolean) $options['autohide'] : true;
		$opt['delay']     = isset($options['delay']) ? (int) $options['delay'] : 5000;

		Factory::getDocument()->addScriptOptions('bootstrap.toast', [$selector => (object) array_filter((array) $opt)]);

		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Method to load the static assets for a given component
	 *
	 * @param   string $script The component name
	 *
	 * @throws \Exception
	 *
	 * @return  void
	 */
	public static function loadComponent(string $script) :void
	{
		if (!in_array($script, static::$loadedScripts)
			&& in_array($script, static::$scripts))
		{
			// Tooltip + popover are combined
			$script = $script === 'tooltip' ? 'popover' : $script;

			// Register the ES2017+ script with an attribute type="module"
			Factory::getApplication()
				->getDocument()
				->getWebAssetManager()
				->registerScript(
					'bootstrap.' . $script . '.ES6',
					'vendor/bootstrap/' . $script . '.es6.min.js',
					[
						'dependencies' => [],
						'attributes' => [
							'type' => 'module',
						]
					]
				)
				->useScript('bootstrap.' . $script . '.ES6')

				// Register the ES5 script with attributes: nomodule, defer
				->registerScript(
					'bootstrap.legacy',
					'vendor/bootstrap/bootstrap.es5.min.js',
					[
						'dependencies' => [],
						'attributes' => [
							'nomodule' => '',
							'defer' => 'defer',
						]
					]
				)
				->useScript('bootstrap.legacy');

			array_push(static::$loadedScripts, $script);
		}
	}

	/**
	 * Method to load the ALL the Bootstrap Components
	 *
	 * If debugging mode is on an uncompressed version of Bootstrap is included for easier debugging.
	 *
	 * @param   mixed  $debug  Is debugging mode on? [optional]
	 *
	 * @return  void
	 *
	 * @since   3.0
	 * @deprecated 5.0
	 */
	public static function framework($debug = null) :void
	{
		array_map(
			function ($script) {
				HTMLHelper::_('bootstrap.' . $script);
			},
			static::$scripts
		);
	}

	/**
	 * Loads CSS files needed by Bootstrap
	 *
	 * @param   boolean  $includeMainCss  If true, main bootstrap.css files are loaded
	 * @param   string   $direction       rtl or ltr direction. If empty, ltr is assumed
	 * @param   array    $attribs         Optional array of attributes to be passed to HTMLHelper::_('stylesheet')
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function loadCss($includeMainCss = true, $direction = 'ltr', $attribs = []) :void
	{
		// Load Bootstrap main CSS
		if ($includeMainCss)
		{
			Factory::getDocument()->getWebAssetManager()->useStyle('bootstrap.css');
		}
	}

	/**
	 * Add javascript support for Bootstrap accordions and insert the accordion
	 *
	 * @param   string  $selector  The ID selector for the tooltip.
	 * @param   array   $options   An array of options for the tooltip.
	 *
	 * @return  string  HTML for the accordion
	 *
	 * @since   3.0
	 *
	 *                             Options for the tooltip can be:
	 *                             - parent  selector  If selector then all collapsible elements under the specified parent will be closed when this
	 *                                                 collapsible item is shown. (similar to traditional accordion behavior)
	 *                             - toggle  boolean   Toggles the collapsible element on invocation
	 *                             - active  string    Sets the active slide during load
	 */
	public static function startAccordion($selector = '.myAccordian', $options = []) :string
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__][$selector]))
		{
			return '';
		}

		// Include Bootstrap component
		HTMLHelper::_('bootstrap.loadComponent', 'collapse');

		// Setup options object
		$opt['parent'] = isset($options['parent']) ?
			($options['parent'] == true ? '#' . preg_replace('/^\.?#/', '', $selector) : $options['parent']) : '';
		$opt['toggle'] = isset($options['toggle']) ? (boolean) $options['toggle'] : !($opt['parent'] === false || isset($options['active']));
		$opt['active'] = isset($options['active']) ? (string) $options['active'] : '';

		// Initialise with the Joomla specifics
		$opt['isJoomla'] = true;

		Factory::getDocument()->addScriptOptions('bootstrap.accordion', [$selector => (object) array_filter((array) $opt)]);

		static::$loaded[__METHOD__][$selector] = $opt;

		return '<div id="' . $selector . '" class="accordion" role="tablist">';
	}

	/**
	 * Close the current accordion
	 *
	 * @return  string  HTML to close the accordion
	 *
	 * @since   3.0
	 */
	public static function endAccordion() :string
	{
		return '</div>';
	}

	/**
	 * Begins the display of a new accordion slide.
	 *
	 * @param   string  $selector  Identifier of the accordion group.
	 * @param   string  $text      Text to display.
	 * @param   string  $id        Identifier of the slide.
	 * @param   string  $class     Class of the accordion group.
	 *
	 * @return  string  HTML to add the slide
	 *
	 * @since   3.0
	 */
	public static function addSlide($selector, $text, $id, $class = '') :string
	{
		$in        = static::$loaded[__CLASS__ . '::startAccordion'][$selector]['active'] === $id ? ' show' : '';
		$collapsed = static::$loaded[__CLASS__ . '::startAccordion'][$selector]['active'] === $id ? '' : ' collapsed';
		$parent    = static::$loaded[__CLASS__ . '::startAccordion'][$selector]['parent'] ?
			'data-bs-parent="' . static::$loaded[__CLASS__ . '::startAccordion'][$selector]['parent'] . '"' : '';
		$class     = (!empty($class)) ? ' ' . $class : '';
		$ariaExpanded = $in === 'show' ? true : false;

		return <<<HTMLSTR
<div class="accordion-item $class">
  <h2 class="accordion-header" id="$id-heading">
    <button class="accordion-button $collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#$id" aria-expanded="$ariaExpanded" aria-controls="$id" role="tab">
		$text
    </button>
  </h2>
  <div id="$id" class="accordion-collapse collapse $in" aria-labelledby="$id-heading" $parent role="tabpanel">
    <div class="accordion-body">
HTMLSTR;

	}

	/**
	 * Close the current slide
	 *
	 * @return  string  HTML to close the slide
	 *
	 * @since   3.0
	 */
	public static function endSlide() :string
	{
		return <<<HTMLSTR
		</div>
	</div>
</div>
HTMLSTR;
	}

	/**
	 * Method to render a Bootstrap modal
	 *
	 * @param   string  $selector  The ID selector for the modal.
	 * @param   array   $options   An array of options for the modal.
	 * @param   string  $body      Markup for the modal body. Appended after the `<iframe>` if the URL option is set
	 *
	 * @return  string  HTML markup for a modal
	 *
	 * @since   3.0
	 *
	 * Options for the modal can be:
	 * - backdrop     string|  true   Includes a modal-backdrop element. Alternatively, specify static
	 *                boolean          for a backdrop which doesn't close the modal on click.
	 * - keyboard     boolean  true   Closes the modal when escape key is pressed
	 * - focus        boolean  true   Closes the modal when escape key is pressed
	 * - title        string   null   The modal title
	 * - closeButton  boolean  true   Display modal close button (default = true)
	 * - footer       string   null   Optional markup for the modal footer
	 * - url          string   null   URL of a resource to be inserted as an `<iframe>` inside the modal body
	 * - height       string   null   Height of the `<iframe>` containing the remote resource
	 * - width        string   null   Width of the `<iframe>` containing the remote resource
	 */
	public static function renderModal($selector = '.modal', $options = [], $body = '') :string
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__][$selector]))
		{
			return '';
		}

		// Initialise with the Joomla specifics
		$options['isJoomla'] = true;

		// Include Basic Bootstrap component
		HTMLHelper::_('bootstrap.modal', '#' . preg_replace('/^\.?#/', '', $selector), $options);

		$layoutData = [
			'selector' => $selector,
			'params'   => $options,
			'body'     => $body,
		];

		static::$loaded[__METHOD__][$selector] = true;

		return LayoutHelper::render('libraries.html.bootstrap.modal.main', $layoutData);
	}


	/**
	 * Creates a tab pane
	 *
	 * @param   string  $selector  The pane identifier.
	 * @param   array   $params    The parameters for the pane
	 *
	 * @return  string
	 *
	 * @since   3.1
	 */
	public static function startTabSet($selector = '.myTab', $params = []) :string
	{
		$sig = md5(serialize([$selector, $params]));

		if (!isset(static::$loaded[__METHOD__][$sig]))
		{
			// Setup options object
			$opt['active'] = (isset($params['active']) && ($params['active'])) ? (string) $params['active'] : '';

			// Initialise with the Joomla specifics
			$opt['isJoomla'] = true;

			// Include the Bootstrap Tab Component
			HTMLHelper::_('bootstrap.tab', $selector, $opt);

			// Set static array
			static::$loaded[__METHOD__][$sig] = true;
			static::$loaded[__METHOD__][$selector]['active'] = $opt['active'];

			return LayoutHelper::render('libraries.html.bootstrap.tab.starttabset', ['selector' => $selector]);
		}
	}

	/**
	 * Close the current tab pane
	 *
	 * @return  string  HTML to close the pane
	 *
	 * @since   3.1
	 */
	public static function endTabSet() :string
	{
		return LayoutHelper::render('libraries.html.bootstrap.tab.endtabset');
	}

	/**
	 * Begins the display of a new tab content panel.
	 *
	 * @param   string  $selector  Identifier of the panel.
	 * @param   string  $id        The ID of the div element
	 * @param   string  $title     The title text for the new UL tab
	 *
	 * @return  string  HTML to start a new panel
	 *
	 * @since   3.1
	 */
	public static function addTab($selector, $id, $title) :string
	{
		static $tabLayout = null;

		$tabLayout = $tabLayout === null ? new FileLayout('libraries.html.bootstrap.tab.addtab') : $tabLayout;
		$active = (static::$loaded[__CLASS__ . '::startTabSet'][$selector]['active'] == $id) ? ' active' : '';

		return $tabLayout->render(['id' => str_replace('.', '', $id), 'active' => $active, 'title' => $title]);
	}

	/**
	 * Close the current tab content panel
	 *
	 * @return  string  HTML to close the pane
	 *
	 * @since   3.1
	 */
	public static function endTab() :string
	{
		return LayoutHelper::render('libraries.html.bootstrap.tab.endtab');
	}
}
