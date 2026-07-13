import { Page, expect } from '@playwright/test';
import { ErpLocators } from '../locator/erp_locator';

export class PluginManagementPage {

    /**
     * Page and Locators
     */
    readonly page: Page;
    readonly erpLocators: ErpLocators;

    constructor(page: Page) {
        this.page = page

        this.erpLocators = new ErpLocators(page);
    }

    /**
     * Navigate to Plugin Management Page
     */
    async gotoPluginManagementPage() {
        await this.page.goto('/admin/plugins');
        await expect(this.page).toHaveURL(/.*admin/);
        await expect(this.erpLocators.pluginSyncButton).toBeVisible();
    }

    /**
     * Install all plugins
     */
    async installAllPlugins() {
        const pluginCount = await this.erpLocators.pluginName.count();
        for (let i = 0; i < pluginCount; i++) {

            await this.erpLocators.pluginthreeDot.nth(i).click();
            const checkInstalled = await this.erpLocators.pluginUninstallButton.nth(i).isVisible();

            if (!checkInstalled) {
                await this.page.waitForLoadState('networkidle');
                await this.erpLocators.pluginInstallButton.nth(0).click();
                await this.page.waitForTimeout(3000); // Wait for 3 seconds to allow installation to complete
                await this.erpLocators.pluginConfirmButton.click();
                const pluginTitle = await this.erpLocators.pluginName.nth(i).innerText();
                console.log(`Installing Plugin: ${pluginTitle}`);
                await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();
            }
        }
    }

    /**
     * Uninstall all plugins
     */
    async uninstallAllPlugins() {
        const pluginCount = await this.erpLocators.pluginName.count();
        for (let i = 0; i < pluginCount; i++) {

            await this.erpLocators.pluginthreeDot.nth(i).click();
            const checkInstalled = await this.erpLocators.pluginUninstallButton.nth(0).isVisible();

            if (checkInstalled) {
                await this.page.waitForLoadState('networkidle');
                await this.page.waitForTimeout(2000);
                await this.erpLocators.pluginUninstallButton.nth(0).click();
                await this.page.waitForTimeout(5000);
                await this.erpLocators.pluginConfirmButton.click();
                const pluginTitle = await this.erpLocators.pluginName.nth(i).innerText();
                console.log(`Uninstalling Plugin: ${pluginTitle}`);
                await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();
            }
        }
    }

    /**
     * Install plugin by name if not installed
     */
    async installPluginByName(pluginName: string) {
        await this.erpLocators.pluginSearchInput.fill(pluginName);

        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(500);

        if (await this.openPluginActionsAndCheckInstalled()) {
            return;
        }

        await this.page.waitForLoadState('networkidle');
        await this.erpLocators.pluginInstallButton.first().click({ timeout: 30000 });
        await this.page.waitForTimeout(3000);
        await this.erpLocators.pluginConfirmButton.click();
        await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();
    }

    /**
     * Open the actions dropdown and report whether the plugin is already installed.
     */
    private async openPluginActionsAndCheckInstalled(): Promise<boolean> {
        for (let attempt = 0; attempt < 3; attempt++) {
            await this.erpLocators.pluginthreeDot.first().click({ timeout: 30000 });

            const uninstall = this.erpLocators.pluginUninstallButton.first();
            const install = this.erpLocators.pluginInstallButton.first();

            const opened = await Promise.race([
                uninstall.waitFor({ state: 'visible', timeout: 5000 }).then(() => 'installed').catch(() => null),
                install.waitFor({ state: 'visible', timeout: 5000 }).then(() => 'not-installed').catch(() => null),
            ]);

            if (opened === 'installed') {
                return true;
            }

            if (opened === 'not-installed') {
                return false;
            }

            await this.page.keyboard.press('Escape');
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1000);
        }

        return false;
    }
}
