<?php
App::uses('AppShell', 'Console/Command');
App::uses('Translation', 'Translations.Model');

/**
 * ImportShell
 */
class ImportShell extends AppShell {

/**
 * _settings
 *
 * @var array
 */
	protected $_settings = array(
	);

/**
 * Gets the option parser instance and configures it.
 * By overriding this method you can configure the ConsoleOptionParser before returning it.
 *
 * @return ConsoleOptionParser
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::getOptionParser
 */
	public function getOptionParser() {

		$parser = parent::getOptionParser();
		return $parser
			->addOption('locale', array(
				'help' => 'the locale to import'
			))
			->addOption('domain', array(
				'help' => 'the domain to import, defaults to "default"'
			))
			->addOption('category', array(
				'help' => 'the category to import, defaults to "LC_MESSAGES"'
			))
			->addOption('overwrite', array(
				'help' => 'Overwrite existing translations - or just create new ones? defaults to false',
				'boolean' => true
			))
			->addOption('purge', array(
				'help' => 'Delete translations that are not in the import file? defaults to false',
				'boolean' => true
			));
	}

/**
 * import
 *
 * Load translations in a recognised format.
 *
 * If the option purge is set, existing translations that aren't in the import
 * are deleted. a single deleteAll query isn't used because.. the field key
 * doesn't get escaped correctly
 *
 */
	public function main() {
		foreach ($this->params as $key => $val) {
			$this->_settings[$key] = $val;
		}

		$this->Translation = ClassRegistry::init('Translations.Translation');

		$settings = $this->_settings;

		$files = $this->permutateFiles($this->args);
		foreach($files as $file) {
			$this->_settings = $settings;

			$this->out(sprintf('<info>Processing %s</info>', $file));
			$this->processFile($file);
		}
	}

/**
 * processFile
 *
 * @param string $file
 * @throws \Exception if the file specified doesn't exist
 */
	public function processFile($file) {
		$return = Translation::parse($file, array_filter($this->_settings));
		$this->_updateSettings($return['translations']);

		$this->out(sprintf('Found %d translations', $return['count']));
		$ids = Hash::extract($return['translations'], '{n}.key');
		$conditions = array(
			'locale' => $this->_settings['locale'],
			'domain' => $this->_settings['domain'],
			'category' => $this->_settings['category'],
		);
		$existing = $this->Translation->find('list', array(
			'conditions' => $conditions,
			'fields' => array('key', 'value'),
		));

		if (!empty($this->_settings['purge'])) {
			$this->_purge($existing, $ids, $conditions);
		}

		$preventNewTranslations = $this->_settings['locale'] !== Configure::read('Config.language');
		if (isset($return['translations'][0]['locale'])) {
			$preventNewTranslations = $return['translations'][0]['locale'] !== Configure::read('Config.language');
		}

		foreach ($return['translations'] as $translation) {
			if (
				$preventNewTranslations &&
				!Translation::hasTranslation($translation['key'], $translation)
			) {
				$this->out(sprintf('<warning>Skipping undefined translation:</warning> "%s"', $translation['key']));
				continue;
			}

			if (empty($this->_settings['overwrite'])) {
				if (array_key_exists($translation['key'], $existing)) {
					$this->out(sprintf('<info>Skipping existing translation:</info> "%s"', $translation['key']));
					continue;
				}
			}

			$this->out(sprintf('Processing "%s"', $translation['key']));
			Translation::update($translation['key'], $translation['value'], $translation);
		}
		$this->out('Done');
	}

/**
 * Purge
 *
 * Delete translation entries which do not exist in the import file
 */
	protected function _purge($existing, $importIds, $conditions) {
		foreach($existing as $id => $value) {
			if (in_array($id, $importIds)) {
				continue;
			}
			$this->out("Deleting translation $id");
			$this->Translation->deleteAll(array('key' => $id));
		}
	}

/**
 * Update settings
 *
 * the translations may not match the parameters specified in the cli arguments,
 * This is most relevant for po and pot files. So - update settings to match the results
 * So that subsequent checks are for the same locale, domain and category.
 */
	protected function _updateSettings($translations) {
		if (!$translations) {
			return;
		}
		$translation = current($translations);
		$this->_settings['locale'] = $translation['locale'];
		$this->_settings['domain'] = $translation['domain'];
		$this->_settings['category'] = $translation['category'];
	}

	public function permutateFiles($input) {
		$return = array();
		foreach($input as $file) {
			if (is_dir($file)) {
				App::uses('Folder', 'Utility');
				$dir = new Folder($file);
				$return = array_merge($return, $dir->findRecursive());
				continue;
			}
			$return[] = $file;
		}

		// Filter out LC_TIME files
		foreach($return as $i => $file) {
			if (substr($file, -7) === 'LC_TIME') {
				unset($return[$i]);
			}
		}

		return $return;
	}
}
