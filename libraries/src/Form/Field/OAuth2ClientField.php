<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Form\Field;

use Joomla\OAuth2\Client;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The Field to load the form for OAuth2 client configuration inside current form
 *
 * @since  __DEPLOY_VERSION__
 */
class OAuth2ClientField extends SubformField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'OAuth2Client';

    /**
     * The callback url
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $callbackUrl;

    /**
     * Layout to render
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $layout;

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param   string  $name  The property name for which to get the value.
     *
     * @return  mixed  The property value or null.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __get($name)
    {
        switch ($name) {
            case 'callbackUrl':
                return $this->$name;
        }

        return parent::__get($name);
    }

    /**
     * Method to set certain otherwise inaccessible properties of the form field object.
     *
     * @param   string  $name   The property name for which to set the value.
     * @param   mixed   $value  The value of the property.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'callbackUrl':
                $this->$name = (string) $value;
                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Method to attach a Form object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value.
     *
     * @return  boolean  True on success.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        /**
         * When you have subforms which are not repeatable (i.e. a subform custom field with the
         * repeat attribute set to 0) you get an array here since the data comes from decoding the
         * JSON into an associative array, including the media subfield's data.
         *
         * However, this method expects an object or a string, not an array. Typecasting the array
         * to an object solves the data format discrepancy.
         */
        $value = \is_array($value) ? (object) $value : $value;

        /**
         * If the value is not a string, it is
         * most likely within a custom field of type subform
         * and the value is a stdClass with properties
         * imagefile and alt_text. So it is fine.
        */
        // @todo
        if (\is_string($value)) {
            json_decode($value);

            // Check if value is a valid JSON string.
            if ($value !== '' && json_last_error() !== JSON_ERROR_NONE) {
                /**
                 * If the value is not empty and is not a valid JSON string,
                 * it is most likely a custom field created in Joomla 3 and
                 * the value is a string that contains the file name.
                */
                if (is_file(JPATH_ROOT . '/' . $value)) {
                    $value = '{"imagefile":"' . $value . '","alt_text":""}';
                } else {
                    $value = '';
                }
            }
        } elseif (
            !\is_object($value)
            || !property_exists($value, 'imagefile')
            || !property_exists($value, 'alt_text')
        ) {
            return false;
        }

        if (!parent::setup($element, $value, $group)) {
            return false;
        }

        $this->callbackUrl   = (string) $this->element['callback_url'];

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<form
    name="oauth2client"
    label="JLIB_FORM_FIELD_PARAM_OAUTH2CLIENT_LABEL"
    >
    <fieldset
        name="oauth2clientconfig"
        label="JLIB_FORM_FIELD_PARAM_OAUTH2CLIENT_LABEL"
        >
        <field
            name="oauth_provider"
            type="list"
            label="JLIB_FORM_FIELD_PARAM_OAUTH_PROVIDER_LABEL"
            default="Google"
            filter="string"
            validate="options"
            >
            <option value="Google">Google</option>
        </field>

        <field
            name="oauth_tokenurl"
            type="text"
            label="JLIB_FORM_FIELD_PARAM_OAUTH_TOKEN_URL_LABEL"
            filter="string"
        />

        <field
            name="oauth_authurl"
            type="text"
            label="JLIB_FORM_FIELD_PARAM_OAUTH_AUTH_URL_LABEL"
            filter="string"
        />

        <field
            name="oauth_client_id"
            type="text"
            label="JLIB_FORM_FIELD_PARAM_OAUTH_CLIENT_ID_LABEL"
            filter="string"
        />

        <field
            name="oauth_client_secret"
            type="password"
            label="JLIB_FORM_FIELD_PARAM_OAUTH_CLIENT_SECRET_LABEL"
            filter="raw"
            lock="true"
        />

        <field
            name="noteCallbackUrl"
            type="note"
            description="JLIB_FORM_FIELD_PARAM_OAUTH_CALLBACK_LABEL"
            class="alert alert-info w-100 mt-2"
        />

    </fieldset>
</form>
XML;
        $this->formsource = $xml;

        $this->layout = 'joomla.form.field.subform.default';

        return true;
    }
}
