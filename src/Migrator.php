<?php

namespace P4\MasterTheme;

use P4\MasterTheme\Migrations\M001EnableEnFormFeature;
use P4\MasterTheme\Migrations\M002EnableLazyYoutube;
use P4\MasterTheme\Migrations\M004UpdateMissingMediaPath;
use P4\MasterTheme\Migrations\M003UpdateArticlesBlockAttribute;
use P4\MasterTheme\Migrations\M005TurnBoxoutSettingIntoBlock;
use P4\MasterTheme\Migrations\M006MoveFeaturesToSeparateOption;
use P4\MasterTheme\Migrations\M007RemoveEnhancedDonateButtonOption;
use P4\MasterTheme\Migrations\M008RemoveArticlesDefaultOptions;
use P4\MasterTheme\Migrations\M009PopulateCookiesFields;
use P4\MasterTheme\Migrations\M010RemoveGdprPluginOptions;
use P4\MasterTheme\Migrations\M011RemoveSmartsheetOption;
use P4\MasterTheme\Migrations\M012RemoveThemeEditorOption;
use P4\MasterTheme\Migrations\M013RemoveDuplicatedOptions;
use P4\MasterTheme\Migrations\M014RemoveDropdownNavigationMenusOption;

/**
 * Run any new migration scripts and record results in the log.
 */
class Migrator
{
    /**
     * Run any new migration scripts and record results in the log.
     */
    public static function migrate(): void
    {

        // Fetch migration script ids that have run from WP option.
        $log = MigrationLog::from_wp_options();

        /**
         * @var MigrationScript[] $scripts
         */
        $scripts = [
            M001EnableEnFormFeature::class,
            M002EnableLazyYoutube::class,
            M004UpdateMissingMediaPath::class,
            M003UpdateArticlesBlockAttribute::class,
            M005TurnBoxoutSettingIntoBlock::class,
            M006MoveFeaturesToSeparateOption::class,
            M007RemoveEnhancedDonateButtonOption::class,
            M008RemoveArticlesDefaultOptions::class,
            M009PopulateCookiesFields::class,
            M010RemoveGdprPluginOptions::class,
            M011RemoveSmartsheetOption::class,
            M012RemoveThemeEditorOption::class,
            M013RemoveDuplicatedOptions::class,
            M014RemoveDropdownNavigationMenusOption::class,
        ];

        // Loop migrations and run those that haven't run yet.
        foreach ($scripts as $script) {
            if ($log->already_ran($script::get_id())) {
                continue;
            }

            $record = $script::run();
            $log->add($record);
        }

        $log->persist();
    }
}
