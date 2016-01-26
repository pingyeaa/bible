<?php
namespace ivanovyordan\migration;
use ComposerScriptEvent;

class Migration {
	public static function migrate($event) {
		$package = $event->getComposer()->getPackage();
		$requirements = $package->getRequires();

		$packageDir = dirname(__FILE__);
		$namespaceDir = dirname($packageDir);
		$vendorDir = dirname($namespaceDir);
		$projectDir = dirname($vendorDir);

		foreach($requirements as $name => $requirement) {
			$requirementDir = $vendorDir . '/' . $name;
			$migrationsDir = $requirementDir . '/migrations';

			if(is_dir($migrationsDir)) {
				print 'Migrating ' . $name . "\n";

				$migrationScript = $projectDir . '/yii migrate';
				$migrationOptions = ' --interactive=0';
				$migrationOptions .= ' --migrationPath=' . $migrationsDir;

				exec($migrationScript . $migrationOptions);
			}
		}

		print 'Migrations Finished';
		return true;
	}
}
