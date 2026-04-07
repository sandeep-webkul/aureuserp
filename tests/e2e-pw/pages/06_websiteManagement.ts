import { type Locator, type Page, expect } from "@playwright/test";
import { ErpLocators } from "../locator/erp_locator";
import { PluginManagementPage } from "./01_pluginManagement";

export type WebsitePageData = {
    title: string;
    content: string;
    metaTitle?: string;
    metaKeywords?: string;
    metaDescription?: string;
    isHeaderVisible?: boolean;
    isFooterVisible?: boolean;
};

export class WebsiteManagementPage {
    readonly page: Page;
    readonly erpLocators: ErpLocators;
    readonly pluginPage: PluginManagementPage;

    constructor(page: Page) {
        this.page = page;
        this.erpLocators = new ErpLocators(page);
        this.pluginPage = new PluginManagementPage(page);
    }

    async ensureWebsitePluginInstalled(): Promise<void> {
        await this.pluginPage.gotoPluginManagementPage();
        await this.pluginPage.installPluginByName("Website");
    }

    async gotoWebsitePagesPage(): Promise<void> {
        await this.page.goto("/admin/website/pages");
        await expect(this.page).toHaveURL(/website\/pages/);
        await expect(this.erpLocators.websitePagesHeading).toBeVisible();
        await expect(this.erpLocators.websitePagesTable).toBeVisible();
    }

    async createWebsitePage(pageData: WebsitePageData): Promise<void> {
        await this.gotoWebsitePagesPage();
        await this.erpLocators.websitePagesCreateButton.click();
        await expect(this.page).toHaveURL(/website\/pages\/create/);

        await this.erpLocators.websitePagesTitleInput.fill(pageData.title);
        await this.fillContent(pageData.content);

        if (pageData.metaTitle) {
            await this.erpLocators.websitePagesMetaTitleInput.fill(pageData.metaTitle);
        }
        if (pageData.metaKeywords) {
            await this.erpLocators.websitePagesMetaKeywordsInput.fill(pageData.metaKeywords);
        }
        if (pageData.metaDescription) {
            await this.erpLocators.websitePagesMetaDescriptionInput.fill(pageData.metaDescription);
        }

        if (typeof pageData.isHeaderVisible === "boolean") {
            await this.toggleSwitch(this.erpLocators.websitePagesHeaderVisibleToggle, pageData.isHeaderVisible);
        }
        if (typeof pageData.isFooterVisible === "boolean") {
            await this.toggleSwitch(this.erpLocators.websitePagesFooterVisibleToggle, pageData.isFooterVisible);
        }

        await this.erpLocators.websitePagesSaveButton.click();
        await this.expectSuccessToast();
    }

    async editWebsitePage(originalTitle: string, updates: Partial<WebsitePageData>): Promise<void> {
        await this.gotoWebsitePagesPage();
        await this.searchPage(originalTitle);
        await this.openRowActions();
        await this.erpLocators.websitePagesEditButton.click();
        // await this.clickAction(
        //     this.erpLocators.websitePagesEditButton,
        //     this.erpLocators.websitePagesEditLink,
        //     this.erpLocators.websitePagesEditActionButton,
        // );

        if (updates.title) {
            await this.erpLocators.websitePagesTitleInput.fill(updates.title);
        }
        if (updates.content) {
            await this.fillContent(updates.content);
        }
        if (updates.metaTitle) {
            await this.erpLocators.websitePagesMetaTitleInput.fill(updates.metaTitle);
        }
        if (updates.metaKeywords) {
            await this.erpLocators.websitePagesMetaKeywordsInput.fill(updates.metaKeywords);
        }
        if (updates.metaDescription) {
            await this.erpLocators.websitePagesMetaDescriptionInput.fill(updates.metaDescription);
        }
        if (typeof updates.isHeaderVisible === "boolean") {
            await this.toggleSwitch(this.erpLocators.websitePagesHeaderVisibleToggle, updates.isHeaderVisible);
        }
        if (typeof updates.isFooterVisible === "boolean") {
            await this.toggleSwitch(this.erpLocators.websitePagesFooterVisibleToggle, updates.isFooterVisible);
        }

        await this.erpLocators.websitePagesSaveButton.click();
        await this.expectSuccessToast();
    }

