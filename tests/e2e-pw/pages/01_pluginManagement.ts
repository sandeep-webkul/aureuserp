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

            const checkInstalled = await this.openPluginActionsAndCheckInstalled(i);

            if (!checkInstalled) {
                await this.page.waitForLoadState('networkidle');
                const pluginTitle = await this.erpLocators.pluginName.nth(i).innerText();
                console.log(`Installing Plugin: ${pluginTitle}`);

                await this.erpLocators.pluginInstallButton.first().click({ timeout: 30000 });
                await this.page.waitForTimeout(3000); // Wait for 3 seconds to allow installation to complete
                await this.erpLocators.pluginConfirmButton.click();

                await this.waitForPluginActionToFinish();
                await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();

                continue;
            }

            // Installing redirects and rebuilds the list, but a plugin that was already
            // installed leaves its dropdown open over the next card's actions button.
            await this.page.keyboard.press('Escape');
            await this.page.waitForTimeout(500);
        }
    }

    /**
     * Uninstall all plugins
     */
    async uninstallAllPlugins() {
        const pluginCount = await this.erpLocators.pluginName.count();
        for (let i = 0; i < pluginCount; i++) {

            const checkInstalled = await this.openPluginActionsAndCheckInstalled(i);

            if (checkInstalled) {
                const pluginTitle = await this.erpLocators.pluginName.nth(i).innerText();
                console.log(`Uninstalling Plugin: ${pluginTitle}`);

                await this.page.waitForTimeout(2000);
                await this.erpLocators.pluginUninstallButton.first().click({ timeout: 30000 });
                await this.page.waitForTimeout(5000);
                await this.erpLocators.pluginConfirmButton.click();

                await this.waitForPluginActionToFinish();
                await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();

                continue;
            }

            await this.page.keyboard.press('Escape');
            await this.page.waitForTimeout(500);
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

        await this.waitForPluginActionToFinish();
        await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();
    }

    /**
     * Wait for the install/uninstall request itself, which runs artisan on the server and
     * takes tens of seconds. The success notification alone is not a safe signal: the
     * previous plugin's toast is still on screen, so it reads as done while this request is
     * still running and every action button is held disabled by wire:loading.
     */
    private async waitForPluginActionToFinish() {
        await this.page.waitForLoadState('networkidle', { timeout: 300000 });
        await expect(this.erpLocators.pluginthreeDot.first()).toBeEnabled({ timeout: 300000 });
    }

    /**
     * Open a plugin's actions dropdown and report whether it is already installed. The
     * dropdown holds a single action set, so the state is always read from its first
     * button: indexing those buttons by the card position never matches, which reads as
     * "not installed" and sends the caller looking for an install button that is not there.
     */
    private async openPluginActionsAndCheckInstalled(cardIndex = 0): Promise<boolean> {
        for (let attempt = 0; attempt < 3; attempt++) {
            await this.erpLocators.pluginthreeDot.nth(cardIndex).click({ timeout: 30000 });

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
