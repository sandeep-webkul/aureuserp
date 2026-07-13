import {  Page, expect } from '@playwright/test';
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
        for (let attempt = 0; attempt < 3; attempt++) {
            try {
                await this.page.goto('/admin/plugins');
                break;
            } catch (error) {
                if (!/ERR_ABORTED|interrupted by another navigation/.test((error as Error).message)) {
                    throw error;
                }
                await this.page.waitForTimeout(500);
            }
        }

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
        if (await this.searchPluginAndCheckInstalled(pluginName)) {
            return;
        }

        await this.erpLocators.pluginthreeDot.first().click();
        await this.erpLocators.pluginInstallButton.first().waitFor({ state: 'visible', timeout: 15000 });
        await this.erpLocators.pluginInstallButton.first().click();
        await this.erpLocators.pluginConfirmButton.first().waitFor({ state: 'visible', timeout: 15000 });
        await this.erpLocators.pluginConfirmButton.first().click();

        // Installing redirects back to the plugin list, which drops the search, so the card
        // has to be searched for again before its badge can be read.
        await this.page.waitForLoadState('networkidle').catch(() => undefined);
        expect(await this.searchPluginAndCheckInstalled(pluginName)).toBeTruthy();
    }

    /**
     * Search a single plugin card and report whether it is already installed. The state is
     * read from the card badge instead of the actions dropdown: opening that dropdown while
     * the search request is still in flight closes it again on the Livewire re-render, and
     * the run then hangs on /admin/plugins?search=... waiting for an install button that is
     * no longer on screen.
     */
    private async searchPluginAndCheckInstalled(pluginName: string): Promise<boolean> {
        await this.erpLocators.pluginSearchInput.waitFor({ state: 'visible', timeout: 15000 });
        await this.erpLocators.pluginSearchInput.fill(pluginName);
        await this.page.waitForLoadState('networkidle').catch(() => undefined);
        await expect(this.erpLocators.pluginCards).toHaveCount(1, { timeout: 20000 });

        const badges = await this.erpLocators.pluginCardBadges.allInnerTexts();

        return badges.some((badge) => badge.trim() === 'Installed');
    }
}