    async deleteWebsitePage(title: string): Promise<void> {
        await this.gotoWebsitePagesPage();
        await this.searchPage(title);
        await this.openRowActions();
        await this.clickAction(
            this.erpLocators.websitePagesDeleteButton,
            this.erpLocators.websitePagesDeleteLink,
            this.erpLocators.websitePagesDeleteActionButton,
        );
        await this.erpLocators.websitePagesConfirmDeleteButton.click();
        await this.expectSuccessToast();
    }

    async searchPage(keyword: string): Promise<void> {
        await this.erpLocators.websitePagesSearchInput.fill(keyword);
        await this.page.waitForLoadState("networkidle");
    }

    async expectPageListed(title: string): Promise<void> {
        await expect(this.erpLocators.websitePagesTable).toContainText(title);
    }

    async expectPageNotListed(title: string): Promise<void> {
        await expect(this.erpLocators.websitePagesTable).not.toContainText(title);
    }

    private async fillContent(content: string): Promise<void> {
        if (await this.erpLocators.websitePagesContentInput.isVisible().catch(() => false)) {
            await this.erpLocators.websitePagesContentInput.fill(content);
            return;
        }

        await expect(this.erpLocators.websitePagesEditableContent.first()).toBeVisible();
        await this.erpLocators.websitePagesEditableContent.first().click();
        await this.erpLocators.websitePagesEditableContent.first().fill(content);
    }

    private async openRowActions(): Promise<void> {
        await this.erpLocators.websitePagesRowActionsButton.click();
        await this.page.waitForTimeout(300);
    }

    private async clickAction(menuItem: Locator, fallbackLink: Locator, fallbackButton: Locator): Promise<void> {
        if (await menuItem.isVisible().catch(() => false)) {
            await menuItem.click();
            return;
        }

        if (await fallbackLink.isVisible().catch(() => false)) {
            await fallbackLink.click();
            return;
        }

        await fallbackButton.click();
    }

    private async toggleSwitch(toggle: Locator, checked: boolean): Promise<void> {
        if (!(await toggle.isVisible().catch(() => false))) {
            return;
        }

        const isChecked = (await toggle.getAttribute("aria-checked")) === "true";
        if (isChecked !== checked) {
            await toggle.click();
        }
    }

    async publishPage(title: string): Promise<void> {
        await this.gotoWebsitePagesPage();
        await this.searchPage(title);
        await this.openRowActions();
        await this.erpLocators.websitePagesEditButton.click();

        await this.page.waitForLoadState("networkidle");

        const publishButton = this.page.getByRole("button", { name: /publish/i }).first();
        if (await publishButton.isVisible().catch(() => false)) {
            await publishButton.click();
            await this.expectSuccessToast();
        }
    }

    async draftPage(title: string): Promise<void> {
        await this.gotoWebsitePagesPage();
        await this.searchPage(title);
        await this.openRowActions();
        await this.erpLocators.websitePagesEditButton.click();

        await this.page.waitForLoadState("networkidle");

        const draftButton = this.page.getByRole("button", { name: /draft/i }).first();
        if (await draftButton.isVisible().catch(() => false)) {
            await draftButton.click();
            await this.expectSuccessToast();
            await this.erpLocators.websitePagesSaveButton.click();
        }
    }

    async checkPageOnFrontend(slug: string, expectedContent: string, headerVisible: boolean, footerVisible: boolean, pageTitle?: string): Promise<void> {
        await this.page.goto(`/pages/${slug}`);
        await expect(this.page).toHaveURL(new RegExp(slug));
        await this.page.waitForLoadState("networkidle");

        await expect(this.page.locator("body")).toContainText(expectedContent);

        const footer = this.page.locator("footer").first();

        await expect(footer).toBeVisible();

        if (! pageTitle) {
            return;
        }

        const headerLinks = this.page.locator("header, nav").getByRole("link", { name: pageTitle, exact: true });
        const footerLinks = footer.getByRole("link", { name: pageTitle, exact: true });

        if (headerVisible) {
            await expect.poll(async () => await headerLinks.count()).toBeGreaterThan(0);
        } else {
            await expect(headerLinks).toHaveCount(0);
        }

        if (footerVisible) {
            await expect.poll(async () => await footerLinks.count()).toBeGreaterThan(0);
            await expect(footerLinks.first()).toBeVisible();
        } else {
            await expect(footerLinks).toHaveCount(0);
        }
    }

    private async expectSuccessToast(): Promise<void> {
        await expect(this.erpLocators.websitePagesSuccessToast).toBeVisible();
    }
}
