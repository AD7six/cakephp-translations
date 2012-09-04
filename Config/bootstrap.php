<?php
/**
 * The default language should always be defined
 *
 * If it's not defined, it is assumed to be 'en'
 */
if (!Configure::read('Config.language')) {
	Configure::write('Config.language', 'en');
}
App::uses('Translation', 'Translations.Model');

/**
 * Translation helper function.
 *
 * @param string $text   Text to translate
 * @param array  $params (optional) Parameters to use when replacing translations
 */
function t($text, $params = array()) {
	$text = Translation::translate($text);
	$text = String::insert($text, $params, array(
		'before' => '{',
		'after'  => '}'
	));
	return $text;
}

// Application configuration for the Translations module
Configure::write('ApplicationConfigurationExtras.translations', array(
	'element'   => 'Translations.application_configuration',
	'modules'   => array('Translations'),
	'global'    => true
));
