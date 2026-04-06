import { test } from "../../setup";
import { WebsiteManagementPage } from "../../pages/06_websiteManagement";

test.describe("Website Pages - Edit", () => {
    test.beforeAll(async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);
        await websitePage.ensureWebsitePluginInstalled();
    });

    test("Edit Website Page - Updates Title and Metadata", async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);
        const key = Date.now();
        const originalTitle = `E2E Website Page ${key}`;
        const updatedTitle = `E2E Website Page Updated ${key}`;

        await websitePage.createWebsitePage({
            title: originalTitle,
            content: `Page content for ${originalTitle}`,
        });

        await websitePage.editWebsitePage(originalTitle, {
            title: updatedTitle,
            content: `Updated content for ${updatedTitle}`,
            metaTitle: `Updated meta title ${key}`,
            metaDescription: `Updated meta description ${key}`,
            isHeaderVisible: false,
            isFooterVisible: false,
        });

        await websitePage.searchPage(updatedTitle);
        await websitePage.expectPageListed(updatedTitle);
    });
});
