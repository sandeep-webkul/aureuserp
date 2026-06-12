<?php

use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->migrator->add('branding.primary_color', null);
        $this->migrator->add('branding.gray_color', null);
        $this->migrator->add('branding.danger_color', null);
        $this->migrator->add('branding.info_color', null);
        $this->migrator->add('branding.success_color', null);
        $this->migrator->add('branding.warning_color', null);
        $this->migrator->add('branding.light_logo', null);
        $this->migrator->add('branding.dark_logo', null);
        $this->migrator->add('branding.favicon', null);
        $this->migrator->add('branding.logo_height', null);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('settings', 'primary_color')) {
            $this->migrator->delete('branding.primary_color');
            $this->migrator->delete('branding.gray_color');
            $this->migrator->delete('branding.danger_color');
            $this->migrator->delete('branding.info_color');
            $this->migrator->delete('branding.success_color');
            $this->migrator->delete('branding.warning_color');
            $this->migrator->delete('branding.light_logo');
            $this->migrator->delete('branding.dark_logo');
            $this->migrator->delete('branding.favicon');
            $this->migrator->delete('branding.logo_height');
        }
    }
};
